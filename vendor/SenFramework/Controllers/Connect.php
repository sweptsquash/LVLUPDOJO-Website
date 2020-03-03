<?php

namespace SenFramework\Controllers;

class Connect extends \SenFramework\DB\Database {
    public $data;

    private $mailer;
    private $security;
    private $facebook;
    private $uriQuery;

    public function __construct($route = NULL, $query = NULL) {
        global $request, $senConfig, $user, $phpbb;
        
        $this->uriQuery = $query;

		$this->security = new \SenFramework\Security();
        $this->mailer = new \SenFramework\Mailer();

        $this->facebook = new \Facebook\Facebook([
            'app_id' => FB_APP_ID,
            'app_secret' => FB_APP_SECRET,
            'default_graph_version' => 'v3.1',
        ]); 

        $this->data['hideNav'] = true;
        $this->data['addressbarTrack'] = false;
        $this->data['template'] = 'connecting';
        
        switch($route[0]) {
            default:
                $this->data['triggererror'] = '404';
            break;

            case"twitch-authentication":
                $this->data['service'] = 'Twitch';

                switch($route[1]) {
                    default:
                        $ret = self::twitchConnect($query);
                    break;

                    case"disconnect":
                        $ret = self::twitchDisconnect();
                    break;
                }
            break;

            case"discord-authentication":
                $this->data['service'] = 'Discord';

                switch($route[1]) {
                    default:
                        $ret = self::discordConnect($query);
                    break;

                    case"disconnect":
                        $ret = self::discordDisconnect();
                    break;
                }
            break;

            case"twitter-authentication":
                $this->data['service'] = 'Twitter';

                switch($route[1]) {
                    default:
                        $ret = self::twitterConnect($query);
                    break;

                    case"disconnect":
                        $ret = self::twitterDisconnect();
                    break;
                }
            break;

            case"facebook-authentication":
                $this->data['service'] = 'Facebook';

                switch($route[1]) {
                    default:
                        $ret = self::facebookConnect();
                    break;

                    case"callback":
                        $ret = self::facebookCallback();
                    break;

                    case"disconnect":
                        $ret = self::facebookDisconnect();
                    break;
                }
            break;
        }

        $this->data['javascript'] = '<script type="text/javascript" nonce="YzY1NjQ3NTQyYWRiNDliODgxOTMwNDkzYzhiZjVmZTMg"> var success = ' . ((!empty($ret->result) && $ret->result == 'success') ? 'true' : 'false') . '; var redirect = '.((!empty($ret->redirect)) ? '"'.$ret->redirect.'"' : 'null').'; var error = ' . ((!empty($ret->result) && $ret->result == 'error') ? 'true' : 'false') . '; var errormsg = ' . ((!empty($ret->result) && $ret->result == 'error') ? '"' . ((!empty($ret->message)) ? $ret->message : 'An Unexpected Error Occurred.') . '"' : 'null') . '; </script>';
    }

    private function checkUsername(string $username): bool {
        global $phpbb;

        if(!empty($username)) {
            $sql = parent::mq("SELECT user_id FROM Users WHERE username='".parent::mres($username)."' OR username_clean='".parent::mres($phpbb->utf8_clean_string($username))."'");

            if($sql->num_rows > 0) {
                return true;
            } else {
                return false;
            }
        } else {
            throw new \Exception("No username passed.");
        }
    }

    private function checkAccount(int $serviceID = 0, string $email, string $service = 'twitch') {
        global $user;

        $result = new \stdClass();

        if(!empty($service) && !empty($serviceID)) {
            $scol = [
                'twitch'    => 'tcid',
                'discord'   => 'dsid',
                'facebook'  => 'fbid',
                'twitter'   => 'twid'
            ]; 

            $sql = parent::mq("SELECT user_id, user_type FROM Users WHERE user_".parent::mres($scol[$service])."='".parent::mres($serviceID)."'");

            if($sql->num_rows > 0) {
                $row = parent::mfa($sql);

                if(!$user->data['is_registered']) {
                    if ($row['user_type'] == USER_INACTIVE) {
                        $result->result = 'error';
                        $result->message = 'This account has been deactivated by an Admin, please <a href="/contact-us/">contact support</a>.';
                    } else {
                        $result->result = 'login';
                        $result->user_id = $row['user_id'];
                    }
                } else {
                    if($row['user_id'] == $user->data['user_id']) {
                        $result->result = 'update';
                    } else {
                        $result->result = 'error';
                        $result->message = ucfirst($service) . ' account already associated with an existing user.';
                    }
                }
            } else {
                if(!empty($email)) {
                    $sql = parent::mq("SELECT user_id, user_type FROM Users WHERE LOWER(user_email)='".parent::mres(strtolower($email))."'");

                    if($sql->num_rows > 0) {
                        $row = parent::mfa($sql);

                        if(!$user->data['is_registered']) {
                            $result->result = 'error';
                            $result->message = 'An account already exists using this email address.';
                        } else {
                            if ($row['user_type'] == USER_INACTIVE) {
                                $result->result = 'error';
                                $result->message = 'This account has been deactivated by an Admin, please <a href="/contact-us/">contact support</a>.';
                            } else {
                                $result->result = 'update';
                            }
                        }
                    } else {
                        if(!$user->data['is_registered']) {
                            $result->result = 'create';
                        } else {
                            $result->result = 'update';
                        }
                    }
                } else {
                    if(!$user->data['is_registered']) {
                        $result->result = 'create';
                    } else {
                        $result->result = 'update';
                    }
                }
            }
        } else {
            $result->result = 'error';
            $result->message = 'Unable to perform account check, key information not passed.';
        }

        return $result;
    }

    public function twitchConnect($query = NULL) {
        global $request, $user, $phpbb;

        $result = new \stdClass();

        $Twitch = new \SenFramework\TwitchProvider([
            'clientId'                => TWITCH,
            'clientSecret'            => TWITCH_SECRET,
            'redirectUri'             => 'https://www.lvlupdojo.com/twitch-authentication',
            'scopes'                  => [
                'user_read'
            ]
        ]);

        if(!isset($query['code'])) {
            $authorizationUrl = $Twitch->getAuthorizationUrl();
            $_SESSION['oauth2state'] = $Twitch->getState();

            header("Location: ".$authorizationUrl);
            exit;
        } else if(empty($query['state']) || (isset($_SESSION['oauth2state']) && $query['state'] !== $_SESSION['oauth2state'])) {
            if (isset($_SESSION['oauth2state'])) {
                unset($_SESSION['oauth2state']);
            }

            $result->result = 'error';
            $result->message = 'Failed to maintain same session state.';
        } else {
            try {
                // Get an access token using authorization code grant.
                $accessToken = $Twitch->getAccessToken('authorization_code', [
                    'code' => $query['code']
                ]);

                // Using the access token, get user profile
                $resourceOwner = $Twitch->getResourceOwner($accessToken);
                $TwitchUser = $resourceOwner->toArray();

                $accountStatus = self::checkAccount($TwitchUser['_id'], $TwitchUser['email']);

                switch($accountStatus->result) {
                    default:
                        if(!empty($accountStatus->message)) {
                            $result->result = 'error';
                            $result->message = $accountStatus->message;
                        } else {
                            throw new \Exception('Unrecognized state observed.');
                        }
                    break;

                    case"login":
                        if(!$user->data['is_registered']) {
                            $authLogin = $user->session_create($accountStatus->user_id, 1);

                            if($authLogin === true) {
                                $result->result = "success";

                                if(isset($_SESSION['redirect'])) {
                                    $result->redirect = 'https://'.((DEVELOP) ? 'development.lvlupdojo.com' : 'www.lvlupdojo.com').$_SESSION['redirect'];
                                    unset($_SESSION['redirect']);
                                }
                            } else {
                                $result->result = 'error';
                                $result->message = $authLogin->message;
                            }
                        } else {
                            $result->result = 'success';
                        }
                    break;

                    case"update":
                        if(!$user->data['is_registered']) {
                            $result->result = 'error';
                            $result->message = 'You\'re not authorized to perform this action.';
                        } else {
                            parent::mq("UPDATE 
                                Users 
                            SET 
                                user_tcid='".parent::mres($TwitchUser['_id'])."',
                                user_tc_name='".parent::mres($TwitchUser['display_name'])."'
                            WHERE 
                                user_id='".parent::mres($user->data['user_id'])."'");

                            $result->result = 'success'; 
                        }
                    break;

                    case"create":
                        if(!$user->data['is_registered']) {
                            $dob = explode('/', date("d/m/Y"));
                            $dob[2] -= 13;

                            $usernameExists = $this->checkUsername($data['username']);

                            $sql_ary = [
                                "user_ip"				=> \SenFramework\SenFramework::getIP(),
                                "group_id"				=> 4,
                                "username"				=> (!$usernameExists) ? $TwitchUser['display_name'] : NULL,
                                "username_clean"		=> (!$usernameExists) ? $phpbb->utf8_clean_string($TwitchUser['display_name']) : NULL,
                                "user_email"			=> strtolower($TwitchUser['email']),
                                "user_email_hash"		=> sprintf('%u', crc32(strtolower($TwitchUser['email']))) . strlen($TwitchUser['email']),
                                "user_password"			=> $this->security->password_hash($user->randomPassword(), PASSWORD_BCRYPT),
                                "user_birthday"			=> date("Y-m-d H:i:s", mktime(0,0,0,$dob[1],$dob[0],$dob[2])),
                                "user_from"				=> 'US',
                                "user_dateformat"		=> 'm/d/Y h:i a',
                                "user_tcid"             => $TwitchUser['_id'],
                                "user_tc_name"           => $TwitchUser['display_name']                   
                            ];

                            parent::mq("INSERT INTO Users ".parent::build_array('INSERT', $sql_ary));
                            $uid = parent::lastId();
                            $subject = 'Welcome to LVLUP Dojo';

                            $to = [
                                'user_id' => $uid,
                                'email' => $sql_ary['user_email'],
                                'name' => (empty($sql_ary['username'])) ? $sql_ary['user_email'] : $sql_ary['username']
                            ];

                            $replace = [
                                '{{ USERNAME }}' => $to['name'],
                                '{{ SUBJECT }}' => $subject,
                                '{{ YEAR }}' => date("Y")												
                            ];

                            $this->mailer->SendMail('welcome', $subject, $to, $replace);

                            $authLogin = $user->session_create($uid, 1);

                            if($authLogin === true) {
                                $result->result = "success";

                                if($sql_ary['username'] === NULL) {
                                    $result->redirect = "https://".((DEVELOP)?"development.lvlupdojo.com":"www.lvlupdojo.com")."/dashboard/username/";
                                }
                            } else {
                                $result->result = 'error';
                                $result->message = $authLogin->message;
                            }
                        } else {
                            $result->result = 'error';
                            $result->message = 'Unable to create new account user already logged in.';
                        }
                    break;
                }
            } catch(\Exception $e) {
                $result->result = 'error';
                $result->message = 'Caught exception: '.$e->getMessage();
            }
        }

        return $result;
    }

    public function twitchDisconnect() {
        global $user;

        $result = new \stdClass();

        if(!$user->data['is_registered']) {
            $result->result = 'error';
            $result->message = 'You\'re not authorized to perform this action.';
        } else {
            parent::mq("UPDATE 
                Users 
            SET
                user_tcid=NULL,
                user_tc_name=NULL
            WHERE 
                user_id='".parent::mres($user->data['user_id'])."'");

            $result->result = 'success';
        }

        return $result;
    }

    public function discordConnect($query = NULL) {
        global $request, $user, $phpbb;

        $result = new \stdClass();

        $provider = new \Wohali\OAuth2\Client\Provider\Discord([
            'clientId'     => DISCORD_BOT_ID,
            'clientSecret' => DISCORD_BOT_SECRET,
            'redirectUri'  => 'https://www.lvlupdojo.com/discord-authentication/'
        ]);

        if(!isset($query['code'])) {
            $authURL = $provider->getAuthorizationUrl(['scope' => ['identify', 'email', 'guilds', 'guilds.join']]);
            $_SESSION['oauth2state'] = $provider->getState();

            header("Location: ".$authURL);
            exit;
        } elseif (empty($query['state']) || (isset($_SESSION['oauth2state']) && $query['state'] !== $_SESSION['oauth2state'])) {
            if(isset($_SESSION['oauth2state'])) {
                unset($_SESSION['oauth2state']); 
            }

            $result->result = 'error';
            $result->message = 'Invalid State';
        } else {
            $token = $provider->getAccessToken('authorization_code', [
                'code' => $query['code'],
            ]);
        
            try {
                $dsuser = $provider->getResourceOwner($token);

                $data = $dsuser->toArray();

                $accountStatus = $this->checkAccount($data['id'], $data['email'], 'discord');

                switch($accountStatus->result) {
                    default:
                        if(!empty($accountStatus->message)) {
                            $result->result = 'error';
                            $result->message = $accountStatus->message;
                        } else {
                            throw new \Exception('Unrecognized state observed.');
                        }
                    break;

                    case"login":
                        if(!$user->data['is_registered']) {
                            $authLogin = $user->session_create($accountStatus->user_id, 1);

                            if($authLogin === true) {
                                $result->result = "success";

                                if(isset($_SESSION['redirect'])) {
                                    $result->redirect = 'https://'.((DEVELOP) ? 'development.lvlupdojo.com' : 'www.lvlupdojo.com').$_SESSION['redirect'];
                                    unset($_SESSION['redirect']);
                                }
                            } else {
                                $result->result = 'error';
                                $result->message = $authLogin->message;
                            }
                        } else {
                            $result->result = 'success';
                        }
                    break;

                    case"update":
                        if(!$user->data['is_registered']) {
                            $result->result = 'error';
                            $result->message = 'You\'re not authorized to perform this action.';
                        } else {
                            parent::mq("UPDATE 
                                Users 
                            SET 
                                user_dsid='".parent::mres($data['id'])."',
                                user_ds_name='".parent::mres($data['username'])."#".parent::mres($data['discriminator'])."'
                            WHERE 
                                user_id='".parent::mres($user->data['user_id'])."'");

                            $result->result = 'success'; 
                        }
                    break;

                    case"create":
                        if(!$user->data['is_registered']) {
                            if(!empty(DISCORD_BOT_TOKEN)) {
                                $member =  NULL;
                                $discord = new \RestCord\DiscordClient(['token' => DISCORD_BOT_TOKEN]);

                                try {
                                    $member = $discord->guild->getGuildMember([
                                        'guild.id'      => DISCORD_SERVER_ID,
                                        'user.id'       => (int)$data['id']
                                    ]);
                                } catch(\Exception $e) {}

                                if(empty($member)) {
                                    try {
                                        $discord->guild->addGuildMember([
                                            'guild.id'      => DISCORD_SERVER_ID,
                                            'user.id'       => (int)$data['id'],
                                            'access_token'  => $token
                                        ]);
                                    } catch(\Exception $e) {}
                                }
                            }

                            $dob = explode('/', date("d/m/Y"));
                            $dob[2] -= 13;

                            //$avatar = 'https://cdn.discordapp.com/avatars/'.$data['id'].'/'.$data['avatar'].'.png?size=1024';

                            $usernameExists = $this->checkUsername($data['username']);

                            $sql_ary = [
                                "user_ip"				=> \SenFramework\SenFramework::getIP(),
                                "group_id"				=> 4,
                                "username"				=> (!$usernameExists) ? $data['username'] : NULL,
                                "username_clean"		=> (!$usernameExists) ? $phpbb->utf8_clean_string($data['username']) : NULL,
                                "user_email"			=> strtolower($data['email']),
                                "user_email_hash"		=> sprintf('%u', crc32(strtolower($data['email']))) . strlen($data['email']),
                                "user_password"			=> $this->security->password_hash($user->randomPassword(), PASSWORD_BCRYPT),
                                "user_birthday"			=> date("Y-m-d H:i:s", mktime(0,0,0,$dob[1],$dob[0],$dob[2])),
                                "user_from"				=> 'US',
                                "user_dateformat"		=> 'm/d/Y h:i a',
                                "user_dsid"             => (int)$data['id'], 
                                "user_ds_name"           => $data['username'].'#'.$data['discriminator']          
                            ];

                            parent::mq("INSERT INTO Users ".parent::build_array('INSERT', $sql_ary));
                            $uid = parent::lastId();
                            $subject = 'Welcome to LVLUP Dojo';

                            $to = [
                                'user_id' => $uid,
                                'email' => $sql_ary['user_email'],
                                'name' => (empty($sql_ary['username'])) ? $sql_ary['user_email'] : $sql_ary['username']
                            ];

                            $replace = [
                                '{{ USERNAME }}' => $to['name'],
                                '{{ SUBJECT }}' => $subject,
                                '{{ YEAR }}' => date("Y")												
                            ];

                            $this->mailer->SendMail('welcome', $subject, $to, $replace);

                            $authLogin = $user->session_create($uid, 1);

                            if($authLogin === true) {
                                $result->result = "success";

                                if($sql_ary['username'] === NULL) {
                                    $result->redirect = "https://".((DEVELOP)?"development.lvlupdojo.com":"www.lvlupdojo.com")."/dashboard/username/";
                                }
                            } else {
                                $result->result = 'error';
                                $result->message = $authLogin->message;
                            }
                        } else {
                            $result->result = 'error';
                            $result->message = 'Unable to create new account user already logged in.';
                        }
                    break;
                }
            } catch(\Exception $e) {
                $result->result = 'error';
                $result->message = $e->getMessage();
            }
        }

        return $result;
    }

    public function discordDisconnect() {
        global $user;

        $result = new \stdClass();

        if(!$user->data['is_registered']) {
            $result->result = 'error';
            $result->message = 'You\'re not authorized to perform this action.';
        } else {
            parent::mq("UPDATE 
                Users 
            SET
                user_dsid=NULL,
                user_ds_name=NULL
            WHERE 
                user_id='".parent::mres($user->data['user_id'])."'");

            $result->result = 'success';
        }

        return $result;
    }

    public function facebookConnect() {
        global $request, $user, $phpbb;

        $result = new \stdClass();

        $request->enable_super_globals();

        $helper = $this->facebook->getRedirectLoginHelper();

        $permissions = ['email'];
        $loginUrl = $helper->getLoginUrl('https://'.((DEVELOP) ? 'development.lvlupdojo.com' : 'www.lvlupdojo.com').'/facebook-authentication/callback/', $permissions);

        $request->disable_super_globals();

        header("Location: ".$loginUrl);
        exit;
    }

    public function facebookCallback() {
        global $request, $user, $phpbb;

        $result = new \stdClass();

        if(isset($this->uriQuery['error_code']) || isset($this->uriQuery['error_message'])) {
            $result->result = 'error';
            $result->message = ((isset($this->uriQuery['error_message'])) ? $this->uriQuery['error_message'] : 'Facebook returned error '.$this->uriQuery['error_code']);
        } else {
            $request->enable_super_globals();

            $helper = $this->facebook->getRedirectLoginHelper();

            try {
                $accessToken = $helper->getAccessToken();
            } catch(\Facebook\Exceptions\FacebookResponseException $e) {
                $result->result = 'error';
                $result->message = 'Graph returned an error: ' . $e->getMessage();
            } catch(\Facebook\Exceptions\FacebookSDKException $e) {
                $result->result = 'error';
                $result->message = 'Facebook SDK returned an error: ' . $e->getMessage();
            }

            if (!isset($accessToken)) {
                if ($helper->getError()) {
                    $result->result = 'error';
                    $result->message = [
                        "Error: " . $helper->getError(),
                        "Error Code: " . $helper->getErrorCode(),
                        "Error Reason: " . $helper->getErrorReason(),
                        "Error Description: " . $helper->getErrorDescription()
                    ];
                } else {
                    $result->result = 'error';
                    $result->message = 'Bad Request';
                }
            }

            if($result->result !== 'error') {
                try {
                    $oAuth2Client = $this->facebook->getOAuth2Client();
                    $tokenMetadata = $oAuth2Client->debugToken($accessToken);
                    $tokenMetadata->validateAppId(FB_APP_ID); 
                    $tokenMetadata->validateExpiration();
                    
                    if (!$accessToken->isLongLived()) {
                        try {
                            $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
                        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
                            $result->result = 'error';
                            $result->message = 'Facebook SDK failed getting long-lived access token: ' . $e->getMessage();
                        }
                    }

                    if(!isset($result->result)) {
                        $_SESSION['fb_access_token'] = (string) $accessToken;

                        try {
                            $response = $this->facebook->get('/me?fields=id,name,email', $accessToken);
                        } catch(Facebook\Exceptions\FacebookResponseException $e) {
                            $result->result = 'error';
                            $result->message = 'Graph returned an error: ' . $e->getMessage();
                        } catch(Facebook\Exceptions\FacebookSDKException $e) {
                            $result->result = 'error';
                            $result->message = 'Facebook SDK returned an error: ' . $e->getMessage();
                        }

                        if(!isset($result->result)) {
                            $fbUser = $response->getGraphUser();

                            $name = explode(' ', $fbUser['name']);

                            $data = [
                                'user_fbid'          => (int)$fbUser['id'],
                                'user_email'         => strtolower($fbUser['email']),
                                'user_first_name'    => is_array($name) ? $name[0] : $name,
                                'user_last_name'     => is_array($name) ? end($name) : NULL
                            ];

                            if(!empty($data['user_email'])) {
                                $accountStatus = $this->checkAccount($data['user_fbid'], $data['user_email'], 'facebook');

                                switch($accountStatus->result) {
                                    default:
                                        if(!empty($accountStatus->message)) {
                                            $result->result = 'error';
                                            $result->message = $accountStatus->message;
                                        } else {
                                            throw new \Exception('Unrecognized state observed.');
                                        }
                                    break;
                
                                    case"login":
                                        if(!$user->data['is_registered']) {
                                            $authLogin = $user->session_create($accountStatus->user_id, 1);

                                            if($authLogin === true) {
                                                $result->result = "success";

                                                if(isset($_SESSION['redirect'])) {
                                                    $result->redirect = 'https://'.((DEVELOP) ? 'development.lvlupdojo.com' : 'www.lvlupdojo.com').$_SESSION['redirect'];
                                                    unset($_SESSION['redirect']);
                                                }
                                            } else {
                                                $result->result = 'error';
                                                $result->message = $authLogin->message;
                                            }
                                        } else {
                                            $result->result = 'success';
                                        }
                                    break;

                                    case"update":
                                        if(!$user->data['is_registered']) {
                                            $result->result = 'error';
                                            $result->message = 'You\'re not authorized to perform this action.';
                                        } else {
                                            parent::mq("UPDATE 
                                                Users 
                                            SET 
                                                user_fbid='".parent::mres($data['user_fbid'])."'
                                            WHERE 
                                                user_id='".parent::mres($user->data['user_id'])."'");

                                            $result->result = 'success'; 
                                        }
                                    break;

                                    case"create":
                                        if(!$user->data['is_registered']) {
                                            $dob = explode('/', date("d/m/Y"));
                                            $dob[2] -= 13;
                
                                            $sql_ary = [
                                                "user_ip"				=> \SenFramework\SenFramework::getIP(),
                                                "group_id"				=> 4,
                                                "username"				=> NULL,
                                                "username_clean"		=> NULL,
                                                "user_email"			=> $data['user_email'],
                                                "user_email_hash"		=> sprintf('%u', crc32(strtolower($data['user_email']))) . strlen($data['user_email']),
                                                "user_password"			=> $this->security->password_hash($user->randomPassword(), PASSWORD_BCRYPT),
                                                "user_birthday"			=> date("Y-m-d H:i:s", mktime(0,0,0,$dob[1],$dob[0],$dob[2])),
                                                "user_from"				=> 'US',
                                                "user_dateformat"		=> 'm/d/Y h:i a',
                                                "user_fbid"             => $data['user_fbid'],
                                                "user_first_name"       => $data['user_first_name'],
                                                "user_last_name"        => $data['user_last_name']             
                                            ];
                
                                            parent::mq("INSERT INTO Users ".parent::build_array('INSERT', $sql_ary));
                                            $uid = parent::lastId();
                                            $subject = 'Welcome to LVLUP Dojo';
                
                                            $to = [
                                                'user_id' => $uid,
                                                'email' => $sql_ary['user_email'],
                                                'name' => (empty($sql_ary['user_first_name'])) ? $sql_ary['user_email'] : $sql_ary['user_first_name']
                                            ];
                
                                            $replace = [
                                                '{{ USERNAME }}' => $to['name'],
                                                '{{ SUBJECT }}' => $subject,
                                                '{{ YEAR }}' => date("Y")												
                                            ];
                
                                            $this->mailer->SendMail('welcome', $subject, $to, $replace);
                
                                            $authLogin = $user->session_create($uid, 1);
                
                                            if($authLogin === true) {
                                                $result->result = "success";
                                                $result->redirect = "https://".((DEVELOP)?"development.lvlupdojo.com":"www.lvlupdojo.com")."/dashboard/username/";
                                            } else {
                                                $result->result = 'error';
                                                $result->message = $authLogin->message;
                                            }
                                        } else {
                                            $result->result = 'error';
                                            $result->message = 'Unable to create new account user already logged in.';
                                        }
                                    break;
                                }
                            } else {
                                $result->result = 'error';
                                $result->message = 'Facebook returned a blank email address, please make sure you have a valid email address tied to your Facebook account.';
                            }
                        }
                    }
                } catch(\Facebook\Exceptions\FacebookSDKException $e) {
                    $result->result = 'error';
                    $result->message = 'Facebook SDK returned an error: ' . $e->getMessage();
                }
            }

            $request->disable_super_globals();
        }

        return $result;
    }

    public function facebookDisconnect() {
        global $user;

        $result = new \stdClass();

        if(!$user->data['is_registered']) {
            $result->result = 'error';
            $result->message = 'You\'re not authorized to perform this action.';
        } else {
            // Attempt to de-authorize the Facebook application from the user
            if(isset($_SESSION['fb_access_token'])) {
                try {
                    $this->facebook->delete('/'.$user->data['user_fbid'].'/permissions', $_SESSION['fb_access_token']);
                } catch (\Facebook\Exceptions\FacebookSDKException $e) {}
            }

            parent::mq("UPDATE 
                Users 
            SET
                user_fbid=NULL
            WHERE 
                user_id='".parent::mres($user->data['user_id'])."'");

            $result->result = 'success';
        }

        return $result;
    }

    public function twitterConnect($query = NULL) {
        global $request, $user, $phpbb;

        $result = new \stdClass();

        if(!isset($query['oauth_token'])) {
            $Twitter = new \Abraham\TwitterOAuth\TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET);

            $request_token = $Twitter->oauth('oauth/request_token', ['oauth_callback' => 'https://www.lvlupdojo.com/twitter-authentication/']);

            if($Twitter->getLastHttpCode() === 200) {
                $_SESSION['oauth_token'] = $request_token['oauth_token'];
                $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

                $url = $Twitter->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));

                if(!empty($url)) {
                    header("Location: ".$url);
                    exit;
                } else {
                    $result->result = 'error';
                    $result->message = 'Twitter Failed To Authorize Callback URL.';    
                }
            } else {
                $result->result = 'error';
                $result->message = 'Twitter Failed To Generate Token Request.'; 
            }
        } elseif(isset($query['denied'])) {
            $result->result='error';
            $result->message = 'Permission was denied. Please start over.';
        } elseif (empty($query['oauth_token']) || (isset($_SESSION['oauth_token']) && $query['oauth_token'] !== $_SESSION['oauth_token'])) {
            if(isset($_SESSION['oauth_token'])) {
                unset($_SESSION['oauth_token']); 
                unset($_SESSION['oauth_token_secret']);
            }

            $result->result = 'error';
            $result->message = 'Invalid State.';            
        } else {
            $connection = new \Abraham\TwitterOAuth\TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);

            if(!empty($query['oauth_verifier'])) {
                $access_token = $connection->oauth("oauth/access_token", ["oauth_verifier" => $query['oauth_verifier']]);

                if($connection->getLastHttpCode() === 200) {
                    $_SESSION['tw_access_token'] = $access_token;

                    unset($_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);

                    $authconnection = new \Abraham\TwitterOAuth\TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);

                    $twUser = $authconnection->get('account/verify_credentials', ['tweet_mode' => 'extended', 'include_entities' => 'true', 'include_email' => 'true']);

                    if(property_exists($twUser, 'id')) {

                        $accountStatus = $this->checkAccount($twUser->id, $twUser->email, 'twitter');
                        
                        switch($accountStatus->result) {
                            default:
                                if(!empty($accountStatus->message)) {
                                    $result->result = 'error';
                                    $result->message = $accountStatus->message;
                                } else {
                                    $result->result = 'error';
                                    $result->message = 'Unrecognized state observed.';
                                }
                            break;
        
                            case"login":
                                if(!$user->data['is_registered']) {
                                    $authLogin = $user->session_create($accountStatus->user_id, 1);

                                    if($authLogin === true) {
                                        $result->result = "success";

                                        if(isset($_SESSION['redirect'])) {
											$result->redirect = 'https://'.((DEVELOP) ? 'development.lvlupdojo.com' : 'www.lvlupdojo.com').$_SESSION['redirect'];
											unset($_SESSION['redirect']);
										}
                                    } else {
                                        $result->result = 'error';
                                        $result->message = $authLogin['message'];
                                    }
                                } else {
                                    $result->result = 'success';
                                }
                            break;

                            case"update":
                                if(!$user->data['is_registered']) {
                                    $result->result = 'error';
                                    $result->message = 'You\'re not authorized to perform this action.';
                                } else {
                                    parent::mq("UPDATE 
                                        Users 
                                    SET 
                                        user_twid='".parent::mres($twUser->id)."',
                                        user_tw_name='".parent::mres($twUser->screen_name)."'
                                    WHERE 
                                        user_id='".parent::mres($user->data['user_id'])."'");

                                    $result->result = 'success'; 
                                }
                            break;

                            case"create":
                                if(!$user->data['is_registered']) {
                                    if(isset($twUser->email) && !empty($twUser->email)) {
                                        $dob = explode('/', date("d/m/Y"));
                                        $dob[2] -= 13;

                                        $usernameExists = $this->checkUsername($twUser->screen_name);

                                        $name = explode(' ', $twUser->name);

                                        $data = [
                                            'user_first_name'    => is_array($name) ? $name[0] : $name,
                                            'user_last_name'     => is_array($name) ? end($name) : NULL
                                        ];
            
                                        $sql_ary = [
                                            "user_ip"				=> \SenFramework\SenFramework::getIP(),
                                            "group_id"				=> 4,
                                            "username"				=> (!$usernameExists) ? $twUser->screen_name : NULL,
                                            "username_clean"		=> (!$usernameExists) ? $phpbb->utf8_clean_string($twUser->screen_name) : NULL,
                                            "user_email"			=> strtolower($twUser->email),
                                            "user_email_hash"		=> sprintf('%u', crc32(strtolower($twUser->email))) . strlen($twUser->email),
                                            "user_password"			=> $this->security->password_hash($user->randomPassword(), PASSWORD_BCRYPT),
                                            "user_birthday"			=> date("Y-m-d H:i:s", mktime(0,0,0,$dob[1],$dob[0],$dob[2])),
                                            "user_from"				=> 'US',
                                            "user_dateformat"		=> 'm/d/Y h:i a',
                                            "user_fbid"             => (int)$twUser->id,
                                            "user_first_name"       => $data['user_first_name'],
                                            "user_last_name"        => $data['user_last_name']             
                                        ];
            
                                        parent::mq("INSERT INTO Users ".parent::build_array('INSERT', $sql_ary));
                                        $uid = parent::lastId();
                                        $subject = 'Welcome to LVLUP Dojo';
            
                                        $to = [
                                            'user_id' => $uid,
                                            'email' => $sql_ary['user_email'],
                                            'name' => (empty($sql_ary['username'])) ? $sql_ary['username'] : $sql_ary['user_email']
                                        ];
            
                                        $replace = [
                                            '{{ USERNAME }}' => $to['name'],
                                            '{{ SUBJECT }}' => $subject,
                                            '{{ YEAR }}' => date("Y")												
                                        ];
            
                                        $this->mailer->SendMail('welcome', $subject, $to, $replace);
            
                                        $authLogin = $user->session_create($uid, 1);
            
                                        if($authLogin === true) {
                                            $result->result = "success";
                                            $result->redirect = "https://".((DEVELOP)?"development.lvlupdojo.com":"www.lvlupdojo.com")."/dashboard/username/";
                                        } else {
                                            $result->result = 'error';
                                            $result->message = $authLogin['message'];
                                        }
                                    } else {
                                        $result->result = 'error';
                                        $result->message = 'Unable to create new account user, no valid email address tied to Twitter Account.';
                                    }
                                } else {
                                    $result->result = 'error';
                                    $result->message = 'Unable to create new account user already logged in.';
                                }
                            break;
                        }
                    } else {
                        $result->result = 'error';
                        $result->message = 'Twitter Failed To Fetch User.'; 
                    }
                } else {
                    $result->result = 'error';
                    $result->message = 'Twitter Failed To Generate Access Token.'; 
                }
            } else {
                $result->result = 'error';
                $result->message = 'Invalid OAuth Verifier.'; 
            }
        }

        return $result;
    }

    public function twitterDisconnect() {
        global $user;

        $result = new \stdClass();

        if(!$user->data['is_registered']) {
            $result->result = 'error';
            $result->message = 'You\'re not authorized to perform this action.';
        } else {
            parent::mq("UPDATE 
                Users 
            SET
                user_twid=NULL,
                user_tw_name=NULL
            WHERE 
                user_id='".parent::mres($user->data['user_id'])."'");

            $result->result = 'success';
        }

        return $result;
    }
}