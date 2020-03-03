<?php

namespace SenFramework\Controllers;

class WebHook extends \SenFramework\DB\Database {

    public $data;
    private $now;

    public function __construct($route = NULL, $query = NULL) {
        global $request;

        $this->now = date("Y-m-d H:i:s");

        $this->data['override']['json'] = true;

        switch($route[1]) {
            default:
                $this->data['response'] = new \StdClass;
                $this->data['response']->result = 'error';
                $this->data['response']->message = 'Unauthorized Access.';
            break;

            case"paypal":
                $ipn = new \SenFramework\PaypalIPN;

                $request->enable_super_globals();

                if(DEVELOP) {
                    $ipn->useSandbox();
                }

                try {
                    $verified = $ipn->verifyIPN();
                } catch (\Exception $e) {
                    \SenFramework\SenFramework::addLogEntry('PayPal Webhook: '.$e->getMessage());
                    exit;
                }

                $request->disable_super_globals();

                if($verified) {
                    $raw_post_data = @file_get_contents('php://input');

                    if(!empty($raw_post_data)) {
                        $raw_post_array = explode('&', $raw_post_data);
                        $payload = array();

                        foreach ($raw_post_array as $keyval) {
                            $keyval = explode('=', $keyval);
                            if (count($keyval) == 2) {
                                // Since we do not want the plus in the datetime string to be encoded to a space, we manually encode it.
                                if ($keyval[0] === 'payment_date') {
                                    if (substr_count($keyval[1], '+') === 1) {
                                        $keyval[1] = str_replace('+', '%2B', $keyval[1]);
                                    }
                                }
                                $payload[$keyval[0]] = urldecode($keyval[1]);
                            }
                        }

                        if(!empty($payload)) {
                            $data = [
                                'status'        => strtolower($payload['payment_status']),
                                'type'          => strtolower($payload['txn_type']),
                                'subscription'  => $payload['recurring_payment_id'],
                                'transaction'   => $payload['txn_id'],
                                'customer'      => $payload['payer_id'],
                                'email'         => $payload['payer_email']
                            ];

                            // Debug
                            //\SenFramework\SenFramework::addLogEntry('Payment Notifcation: '.print_r($payload, true), 'debug', 'info');

                            if($data['type'] == 'recurring_payment') {
                                $this->handleSuccessfulPayment('paypal', $data);
                            } else if($data['type'] == 'recurring_payment_failed' || $data['type'] == 'recurring_payment_expired') {
                                $this->handleFailedPayment('paypal', $data['customer'], $data['subscription']);
                            } else if($data['type'] == 'recurring_payment_profile_cancel') {
                                $this->handleCancel('paypal', $data['customer'], $data['subscription']);
                            }
                        } else {
                            http_response_code(400);
                            exit();
                        }
                    } else {
                        http_response_code(400);
                        exit();
                    }
                }
            break;

            case"stripe":
                \Stripe\Stripe::setApiKey((DEVELOP) ? STRIPE_SECRET_DEV : STRIPE_SECRET);
                \Stripe\Stripe::setClientId((DEVELOP) ? STRIPE_KEY_DEV : STRIPE_KEY);
                \Stripe\Stripe::setApiVersion(STRIPE_API_VERSION);

                $payload = @file_get_contents('php://input');

                if(!empty($payload)) {
                    $payload = json_decode($payload);

                    if($payload->type == 'invoice.payment_succeeded' || $payload->type == 'invoice.payment_failed' || $payload->type == 'customer.subscription.deleted') {
                        try {
                            $event = \Stripe\Event::retrieve($payload->id);
                        } catch(\Stripe\Error\InvalidRequest $e) {
                            http_response_code(400);
                            exit();
                        } catch(\Stripe\Error\Api $e) {
                            http_response_code(400);
                            exit();
                        }

                        if(!empty($event)) {
                            if($event->type == 'invoice.payment_succeeded') {
                                $this->handleSuccessfulPayment('stripe', $event);
                            } else if ($event->type == 'invoice.payment_failed') {
                                $this->handleFailedPayment('stripe', $event->object->customer, $event->object->subscription);
                            } else if ($event->type == 'customer.subscription.deleted') {
                                $this->handleCancel('stripe', $event->data->object->customer, $event->data->object->id);
                            }
                        }
                    }
                }
            break;
        }
    }

    /**
     * Handle Successful Payments Events.
     *
     * @param string $service Payment Service
     * @param string $data Payment Service Event Data
     * @return void
     */
    private function handleSuccessfulPayment(string $service, $data) {
        global $user;

        if(!empty($service) && !empty($data)) {
            $customerID = ($service == 'stripe') ? $data->data->object->customer : $data['customer']; 

            $sql = parent::mq("SELECT * FROM Transactions WHERE PaymentMethod='".parent::mres($service)."' AND ServiceCustomerID='".parent::mres($customerID)."' AND plan_id <> 0 ORDER BY id DESC LIMIT 1");

            if($sql->num_rows > 0) {
                $row = parent::mfa($sql);

                $membership = [
                    'plan_id'           => $row['plan_id'],
                    'user_id'           => $row['user_id'],
                    'transaction_id'    => $row['id']
                ];

                if($service == 'stripe') {
                    if(!empty($data->data->object->charge)) {
                        $charge = \Stripe\Charge::retrieve($data->data->object->charge);

                        $transaction = [
                            'PaymentIdentifier'         => (($charge->source->object == 'card') ? $charge->source->brand . ' Ending ' . $charge->source->last4 : $charge->source->object),
                            'ServiceCustomerID'         => $data->data->object->customer,
                            'ServiceTransactionID'      => $data->data->object->charge,
                            'ServiceSubscriptionID'     => $data->data->object->subscription
                        ];
                    } else {
                        $customer = \Stripe\Customer::retrieve($data->data->object->customer);

                        if(!empty($customer->email)) {
                            $email = $customer->email;
                        } else {
                            $usql = parent::mq("SELECT user_email FROM Users WHERE user_id='".parent::mres($row['user_id'])."'");

                            if($usql->num_rows > 0) {
                                $urow = parent::mfa($usql);

                                $email = $urow['user_email'];
                            } else {
                                $email = NULL;
                            }
                        }

                        $transaction = [
                            'PaymentIdentifier'         => strtolower($email),
                            'ServiceCustomerID'         => $data->data->object->customer,
                            'ServiceTransactionID'      => NULL,
                            'ServiceSubscriptionID'     => $data->data->object->subscription
                        ];
                    }
                } else if($service == 'paypal') {
                    $transaction = [
                        'PaymentIdentifier'         => $data['email'],
                        'ServiceCustomerID'         => $data['customer'],
                        'ServiceTransactionID'      => $data['transaction'],
                        'ServiceSubscriptionID'     => $data['subscription']
                    ];
                }

                if($row['OrderStatus'] == 2 || $row['OrderStatus'] == 3) { // New Order
                    $transaction['OrderStatus'] = 5; 

                    parent::mq("UPDATE Transactions SET ".parent::build_array('UPDATE', $transaction)." WHERE id='".parent::mres($row['id'])."'");
                    parent::mq("INSERT INTO Users_Subscriptions ".parent::build_array('INSERT', $membership)." ON DUPLICATE KEY UPDATE transaction_id='".parent::mres($tid)."', plan_id='".parent::mres($row['plan_id'])."'");
                } else { // Renewal
                    $transaction = array_merge($transaction, [
                        'InvoiceNo'                 => $this->createInvoiceID(),
                        'InvoiceDate'               => $this->now,
                        'plan_id'                   => $row['plan_id'],
                        'user_id'                   => $row['user_id'],
                        'PaymentMethod'             => $service,
                        'DiscountCode'              => $row['DiscountCode'],
                        'currency_code'             => 'USD',
                        'currency_value_original'   => $row['currency_value_original'],
                        'currency_value'            => $row['currency_value'],
                        'OrderStatus'               => 5,
                    ]);

                    if((int)$row['IsExpired'] == 0) {
                        if(empty($row['CancelledOn']) && empty($row['RefundedOn'])) {
                            $transaction['parent_transaction'] = $row['id'];
                        }

                        $expired = [
                            'IsExpired'     => 1,
                            'ExpiredOn'     => $this->now
                        ];

                        parent::mq("UPDATE Transactions SET ".parent::build_array('UPDATE', $expired)."WHERE id='".parent::mres($row['id'])."'");
                    }

                    parent::mq("INSERT INTO Transactions ".parent::build_array('INSERT', $transaction));
                    $tid = parent::lastId();

                    parent::mq("INSERT INTO Users_Subscriptions ".parent::build_array('INSERT', $membership)." ON DUPLICATE KEY UPDATE transaction_id='".parent::mres($tid)."', plan_id='".parent::mres($row['plan_id'])."'"); 
                }

                $mailer = new \SenFramework\Mailer;

                $usql = parent::mq("SELECT username, user_first_name, user_last_name, user_email FROM Users WHERE user_id='".parent::mres($row['user_id'])."'");
                $urow = parent::mfa($usql);

                $psql = parent::mq("SELECT * FROM Pricing WHERE id='".parent::mres($row['plan_id'])."'");
                $prow = parent::mfa($psql);

                $period = ($prow['period'] == 'Year') ? "12" : "1";
				$nextBilling = strtotime($this->now." +".$period." month");

                $replacements = [
                    '{{ INVOICE_ACCOUNT }}'	=> 	'<strong>'.((empty($urow['username'])) ? ((!empty($urow['user_first_name'])) ? $urow['user_first_name'].' '.$urow['user_last_name'] : NULL) : $urow['username']) . '</strong> ('.$urow['user_email'].')',
                    '{{ INVOICE_TO }}'		=>	(($row['PaymentMethod'] == 'paypal') ? '<strong>PayPal Account ('.$transaction['PaymentIdentifier'].')</strong>' : '<strong>'.$transaction['PaymentIdentifier'].'</strong>'),
                    '{{ INVOICE_NUMBER }}'	=>	$transaction['InvoiceNo'],
                    '{{ INVOICE_DATE }}'	=>	$user->format_date(strtotime($this->now), $urow['user_dateformat'], false),
                    '{{ INVOICE_TOTAL }}'	=>	'$'.number_format($row['currency_value'], 2),
                    '{{ INVOICE_ORDERS }}'  =>  $prow['name'],
                    '{{ INVOICE_SERVICE }}' =>  $user->format_date($nextBilling, $urow['user_dateformat'], false),
                    '{{ YEAR }}'            =>  date("Y")
                ];

                $to = [
                    'user_id' => $urow['user_id'],
                    'email' => $urow['user_email'],
                    'name' => ((!empty($urow['user_first_name'])) ? $urow['user_first_name'].' '.$urow['user_last_name'] : ((!empty($urow['username'])) ? $urow['username'] : $urow['user_email']))
                ];

                $mailer->SendMail('subscription', 'LVLUP Dojo: Subscription'.(($row['OrderStatus'] == 2 || $row['OrderStatus'] == 3) ? ' Confirmation' : ' Renewed'), $to, $replacements);
            }
        }
    }

    /**
     * Handle Failed Payments Events.
     *
     * @param string $service Payment Service
     * @param string $customerID Payment Services Unique ID for Customer
     * @param string $subscriptionID Payment Services Unique ID for Subscription
     * @return void
     */
    private function handleFailedPayment(string $service, string $customerID, string $subscriptionID) {
        if(!empty($service) && !empty($customerID) && !empty($subscriptionID)) {
            $sql = parent::mq("SELECT 
                * 
            FROM 
                Transactions 
            WHERE 
                PaymentMethod='".parent::mres($service)."' 
            AND 
                ServiceCustomerID='".parent::mres($customerID)."' 
            AND 
                ServiceSubscriptionID='".parent::mres($subscriptionID)."' 
            ORDER BY 
                id 
            DESC LIMIT 1");

            if($sql->num_rows > 0) {
                $row = parent::mfa($sql);

                $expired = [
                    'IsExpired'     => 1,
                    'ExpiredOn'     => $this->now
                ];

                parent::mq("UPDATE Transactions SET ".parent::build_array('UPDATE', $expired)."WHERE id='".parent::mres($row['id'])."'");
                parent::mq("DELETE FROM Users_Subscriptions WHERE user_id='".parent::mres($row['user_id'])."'");

                $startDate = date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
                $endDate = date("Y-m-d H:i:s", strtotime($startDate." +24 hours"));

                $fsql = parent::mq("SELECT id FROM Transactions WHERE (InvoiceDate >= '".parent::mres($startDate)."' AND InvoiceDate <= '".parent::mres($endDate)."') AND OrderStatus='6' AND plan_id <> 0");

                if($fsql->num_rows == 0) {
                    $transaction = [
                        'InvoiceNo'                 => $this->createInvoiceID(),
                        'InvoiceDate'               => $this->now,
                        'user_id'                   => $row['user_id'],
                        'PaymentMethod'             => $service,
                        'DiscountCode'              => $row['DiscountCode'],
                        'currency_code'             => 'USD',
                        'currency_value_original'   => $row['currency_value_original'],
                        'currency_value'            => $row['currency_value'],
                        'OrderStatus'               => 6,
                        'IsExpired'                 => 1,
                        'ExpiredOn'                 => $this->now
                    ];

                    parent::mq("INSERT INTO Transactions ".parent::build_array('INSERT', $transaction));
                }
            }
        }
    }

    /**
     * Handle Payment Cancellation Events.
     *
     * @param string $service Payment Service
     * @param string $customerID Payment Services Unique ID for Customer
     * @param string $subscriptionID Payment Services Unique ID for Subscription
     * @return void
     */
    private function handleCancel(string $service, string $customerID, string $subscriptionID) {
        if(!empty($service) && !empty($customerID) && !empty($subscriptionID)) {
            $sql = parent::mq("SELECT 
                * 
            FROM 
                Transactions 
            WHERE 
                PaymentMethod='".parent::mres($service)."' 
            AND 
                ServiceCustomerID='".parent::mres($customerID)."' 
            AND 
                ServiceSubscriptionID='".parent::mres($subscriptionID)."' 
            AND 
                OrderStatus='5'
            ORDER BY 
                id 
            DESC LIMIT 1");

            if($sql->num_rows > 0) {
                $row = parent::mfa($sql);

                $cpsql = parent::mq("SELECT count(*) AS Total FROM Courses_Progress WHERE user_id='".parent::mres($row['user_id'])."'");
				$crow = parent::mfa($cpsql);

				$tsql = parent::mq("SELECT count(*) AS Total FROM Transactions WHERE user_id='".parent::mres($row['user_id'])."' AND plan_id <> 0");
				$trow = parent::mfa($tsql);

				$usql = parent::mq("SELECT * FROM Users WHERE user_id='".parent::mres($row['user_id'])."'");
				$user_row = parent::mfa($usql);

				$oneMonthOn = strtotime($row['InvoiceDate']." +1 month");

                $expired = [
                    'CancelledOn'   => $this->now,
                    'OrderStatus'   => 7
				];

                $mailer = new \SenFramework\Mailer;

				if($row['PaymentMethod'] == 'stripe') {
					\Stripe\Stripe::setApiKey((DEVELOP) ? STRIPE_SECRET_DEV : STRIPE_SECRET);
					\Stripe\Stripe::setClientId((DEVELOP) ? STRIPE_KEY_DEV : STRIPE_KEY);
					\Stripe\Stripe::setApiVersion(STRIPE_API_VERSION);

					// Check to see if we're still within the first 30 days and this is our only ever subscription with no progress
					if($oneMonthOn >= $this->now && ($crow['Total'] == 0 && $trow['Total'] == 1)) {
                        try {
							$subscriptionInfo = \Stripe\Invoice::all([
                                'customer'      => $row['ServiceCustomerID'],
                                'subscription'  => $row['ServiceSubscriptionID']
							]);
						} catch(\Exception $e) {
                            \SenFramework\SenFramework::addLogEntry('Stripe Cancelation: Failed to obtain Customer {'.$row['ServiceCustomerID'].'} subscription {'.$row['ServiceSubscriptionID'].'} invoice information.', 'debug', 'info');
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
									\SenFramework\SenFramework::addLogEntry('Stripe failed to create refund for Customer {'.$row['ServiceCustomerID'].'} subscription {'.$row['ServiceSubscriptionID'].'} invoice information.', 'debug', 'info');
								}

								if(!empty($refund)) {
									$expired['IsExpired'] = 1;
									$expired['ExpiredOn'] = date("Y-m-d H:i:s", $this->now);

									if($refund->status == 'succeeded') {
										$expired['OrderStatus'] = 8;

                                        $replacements = [
                                            '{{ SERVICE }}'         => ucfirst($service),
                                            '{{ STATUS }}'          => 'Refunded',
                                            '{{ USER }}'            => '(UID:'. $user_row['user_id'] .') - '.((!empty($user_row['user_first_name'])) ? $user_row['user_first_name'].' '.$user_row['user_last_name'] : ((!empty($user_row['username'])) ? $user_row['username'] : $user_row['user_email'])),
                                            '{{ EMAIL }}'           => $user_row['user_email'],
                                            '{{ INVOICE_DATE }}'    => $row['InvoiceDate'],
                                            '{{ CANCEL_DATE }}'     => date("Y-m-d H:i:s", $this->now),
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
                                            '{{ CANCEL_DATE }}'     => date("Y-m-d H:i:s", $this->now),
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

                            // Cancel Users Subscription right away
                            parent::mq("DELETE FROM Users_Subscriptions WHERE user_id='".parent::mres($row['user_id'])."'");
                        } 
                    }

                    // Update Transaction record
					parent::mq("UPDATE Transactions SET ".parent::build_array('UPDATE', $expired)." WHERE id='".parent::mres($row['id'])."'");
                } else if($row['PaymentMethod'] == 'paypal') {
					if($oneMonthOn >= $this->now && ($crow['Total'] == 0 && $trow['Total'] == 1)) {
                        $transactions = NULL;
                        
                        $billing = new \SenFramework\Billing;

						try {
							$transactions = $billing->PaypalListSubscriptionTransactions($row['ServiceSubscriptionID']);
						} catch(\Exception $e) {
                            \SenFramework\SenFramework::addLogEntry('Paypal Failed to create refund for Customer {'.$row['ServiceCustomerID'].'} subscription {'.$row['ServiceSubscriptionID'].'} invoice information.', 'debug', 'info');
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
									$refundedSale = $sale->refund($refund, $billing->getPaypalContext());

									$replacements = [
										'{{ SERVICE }}'         => ucfirst($service),
										'{{ STATUS }}'          => 'Refunded',
										'{{ USER }}'            => '(UID:'. $user_row['user_id'] .') - '.((!empty($user_row['user_first_name'])) ? $user_row['user_first_name'].' '.$user_row['user_last_name'] : ((!empty($user_row['username'])) ? $user_row['username'] : $user_row['user_email'])),
										'{{ EMAIL }}'           => $user_row['user_email'],
										'{{ INVOICE_DATE }}'    => $row['InvoiceDate'],
										'{{ CANCEL_DATE }}'     => date("Y-m-d H:i:s", $this->now),
										'{{ SUBSCRIPTION_ID }}' => $row['ServiceSubscriptionID'],   
										'{{ CUSTOMER_ID }}'     => $row['ServiceCustomerID']
									];
								} catch (\Exception $ex) {
									$expired['IsExpired'] = 1;
									$expired['ExpiredOn'] = date("Y-m-d H:i:s", $this->now);

									// Send Mail that refund failed to Admin
									$replacements = [
										'{{ SERVICE }}'         => ucfirst($service),
										'{{ STATUS }}'          => 'Refund Failed',
										'{{ USER }}'            => '(UID:'. $user_row['user_id'] .') - '.((!empty($user_row['user_first_name'])) ? $user_row['user_first_name'].' '.$user_row['user_last_name'] : ((!empty($user_row['username'])) ? $user_row['username'] : $user_row['user_email'])),
										'{{ EMAIL }}'           => $user_row['user_email'],
										'{{ INVOICE_DATE }}'    => $row['InvoiceDate'],
										'{{ CANCEL_DATE }}'     => date("Y-m-d H:i:s", $this->now),
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
                }
            }
        }
    }

    /**
     * Generate a random unique Invoice ID.
     *
     * @return string
     */
    private function createInvoiceID() {
        return strtoupper('IV'.substr(base64_encode(md5(rand())), 0, 6));
    }
}