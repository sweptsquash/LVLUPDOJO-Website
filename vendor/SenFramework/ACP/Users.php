<?php

namespace SenFramework\ACP;

class Users extends \SenFramework\DB\Database {

    public $data;

    public $order_state = [
		0 => 'unknown',
		1 => 'cart',
		2 => 'new',
		3 => 'processing',
		4 => 'authorized',
		5 => 'completed',
		6 => 'failed',
		7 => 'cancelled',
		8 => 'refunded'
	];

    public function __construct($route = NULL, $query = NULL) {
        global $request, $senConfig, $user, $phpbb;

        $security = new \SenFramework\Security();
		$mailer = new \SenFramework\Mailer();

        $this->data['template_folder'] = 'acp/users';
        $this->data['nav'] = 'users';
        $this->data['single'] = true;

        switch($route[2]) {
            default:        
                $this->data['override']['title'] = 'Users &bull; Admin Panel ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
                $this->data['template'] = 'list';
            break;

            /* Edit */
            case"e":
                $uid = intval($route[3]);

                if(!empty($uid)) {
                    $user_row = $this->fetchUser($uid);

                    if(!empty($user_row)) {
                        $this->data['override']['title'] = 'Editting User "'.((!empty($user_row['display_name'])) ? $user_row['display_name'] : $user_row['user_email']).'" &bull; Admin Panel ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
                        $this->data['template'] = 'edit';

                        if (!empty($user_row['user_birthday'])) {					
                            $date =  \DateTime::createFromFormat("Y-m-d H:i:s", $user_row['user_birthday']);

                            $user_row['user_birthday'] = array();

                            list($user_row['user_birthday'][0], $user_row['user_birthday'][1], $user_row['user_birthday'][2]) = explode('-', $date->format("j-m-Y"));
                        } else {
                            $user_row['user_birthday'] = array();

                            list($user_row['user_birthday'][0], $user_row['user_birthday'][1], $user_row['user_birthday'][2]) = explode('-', date("j-m-Y"));
                        }

                        // Handle Account Update
                        if($request->is_set_post('updateAccount')) {
                            $user_data = [
                                'username'          => $request->variable('username', $user_row['username'], true),
                                'user_email'        => strtolower($request->raw_variable('user_email', $user_row['user_email'])),
                                'user_first_name'   => $request->variable('user_first_name', $user_row['user_first_name'], true),
                                'user_last_name'    => $request->variable('user_last_name', $user_row['user_last_name'], true),
                                'user_from'         => $request->variable('user_from', $user_row['user_from'], true),
                                'user_timezone'     => $request->variable('user_timezone', $user_row['user_timezone'], true),
                                'user_dateformat'   => $request->variable('user_dateformat', $user_row['user_dateformat'], true),
                                'user_birthday'     => [
                                    $request->variable('user_birthday_day', (string)$user_row['user_birthday'][0]),
                                    $request->variable('user_birthday_month', (string)$user_row['user_birthday'][1]),
                                    $request->variable('user_birthday_year', (string)$user_row['user_birthday'][2])
                                ],
                                'group_id'        => $request->variable('group_id', (string)$user_row['group_id'], true)
                            ];

                            if(!empty($user_data['username'])) {
                                if(preg_match('/[\'^£$%&*()}{@#~?><>,.|=+¬-]/', $user_data['username']) || preg_match('/\s/', $user_data['username'])) {
                                    $this->data['error'][] = 'Username\'s can only contain numbers and letters.';
                                }

                                if(strlen($user_data['username']) < 3) {
                                    $this->data['error'][] = 'Username\'s must be longer than 3 characters.';
                                }
                                
                                if(strlen($user_data['username']) > 20) {
                                    $this->data['error'][] = 'Username\'s must be no longer than 20 characters.';
                                }
                            } else {
                                unset($user_data['username']);
                            }
                            
                            if (!preg_match('/^' . $phpbb->get_preg_expression('email') . '$/i', $user_data['user_email'])) {
								$this->data['error'][] = 'Email address supplied is considered invalid, please supply another.';
                            }

                            if(!isset($this->data['error'])) {
                                if($user_data['user_email'] === strtolower($user_row['user_email'])) {
                                    unset($user_data['user_email']);
                                } else {
                                    $user_data["user_email_hash"] = sprintf('%u', crc32($user_data['user_email'])) . strlen($user_data['user_email']);
                                }

                                if(!empty($user_data['username'])) {
                                    $username_clean = $phpbb->utf8_clean_string($user_data['username']);

                                    if($username_clean !== $user_row['username_clean']) {
                                        $user_data['username_clean'] = $username_clean;
                                    } else {
                                        unset($user_data['username']);
                                    }
                                }

                                // Convert to SQL Friendly Date
                                $user_data['user_birthday'] = date("Y-m-d H:i:s", mktime(0,0,0,$user_data['user_birthday'][1],$user_data['user_birthday'][0],$user_data['user_birthday'][2]));

                                parent::mq("UPDATE Users SET ".parent::build_array('UPDATE', $user_data)." WHERE user_id='".parent::mres($uid)."'");

                                $this->data['updated'] = true;
                            }

                            $user_row = array_merge($user_row, $user_data);
                        }

                        // Options Listing
                        $this->data['options'] = [
                            'timezone'  => \SenFramework\SenFramework::Timezones($user_row['user_timezone']),
                            'format'	=> \SenFramework\SenFramework::dateFormats($user_row['user_dateformat']),
                            'groups'    => '',
                            'birthday'  => [
                                'day'   => '',
                                'month' => '',
                                'year'  => ''
                            ]
                        ];

                        for($i = 1; $i < 32; $i++) {
                            $this->data['options']['birthday']['day'] .= '<option value="'.$i.'"'.((intval($user_row['user_birthday'][0]) == $i) ? ' selected' : NULL).'>'.$i.'</option>';
                        }

                        for($i = 1; $i < 13; $i++) {
                            $month = (string)date("m", mktime(0,0,0,$i,1,date("Y")));

                            $this->data['options']['birthday']['month'] .= '<option value="'.$month.'"'.(($user_row['user_birthday'][1] == $month) ? ' selected' : NULL).'>'.date("F", mktime(0,0,0,$i,1,date("Y"))).'</option>';
                        }

                        for($i = 1930; $i <= intval(date("Y")); $i++) {
                            $this->data['options']['birthday']['year'] .= '<option value="'.$i.'"'.((intval($user_row['user_birthday'][2]) == $i) ? ' selected' : NULL).'>'.$i.'</option>';
                        }

                        $gsql = parent::mq("SELECT * FROM Users_Groups WHERE active='1' AND id <> 7 ORDER BY id ASC");
                        
                        while($grow = parent::mfa($gsql)) {
                            $this->data['options']['groups'] .= '<option value="'.$grow['id'].'"'.(($grow['id'] == $user_row['group_id']) ? ' selected="selected"' : NULL).'>'.$grow['name'].'</option>';
                        }

                        // Upgrade to Lifetime Subscription
                        if($request->is_set_post('upgradeLifetime')) {
                            $sub = [
                                'user_id'           => $uid,
                                'plan_id'           => 4,
                                'transaction_id'    => 0
                            ];

                            $user_row['plan'] = 4;

                            parent::mq("INSERT INTO Users_Subscriptions ".parent::build_array('INSERT', $sub)." ON DUPLICATE KEY UPDATE plan_id='4'");

                            $this->data['subupdated'] = true;
                        }
                        
                        // Cancel Lifetime Subscription
                        if($request->is_set_post('cancelLifetime')) {
                            parent::mq("DELETE FROM Users_Subscriptions WHERE user_id='".parent::mres($uid)."' AND plan_id='4'");

                            $user_row['plan'] = 1;

                            $this->data['subupdated'] = true;
                        }

                        /* Cancel A Users Paid Subscription */
                        if($request->is_set_post('cancelSubscription')) {
                            /* Cancel Any Subscriptions */
                            try {
                                $this->cancelSubscription($uid);
                            } catch(\Exception $ex) {
                                $this->data['error'][] = $ex->getMessage();
                            }

                            if(!isset($this->data['error'])) {
                                $user_row['plan'] = 1;

                                $this->data['subupdated'] = true;
                            }
                        }

                        $this->data['u'] = $user_row;
                    } else {
                        $this->data['triggererror'] = '404';
                    }
                } else {
                    $this->data['triggererror'] = '404';
                }
            break;

            /* Reactivate Account */
            case"a":
                $uid = intval($route[3]);

                if(!empty($uid)) {
                    $user_row = $this->fetchUser($uid);

                    if(!empty($user_row)) {
                        $this->data['override']['title'] = 'Activate User "'.((!empty($user_row['display_name'])) ? $user_row['display_name'] : $user_row['user_email']).'" &bull; Admin Panel ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
                        $this->data['template'] = 'activate';
                        $this->data['u'] = $user_row;

                        if($request->is_set_post('submit')) {
                            parent::mq("UPDATE Users SET user_type='0' WHERE user_id='".parent::mres($uid)."'");

                            $this->data['success'] = true;
                        }
                    } else {
                        $this->data['triggererror'] = '404';
                    }
                } else {
                    $this->data['triggererror'] = '404';
                }
            break;

            /* Deactivate Account */
            case"b":
                $uid = intval($route[3]);

                if(!empty($uid)) {
                    $user_row = $this->fetchUser($uid);

                    if(!empty($user_row)) {
                        $this->data['override']['title'] = 'Deactivate User "'.((!empty($user_row['display_name'])) ? $user_row['display_name'] : $user_row['user_email']).'" &bull; Admin Panel ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
                        $this->data['template'] = 'deactivate';
                        $this->data['u'] = $user_row;

                        if($request->is_set_post('submit')) {
                            /* Cancel Any Subscriptions */
                            try {
                                $this->cancelSubscription($uid);
                            } catch(\Exception $ex) {
                                $this->data['error'][] = $ex->getMessage();
                            }

                            if(!isset($this->data['error'])) {
                                parent::mq("UPDATE Users SET user_type='1' WHERE user_id='".parent::mres($uid)."'");

                                $this->data['success'] = true;
                            }
                        }
                    } else {
                        $this->data['triggererror'] = '404';
                    }
                } else {
                    $this->data['triggererror'] = '404';
                }
            break;

            /* Reset Password */
            case"r":
                $uid = intval($route[3]);

                if(!empty($uid)) {
                    $user_row = $this->fetchUser($uid);

                    if(!empty($user_row)) {
                        $this->data['override']['title'] = 'Send Password Reset To User "'.((!empty($user_row['display_name'])) ? $user_row['display_name'] : $user_row['user_email']).'" &bull; Admin Panel ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
                        $this->data['template'] = 'reset';
                        $this->data['u'] = $user_row;

                        if($request->is_set_post('submit')) {
                            $user_actkey = strtoupper($this->gen_rand_string(mt_rand(6, 10)));

                            parent::mq("UPDATE Users SET user_actkey='".$user_actkey."' WHERE user_id='".$user_row['user_id']."'");

                            $browser = $security->getBrowser();
                            $os		 = $security->getOS();

                            $sql_ary = [
                                'user_id'		=>	$user_row['user_id'],
                                'user_ip'		=>  NULL,
                                'user_agent'	=>  NULL
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
                                '{{ ADMIN }}'		=> true,
                                '{{ SUBJECT }}' 	=> $subject,
                                '{{ YEAR }}' 		=> date("Y")												
                            ];

                            $result = $mailer->SendMail('forgot-admin', $subject, $to, $replace);

                            $this->data['success'] = true;
                        }
                    } else {
                        $this->data['triggererror'] = '404';
                    }
                } else {
                    $this->data['triggererror'] = '404';
                }
            break;

            /* Delete */
            case"d":
                $uid = intval($route[3]);

                if(!empty($uid)) {
                    $user_row = $this->fetchUser($uid);

                    if(!empty($user_row)) {
                        $this->data['override']['title'] = 'Delete User "'.((!empty($user_row['display_name'])) ? $user_row['display_name'] : $user_row['user_email']).'" &bull; Admin Panel ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
                        $this->data['template'] = 'delete';
                        $this->data['u'] = $user_row;

                        if($request->is_set_post('submit')) {

                            /* Cancel Any Subscriptions */
                            try {
                                $this->cancelSubscription($uid);
                            } catch(\Exception $ex) {
                                $this->data['error'][] = $ex->getMessage();
                            }

                            if(!isset($this->data['error'])) {
                                parent::mq("DELETE FROM Cart WHERE user_id='".parent::mres($uid)."'");
                                parent::mq("DELETE FROM Sessions WHERE session_user_id='".parent::mres($uid)."'");
                                parent::mq("DELETE FROM Session_Keys WHERE user_id='".parent::mres($uid)."'");
                                parent::mq("DELETE FROM Courses_Reviews WHERE user_id='".parent::mres($uid)."'");
                                parent::mq("DELETE FROM Users WHERE user_id='".parent::mres($uid)."'");
                                parent::mq("DELETE FROM Users_Resets WHERE user_id='".parent::mres($uid)."'");
                                parent::mq("DELETE FROM Users_Subscriptions WHERE user_id='".parent::mres($uid)."'");
                                parent::mq("DELETE FROM Users_login_attempts WHERE user_id='".parent::mres($uid)."'");
                                parent::mq("DELETE FROM Transactions_Items WHERE user_id='".parent::mres($uid)."'");
                                parent::mq("DELETE FROM Transactions WHERE user_id='".parent::mres($uid)."'");

                                $this->data['success'] = true;
                            }
                        }
                    } else {
                        $this->data['triggererror'] = '404';
                    }
                } else {
                    $this->data['triggererror'] = '404';
                }
            break;
        }
    }

    private function fetchUsers(): array {
        global $user;
        
        $users = array();

        $sql = parent::mq("SELECT user_id, user_type, username, user_email, user_first_name, user_last_name, user_regdate, user_last_visit, user_avatar FROM Users WHERE user_id <> 1 ORDER BY LOWER(user_email) ASC");

        if($sql->num_rows > 0) {
            while($row = parent::mfa($sql)) {
                $users[$row['user_id']] = $row;
                $users[$row['user_id']]['active'] = ($row['user_type'] == USER_INACTIVE) ? 0 : 1;
                $users[$row['user_id']]['display_name']     = ((!empty($row['user_first_name'])) ? $row['user_first_name'].((!empty($row['user_last_name'])) ? ' '.$row['user_last_name'] : NULL) : ((!empty($row['username'])) ? $row['username'] : NULL));
                $users[$row['user_id']]['user_regdate']     = $user->format_date(strtotime($row['user_regdate']));
                $users[$row['user_id']]['user_last_visit']  = ($row['user_last_visit'] > 0 ) ? $user->format_date($row['user_last_visit']) : 'N/A';
                $users[$row['user_id']]['user_last_visit_unix']  = $row['user_last_visit'];

                if(empty($users[$row['user_id']]['display_name'])) {
                    unset($users[$row['user_id']]['display_name']);
                }
            }
        }

        return $users;
    }

    private function fetchUser(int $user_id): array {
        global $user;

        $udata = array();

        $sql = parent::mq("SELECT * FROM Users WHERE user_id='".parent::mres($user_id)."'");

        if($sql->num_rows > 0) {
            $udata = parent::mfa($sql);
            $udata['user_last_visit']  = ($udata['user_last_visit'] > 0 ) ? $user->format_date($udata['user_last_visit']) : 'N/A';

            $uplan = parent::mq("SELECT plan_id FROM Users_Subscriptions WHERE user_id='".parent::mres($user_id)."'");

            if($uplan->num_rows > 0) {
                $urow = parent::mfa($uplan);

                $udata['plan'] = (int)$urow['plan_id'];
            } else {
                $udata['plan'] = 1;
            }

            $sql = parent::mq("SELECT
				t.InvoiceNo,
				t.InvoiceDate,
				t.PaymentMethod,
				t.PaymentIdentifier,
				t.ServiceSubscriptionID,
				t.OrderStatus,
				t.currency_value
			FROM 
				Transactions AS t
			WHERE 
				t.user_id='".parent::mres($user_id)."'
			ORDER BY 
				t.InvoiceDate 
			DESC");
			
			if($sql->num_rows > 0) {
				while($row = parent::mfa($sql)) {
					if($row['PaymentMethod'] == 'paypal' && empty($row['ServiceSubscriptionID']) && $row['OrderStatus'] == 2) {
						// Hide this since its a placeholder till paypal sub is completed (cron cleanup job if user cancels).
					} else {
						$udata['history'][] = [
							'type'		=>	$this->order_state[$row['OrderStatus']],
							'typeid'	=> 	$row['OrderStatus'],
							'id'		=>	$row['InvoiceNo'],
							'date'		=>	[
								'raw'			=>	strtotime($row['InvoiceDate']),
								'formatted'		=>	$user->format_date(strtotime($row['InvoiceDate']), $user->data['user_dateformat'], false)
							],
							'method'	=>	(($row['PaymentMethod'] == 'paypal') ? 'PayPal' : 'Stripe'),
							'account'	=>	$row['PaymentIdentifier'],
							'currency'	=>	'&#36;',
							'amount'	=>	number_format($row['currency_value'], 2)
						];		
					}
				}
			}
        }

        return $udata;
    }

    private function cancelSubscription(int $uid): boolean {
        global $user;

        $sql = parent::mq("SELECT 
            p.id,
            p.name,
            p.slug,
            p.cost,
            p.currency,
            p.validity,
            p.period,
            t.InvoiceNo,
            t.InvoiceDate,
            t.PaymentMethod,
            t.PaymentIdentifier,
            t.ServiceCustomerID,
            t.ServiceSubscriptionID,
            t.OrderStatus,
            t.currency_value
        FROM 
            Users_Subscriptions AS us
        INNER JOIN
            Pricing AS p
        ON
            p.id=us.plan_id
        INNER JOIN
            Transactions AS t
        ON
            t.id=us.transaction_id
        WHERE 
            us.user_id='".parent::mres($uid)."'");

        if($sql->num_rows > 0) {
            $row = parent::mfa($sql);

            $cpsql = parent::mq("SELECT count(*) AS Total FROM Courses_Progress WHERE user_id='".parent::mres($row['user_id'])."'");
            $crow = parent::mfa($cpsql);

            $tsql = parent::mq("SELECT count(*) AS Total FROM Transactions WHERE user_id='".parent::mres($row['user_id'])."' AND plan_id <> 0");
            $trow = parent::mfa($tsql);

            $usql = parent::mq("SELECT * FROM Users WHERE user_id='".parent::mres($row['user_id'])."'");
            $user_row = parent::mfa($usql);

            $oneMonthOn = strtotime($row['InvoiceDate']." +1 month");

            $now = time();

            $expired = [
                'CancelledOn'   => date("Y-m-d H:i:s", $now),
                'OrderStatus'   => 7
            ];
            
            $mailer = new \SenFramework\Mailer;

            if($row['PaymentMethod'] == 'stripe') {
                \Stripe\Stripe::setApiKey((DEVELOP) ? STRIPE_SECRET_DEV : STRIPE_SECRET);
                \Stripe\Stripe::setClientId((DEVELOP) ? STRIPE_KEY_DEV : STRIPE_KEY);
                \Stripe\Stripe::setApiVersion(STRIPE_API_VERSION);

                // Check to see if we're still within the first 30 days and this is our only ever subscription with no progress
                try {
                    $subscription = \Stripe\Subscription::retrieve($row['ServiceSubscriptionID']);
                } catch(\Exception $e) {
                    throw new \Exception("Failed to obtain users subscription information.", 474, $e);
                    exit;
                }

                if($oneMonthOn >= $now && ($crow['Total'] == 0 && $trow['Total'] == 1)) {
                    $subscription->cancel();

                    try {
                        $subscriptionInfo = \Stripe\Invoice::all([
                            'customer'      => $row['ServiceCustomerID'],
                            'subscription'  => $row['ServiceSubscriptionID']
                        ]);
                    } catch(\Exception $e) {
                        throw new \Exception("Failed to obtain users subscription invoice information.", 484, $e);
                        exit;
                    }

                    if(!empty($subscriptionInfo)) {
                        $charge = $subscriptionInfo->data[0]->charge;

                        if(!empty($charge)) {
                            try {
                                $refund = \Stripe\Refund::create([
                                    'charge' => $charge,
                                ]);
                            } catch(\Exception $e) {
                                throw new \Exception("Failed to create refund for user.", 498, $e);
                                exit;
                            }

                            if(!empty($refund)) {
                                $expired['IsExpired'] = 1;
                                $expired['ExpiredOn'] = date("Y-m-d H:i:s", $now);

                                if($refund->status == 'succeeded') {
                                    $expired['OrderStatus'] = 8;

                                    $replacements = [
                                        '{{ SERVICE }}'         => ucfirst($service),
                                        '{{ STATUS }}'          => 'Refunded',
                                        '{{ USER }}'            => '(UID:'. $user_row['user_id'] .') - '.((!empty($user_row['user_first_name'])) ? $user_row['user_first_name'].' '.$user_row['user_last_name'] : ((!empty($user_row['username'])) ? $user_row['username'] : $user_row['user_email'])),
                                        '{{ EMAIL }}'           => $user_row['user_email'],
                                        '{{ INVOICE_DATE }}'    => $row['InvoiceDate'],
                                        '{{ CANCEL_DATE }}'     => date("Y-m-d H:i:s", $now),
                                        '{{ SUBSCRIPTION_ID }}' => $row['ServiceSubscriptionID'],   
                                        '{{ CUSTOMER_ID }}'     => $row['ServiceCustomerID']
                                    ];
                                } else {
                                    $replacements = [
                                        '{{ SERVICE }}'         => ucfirst($service),
                                        '{{ STATUS }}'          => 'Refund Failed',
                                        '{{ USER }}'            => '(UID:'. $user_row['user_id'] .') - '.((!empty($user_row['user_first_name'])) ? $user_row['user_first_name'].' '.$user_row['user_last_name'] : ((!empty($user_row['username'])) ? $user_row['username'] : $user_row['user_email'])),
                                        '{{ EMAIL }}'           => $user_row['user_email'],
                                        '{{ INVOICE_DATE }}'    => $row['InvoiceDate'],
                                        '{{ CANCEL_DATE }}'     => date("Y-m-d H:i:s", $now),
                                        '{{ SUBSCRIPTION_ID }}' => $row['ServiceSubscriptionID'],   
                                        '{{ CUSTOMER_ID }}'     => $row['ServiceCustomerID']
                                    ];
                                }

                                if(isset($replacements) && !empty($replacements)) {
                                    $subject = 'LVLUP Dojo: User ' . $row['user_id'] . ' - ' . ucfirst($service) . ' Subscription ' . $replacements['{{ STATUS }}'];
        
                                    $mail_data = [
                                        'email' => 'pizza@lvlupdojo.com',
                                        'name'  => 'LVLUP Dojo',
                                    ];
        
                                    $mailer->SendMail('admin_sub_cancel', $subject, $mail_data, $replacements);
                                }
                            }
                        }
                    }

                    // Cancel Users Subscription right away
                    parent::mq("DELETE FROM Users_Subscriptions WHERE user_id='".parent::mres($row['user_id'])."'");
                } else {
                    $subscription->cancel(['at_period_end' => true]);
                }
                
                // Update Transaction record
                parent::mq("UPDATE Transactions SET ".parent::build_array('UPDATE', $expired)." WHERE id='".parent::mres($row['id'])."'");

                return true;
            } else if($row['PaymentMethod'] == 'paypal') {
                if($oneMonthOn >= $now && ($crow['Total'] == 0 && $trow['Total'] == 1)) {
                    $transactions = NULL;

                    try {
                        $transactions = $this->PaypalListSubscriptionTransactions($row['ServiceSubscriptionID']);
                    } catch(\Exception $e) {
                        throw new \Exception("Failed to obtain users subscription transaction information.", 565, $e);
                        exit;
                    }

                    if(!empty($transactions)) {
                        if(strtolower($transactions[0]->getStatus()) === 'completed') {
                            $transactionID = $transactions[0]->getTransactionId();
                            $transactionAmount = $transactions[0]->getAmount();

                            $amt = new \PayPal\Api\Amount();
                            $amt->setTotal($transactionAmount)->setCurrency('USD');

                            $refund = new \PayPal\Api\Refund();
                            $refund->setAmount($amt);

                            $sale = new \PayPal\Api\Sale();
                            $sale->setId($transactionID);

                            $refundedSale = NULL;

                            try {
                                $refundedSale = $sale->refund($refund, $this->paypalContext);

                                $replacements = [
                                    '{{ SERVICE }}'         => ucfirst($service),
                                    '{{ STATUS }}'          => 'Refunded',
                                    '{{ USER }}'            => '(UID:'. $user_row['user_id'] .') - '.((!empty($user_row['user_first_name'])) ? $user_row['user_first_name'].' '.$user_row['user_last_name'] : ((!empty($user_row['username'])) ? $user_row['username'] : $user_row['user_email'])),
                                    '{{ EMAIL }}'           => $user_row['user_email'],
                                    '{{ INVOICE_DATE }}'    => $row['InvoiceDate'],
                                    '{{ CANCEL_DATE }}'     => date("Y-m-d H:i:s", $now),
                                    '{{ SUBSCRIPTION_ID }}' => $row['ServiceSubscriptionID'],   
                                    '{{ CUSTOMER_ID }}'     => $row['ServiceCustomerID']
                                ];
                            } catch (\Exception $ex) {
                                $expired['IsExpired'] = 1;
                                $expired['ExpiredOn'] = date("Y-m-d H:i:s", $now);

                                // Send Mail that refund failed to Admin
                                $replacements = [
                                    '{{ SERVICE }}'         => ucfirst($service),
                                    '{{ STATUS }}'          => 'Refund Failed',
                                    '{{ USER }}'            => '(UID:'. $user_row['user_id'] .') - '.((!empty($user_row['user_first_name'])) ? $user_row['user_first_name'].' '.$user_row['user_last_name'] : ((!empty($user_row['username'])) ? $user_row['username'] : $user_row['user_email'])),
                                    '{{ EMAIL }}'           => $user_row['user_email'],
                                    '{{ INVOICE_DATE }}'    => $row['InvoiceDate'],
                                    '{{ CANCEL_DATE }}'     => date("Y-m-d H:i:s", $now),
                                    '{{ SUBSCRIPTION_ID }}' => $row['ServiceSubscriptionID'],   
                                    '{{ CUSTOMER_ID }}'     => $row['ServiceCustomerID']
                                ];
                            }

                            if(isset($replacements) && !empty($replacements)) {
                                $subject = 'LVLUP Dojo: User ' . $row['user_id'] . ' - ' . ucfirst($service) . ' Subscription ' . $replacements['{{ STATUS }}'];
    
                                $mail_data = [
                                    'email' => 'pizza@lvlupdojo.com',
                                    'name'  => 'LVLUP Dojo',
                                ];
    
                                $mailer->SendMail('admin_sub_cancel', $subject, $mail_data, $replacements);
                            }

                            if(!empty($refundedSale)) {
                                if(strtolower($refundedSale->getState()) === 'completed') {
                                    $expired['OrderStatus'] = 8;
                                }
                            }

                            parent::mq("DELETE FROM Users_Subscriptions WHERE user_id='".parent::mres($row['user_id'])."'");
                        }
                    }
                }

                // Update Transaction record
                parent::mq("UPDATE Transactions SET ".parent::build_array('UPDATE', $expired)." WHERE id='".parent::mres($row['id'])."'");

                // Cancel Subscription
                try {
                    $this->PaypalCancelAgreement($row['ServiceSubscriptionID']);

                    return true;
                } catch (\Exception $ex) {
                    \SenFramework\SenFramework::addLogEntry('Failed cancelling Paypal Subscription: '.$ex->getMessage());

                    return false;
                }					
            } else {
                throw new \Exception('Unknown payment service specified.');
            }
        } else {
            return true;
        }

		return false;
    }

    private function gen_rand_string($num_chars = 8) {
		// [a, z] + [0, 9] = 36
		return substr(strtoupper(base_convert(bin2hex(random_bytes(8)), 16, 36)), 0, $num_chars);
	}
}