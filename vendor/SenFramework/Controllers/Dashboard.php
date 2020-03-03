<?php

namespace SenFramework\Controllers;

use \PayPal\Auth\OAuthTokenCredential;
use \PayPal\Rest\ApiContext;

class Dashboard extends \SenFramework\DB\Database {
	public $data;

	/**
     * Generate a random unique Invoice ID.
     *
     * @return string
     */
    private function createInvoiceID() {
        return strtoupper('IV'.substr(base64_encode(md5(rand())), 0, 6));
    }
	
	public function __construct($route = NULL, $query = NULL)  {
		global $request, $senConfig, $user, $phpbb;
		
		$security = new \SenFramework\Security();
		$billing = new \SenFramework\Billing();
		
		$this->data['addressbarTrack'] = false;
		
		if(!$user->data['is_registered']) {
			header("Location: https://".((DEVELOP) ? 'development.lvlupdojo.com' : 'www.lvlupdojo.com')."/sign-in/");
			exit;
		}
		
		switch($route[1]) {
			default:
			case"courses":
				$offset = intval($route[3]);
						
				if($offset <= 0) {
					$offset = 1;
				}

				$this->data['override']['title'] = 'Courses Library' . (($offset > 1) ? ' &bull; Page ' . $offset : NULL) . ' &bull; Dashboard  ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];

				$Courses = new \SenFramework\Courses;
				$Courses->url = 'dashboard/courses';
				
				if($user->data['subscription']['id'] != 1) {
					$this->data['courses'] = $Courses->getPublishedCourses(NULL, $offset);
					$this->data['pagination'] = $this->data['courses']['pagination'];
					$this->data['meta'] = $this->data['courses']['meta'];
					
					unset($this->data['courses']['pagination'], $this->data['courses']['meta']);
				} else {
					$attributes = NULL;

					$sql = parent::mq("SELECT * FROM Transactions_Items WHERE user_id='".$user->data['user_id']."'");

					if($sql->num_rows > 0) {
						$i = 0;

						while($row = parent::mfa($sql)) {
							$attributes[] = [
								'operator' 	=> (($i == 0) ? 'AND' : 'OR'),
								'method' 	=> 'EQUALS',
								'column'	=> 'Courses.id',
								'value' 	=> $row['course_id']
							];

							if($i == 0) {
								$i = 1;
							}
						}
					}

					if(!empty($attributes)) {
						$this->data['courses'] = $Courses->getPublishedCourses($attributes, $offset);
						$this->data['pagination'] = $this->data['courses']['pagination'];
						$this->data['meta'] = $this->data['courses']['meta'];
						
						unset($this->data['courses']['pagination'], $this->data['courses']['meta']);
					}
				}
			break;

			case"remove":
				$this->data['override']['title'] = 'Delete My Account &bull; Dashboard  ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
				$this->data['template'] = 'gdpr_confirm';

				if($request->is_set_post('confirm')) {
					$uid = $user->data['user_id'];
					
					/* Cancel Any Subscriptions */
					try {
						$billing->cancelSubscription();
					} catch(\Exception $ex) {
						$this->data['error'][] = $ex->getMessage();
					}

					if(!isset($this->data['error'])) {
						/* Destory User System Wide */
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

						// Log User Out
						$user->session_kill();

						// Redirect them to homepage
						header("Location: https://".((DEVELOP) ? 'development.lvlupdojo.com' : 'www.lvlupdojo.com')."/");
						exit;
					}					
				}
			break;
				
			case"username":
				$this->data['override']['title'] = 'Create A Username &bull; Dashboard  ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
				$this->data['template'] = 'username';
				
				if(!empty($user->data['username_clean'])) {
					if($request->is_set_post('username')) {
						$response = new \stdClass();
						
						$this->data['username'] = $request->variable('username', '', true);
						
						if(preg_match('/[\'^£$%&*()}{@#~?><>,.|=+¬-]/', $this->data['username']) || preg_match('/\s/', $this->data['username'])) {
							$this->data['error'][] = 'Username\'s can only contain numbers and letters.';
						}
						
						if(strlen($this->data['username']) < 3) {
							$this->data['error'][] = 'Username\'s must be longer than 3 characters.';
						}
						
						if(strlen($this->data['username']) > 20) {
							$this->data['error'][] = 'Username\'s must be no longer than 20 characters.';
						}
						
						$username_clean = $phpbb->utf8_clean_string($this->data['username']);
						
						$sql = parent::mq("SELECT user_id FROM Users WHERE username_clean='".parent::mres($username_clean)."'");
						
						if($sql->num_rows > 0) {
							$this->data['error'][] = 'Username already exists.';
						}
						
						if(!isset($this->data['error'])) {
							parent::mq("UPDATE Users SET username='".parent::mres($this->data['username'])."', username_clean='".parent::mres($username_clean)."' WHERE user_id='".$user->data['user_id']."'");

							$this->data['success'] = true;

							header("Location: https://".((DEVELOP) ? 'development.lvlupdojo.com' : 'www.lvlupdojo.com').'/dashboard/account/');
							exit;
						}
					}					
				}
			break;
				
			case"billing":
				switch($route[2]) {
					// Show Current Plan and Monthly Payment History
					default:
						$this->data['override']['title'] = 'Billing &bull; Dashboard  ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
						$this->data['template'] = 'billing';
						
						$this->data['plan'] = $user->data['subscription'];
						$this->data['plan']['payment']['history'] = $billing->getTransactions();	
					break;
						
					case"receipt":
						if(!empty($route[3])) {
							$pdf = $billing->getReceipt($route[3]);

							if(!empty($pdf)) {
								exit;
							} else {
								$this->data['triggererror'] = '404';
							}
						} else {
							$this->data['triggererror'] = '404';
						}
					break;
						
					// Option to downgrade to a cheaper plan (Use cancel if they wish to go back to the free plan)
					case"downgrade":
						
					break;
						
					// Upgrade from any package
					case"upgrade":
						$this->data['override']['title'] = 'Upgrade Subscription &bull; Billing &bull; Dashboard  ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
						$this->data['template'] = 'billing_upgrade';

						$this->data['form'] = [
							'selected'		=> 0,
							'discount' 		=> NULL,
							'discountID' 	=> 0,
							'discountPlans' => NULL
						];

						if(isset($_SESSION['Subscription'])) {
							$this->data['form']['selected'] = $_SESSION['Subscription'];
						}

						if(isset($route[3]) && !empty($route[3])) {
							$this->data['form']['selected'] = $route[3];
						} 						

						if(isset($_SESSION['Discount'])) {
							$dsql = parent::mq("SELECT * FROM Pricing_Discounts WHERE code='".parent::mres($_SESSION['Discount'])."' AND (start <= NOW() AND end >= NOW())");

							if($dsql->num_rows > 0) {
								$this->data['form']['discount'] = $_SESSION['Discount'];

								while($drow = parent::mfa($dsql)) {									
									$this->data['form']['discountPlans'][$drow['pricing_id']] = [
										'id' => $drow['id'],
										'percentage' => $drow['percentage']
									];
								}
							} else {
								unset($_SESSION['Discount']);
							}
						}
						
						$sql = parent::mq("SELECT * FROM Pricing_Options ORDER BY id ASC");

						if(!empty($sql) && $sql->num_rows > 0) {
							while($row = parent::mfa($sql)) {
								$this->data['pricingOptions'][$row['id']] = $row;	

								unset($this->data['pricingOptions'][$row['id']]['pricing_ids']);

								$plans = explode(',', $row['pricing_ids']);

								foreach($plans as $key => $value) {
									$this->data['pricingOptions'][$row['id']]['plans'][$value] = 1;
								}

								unset($plans);
							}
						}
						
						$sql = parent::mq("SELECT * FROM Pricing WHERE id <> 1 AND active='1' AND published='1' ORDER BY OrderNo ASC");
						
						if(!empty($sql) && $sql->num_rows > 0) {
							while($row = parent::mfa($sql)) {
								$this->data['pricing'][$row['id']] = $row;

								if($this->data['form']['selected'] !== 0) {
									if($this->data['form']['selected'] == $row['id']) {
										$this->data['form']['selected'] = (int)$row['id'];
									} else if(is_string($this->data['form']['selected']) && $this->data['form']['selected'] == $row['slug']) {
										$this->data['form']['selected'] = (int)$row['id'];
									}
								}

								if(!empty($this->data['form']['discountPlans']) && array_key_exists($row['id'], $this->data['form']['discountPlans'])) {
									$this->data['pricing'][$row['id']]['cost'] = number_format($row['cost'] - ($row['cost'] * ($this->data['form']['discountPlans'][$row['id']]['percentage'] / 100)), 2);
								}
							}

							if(!is_int($this->data['form']['selected'])) {
								$this->data['form']['selected'] = 0;
							}
						}

						if(isset($query['token']) && isset($query['action']) && $query['action'] == 'cancel') {
							parent::mq("DELETE FROM Transactions WHERE user_id='".parent::mres($user->data['user_id'])."' AND OrderStatus='2' AND ServiceCustomerID=NULL AND ServiceSubscriptionID=NULL");
						}

						if($request->is_set_post('paymentPlan')) {
							$this->data['form'] = [
								'selected'		=> (int)$request->variable('paymentPlan', '0'),
								'discountID' 	=> (int)$request->variable('couponID', '0'),
								'method'    	=> $request->variable('paymentMethod', ''),
								'token'     	=> $request->variable('paymentToken', ''),
								'email'     	=> $request->variable('paymentEmail', '')
							];

							$this->data['error'] = NULL;

							if($this->data['form']['selected'] != 0) {
								if(!empty($this->data['form']['discountID'])) {
									$psql = parent::mq("SELECT * FROM Pricing INNER JOIN Pricing_Discounts ON Pricing_Discounts.pricing_id=Pricing.id WHERE Pricing_Discounts.id='".parent::mres($this->data['form']['discountID'])."' AND Pricing.id='".parent::mres($this->data['form']['selected'])."'");
								} else {
									$psql = parent::mq("SELECT * FROM Pricing WHERE id='".parent::mres($this->data['form']['selected'])."'");
								}

								if($psql->num_rows > 0) {
									$prow = parent::mfa($psql);

									if(!empty($this->data['form']['discountID'])) {
										$cost = number_format($prow['cost'] - ($prow['cost'] * ($prow['percentage'] / 100)), 2);
									} else {
										$cost = floatval($prow['cost']);
									}

									$PlanID = str_replace(['.', ' '], '-', $prow['name']."-".round($cost, 2));

									if($this->data['form']['method'] === 'stripe') {
										\Stripe\Stripe::setApiKey((DEVELOP) ? STRIPE_SECRET_DEV : STRIPE_SECRET);
										\Stripe\Stripe::setClientId((DEVELOP) ? STRIPE_KEY_DEV : STRIPE_KEY);
										\Stripe\Stripe::setApiVersion(STRIPE_API_VERSION);

										$plan = $customer = $subscription = $charge = NULL;

										try {
											$plan = \Stripe\Plan::retrieve($PlanID);
											$plan = $plan->id;
										} catch(\Stripe\Error\InvalidRequest $e) {
											try{
												$plan = \Stripe\Plan::create(array(
													"amount" => 100 * $cost,
													"trial_period_days" => 0,
													"interval" => strtolower($prow['period']),
													"product" => array(
														"name" 	=> $PlanID,
														'type'	=> 'service',
														'metadata'	=> [
															'plan_id'				=> $this->data['form']['selected'],
															'discount_id'			=> (!empty($this->data['form']['discountID'])) ? $this->data['form']['discountID'] : 0,
															'discount_percentage'	=> $prow['percentage'],
															'original_value'		=> $prow['cost']
														]
													),
													"currency" => "usd",
													"id" => $PlanID
												));

												$plan = $plan->id;
											} catch(\Stripe\Error\InvalidRequest $e) {
												\SenFramework\SenFramework::addLogEntry($e->getMessage());

												$this->data['error'][] = 'An error occured during subscription processing.';
											}
										}

										if(empty($this->data['error'])) {
											try {
												$customers = \Stripe\Customer::all(['email' => $user->data['user_email']]);

												foreach($customers->data as $key => $cusdata) {
													if($user->data['email'] == $cusdata->email) {
														$customer = $cusdata->id;
													}
												}

												if(empty($customer)) {
													throw new \Stripe\Error\InvalidRequest('No Customer Found.', null);
												}
											} catch(\Stripe\Error\InvalidRequest $e) {
												$customer = \Stripe\Customer::create(array(
													"description" => "Customer for ".$user->data['user_email'],
													"email"		=> $user->data['user_email'],
													"metadata"	=> [
														"user_id"	=> $user->data['user_id']
													],
													"source" => $this->data['form']['token']
												));

												$customer = $customer->id;
											}

											// Create Subscription
											try {
												$subscription = \Stripe\Subscription::create(array(
													'customer'		=> $customer,
													'tax_percent' 	=> 0,
													'items'			=> [
														[
															'plan'	=> $plan
														]
													],											
												));

												$subscription = $subscription->id;
											} catch(\Stripe\Error\InvalidRequest $e) {
												\SenFramework\SenFramework::addLogEntry($e->getMessage());

												$this->data['error'][] = 'An error occured during subscription charge process.';
											}

											// Create Charge
											if(empty($this->data['error']) && !empty($plan) && !empty($customer) && !empty($subscription)) {
												$transaction = [
													'InvoiceNo'					=> self::createInvoiceID(),
													'user_id'					=> $user->data['user_id'],
													'plan_id'					=> (int)$this->data['form']['selected'],
													'PaymentMethod'				=> 'stripe',
													'ServiceCustomerID'         => $customer,
													'ServiceSubscriptionID'     => $subscription,
													'OrderStatus'				=> 3,
													'currency_code'				=> 'USD',
													'currency_value_original'	=> $prow['cost'],
													'currency_value'			=> $cost,
													'ip'                        => \SenFramework\SenFramework::getIP(),
													'user_agent'                => $request->header('User-Agent')
												];
		
												if(!empty($this->data['form']['discountID'])) {
													$transaction['DiscountCode'] = (int)$this->data['form']['discountID'];
												}
		
												// Webhook will update once it processes the payment.
												parent::mq("INSERT INTO Transactions ".parent::build_array('INSERT', $transaction));

												if(isset($_SESSION['Discount'])) {
													unset($_SESSION['Discount']);
												}
		
												$this->data['result'] = 'success';
											}
										}
									} else {
										$data = [
											'selected' => $this->data['form']['selected'],
											'discountID' => $this->data['form']['discountID']
										];

										$paypalPrepare = NULL;

										try {
											$paypalPrepare = $billing->PaypalPrepareAgreement($data);
										} catch (\Exception $e) {
											$this->data['error'][] = $e->getMessage();
										}

										if(!empty($paypalPrepare)) {
											if(!empty($data['discountID'])) {
												$psql = parent::mq("SELECT * FROM Pricing INNER JOIN Pricing_Discounts ON Pricing_Discounts.pricing_id=Pricing.id WHERE Pricing_Discounts.id='".parent::mres($data['discountID'])."' AND Pricing.id='".parent::mres($data['selected'])."'");
											} else {
												$psql = parent::mq("SELECT * FROM Pricing WHERE id='".parent::mres($data['selected'])."'");
											}
							
											if($psql->num_rows > 0) {
												$prow = parent::mfa($psql);
							
												if(!empty($data['discountID'])) {
													$cost = number_format($prow['cost'] - ($prow['cost'] * ($prow['percentage'] / 100)), 2);
												} else {
													$cost = number_format(floatval($prow['cost']), 2);
												}

												$transaction = [
													'InvoiceNo'					=> self::createInvoiceID(),
													'user_id'					=> $user->data['user_id'],
													'plan_id'					=> (int)$this->data['form']['selected'],
													'PaymentMethod'				=> 'paypal',
													'OrderStatus'				=> 2,
													'currency_code'				=> 'USD',
													'currency_value_original'	=> $prow['cost'],
													'currency_value'			=> $cost,
													'ip'                        => \SenFramework\SenFramework::getIP(),
													'user_agent'                => $request->header('User-Agent')
												];

												if(!empty($this->data['form']['discountID'])) {
													$transaction['DiscountCode'] = (int)$this->data['form']['discountID'];
												}

												parent::mq("INSERT INTO Transactions ".parent::build_array('INSERT', $transaction));
											}

											header("Location: ".$paypalPrepare);
											exit;
										}
									}
								}
							}
						}
					break;
						
					case"success":
						$this->data['override']['title'] = 'Upgrade Subscription &bull; Billing &bull; Dashboard  ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
						$this->data['template'] = 'billing_success';

						$token = isset($query['token']) ? $query['token'] : NULL;

						if(!empty($token)) {
							try {
								$subscriptionInfo = $billing->PaypalExecuteAgreement($token);

								$customerInfo = $subscriptionInfo->getPayer();
								$customerPayerInfo = $customerInfo->getPayerInfo();

								$transaction = [
									'PaymentIdentifier'			=> $customerPayerInfo->getEmail(),
									'ServiceCustomerID'         => $customerPayerInfo->getPayerId(),
									'ServiceSubscriptionID'     => $subscriptionInfo->getId(),
									'OrderStatus'				=> 3,
								];

								parent::mq("UPDATE Transactions SET ".parent::build_array('UPDATE', $transaction)." WHERE user_id='".parent::mres($user->data['user_id'])."' AND OrderStatus='2'");
							} catch(\Exception $e) {
								$errorData = $e->getData();
								$errorMessage = $e->getMessage();

								if(!empty($errorData)) {
									$errorData = json_decode($errorData);

									if(!empty($errorData->message)) {
										$this->data['error'][] = "PayPal Returned with the following error: ".$errorData->message;
									} else {
										$this->data['error'][] = $errorMessage;
									}
								} else {
									$this->data['error'][] = $errorMessage;
								}
							}
						} else {
							$this->data['error'][] = 'Invalid PayPal Token Provided.';
						}						
					break;
						
					// Cancel Recurring payments and put user on the free plan
					case"cancel":
						$this->data['override']['title'] = 'Cancel Subscription &bull; Billing &bull; Dashboard  ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
						$this->data['template'] = 'billing_cancel';
						
						if($request->is_set_post('cancel')) {
							try {
								$cancel = $billing->cancelSubscription();

								if($cancel) {
									$this->data['success'] = true;
								}
							} catch(\Exception $e) {
								$this->data['error'][] = $e->getMessage();
							}
						}
					break;
				}				
			break;
				
			case"account":
				$this->data['override']['title'] = 'Account Management &bull; Dashboard  ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
				$this->data['template'] = 'account';
				
				if ($user->data['user_birthday']) {					
					list($default['bday_day'], $default['bday_month'], $default['bday_year']) = explode('-', date("j-m-Y", strtotime($user->data['user_birthday'])));
				} else {
					list($default['bday_day'], $default['bday_month'], $default['bday_year']) = explode('-', date("j-m-Y"));
				}
				
				$this->data['form'] = [
					'CSRF' 						=> [
						$request->variable('CSRFName', '', true),
						$request->variable('CSRFToken', '', true)
					],
					'error' 					=> [],
					'user_first_name'			=> $request->variable('user-first-name', $user->data['user_first_name'], true),
					'user_last_name'			=> $request->variable('user-last-name', $user->data['user_last_name'], true),
					'user_email'				=> $request->variable('user-email', $user->data['user_email'], true),
					'user_email_valid'			=> true,
					'user_birthday'				=> [
						$request->variable('user-birthday-day', (string)$default['bday_day'], false),
						$request->variable('user-birthday-month', (string)$default['bday_month'], false),
						$request->variable('user-birthday-year', (string)$default['bday_year'], false)
					],
					'user_dateformat'			=> $request->variable('user-dateformat', $user->data['user_dateformat'], true),
					'user_timezone'				=> $request->variable('user-timezone', $user->data['user_timezone'], true),
					'user_from'					=> $request->variable('user-from', $user->data['user_from'], true),
					'user_newsletter'			=> ($request->is_set_post('user-newsletter')) ? 1 : 0
				];
				
				$this->data['options'] = [
					'timezone'  => \SenFramework\SenFramework::Timezones($this->data['form']['user_timezone']),
					'format'	=> \SenFramework\SenFramework::dateFormats($this->data['form']['user_dateformat'])
				];	
				
				$email = strtolower($this->data['form']['user_email']);

				if (!preg_match('/^' . $phpbb->get_preg_expression('email') . '$/i', $email)) {
					$this->data['form']['user_email_valid'] = false;
				}
				
				if((!$request->is_set_post('CSRFName') || !$request->is_set_post('CSRFToken'))) {
					$this->data['form']['CSRF'] = $security->generate_csrf_token('Account');
				}
				
				if($request->is_set_post('CSRFName') && $request->is_set_post('CSRFToken')) {							
					$CSRFValid = false;

					if(!empty($this->data['form']['CSRF'][0]) && !empty($this->data['form']['CSRF'][1])) {
						if($security->validate_csrf_token($this->data['form']['CSRF'][0], $this->data['form']['CSRF'][1], false)) {
							$security->unset_stored_csrf($this->data['form']['CSRF'][0]);

							$CSRFValid = true;

							$this->data['form']['CSRF'] = $security->generate_csrf_token('Account');
						} else {
							$this->data['form']['error'][] = 'CSRF Token Mismatch! Bruteforce attempt logged.';
						}
					} else {
						$this->data['form']['error'][] = 'No CSRF Token Provided! Bruteforce attempt logged.';
					}
					
					if($this->data['form']['user_email_valid'] === false) {
						$this->data['form']['error'][] = 'The Email Address provided is not valid.';
					}
					
					if(empty($this->data['form']['error']) && $CSRFValid) {
						if($email != $user->data['user_email']) {
							parent::mq("UPDATE 
								Newsletter_Subscribers 
							SET 
								EmailAddress='".parent::mres($email)."',
								IsActive='".parent::mres($this->data['form']['user_newsletter'])."'
							WHERE 
								LOWER(EmailAddress)='".parent::mres($user->data['user_email'])."'");
						} else {
							parent::mq("UPDATE 
								Newsletter_Subscribers 
							SET 
								IsActive='".parent::mres($this->data['form']['user_newsletter'])."'
							WHERE 
								LOWER(EmailAddress)='".parent::mres($user->data['user_email'])."'");
						}

						$user->data['user_newsletter'] = $this->data['form']['user_newsletter'];

						$sql_ary = [
							'user_first_name'			=> $this->data['form']['user_first_name'],
							'user_last_name'			=> $this->data['form']['user_last_name'],
							'user_email'				=> $email,
							'user_email_hash'			=> sprintf('%u', crc32(strtolower($email))) . strlen($email),
							'user_birthday'				=> date("Y-m-d H:i:s", mktime(0,0,0,$this->data['form']['user_birthday'][1],$this->data['form']['user_birthday'][0],$this->data['form']['user_birthday'][2])),
							'user_dateformat'			=> $this->data['form']['user_dateformat'],
							'user_timezone'				=> $this->data['form']['user_timezone'],
							'user_from'					=> $this->data['form']['user_from']
						];
						
						parent::mq("UPDATE
								Users
							SET
								".parent::build_array('UPDATE', $sql_ary)."
							WHERE
								user_id='".$user->data['user_id']."'");
						
						$this->data['form']['success'] = true;
					}
				}								
			break;
				
			case"security":
				$this->data['override']['title'] = 'Account Security &bull; Dashboard  ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
				$this->data['template'] = 'security';
				
				/* Password Management */				
				if(!$request->is_set_post('updatePasswordBtn')) {
					$this->data['passwordForm']['CSRF'] = $security->generate_csrf_token('ChangePassword');
				}
				
				if($request->is_set_post('updatePasswordBtn')) {
					$form = [
						'currentPassword' 		=> $request->untrimmed_variable('currentPassword', '', true),
						'newPassword'			=> $request->untrimmed_variable('newPassword', '', true),
						'confirmNewPassword'	=> $request->untrimmed_variable('confirmNewPassword', '', true)
					];
					
					$this->data['passwordForm']['CSRF'] = [
						$request->variable('CSRFName', '', true),
						$request->variable('CSRFToken', '', true)
					];
					
					$CSRFValid = false;

					if(!empty($this->data['passwordForm']['CSRF'][0]) && !empty($this->data['passwordForm']['CSRF'][1])) {
						if($security->validate_csrf_token($this->data['passwordForm']['CSRF'][0], $this->data['passwordForm']['CSRF'][1], false)) {
							$security->unset_stored_csrf($this->data['passwordForm']['CSRF'][0]);

							$CSRFValid = true;

							$this->data['passwordForm']['CSRF'] = $security->generate_csrf_token('ChangePassword');
						} else {
							$this->data['passwordError'][] = 'CSRF Token Mismatch! Bruteforce attempt logged.';
						}
					} else {
						$this->data['passwordError'][] = 'No CSRF Token Provided! Bruteforce attempt logged.';
					}
					
					if($CSRFValid) {					
						if(empty($form['currentPassword'])) {
							$this->data['passwordError'][] = 'You must provide your current password to be able to change your password.';
						} else {
							if(empty($form['newPassword']) || empty($form['confirmNewPassword'])) {
								$this->data['passwordError'][] = 'New Password fields where left empty.';
							} else {
								if(strlen($form['newPassword']) < 6) {
									$this->data['passwordError'][] = 'Your password is too short, passwords must be a minimum of 6 characters long and a max of 100 characters.';
								}

								if(strlen($form['newPassword']) > 100) {
									$this->data['passwordError'][] = 'Your password is too long, passwords must be a minimum of 6 characters long and a max of 100 characters.';
								}

								if($form['newPassword'] != $form['confirmNewPassword']) {
									$this->data['passwordError'][] = 'New Password and Confirm New Password do not match.';
								}

								if(!sizeof($this->data['passwordError'])) {
									if($security->password_verify($form['currentPassword'], $user->data['user_password'])) {
										$newPassword = $security->password_hash($form['newPassword'], PASSWORD_BCRYPT);
										
										if($newPassword == $user->data['user_password']) {
											$this->data['passwordError'][] = 'New Password must be different to your current password.';
										} else {
											parent::mq("UPDATE Users SET user_password='".$newPassword."' WHERE user_id='".$user->data['user_id']."'");
											
											$this->data['passwordSuccess'] = true;
										}
									} else {
										$this->data['passwordError'][] = 'Current Password is incorrect.';
									}
								}
							}
						}
					}
				}
				
				/* TFA */
				
				/* Sessions */
				$this->data['sessions'] = [
					'current' 	=> [],
					'past'		=> []
				];

				$sessionsQuery = parent::mq("SELECT key_id, last_ip, last_login FROM Session_Keys WHERE user_id='".$user->data['user_id']."' ORDER BY last_login DESC");

				if(!empty($sessionsQuery) && $sessionsQuery->num_rows > 0) {
					$i = 0;

					while($row = parent::mfa($sessionsQuery)) {
						if($i == 0) {								
							$this->data['sessions']['current'] = [
								'key_id'		=> $row['key_id'],
								'last_login'	=> $user->format_date($row['last_login']),
								'last_ip'		=> ($row['last_ip'] == '127.0.0.1') ? 'UNKNOWN' : $row['last_ip']
							];

							$i++;
						} else {
							$this->data['sessions']['past'][] = [
								'key_id'		=> $row['key_id'],
								'last_login'	=> $user->format_date($row['last_login']),
								'last_ip'		=> ($row['last_ip'] == '127.0.0.1') ? 'UNKNOWN' : $row['last_ip']
							];
						}
					}
				}					
			break;
			
			case"connections":
				$this->data['override']['title'] = 'Connections Management &bull; Dashboard  ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
				$this->data['template'] = 'connections';

				$fbName = (!empty($user->data['user_first_name'])) ? $user->data['user_first_name'] . ((!empty($user->data['user_last_name'])) ? ' '.$user->data['user_last_name'] : NULL) : $user->data['user_fbid'];

				$this->data['service'] = [
					'twitch' => [
						'status' 	=> SOCIAL_TWITCH,
						'name'		=> ((!empty($user->data['user_tc_name'])) ? $user->data['user_tc_name'] : $user->data['user_tcid'])
					],
					'twitter' => [
						'status'	=> SOCIAL_TWITTER,
						'name'		=> ((!empty($user->data['user_tw_name'])) ? $user->data['user_tw_name'] : $user->data['user_twid'])
					],
					'discord' => [
						'status'	=> SOCIAL_DISCORD,
						'name'		=> ((!empty($user->data['user_ds_name'])) ? $user->data['user_ds_name'] : $user->data['user_dsid'])
					],
					'facebook' => [
						'status'	=> SOCIAL_FACEBOOK,
						'name'		=> $fbName
					]
				];
				
			break;
		}
	}
	
	private function setupProviders() {
		
		
		
	}
}