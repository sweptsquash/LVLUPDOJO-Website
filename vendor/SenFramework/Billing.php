<?php

namespace SenFramework;

class Billing extends \SenFramework\DB\Database {
	
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

	private $paypalContext;

	public $currency = [
		'USD'	=> '&dollar;'
	];

	public function PaypalConfig() {
		$apiContext = new \PayPal\Rest\ApiContext(
			new \PayPal\Auth\OAuthTokenCredential(
				(DEVELOP) ? PAYPAL_KEY_DEV : PAYPAL_KEY,
				(DEVELOP) ? PAYPAL_SECRET_DEV : PAYPAL_SECRET
			)
		);
	
		$apiContext->setConfig(
			array(
				'mode' => (DEVELOP) ? 'sandbox' : 'live',
				'log.LogEnabled' => true,
				'log.FileName' => ABSPATH.'logs/' . ((DEVELOP) ? 'PayPal_Sandbox.log' : 'PayPal_Production.log'),
				'log.LogLevel' => (DEVELOP) ? 'DEBUG' : 'INFO',
				'cache.enabled' => true,
				'cache.FileName' => ABSPATH.((DEVELOP) ? '../../PaypalSandboxCache' : '../PaypalCache')
			)
		);

		return $apiContext;
	}

	public function getPaypalContext() {
		return $this->paypalContext;
	}

	public function PaypalFindPlan(string $planID) {
		if(!empty($this->paypalContext)) {
			$plans = NULL;
			$plan = new \PayPal\Api\Plan;
			$params = [
				'page_size' 		=> 20,
				'status'			=> 'ACTIVE',
				'page'				=> 0,				
				'total_required' 	=> 'yes'
			];

			$planActive = true;
			$planFound = false;
			$planPID = NULL;

			try {
				$plans = $plan->all($params, $this->paypalContext);

				if(!empty($plans)) {
					$planList = $plans->getPlans();

					foreach($planList as $key => $planData) {
						if(strtolower($planData->getName()) === strtolower($planID)) {
							$planPID = $planData->getId();
							$planFound = true;

							if(strtolower($planData->getState()) !== 'active') {
								$planActive = false;
							}

							break;
						}
					}

					if(!$planFound) {
						if($plans->getTotalPages() > 1) {
							for($i = 1; $i < $plans->getTotalPages(); $i++) {
								$params['page'] = $i;

								try {
									$planRequest = $plan->all($params, $this->paypalContext);

									if($planRequest != NULL) {
										$planRequestList = $planRequest->getPlans();

										foreach($planRequestList as $key => $planData) {
											if(strtolower($planData->getName()) === strtolower($planID)) {
												$planPID = $planData->getId();
												$planFound = true;

												if(strtolower($planData->getState()) !== 'active') {
													$planActive = false;
												}
						
												break;
											}
										}
									}

									unset($planRequest);
								} catch(\Exception $e) {}
							}
						}
					}

					unset($plans, $plan);

					if(!$planActive) {
						$this->PaypalActivatePlan($planPID);
					}
				}
			} catch (\Exception $e) {
				\SenFramework\SenFramework::addLogEntry('PayPal Plan List: '.$e->getMessage());
			}
		} else {
			throw new \Exception("Missing Paypal Configuration");
		}

		return $planPID;
	}

	private function PaypalCreatePlan(array $data): string {
		if(!empty($data)) {
			if(!empty($this->paypalContext)) {
				$plan = new \PayPal\Api\Plan;

				$plan->setName($data['name'])
					->setDescription($data['description'])
					->setType('INFINITE');

				$planPaymentDefinition = new \PayPal\Api\PaymentDefinition;

				$planPaymentDefinition->setName($data['name'].'-Regular')
					->setType('REGULAR')
					->setCycles("0")
					->setFrequency(strtoupper($data['period']))
					->setFrequencyInterval(($data['period'] == 'Month') ? '1' : '12')
					->setAmount(new \PayPal\Api\Currency(array('value' => $data['amount'], 'currency' => 'USD')));

				$planMerchantDefinition = new \PayPal\Api\MerchantPreferences;

				$planMerchantDefinition->setReturnUrl("https://".((DEVELOP) ? "development.lvlupdojo.com" : "www.lvlupdojo.com")."/dashboard/billing/success/")
					->setCancelUrl("https://".((DEVELOP) ? "development.lvlupdojo.com" : "www.lvlupdojo.com")."/dashboard/billing/upgrade/?action=cancel")
					->setAutoBillAmount("yes")
					->setInitialFailAmountAction("CONTINUE")
					->setMaxFailAttempts("0")
					->setSetupFee(new \PayPal\Api\Currency(array('value' => 0, 'currency' => 'USD')));

				$plan->setPaymentDefinitions(array($planPaymentDefinition));
				$plan->setMerchantPreferences($planMerchantDefinition);
				
				$output = $plan->create($this->paypalContext);

				if(!empty($output)) {
					$this->PaypalActivatePlan($output->getId());

					return $output->getId();
				}
			} else {
				throw new \Exception("Missing Paypal Configuration");
			}
		} else {
			throw new \Exception("No Plan Definition Passed to PaypalCreatePlan");
		}
	}

	private function PaypalActivatePlan(string $planID): void {
		if(!empty($planID)) {
			if(!empty($this->paypalContext)) {
				$plan = new \PayPal\Api\Plan;
				$plan = $plan->get($planID, $this->paypalContext);

				$planPatch = new \PayPal\Api\Patch;
				$planPatchRequest = new \PayPal\Api\PatchRequest;
				$planModel = new \PayPal\Common\PayPalModel('{"state":"ACTIVE"}');

				$planPatch->setOp('replace')->setPath('/')->setValue($planModel);

				$planPatchRequest->addPatch($planPatch);

				$plan->update($planPatchRequest, $this->paypalContext);
			} else {
				throw new \Exception("Missing Paypal Configuration");
			}
		} else {
			throw new \Exception("No Plan ID Passed to PaypalActivatePlan");
		}
	}

	public function PaypalPrepareAgreement(array $data) {
		if(!empty($data)) {
			if($data['selected'] !== 0) {
				$this->paypalContext = $this->PaypalConfig();

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
						$cost = floatval($prow['cost']);
					}

					$PlanID = str_replace(['.', ' '], '-', $prow['name']."-".round($cost, 2));

					$planPID = $this->PaypalFindPlan($PlanID);

					// No Play Found, Create One.
					if(empty($planPID)) {
						$definition = [
							'name' 			=> $PlanID,
							'amount'		=> $cost,
							'period'		=> $prow['period'],
							'description'	=> 'Recurring payment plan to purchase '.$prow['name'].' for USD '.$cost
						];

						if(!empty($data['discountID'])) {
							$definition['discount']	= $prow['percentage'];
							$definition['original_amount'] = $prow['cost'];
						}

						$planPID = $this->PaypalCreatePlan($definition);
					}

					if(!empty($planPID)) {
						$plan = new \PayPal\Api\Plan;
						$planAgreement = new \PayPal\Api\Agreement;
						$customer = new \PayPal\Api\Payer;

						$start_time = new \DateTime();
						$start_time->modify('+5 minutes');
						
						$planAgreement->setName($PlanID)
							->setDescription('Recurring payment plan to purchase '.$prow['name'].' for USD '.$cost)
							->setStartDate($start_time->format('Y-m-d\TH:i:s\Z'));
						
						$plan->setId($planPID);
						$planAgreement->setPlan($plan);
						
						$customer->setPaymentMethod('paypal');
						$planAgreement->setPayer($customer);

						try {
							$planAgreement->create($this->paypalContext);	

							return $planAgreement->getApprovalLink();
						} catch(\Exception $ex) {
							throw new \Exception("An unexpected error occured during the creation of the billing agreement.");
						}						
					} else {
						throw new \Exception("No Plan Information Passed.");
					}
				} else {
					throw new \Exception("No Pricing Option Found.");
				}
			} else {
				throw new \Exception("No Plan Selected.");
			}
		} else {
			throw new \Exception("No data passed to PaypalPrepareAgreement.");
		}
	}

	public function PaypalExecuteAgreement(string $token) {
		if(!empty($token)) {
			if(empty($this->paypalContext)) {
				$this->paypalContext = $this->PaypalConfig();
			}

			$planAgreement = new \PayPal\Api\Agreement;
			$planAgreement->execute($token, $this->paypalContext);

			$executedAgreement = new \PayPal\Api\Agreement;
			$executedAgreement = $executedAgreement->get($planAgreement->getId(), $this->paypalContext);

			return $executedAgreement;
		} else {
			throw new \Exception("No Token passed to PaypalExecuteAgreement.");
		}
	}

	public function PaypalCancelAgreement(string $subscriptionID) {
		if(!empty($subscriptionID)) {
			if(empty($this->paypalContext)) {
				$this->paypalContext = $this->PaypalConfig();
			}

			$agreement = new \PayPal\Api\Agreement;

			$agreementStateDescriptor = new \PayPal\Api\AgreementStateDescriptor();
			$agreementStateDescriptor->setNote("User Cancelled The Agreement.");

			$subscription = null;

			$subscription = $agreement->get($subscriptionID, $this->paypalContext);
			$subscription->cancel($agreementStateDescriptor, $this->paypalContext);
		} else {
			throw new \Exception("No Subscription ID Provided to PaypalCancelAgreement.");
		}
	}

	public function PaypalListSubscriptionTransactions(string $subscriptionID) {
		if(!empty($subscriptionID)) {
			if(empty($this->paypalContext)) {
				$this->paypalContext = $this->PaypalConfig();
			}

			$agreementTransactions = new \PayPal\Api\Agreement;
			$transactions = $agreementTransactions->searchTransactions($subscriptionID, ['start_date' => date('Y-m-d', strtotime('-2 years')), 'end_date' => date('Y-m-d', strtotime('+5 days'))], $this->paypalContext);

			if(!empty($transactions)) {
				$transactionsList = array_reverse($transactions->getAgreementTransactionList());

				return $transactionsList;
			}
		} else {
			throw new \Exception("No Subscription ID Provided to PaypalListSubscriptionTransactions.");
		}
	}

	private function PaypalGetPayment(string $transactionID) {



	}

	private function createInvoiceID() {
        return strtoupper('IV'.substr(base64_encode(md5(rand())), 0, 6));
    }
	
	public function getDowngradeOption(int $currentPlan = 1) {
		global $user;
		
		$downgrade = 'free';
		
		if($user->data['user_id'] !== 1) {
			if($currentPlan !== 0 && $currentPlan !== 1) {
				$lowerPlan = ($currentPlan - 1);
				
				$sql = parent::mq("SELECT name FROM Pricing WHERE id='".parent::mres($lowerPlan)."'");
				
				if($sql->num_rows > 0) {
					$row = parent::mfa($sql);
					
					$downgrade = $row['name'];
				}
			}
		}
		
		return $downgrade;
	}

	public function cancelSubscription() {
		global $user;

		if($user->data['user_id'] !== 1) {
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
				us.user_id='".$user->data['user_id']."'");

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
						throw new \Exception("Failed to obtain users subscription information.", 390, $e);
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
							throw new \Exception("Failed to obtain users subscription invoice information.", 400, $e);
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
									throw new \Exception("Failed to create refund for user.", 414, $e);
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
							throw new \Exception("Failed to obtain users subscription transaction information.", 400, $e);
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
				throw new \Exception('Failed to retrieve users subscription information.');
			}
		} else {
			throw new \Exception('Unauthorized access.');
		}

		return false;
	}
	
	public function getTransactions() { 
		global $user;
		
		$transactions = [];
		
		if($user->data['user_id'] !== 1) {
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
				t.user_id='".$user->data['user_id']."'
			ORDER BY 
				t.InvoiceDate 
			DESC");
			
			if($sql->num_rows > 0) {
				while($row = parent::mfa($sql)) {
					if($row['PaymentMethod'] == 'paypal' && empty($row['ServiceSubscriptionID']) && $row['OrderStatus'] == 2) {
						// Hide this since its a placeholder till paypal sub is completed (cron cleanup job if user cancels).
					} else {
						$transactions[] = [
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
		
		return $transactions;
	}
	
	public function getReceipt(string $transactionID = NULL) {
		global $request, $user;
		
		if($user->data['user_id'] !== 1) {
			if(!empty($transactionID)) {
				$sql = parent::mq("SELECT * FROM Transactions WHERE InvoiceNo='".parent::mres(strtoupper($transactionID))."' AND user_id='".$user->data['user_id']."'");

				if($sql->num_rows > 0) {
					$row = parent::mfa($sql);

					$usql = parent::mq("SELECT username, user_first_name, user_last_name, user_email, user_dateformat FROM Users WHERE user_id='".$row['user_id']."'");
					$urow = parent::mfa($usql);

					$request->enable_super_globals();
					
					$pdf_options = new \Dompdf\Options();
					$pdf_options->set('tempDir', PDFTEMP);
					$pdf_options->set('isRemoteEnabled', true);
					$pdf_options->set('isHtml5ParserEnabled', true);
					$pdf_options->set('defaultMediaType', 'screen');
					$pdf_options->set('dpi', 300);

					$pdf = new \Dompdf\Dompdf($pdf_options);

					$pdf->setPaper('A4', 'portrait');

					$html = file_get_contents(ABSPATH.'theme/pdf/'.((empty($row['plan_id'])) ? 'invoice' : 'subscription').'.html');

					$replacements = [
						'{{ INVOICE_ACCOUNT }}'	=> 	'<strong>'.((empty($urow['username'])) ? ((!empty($urow['user_first_name'])) ? $urow['user_first_name'].' '.$urow['user_last_name'] : NULL) : $urow['username']) . '</strong> ('.$urow['user_email'].')',
						'{{ INVOICE_TO }}'		=>	(($row['PaymentMethod'] == 'paypal') ? '<strong>PayPal Account ('.$row['PaymentIdentifier'].')</strong>' : '<strong>'.$row['PaymentIdentifier'].'</strong>'),
						'{{ INVOICE_NUMBER }}'	=>	$row['InvoiceNo'],
						'{{ INVOICE_DATE }}'	=>	$user->format_date(strtotime($row['InvoiceDate']), $urow['user_dateformat'], false),
						'{{ INVOICE_ORDERS }}'	=>	NULL,
						'{{ INVOICE_TOTAL }}'	=>	'$'.number_format($row['currency_value'], 2)						
					];

					if(empty($row['plan_id'])) {
						$tsql = parent::mq("SELECT 
							Courses.name,
							Courses.slug,
							Courses.thumbnail,
							Courses_Pricing.price,
							Transactions_Items.original_value,
							Transactions_Items.value
						FROM 
							Transactions_Items 
						INNER JOIN 
							Courses_Pricing 
						ON 
							Courses_Pricing.course_id=Transactions_Items.course_id 
						INNER JOIN 
							Courses
						ON
							Courses.id=Transactions_Items.course_id
						WHERE 
							Transactions_Items.transaction_id='".$row['id']."' 
						AND 
							Transactions_Items.user_id='".$user->data['user_id']."'");

						if($tsql->num_rows > 0) {
							$items = '';

							while($trow = parent::mfa($tsql)) {
								if($trow['value'] < $trow['original_value']) {
									$price = '<small><s>$'.number_format($trow['original_value'], 2).'</s></small><br /><strong>$'.number_format($trow['value'], 2).'</strong>';
								} else {
									$price = '<strong>$'.number_format($trow['value'], 2).'</strong>';
								}

								$items .= '<tr><td class="text-center"><img src="http://www.lvlupdojo.com' .$trow['thumbnail'] . '" width="320" height="" alt="" /></td><td><a href="https://www.lvlupdojo.com/courses/' . $trow['slug']. '/">' . $trow['name'] . '</a></td><td class="text-center">'.$price.'</td></tr>';
							}

							$replacements['{{ INVOICE_ORDERS }}'] = $items;
						}
					} else {
						$psql = parent::mq("SELECT name, period FROM Pricing WHERE id='".$row['plan_id']."'");
						$prow = parent::mfa($psql);

						$replacements['{{ INVOICE_ORDERS }}'] = $prow['name'];

						$period = ($prow['period'] == 'Year') ? "12" : "1";
						$nextBilling = strtotime($row['InvoiceDate']." +".$period." month");						

						$replacements['{{ INVOICE_SERVICE }}'] = $user->format_date($nextBilling, $urow['user_dateformat'], false);
					}					

					$time = substr_replace(date('YmdHisO'), '\'', (0 - 2), 0).'\'';

					$pdf->add_info('Subject', 'Invoice: '.$row['InvoiceNo']);
					$pdf->add_info('Creator', 'domPDF');
					$pdf->add_info('Author', 'LVLUP Dojo');
					$pdf->add_info('Title', 'Invoice: '.$row['InvoiceNo']);
					$pdf->add_info('Keywords', 'Invoice, LVLUP Dojo, LVLUP Dojo Invoice, Invoice '.$row['InvoiceNo'].', '.$row['InvoiceNo']);
					$pdf->add_info('CreationDate', 'D:'.$time);
					$pdf->add_info('ModDate', 'D:'.$time);
					
					$pdf->loadHtml(str_replace(array_keys($replacements), $replacements, $html));	
					$pdf->render();
					
					$request->disable_super_globals();

					return $pdf->stream('lvlupdojo-invoice-'.$row['InvoiceNo'].'-'.date("Y-m-d", strtotime($row['InvoiceDate'])).'.pdf');
				}
			}
		}
		
		return false;
	}
	
	public function subscriptionStatus(int $user_id = 1, string $user_dateformat) {
		global $user;
		
		$plan = [
			'id'	=> 1,
			'name'	=> 'Free Students',
			'slug'	=> 'free'
		];
		
		if($user_id !== 1) {
			$sql = parent::mq("SELECT 
				p.id,
				p.name,
				p.slug,
				p.cost,
				p.currency,
				p.validity,
				p.period
			FROM 
				Users_Subscriptions AS us
			INNER JOIN
				Pricing AS p
			ON
				p.id=us.plan_id
			WHERE 
				us.user_id='".parent::mres($user_id)."'");

			if($sql->num_rows > 0) {
				$row = parent::mfa($sql);

				if($row['id'] != 4) {
					$rsql = parent::mq("SELECT
						t.InvoiceDate,
						t.PaymentMethod,
						t.PaymentIdentifier,
						t.currency_value,
						t.CancelledOn
					FROM 
						Transactions AS t
					WHERE 
						t.user_id='".parent::mres($user_id)."'
					AND
						t.OrderStatus='5' ORDER BY t.InvoiceDate DESC LIMIT 1");

					if($rsql->num_rows > 0) {
						$rrow = parent::mfa($rsql);

						$period = intval($row['validity']) * (($row['period'] == 'Year') ? 12 : 1);
						$nextBilling = strtotime($rrow['InvoiceDate']." +".$period." month");
						
						$plan = [
							'id'		=>	intval($row['id']),
							'name'		=>	$row['name'],
							'slug'		=>	$row['slug'],
							'payment'	=> 	[
								'method'		=> 	(($rrow['PaymentMethod'] == 'paypal') ? 'PayPal' : 'Stripe'),
								'identity'		=> 	$rrow['PaymentIdentifier'],
								'next_billing'	=> 	$this->format_date($nextBilling, $user_dateformat, false),
								'currency'		=>	'&#36;',
								'amount'		=> 	number_format($rrow['currency_value'], 2)
							],
							'downgrade' =>	self::getDowngradeOption($row['id'])
						];

						if(!empty($rrow['CancelledOn'])) {
							$plan['cancelled'] = true;
						}
					}
				} else {
					$plan = [
						'id'		=>	$row['id'],
						'name'		=>	$row['name'],
						'slug'		=>	$row['slug'],
						'downgrade' =>	NULL
					];
				}
			}
		}
		
		return $plan;
	}

	private function format_date($epoch, $format = false, $time = true) {
		global $user;

		try {
			$timezone = new \DateTimeZone(((isset($user->data['user_timezone']) && !empty($user->data['user_timezone'])) ? $user->data['user_timezone'] : 'America/Los_Angeles'));
		} catch (\Exception $e) {
			$timezone = new \DateTimeZone('UTC');
		}

		$date = new \DateTime('@' . (int) $epoch, $timezone);		

		if (!empty($format) && $format !== false) {
			if($time) {			
				return $date->format($format);
			} else {
				$remove = [
					"h:i a",
					"H:i",
					"g:i a",
				];
				
				$format = str_replace($remove, "", $format);
				
				return $date->format($format);
			}
		} else {
			return $date->format(((isset($user->data['user_dateformat']) && !empty($user->data['user_dateformat'])) ? $user->data['user_dateformat'] :'m/d/Y h:i a'));
		}		
	}
	
	public function productOwned(int $productID = 0) {
		global $user;
		
		if($user->data['user_id'] !== 1) {
			if(isset($user->data['subscription']['id']) && !empty($user->data['subscription']['id']) && $user->data['subscription']['id'] !== 1) {
				return true;
			}

			$sql = parent::mq("SELECT * FROM Transactions_Items WHERE course_id='".parent::mres($productID)."' AND user_id='".$user->data['user_id']."'");

			if($sql->num_rows > 0) {
				return true;
			}
		}

		return false;
	}

	public function processCart(array $payment) {
		global $request, $user;
		
		$invoiceID = NULL;

        if(!empty($payment)) {
			$mailer = new \SenFramework\Mailer();

            // Create Transaction DB Entries
            $invoiceID = $this->createInvoiceID();

            $invoice = [
                'InvoiceNo'                 => $invoiceID,
				'user_id'                   => $user->data['user_id'],
                'PaymentMethod'             => $payment['method'],
                'PaymentIdentifier'         => (($payment['method'] == 'paypal') ? $payment['email'] : $payment['card_brand'].' Ending '.$payment['card']),
                'ServiceCustomerID'         => $payment['payer_id'],
				'ServiceTransactionID'      => $payment['id'],
                'currency_code'             => 'USD',
				'currency_value'            => $user->data['cart']['cost'],
				'OrderStatus'				=> 5,
                'ip'                        => \SenFramework\SenFramework::getIP(),
                'user_agent'                => $request->header('User-Agent')
            ];

            parent::mq("INSERT INTO Transactions ".parent::build_array('INSERT', $invoice));
            $transactionID = parent::lastId();

            $items = NULL;

            foreach($user->data['cart']['items'] as $item) {
                $product = [
                    'transaction_id'        => $transactionID,
                    'course_id'            	=> $item['id'],
					'user_id'               => $user->data['user_id'],
					'original_value'		=> $item['cost']['price'],
					'value'					=> ($item['cost']['discount_value'] != NULL) ? $item['cost']['discount_value'] : $item['cost']['price']
                ];

                parent::mq("INSERT INTO Transactions_Items ".parent::build_array('INSERT', $product));

                if($item['cost']['discount'] != NULL) {
                    $price = '<small><s>$'.$item['cost']['price'].'</s></small><br /><strong>$'.$item['cost']['discount_value'].'</strong>';
                } else {
                    $price = '<strong>$'.$item['cost']['price'].'</strong>';
                }

				$items .= '<tr><td><img src="https://www.lvlupdojo.com' .$item['thumbnail'] . '" width="140" height="" alt="" /></td><td><a href="https://www.lvlupdojo.com/courses/' . $item['slug']. '/">' . $item['name'] . '</a></td><td class="text-center">'.$price.'</td></tr>';
				
				parent::mq("DELETE FROM Cart_Items WHERE cart_id='".parent::mres($user->data['cart']['id'])."' AND course_id='".parent::mres($item['id'])."'");
			}

			$replacements = [
                '{{ YEAR }}'            =>  date("Y"),
                '{{ INVOICE_ACCOUNT }}'	=> 	'<strong>'.((!empty($cp['pf_name'])) ? $cp['pf_name'] : $user->data['username']) . '</strong> ('.$user->data['user_email'].')',
                '{{ INVOICE_TO }}'		=>	(($payment['method'] == 'paypal') ? '<strong>PayPal Account</strong> ('.$payment['email'].')' : $payment['card_brand'].' Ending <strong>'.$payment['card'].'</strong>'),
                '{{ INVOICE_NUMBER }}'	=>	$invoiceID,
                '{{ INVOICE_DATE }}'	=>	$user->format_date(time(), $user->data['user_dateformat'], false),
                '{{ INVOICE_ORDERS }}'	=>	$items,
                '{{ INVOICE_TOTAL }}'	=>	'$'.number_format($user->data['cart']['cost'], 2)						
            ];
			
			// Generate PDF
            /*$request->enable_super_globals();

			$pdf_options = new \Dompdf\Options();
			$pdf_options->set('tempDir', PDFTEMP);
            $pdf_options->set('isRemoteEnabled', true);
            $pdf_options->set('isHtml5ParserEnabled', true);
            $pdf_options->set('defaultMediaType', 'print');
            $pdf_options->set('dpi', 300);

            $pdf = new \Dompdf\Dompdf($pdf_options);

            $pdf->setPaper('A4', 'portrait');
            $time = substr_replace(date('YmdHisO'), '\'', (0 - 2), 0).'\'';

            $pdf->add_info('Subject', 'Invoice: '.$invoiceID);
            $pdf->add_info('Creator', 'domPDF');
			$pdf->add_info('Author', 'LVLUP Dojo');
			$pdf->add_info('Title', 'Invoice: '.$invoiceID);
			$pdf->add_info('Keywords', 'Invoice, LVLUP Dojo, LVLUP Dojo Invoice, Invoice '.$invoiceID.', '.$invoiceID);
			$pdf->add_info('CreationDate', 'D:'.$time);
			$pdf->add_info('ModDate', 'D:'.$time);

            $html = file_get_contents(ABSPATH.'theme/pdf/invoice.table.html');
            $pdf->loadHtml(str_replace(array_keys($replacements), $replacements, $html));
            $pdf->render();

			$request->disable_super_globals();*/
			
			$subject = 'Order ' . $invoiceID . ' Confirmed';

			$mail_data = [
				'user_id' => $user->data['user_id'],
				'email' => $user->data['user_email'],
				'name' => ((!empty($user->data['user_first_name'])) ? $user->data['user_first_name'].' '.$user->data['user_last_name'] : ((!empty($user->data['username'])) ? $user->data['username'] : $user->data['user_email'])),
				/*'attachments' => [
					'type'		=> 'application/pdf',
					'name'		=> 'LVLUP-Dojo-'.$invoiceID.'.pdf',
					'content' 	=> $pdf->output() 
				]*/
			];

			$mailer->SendMail('invoice', $subject, $mail_data, $replacements);

			//unset($pdf, $pdf_options);
		}

		return $invoiceID;
	}
}