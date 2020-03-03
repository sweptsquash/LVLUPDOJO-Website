<?php

namespace SenFramework\Controllers;

class Auth extends \SenFramework\DB\Database {
	
	public $data;
	
	public function __construct($route = NULL, $query = NULL)  {
		global $request, $senConfig, $user, $phpbb, $cart;
		
		$security = new \SenFramework\Security();
		$mailer = new \SenFramework\Mailer();
		
		$this->data['addressbarTrack'] = false;
		
		switch($route[0]) {
			default:
			case"sign-in":
			case"login":	
				if(!$user->data['is_registered']) {
					$ip = \SenFramework\SenFramework::getIP();
					$recaptcha = new \ReCaptcha\ReCaptcha(CAPTCHA_SECRET);
					
					$this->data['form'] = [
						'username' 				=> $request->untrimmed_variable('username', '', true),
						'password' 				=> $request->untrimmed_variable('password', '', true),
						'captcha_response'		=> $request->variable('g-recaptcha-response', '', true),
						'autologin' 			=> true,
						'CSRF' 					=> [
							$request->variable('CSRFName', '', true),
							$request->variable('CSRFToken', '', true)
						]
					];
					
					if($request->is_ajax()) {
						$ret = new \stdClass();
						
						if(isset($_SESSION['LoginAjaxCaptcha'])) {
							$ret->captcha = CAPTCHA;
						}
						
						if(($request->is_set_post('CSRFName') && $request->is_set_post('CSRFToken'))) {							
							if(!empty($this->data['form']['CSRF'][0]) && !empty($this->data['form']['CSRF'][1])) {
								if(isset($_SESSION['LoginAjaxCaptcha'])) {
									$resp = $recaptcha->verify($this->data['form']['captcha_response'], $ip); 

									if ($resp->isSuccess()) {
										$username_clean = $phpbb->utf8_clean_string($this->data['form']['username']);
		
										$sql = parent::mq("SELECT user_id, user_type FROM Users WHERE username_clean='" . parent::mres($username_clean) . "' OR LOWER(user_email)='" . parent::mres($this->data['form']['username']) . "'");

										if(!empty($sql) && $sql->num_rows > 0) {
											$row = parent::mfa($sql);
										
											parent::mq("DELETE FROM Users_login_attempts WHERE user_id='".parent::mres($row['user_id'])."'");
											
											unset($row);
										}
										
										unset($sql);
										
										$captcha = true;
									} else {
										$captchaErrors = NULL;

										foreach ($resp->getErrorCodes() as $code) {
											$captchaErrors .= '<tt>' . $code . '</tt> ';	 
										}
										
										$ret->result = 'error';
										$ret->message = 'reCAPTCHA returned the following error: ' . $captchaErrors . '';
										
										$captcha = false;
									}
								} else {
									$captcha = true;
								}
								
								if(!isset($ret->result) && $captcha && $security->validate_csrf_token($this->data['form']['CSRF'][0], $this->data['form']['CSRF'][1], false)) {
									$result = $user->login($this->data['form']['username'], $this->data['form']['password'], 1);
									
									if ($result['status'] != 'success') {
										$ret->result = 'error';
										
										switch($result['status']) {
											case"attempts":
												$ret->message = $result['message'];
												
												$_SESSION['LoginAjaxCaptcha'] = time();
												
												// Trigger Captcha
												$ret->captcha = CAPTCHA;
											break;
												
											default:
												$ret->message = $result['message'];
											break;
										}
									} else {
										unset($_SESSION['LoginAjaxCaptcha']);
										
										// Remove CSRF
										$security->unset_stored_csrf($this->data['form']['CSRF'][0]);

										// Redirect
										$ret->result = 'success';

										if(empty($result['user_row']['username'])) {
											$_SESSION['redirect'] = '/dashboard/username/';
										}

										if(isset($_SESSION['redirect'])) {
											$ret->redirect = 'https://'.((DEVELOP) ? 'development.lvlupdojo.com' : 'www.lvlupdojo.com').$_SESSION['redirect'];

											unset($_SESSION['redirect']);
										}
									}									
								} else {
									$ret->result = 'error';
									$ret->message = 'CSRF Token Mismatch! Bruteforce attempt logged.';
								}
							} else {
								$ret->result = 'error';
								$ret->message = 'No CSRF Token Provided! Bruteforce attempt logged.';
							}
						}
						
						$this->data['override']['json'] = true;
						$this->data['response'] = $ret;
					} else {
						$this->data['captcha'] = (isset($_SESSION['LoginCaptcha'])) ? CAPTCHA : NULL;
						
						if((!$request->is_set_post('CSRFName') || !$request->is_set_post('CSRFToken'))) {
							$this->data['form']['CSRF'] = $security->generate_csrf_token('Login');
						} else {
							$this->data['form']['CSRF'] = [
								$request->variable('CSRFName', '', true),
								$request->variable('CSRFToken', '', true)
							];
						}

						if(($request->is_set_post('CSRFName') && $request->is_set_post('CSRFToken'))) {							
							if(!empty($this->data['form']['CSRF'][0]) && !empty($this->data['form']['CSRF'][1])) {
								
								if(isset($_SESSION['LoginCaptcha'])) {
									$resp = $recaptcha->verify($this->data['form']['captcha_response'], $ip); 

									if ($resp->isSuccess()) {
										$username_clean = $phpbb->utf8_clean_string($this->data['form']['username']);
		
										$sql = parent::mq("SELECT user_id FROM Users WHERE username_clean='" . parent::mres($username_clean) . "' OR LOWER(user_email)='" . parent::mres($this->data['form']['username']) . "'");

										if(!empty($sql) && $sql->num_rows > 0) {
											$row = parent::mfa($sql);
										
											parent::mq("DELETE FROM Users_login_attempts WHERE user_id='".parent::mres($row['user_id'])."'");
											
											unset($row);
										}
										
										unset($sql);
										
										$captcha = true;
									} else {
										$captchaErrors = NULL;

										foreach ($resp->getErrorCodes() as $code) {
											$captchaErrors .= '<tt>' . $code . '</tt> ';	 
										}
										
										$this->data['error'][] = 'reCAPTCHA returned the following error: ' . $captchaErrors . '';
										
										$captcha = false;
									}
								} else {
									$captcha = true;
								}
								
								if(empty($this->data['error']) && $captcha && $security->validate_csrf_token($this->data['form']['CSRF'][0], $this->data['form']['CSRF'][1], false)) {
									$result = $user->login($this->data['form']['username'], $this->data['form']['password'], 1);
									
									if ($result['status'] != 'success') {
										switch($result['status']) {
											case"attempts":
												$this->data['error'][] = $result['message'];
												
												$_SESSION['LoginAjaxCaptcha'] = time();
												
												// Trigger Captcha
												$this->data['captcha'] = CAPTCHA;
											break;
												
											default:
												$this->data['error'][] = $result['message'];
											break;
										}
									} else {
										unset($_SESSION['LoginAjaxCaptcha']);
										
										// Remove CSRF
										$security->unset_stored_csrf($this->data['form']['CSRF'][0]);

										if(empty($result['user_row']['username'])) {
											$_SESSION['redirect'] = '/dashboard/username/';
										}

										// Redirect
										if(isset($_SESSION['redirect'])) {
											$location = 'https://'.((DEVELOP) ? 'development.lvlupdojo.com' : 'www.lvlupdojo.com').$_SESSION['redirect'];
											unset($_SESSION['redirect']);
										} else {
											$location = 'https://'.((DEVELOP) ? 'development.lvlupdojo.com' : 'www.lvlupdojo.com').'/dashboard/';
										}

										header('Location: '.$location);
										exit;
									}									
								} else {
									$this->data['error'][] = 'CSRF Token Mismatch! Bruteforce attempt logged.';
								}
							} else {
								$this->data['error'][] = 'No CSRF Token Provided! Bruteforce attempt logged.';
							}
						}
					}	
				} else {
					header("Location: https://".((DEVELOP) ? 'development.lvlupdojo.com' : 'www.lvlupdojo.com').'/');
					exit;
				}			
			break;
				
			case"sign-out":
			case"logout":
				if($user->data['is_registered']) {
					$cart->destoryCart($user->data['cart']['id']);
					$user->session_kill();
				}

				header("Location: https://".((DEVELOP) ? 'development.lvlupdojo.com' : 'www.lvlupdojo.com')."/");
				exit;
			break;
				
			case"sign-up":
			case"register":
				if(!$user->data['is_registered']) {
					$ip = \SenFramework\SenFramework::getIP();
					$recaptcha = new \ReCaptcha\ReCaptcha(CAPTCHA_SECRET);
					
					if($request->is_ajax()) {
						$response = new \stdClass();

						$default = [
							'format'	=> 'm/d/Y h:i a',
							'pricing' 	=> 'free',
							'country' 	=> 'US',
							'dob'		=> [
								date("j"),
								date("n"),
								date("Y")
							]
						];

						$this->data['form'] = [
							'valid' 				=> [],
							'error' 				=> [],
							'email'					=> strtolower($request->variable('signup-email', '', true)),
							'username'				=> $request->variable('signup-username', '', true),
							'password'				=> $request->untrimmed_variable('signup-password', '', true),
							'dateofbirth'			=> [
								$request->variable('signup-dob-day', (string)$default['dob'][0], false),
								$request->variable('signup-dob-month', (string)$default['dob'][1], false),
								$request->variable('signup-dob-year', (string)$default['dob'][2], false)
							],
							'location'				=> $request->variable('signup-location', $default['country'], true),
							'captcha_response'		=> $request->variable('g-recaptcha-response', '', true),
							'CSRF' 					=> [
								$request->variable('CSRFName', '', true),
								$request->variable('CSRFToken', '', true)
							]
						];

						if(($request->is_set_post('CSRFName') && $request->is_set_post('CSRFToken'))) {
							if(preg_match('/[\'^£$%&*()}{@#~?><>,.|=+¬-]/', $this->data['form']['username']) || preg_match('/\s/', $this->data['form']['username'])) {
								$response->result = 'error';
								$response->message[] = $response->invalid['username'] = 'Username\'s can only contain numbers and letters.';
							}
	
							$email = strtolower($this->data['form']['email']);

							if (!preg_match('/^' . $phpbb->get_preg_expression('email') . '$/i', $email)) {
								$response->result = 'error';
								$response->message[] = $response->invalid['email'] = 'Email address supplied is considered invalid, please supply another.';
							}	

							if(strlen($this->data['form']['username']) < 3) {
								$response->result = 'error';
								$response->message[] = $response->invalid['username'] = 'Username\'s must be longer than 3 characters.';
							}
							
							if(strlen($this->data['form']['username']) > 20) {
								$response->result = 'error';
								$response->message[] = $response->invalid['username'] = 'Username\'s must be no longer than 20 characters.';
							}

							if(empty($this->data['form']['password'])) {
								$response->result = 'error';
								$response->message[] = $response->invalid['password'] = 'You must provide a password.';
							}

							if(strlen($this->data['form']['password']) < 6) {
								$response->result = 'error';
								$response->message[] = $response->invalid['password'] = 'Your password is too short, passwords must be a minimum of 6 characters long and a max of 100 characters.';
							}

							if(strlen($this->data['form']['password']) > 100) {
								$response->result = 'error';
								$response->message[] = $response->invalid['password'] = 'Your password is too long, passwords must be a minimum of 6 characters long and a max of 100 characters.';
							}
	
							$bday = new \DateTime(date("Y-m-d", mktime(0,0,0,$this->data['form']['dateofbirth'][1],$this->data['form']['dateofbirth'][0],$this->data['form']['dateofbirth'][2])));
							$today = new \DateTime();
							$diff = $today->diff($bday);
	
							$age = $diff->y;
	
							if($age < 13) {
								$response->result = 'error';
								$response->message[] = 'You must be atleast 13 years of age to register.';
							}

							if($response->result !== 'error') {
								if(!empty($this->data['form']['CSRF'][0]) && !empty($this->data['form']['CSRF'][1])) {
									if($security->validate_csrf_token($this->data['form']['CSRF'][0], $this->data['form']['CSRF'][1], false)) {
										$resp = $recaptcha->verify($this->data['form']['captcha_response'], $ip); 
	
										if ($resp->isSuccess()) {
											$username_clean = $phpbb->utf8_clean_string($this->data['form']['username']);
											//$act_key = strtoupper(gen_rand_string(mt_rand(6, 10)));

											$sql_ary = [
												"user_ip"				=> $ip,
												"group_id"				=> 4,
												"username"				=> $this->data['form']['username'],
												"username_clean"		=> $username_clean,
												"user_email"			=> $email,
												"user_email_hash"		=> sprintf('%u', crc32(strtolower($email))) . strlen($email),
												"user_password"			=> $security->password_hash($this->data['form']['password'], PASSWORD_BCRYPT),
												"user_birthday"			=> date("Y-m-d H:i:s", mktime(0,0,0,$this->data['form']['dateofbirth'][1],$this->data['form']['dateofbirth'][0],$this->data['form']['dateofbirth'][2])),
												"user_from"				=> $this->data['form']['location'],
												"user_dateformat"		=> $default['format'],
												"user_timezone"			=> 'UTC'
											];

											parent::mq("INSERT INTO Users ".parent::build_array('INSERT', $sql_ary));
											$uid = parent::lastId();
											$subject = 'Welcome to LVLUP Dojo';

											$to = [
												'user_id' => $uid,
												'email' => $sql_ary['user_email'],
												'name' => $sql_ary['username']
											];

											$replace = [
												'{{ USERNAME }}' => $to['name'],
												'{{ SUBJECT }}' => $subject,
												'{{ YEAR }}' => date("Y")												
											];

											$mailer->SendMail('welcome', $subject, $to, $replace);

											// Perform Login
											$authLogin = $user->session_create($uid, 1);

											if($authLogin === true) {
												$response->login = true;
											}

											$response->result = 'success';
										} else {
											$captchaErrors = NULL;
	
											foreach ($resp->getErrorCodes() as $code) {
												$captchaErrors .= '<tt>' . $code . '</tt> ';	 
											}
											
											$response->result = 'error';
											$response->message[] = 'reCAPTCHA returned the following error: ' . $captchaErrors . '';
										}
									} else {
										$response->result = 'error';
										$response->message[] = 'CSRF Token Mismatch! Bruteforce attempt logged.';
									}
								} else {
									$response->result = 'error';
									$response->message[] = 'No CSRF Token Provided! Bruteforce attempt logged.';
								}
							}
						}
						
						$this->data['override']['json'] = true;
						$this->data['response'] = $response;
					} else {
						$default = [
							'format'	=> 'm/d/Y h:i a',
							'pricing' 	=> (!empty($route[1])) ? $route[1] : 'free',
							'country' 	=> 'US',
							'dob'		=> [
								date("j"),
								date("n"),
								date("Y")
							]
						];

						if(isset($_SESSION['Discount'])) {
							$dsql = parent::mq("SELECT * FROM Pricing_Discounts WHERE code='".parent::mres($_SESSION['Discount'])."' AND (start <= NOW() AND end >= NOW())");
				
							if($dsql->num_rows > 0) {
								while($drow = parent::mfa($dsql)) {									
									$this->data['discountPlans'][$drow['pricing_id']] = [
										'id' => $drow['id'],
										'percentage' => $drow['percentage']
									];
								}
							} else {
								unset($_SESSION['Discount']);
							}
						}

						$sql = parent::mq("SELECT * FROM Pricing WHERE active='1' AND published='1' ORDER BY OrderNo ASC");
		
						if(!empty($sql) && $sql->num_rows > 0) {
							$matched = false;

							while($row = parent::mfa($sql)) {
								$this->data['pricing'][$row['id']] = $row;	

								if(isset($_SESSION['Discount']) && !empty($this->data['discountPlans']) && array_key_exists($row['id'], $this->data['discountPlans'])) {
									$this->data['pricing'][$row['id']]['cost'] = number_format($row['cost'] - ($row['cost'] * ($this->data['discountPlans'][$row['id']]['percentage'] / 100)), 2);
								}
								
								if($default['pricing'] == $row['slug']) {
									$default['pricing'] = $row['id'];
									$matched = true;
								}
							}

							if(!$matched) {
								$default['pricing'] = '1';
							}
						}
						
						// Set Default Country Based On IP
						if($ip != 'UNKNOWN') {
							$default['country'] = \geoip_country_code_by_name($ip);
							$region = \geoip_record_by_name($ip);

							// Fix for new IPs
							if(empty($default['country'])) {
								$default['country'] = 'US';
							}
							
							if(empty($region)) {
								$region['region'] = 'CA';
							}
						} else {
							$default['country'] = 'US';
							$region['region'] = 'CA';
						}
						
						$default['timezone'] = \geoip_time_zone_by_country_and_region($default['country'], $region['region']);
						
						// If Timezone returns false set a default
						if(!$default['timezone']) {
							$default['timezone'] = 'America/Los_Angeles';
						}	
						
						// Ensure default pricing is in options otherwise set as Free plan
						if(!\SenFramework\SenFramework::in_array_r($default['pricing'], $this->data['pricing'])) {
							$default['pricing'] = '1';
						}			
						
						$this->data['form'] = [
							'email'					=> strtolower($request->variable('signup-email', '', true)),
							'username'				=> $request->variable('signup-username', '', true),
							'password'				=> $request->untrimmed_variable('signup-password', '', true),
							'dateofbirth'			=> [
								$request->variable('signup-dob-day', (string)$default['dob'][0], false),
								$request->variable('signup-dob-month', (string)$default['dob'][1], false),
								$request->variable('signup-dob-year', (string)$default['dob'][2], false)
							],
							'forename'				=> $request->variable('signup-first-name', '', true),
							'surname'				=> $request->variable('signup-last-name', '', true),
							'format'				=> $request->variable('signup-format', $default['format'], true),
							'timezone'				=> $request->variable('signup-timezone', $default['timezone'], true),
							'location'				=> $request->variable('signup-location', $default['country'], true),
							'info'					=> $request->variable('signup-info', '', true),
							'plan' 					=> (int)$request->variable('signup-plan', $default['pricing'], true),
							'captcha_response'		=> $request->variable('g-recaptcha-response', '', true),
							'CSRF' 					=> [
								$request->variable('CSRFName', '', true),
								$request->variable('CSRFToken', '', true)
							]
						];

						if((!$request->is_set_post('CSRFName') || !$request->is_set_post('CSRFToken'))) {
							$this->data['form']['CSRF'] = $security->generate_csrf_token('signup');
						}
						
						$this->data['options'] = [
							'timezone'  => \SenFramework\SenFramework::Timezones($this->data['form']['timezone']),
							'format'	=> \SenFramework\SenFramework::dateFormats($this->data['form']['format'])
						];	
						
						if(($request->is_set_post('CSRFName') && $request->is_set_post('CSRFToken'))) {							
							if(!empty($this->data['form']['CSRF'][0]) && !empty($this->data['form']['CSRF'][1])) {

								$email = strtolower($this->data['form']['email']);

								if (!preg_match('/^' . $phpbb->get_preg_expression('email') . '$/i', $email)) {
									$this->data['form']['error'][] = 'Email address supplied is considered invalid, please supply another.';
								}	

								if(strlen($this->data['form']['username']) < 3) {
									$this->data['form']['error'][] = 'Username\'s must be longer than 3 characters.';
								}
								
								if(strlen($this->data['form']['username']) > 20) {
									$this->data['form']['error'][] = 'Username\'s must be no longer than 20 characters.';
								}

								if(empty($this->data['form']['password'])) {
									$this->data['form']['error'][] = 'You must provide a password.';
								}

								if(strlen($this->data['form']['password']) < 6) {
									$this->data['form']['error'][] = 'Your password is too short, passwords must be a minimum of 6 characters long and a max of 100 characters.';
								}

								if(strlen($this->data['form']['password']) > 100) {
									$this->data['form']['error'][] = 'Your password is too long, passwords must be a minimum of 6 characters long and a max of 100 characters.';
								}
		
								$bday 	= new \DateTime(date("Y-m-d", mktime(0,0,0,$this->data['form']['dateofbirth'][1],$this->data['form']['dateofbirth'][0],$this->data['form']['dateofbirth'][2])));
								$today 	= new \DateTime();
								$diff 	= $today->diff($bday);
		
								$age 	= $diff->y;
		
								if($age < 13) {
									$this->data['form']['error'][] = 'You must be atleast 13 years of age to register.';
								}
								
								if(empty($this->data['form']['error']) && $security->validate_csrf_token($this->data['form']['CSRF'][0], $this->data['form']['CSRF'][1], false)) {
									$resp = $recaptcha->verify($this->data['form']['captcha_response'], $ip); 

									if ($resp->isSuccess()) {
										// Set session so users redirect upon login to setup their subscription
										if((int)$this->data['form']['plan'] != 1) {
											$_SESSION['Subscription'] = $this->data['form']['plan'];
											$_SESSION['redirect'] = '/dashboard/billing/upgrade/';
										}
										
										$username_clean = $phpbb->utf8_clean_string($this->data['form']['username']);
										//$act_key = strtoupper(gen_rand_string(mt_rand(6, 10)));

										$sql_ary = [
											"user_ip"				=> $ip,
											"group_id"				=> 4,
											"user_first_name"		=> $this->data['form']['forename'],
											"user_last_name"		=> $this->data['form']['surname'],
											"username"				=> $this->data['form']['username'],
											"username_clean"		=> $username_clean,
											"user_email"			=> $email,
											"user_email_hash"		=> sprintf('%u', crc32(strtolower($email))) . strlen($email),
											"user_password"			=> $security->password_hash($this->data['form']['password'], PASSWORD_BCRYPT),
											"user_birthday"			=> date("Y-m-d H:i:s", mktime(0,0,0,$this->data['form']['dateofbirth'][1],$this->data['form']['dateofbirth'][0],$this->data['form']['dateofbirth'][2])),
											"user_from"				=> $this->data['form']['location'],
											"user_dateformat"		=> $default['format'],
											"user_timezone"			=> 'UTC'
										];

										parent::mq("INSERT INTO Users ".parent::build_array('INSERT', $sql_ary));
										$uid = parent::lastId();

										$this->data['user_id'] = $uid;

										$subject = 'Welcome to LVLUP Dojo';

										$to = [
											'user_id' => $uid,
											'email' => $sql_ary['user_email'],
											'name' => ((!empty($sql_ary['user_first_name'])) ? $sql_ary['user_first_name'].' '.$sql_ary['user_last_name'] : ((!empty($sql_ary['username'])) ? $sql_ary['username'] : $sql_ary['user_email']))
										];

										$replace = [
											'{{ USERNAME }}' => $to['name'],
											'{{ SUBJECT }}' => $subject,
											'{{ YEAR }}' => date("Y")												
										];

										$mailer->SendMail('welcome', $subject, $to, $replace);

										$authLogin = $user->session_create($uid, 1);

										if($authLogin === true) {
											if(!isset($_SESSION['redirect'])) {
												header("Location: https://".((DEVELOP) ? 'development.lvlupdojo.com' : 'www.lvlupdojo.com')."/dashboard/");
											} else {
												header("Location: https://".((DEVELOP) ? 'development.lvlupdojo.com' : 'www.lvlupdojo.com').$_SESSION['redirect']);
											}

											exit;
										}

										$this->data['registered'] = true;
									} else {
										$captchaErrors = NULL;

										foreach ($resp->getErrorCodes() as $code) {
											$captchaErrors .= '<tt>' . $code . '</tt> ';	 
										}
										
										$this->data['form']['error'][] = 'reCAPTCHA returned the following error: ' . $captchaErrors . '';
										
										$captcha = false;
									}
								}
							}
						}
					}
				} else {
					if(!empty($route[1])) {
						$sql = parent::mq("SELECT id FROM Pricing WHERE slug='".parent::mres($route[1])."'");

						if($sql->num_rows > 0) {
							$_SESSION['Subscription'] = $route[1];
							header("Location: https://".((DEVELOP) ? 'development.lvlupdojo.com' : 'www.lvlupdojo.com').'/dashboard/billing/');
						} else {
							header("Location: https://".((DEVELOP) ? 'development.lvlupdojo.com' : 'www.lvlupdojo.com').'/');
							exit;
						}
					} else {
						header("Location: https://".((DEVELOP) ? 'development.lvlupdojo.com' : 'www.lvlupdojo.com').'/');
						exit;
					}
				}	
			break;
				
			case"forgot":
			case"forgot-password":
				if(!$user->data['is_registered']) {
					$ip = \SenFramework\SenFramework::getIP();
					$recaptcha = new \ReCaptcha\ReCaptcha(CAPTCHA_SECRET);

					if(!empty($route[1])) {
						$actkey = explode('-', $route[1]);
	
						if(!empty($actkey) && is_array($actkey)) {
							$sql = parent::mq("SELECT 
								u.user_id,
								u.username,
								u.user_email,
								ur.requested
							FROM
								Users AS u
							INNER JOIN
								Users_Resets AS ur
							ON
								ur.user_id=u.user_id
							WHERE
								u.user_id='".parent::mres($actkey[0])."'
							AND
								u.user_actkey='".parent::mres(strtoupper($actkey[1]))."'");

							if($sql->num_rows > 0) {
								$user_row = parent::mfa($sql);

								$this->data['form'] = [
									'request'			=> 'reset',
									'username'			=> (!empty($user_row['username'])) ? $user_row['username'] : $user_row['user_email'],
									'captcha_response' 	=> $request->variable('g-recaptcha-response', '', true),
									'password' 			=> $request->untrimmed_variable('password', '', true),
									'passwordConfirm'	=> $request->untrimmed_variable('password_repeat', '', true),
									'CSRF'				=> []
								];
								
								if((!$request->is_set_post('CSRFName') || !$request->is_set_post('CSRFToken'))) {
									$this->data['form']['CSRF'] = $security->generate_csrf_token('ForgotReset');
								} else {
									$this->data['form']['CSRF'] = [
										$request->variable('CSRFName', '', true),
										$request->variable('CSRFToken', '', true)
									];
								}

								if($request->is_set_post('updateReset')) {
									$CSRFValid = false;
		
									if(!empty($this->data['form']['CSRF'][0]) && !empty($this->data['form']['CSRF'][1])) {
										if($security->validate_csrf_token($this->data['form']['CSRF'][0], $this->data['form']['CSRF'][1], false)) {
											$security->unset_stored_csrf($this->data['form']['CSRF'][0]);
		
											$CSRFValid = true;
		
											$this->data['form']['CSRF'] = $security->generate_csrf_token('ForgotReset');
										} else {
											$this->data['error'][] = 'CSRF Token Mismatch! Bruteforce attempt logged.';
										}
									} else {
										$this->data['error'][] = 'No CSRF Token Provided! Bruteforce attempt logged.';
									}

									if(empty($this->data['form']['password'])) {
										$this->data['error'][] = $this->data['form']['invalid']['password'] = 'You must provide a password.';
									}
		
									if(strlen($this->data['form']['password']) < 6) {
										$this->data['error'][] = $this->data['form']['invalid']['password'] = 'Your password is too short, passwords must be a minimum of 6 characters long and a max of 100 characters.';
									}
		
									if(strlen($this->data['form']['password']) > 100) {
										$this->data['error'][] = $this->data['form']['invalid']['password'] = 'Your password is too long, passwords must be a minimum of 6 characters long and a max of 100 characters.';
									}

									if(empty($this->data['form']['passwordConfirm'])) {
										$this->data['error'][] = $this->data['form']['invalid']['passwordConfirm'] = 'You must confirm your password.';
									}

									if($this->data['form']['password'] != $this->data['form']['passwordConfirm']) {
										$this->data['error'][] = $this->data['form']['invalid']['passwordConfirm'] = 'Confirm password doesn\'t match.';
									}

									if($CSRFValid && !isset($this->data['error'])) {
										$resp = $recaptcha->verify($this->data['form']['captcha_response'], $ip); 

										if ($resp->isSuccess()) {
											$sql_ary = [
												'user_ip'			=> $ip,
												'user_password'		=> $security->password_hash($this->data['form']['password'], PASSWORD_BCRYPT),
												'user_actkey'		=> NULL,
												'user_passchg'		=> time()
											];

											parent::mq("UPDATE Users SET ".parent::build_array('UPDATE', $sql_ary)." WHERE user_id='".$user_row['user_id']."'");

											parent::mq("DELETE FROM Users_Resets WHERE user_id='".$user_row['user_id']."'");

											$this->data['updated'] = true;
										} else {
											$this->data['error']['captcha'] = 'reCAPTCHA returned the following error: <ul>';
		
											foreach ($resp->getErrorCodes() as $code) {
												$this->data['error']['captcha'] .= '<li>' . $code . '</li>';	 
											}
		
											$this->data['error']['captcha'] .= '</ul>';
										}
									}									
								}
							} else {
								$this->data['error'][] = 'The password reset request has either expired or does not exist.';
								$this->data['hide'] = true;
							}
						} else {
							$this->data['triggererror'] = '404';	
						}
					} else {
						$this->data['form'] = [ 
							'request'			=> 'request',
							'captcha_response' 	=> $request->variable('g-recaptcha-response', '', true),
							'username' 			=> $request->variable('username', '', true),
							'email' 			=> strtolower($request->variable('email', '', true)),
							'CSRF'				=> []
						];
						
						if((!$request->is_set_post('CSRFName') || !$request->is_set_post('CSRFToken'))) {
							$this->data['form']['CSRF'] = $security->generate_csrf_token('Forgot');
						} else {
							$this->data['form']['CSRF'] = [
								$request->variable('CSRFName', '', true),
								$request->variable('CSRFToken', '', true)
							];
						}

						if($request->is_set_post('resetBtn')) {
							$CSRFValid = false;

							if(!empty($this->data['form']['CSRF'][0]) && !empty($this->data['form']['CSRF'][1])) {
								if($security->validate_csrf_token($this->data['form']['CSRF'][0], $this->data['form']['CSRF'][1], false)) {
									$security->unset_stored_csrf($this->data['form']['CSRF'][0]);

									$CSRFValid = true;

									$this->data['form']['CSRF'] = $security->generate_csrf_token('Forgot');
								} else {
									$this->data['error'][] = 'CSRF Token Mismatch! Bruteforce attempt logged.';
								}
							} else {
								$this->data['error'][] = 'No CSRF Token Provided! Bruteforce attempt logged.';
							}

							if(empty($this->data['form']['username']) && empty($this->data['form']['email'])) {
								$this->data['error'][] = 'Please specify either a Username or Email Address.';
							}

							if(!empty($this->data['form']['username']) && (preg_match('/[\'^£$%&*()}{@#~?><>,.|=+¬-]/', $this->data['form']['username']) || preg_match('/\s/', $this->data['form']['username']))) {
								$this->data['error'][] = 'Username\'s can only contain numbers and letters.';
							}

							if (!empty($this->data['form']['email']) && !preg_match('/^' . $phpbb->get_preg_expression('email') . '$/i', $this->data['form']['email'])) {
								$this->data['error'][] = 'Email address supplied is considered invalid, please supply another.';
							}

							if($CSRFValid && !isset($this->data['error'])) {
								$resp = $recaptcha->verify($this->data['form']['captcha_response'], $ip); 

								if ($resp->isSuccess()) {
									$Where = NULL;

									if(!empty($this->data['form']['username'])) {
										$Where .= "username_clean='" . parent::mres($phpbb->utf8_clean_string($this->data['form']['username'])) . "'";
									}

									if(!empty($this->data['form']['email'])) {
										$Where .= ((!empty($this->data['form']['username'])) ? ' OR' : NULL) . " LOWER(user_email)='".parent::mres($this->data['form']['email'])."'";
									}

									if(!empty($Where)) {
										$sql = parent::mq("SELECT 
											user_id,
											username,
											user_first_name,
											user_last_name,
											user_type,
											user_email
										FROM
											Users
										WHERE ".$Where." LIMIT 1");

										if($sql->num_rows > 0) {
											$user_row = parent::mfa($sql);

											if ($user_row['user_type'] == USER_INACTIVE) {
												$this->data['error'][] = 'This account has been deactivated by an Admin, please <a href="/contact-us/">contact support</a>.';
											}

											if(!isset($this->data['error'])) {
												$user_actkey = strtoupper($this->gen_rand_string(mt_rand(6, 10)));

												parent::mq("UPDATE Users SET user_actkey='".$user_actkey."' WHERE user_id='".$user_row['user_id']."'");

												$browser = $security->getBrowser();
												$os		 = $security->getOS();

												$sql_ary = [
													'user_id'		=>	$user_row['user_id'],
													'user_ip'		=>  $ip,
													'user_agent'	=>  $request->server('HTTP_USER_AGENT')
												];

												parent::mq("INSERT INTO Users_Resets ".parent::build_array('INSERT', $sql_ary));

												$user_actkey = $user_row['user_id'].'-'.$user_actkey;

												$to = [
													'user_id' => $user_row['user_id'],
													'email' => $user_row['user_email'],
													'name' => ((!empty($user_row['user_first_name'])) ? $user_row['user_first_name'].' '.$user_row['user_last_name'] : ((!empty($user_row['username'])) ? $user_row['username'] : $user_row['user_email']))
												];

												$subject = 'LVLUP Dojo Password Reset';

												$replace = [
													'{{ USERNAME }}' 	=> $to['name'],
													'{{ USER_ACTKEY }}' => $user_actkey,
													'{{ OS }}'			=> $os,
													'{{ BROWSER }}'		=> $browser,
													'{{ IP }}'			=> $ip,
													'{{ SUBJECT }}' 	=> $subject,
													'{{ YEAR }}' 		=> date("Y")												
												];

												$result = $mailer->SendMail('forgot', $subject, $to, $replace);
												
												$this->data['reminded'] = true;
											}
										} else {
											$this->data['error'][] = 'No user found with the details supplied.';
										}
									} else {
										$this->data['error'][] = 'An unexpected error occured during user lookup.';
									}
								} else {
									$this->data['error']['captcha'] = 'reCAPTCHA returned the following error: <ul>';

									foreach ($resp->getErrorCodes() as $code) {
										$this->data['error']['captcha'] .= '<li>' . $code . '</li>';	 
									}

									$this->data['error']['captcha'] .= '</ul>';
								}
							}
						}
					}					
				} else {
					header("Location: https://".((DEVELOP) ? 'development.lvlupdojo.com' : 'www.lvlupdojo.com').'/');
					exit;
				}	
			break;
		}
	}

	private function gen_rand_string($num_chars = 8) {
		// [a, z] + [0, 9] = 36
		return substr(strtoupper(base_convert(bin2hex(random_bytes(8)), 16, 36)), 0, $num_chars);
	}
}