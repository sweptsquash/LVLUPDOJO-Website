<?php

define('ABSPATH', dirname(__FILE__) . '/../');
define('CURDIR', dirname(__FILE__));

define('SECRET', 'INSERT-SECRET');

include(ABSPATH.'init.php');

$SenFramework = new \SenFramework\SenFramework($senConfig);

class Installer extends \SenFramework\DB\Database {
	private $Security;
	
	public function __construct() {
		$this->Security = new \SenFramework\Security;

		//self::discountTable();
		
		//self::courseMaterialsTable();
		
		//self::usersTable();

		//self::handleAvatarFiles();

		self::userTransactions();
	}

	public function handleAvatarFiles() {
		$path = ABSPATH.'img/user/';

		$iterator = new \DirectoryIterator($path);

		foreach ($iterator as $fileinfo) {
			if ($fileinfo->isFile()) {
				$filename = $fileinfo->getFilename();

				if(strpos($filename, '-min') !== false) {
					$oldFile = $path.$filename;
					$newFilename = str_replace('-min', '', $filename);
					$newFile = $path.$newFilename;

					rename($oldFile, $newFile);

					echo "Renamed: ".$filename." To: ".$newFilename.CRLF;
				}
			}
		}
	}
	
	private function processCSV($file) {
		$csv = NULL;
		
		if(!empty($file) && is_readable(CURDIR.'/data/'.$file.'.csv')) {
			$csv = array_map('str_getcsv', file(dirname(__FILE__).'/data/'.$file.'.csv'));
		
			array_walk($csv, function(&$a) use ($csv) {
				$a = array_combine($csv[0], $a);
			});
			
			array_shift($csv);
		}
		
		return $csv;
	}
	
	private function decrypt($password) {
		$td = mcrypt_module_open(\MCRYPT_3DES, '', \MCRYPT_MODE_ECB, '');
		
		$hash = md5(utf8_encode(SECRET), true);
		$hash .= substr($hash, 0, 8);
		
		$data = base64_decode($password);
		
		$rawStr = mcrypt_decrypt(\MCRYPT_3DES, $hash, $data, \MCRYPT_MODE_ECB);
		
		$block = mcrypt_get_block_size(\MCRYPT_3DES, \MCRYPT_MODE_ECB);
		$len = strlen($rawStr);
		$pad = ord($rawStr[$len-1]);
		
		return substr($rawStr, 0, strlen($rawStr) - $pad);
	}
	
	private function email_hash($email) {
		return sprintf('%u', crc32(strtolower($email))) . strlen($email);
	}
	
	public function coursesTable() {
		$csvData = self::processCSV('Courses');
		
		if(!empty($csvData)) {
			foreach($csvData as $offset => $course) {
				$slug = \SenFramework\SenFramework::createURL($course['Name']);
				
				$excerpt = rtrim(str_replace(['"', "'"], ['&quot;', '&#39;'], substr(strip_tags($course['Description']), 0, 160)), " ").'...';
				
				parent::mq("INSERT INTO Courses (
					id,
					name,
					slug,
					mentor,
					excerpt,
					description,
					keywords,
					categories,
					thumbnail,
					banner,
					published,
					publishDate
				) VALUES (
					'".$course[key($course)]."',
					'".parent::mres($course['Name'])."',
					'".$slug."',
					'".$course['SupplierID']."',
					".((!empty($excerpt)) ? "'".$excerpt."'" : "NULL").",
					'".parent::mres($course['Description'])."',
					NULL,
					NULL,
					NULL,
					NULL,
					'".$course['IsPublished']."',
					'".date("Y-m-d H:i:s", strtotime($course['PublishDate']))."'
				) ON DUPLICATE KEY UPDATE
					slug='".$slug."',
					mentor='".$course['SupplierID']."',
					excerpt='".$excerpt."',
					description='".parent::mres($course['Description'])."',
					published='".$course['IsPublished']."',
					publishDate='".date("Y-m-d H:i:s", strtotime($course['PublishDate']))."'");	
			}
		} else {
			die('No Courses Data');
		}		
	}
	
	public function courseMaterialsTable() {
		$csvData = self::processCSV('Courses_Materials');
		
		if(!empty($csvData)) {
			$totalItems = $lessonWB = 0;
			
			foreach($csvData as $offset => $courseMaterials) {
				$slug = \SenFramework\SenFramework::createURL($courseMaterials['Name']);
				
				$excerpt = rtrim(str_replace(['"', "'"], ['&quot;', '&#39;'], substr(strip_tags($courseMaterials['Description']), 0, 160)), " ").'...';
				
				switch($courseMaterials['TypeID']) {
					// Intro
					case 1:
						$lessonWB = 0;
						$totalItems = 0;
						$orderNo = 1;
						$thumbnail = '/img/lessons/'.$courseMaterials['ItemObjectID'].'/intro.jpg';
					break;
						
					// Course Video
					case 2:
						$orderNo = $courseMaterials['OrderSequence'] + 1;
						$totalItems = $orderNo;
						$thumbnail = '/img/lessons/'.$courseMaterials['ItemObjectID'].'/'.$courseMaterials['OrderSequence'].'.jpg';
					break;
						
					// Lesson Workbook
					case 3:
						$lessonWB++;
						
						$orderNo = $lessonWB;
					break;
						
					// Outro
					case 4:
						$orderNo = $totalItems + 1;
						$thumbnail = '/img/lessons/outro.jpg';
					break;
						
					// Course Workbook
					case 5:
						$lessonWB++;
						
						$orderNo = $lessonWB;
					break;
				}
				
				parent::mq("INSERT INTO Courses_Materials (
					id,
					course_id,
					parent_id,
					name,
					slug,
					type,
					excerpt,
					description,
					keywords,
					thumbnail,
					source,
					published,
					publishDate,
					free,
					orderNo
				) VALUES (
					'".$courseMaterials[key($courseMaterials)]."',
					".((!empty($courseMaterials['ItemObjectID']) && $courseMaterials['ItemObjectID'] !== 'NULL') ? "'".$courseMaterials['ItemObjectID']."'" : "'0'").",
					".((!empty($courseMaterials['ParentID']) && $courseMaterials['ParentID'] !== 'NULL') ? "'".$courseMaterials['ParentID']."'" : "'0'").",
					'".parent::mres($courseMaterials['Name'])."',
					'".$slug."',
					'".$courseMaterials['TypeID']."',
					".((($courseMaterials['TypeID'] == 3 || $courseMaterials['TypeID'] == 5) || (empty($excerpt) || $excerpt === 'NULL')) ? "NULL" : "'".$excerpt."'").",
					".((($courseMaterials['TypeID'] == 3 || $courseMaterials['TypeID'] == 5) || (empty($courseMaterials['Description']) || $courseMaterials['Description'] === 'NULL')) ? "NULL" : "'".parent::mres($courseMaterials['Description'])."'").",
					".((!empty($courseMaterials['Tags']) && $courseMaterials['Tags'] !== 'NULL') ? "'".$courseMaterials['Tags']."'" : "NULL").",
					".((!empty($thumbnail)) ? "'".$thumbnail."'" : "NULL").",
					'".$courseMaterials['SourcePath']."',
					'".$courseMaterials['IsActive']."',
					'".date("Y-m-d H:i:s", strtotime($courseMaterials['Date']))."',
					'".$courseMaterials['IsFree']."',
					".((!empty($courseMaterials['OrderSequence']) && $courseMaterials['OrderSequence'] !== 'NULL') ? "'".$courseMaterials['OrderSequence']."'" : "'0'")."
				) ON DUPLICATE KEY UPDATE 
					course_id=".((!empty($courseMaterials['ItemObjectID']) && $courseMaterials['ItemObjectID'] !== 'NULL') ? "'".$courseMaterials['ItemObjectID']."'" : "'0'").",
					parent_id=".((!empty($courseMaterials['ParentID']) && $courseMaterials['ParentID'] !== 'NULL') ? "'".$courseMaterials['ParentID']."'" : "'0'").",
					name='".parent::mres($courseMaterials['Name'])."',
					slug='".$slug."',
					type='".$courseMaterials['TypeID']."',
					excerpt=".((($courseMaterials['TypeID'] == 3 || $courseMaterials['TypeID'] == 5) || (empty($excerpt) || $excerpt === 'NULL')) ? "NULL" : "'".$excerpt."'").",
					description=".((($courseMaterials['TypeID'] == 3 || $courseMaterials['TypeID'] == 5) || (empty($courseMaterials['Description']) || $courseMaterials['Description'] === 'NULL')) ? "NULL" : "'".parent::mres($courseMaterials['Description'])."'").",
					keywords=".((!empty($courseMaterials['Tags']) && $courseMaterials['Tags'] !== 'NULL') ? "'".$courseMaterials['Tags']."'" : "NULL").",
					thumbnail=".((!empty($thumbnail)) ? "'".$thumbnail."'" : "NULL").",
					source='".$courseMaterials['SourcePath']."',
					published='".$courseMaterials['IsActive']."',
					publishDate='".date("Y-m-d H:i:s", strtotime($courseMaterials['Date']))."',
					free='".$courseMaterials['IsFree']."',
					orderNo=".((!empty($courseMaterials['OrderSequence']) && $courseMaterials['OrderSequence'] !== 'NULL') ? "'".$courseMaterials['OrderSequence']."'" : "'0'")."");
			}
		} else {
			die('No Course Materials Data');
		}
	}

	public function discountTable() {
		$csvData = self::processCSV('Discounts');

		if(!empty($csvData)) {
			foreach($csvData as $offset => $discount) {
				if($discount['course_id'] == 1 || $discount['course_id'] == 2) {
					parent::mq("INSERT INTO Pricing_Discounts (
						pricing_id, 
						user_id, 
						old_uid, 
						code, 
						percentage, 
						usage_limit, 
						start, 
						end, 
						added
					) VALUES (
						'".(($discount['course_id'] == 1) ? 2 : 3)."',
						'0',
						'".$discount['old_uid']."',
						'".$discount['code']."',
						'".$discount['percentage']."',
						'".((!empty($discount['UsageLimit']) && $discount['UsageLimit'] !== 'NULL') ? (int)$discount['UsageLimit'] : 0)."',
						'".\DateTime::createFromFormat('d/m/Y H:i:s', $discount['StartDate'])->format('Y-m-d H:i:s')."',
						'".\DateTime::createFromFormat('d/m/Y H:i:s', $discount['EndDate'])->format('Y-m-d H:i:s')."',
						'".\DateTime::createFromFormat('d/m/Y H:i:s', $discount['added'])->format('Y-m-d H:i:s')."'
					)");
				} else {
					parent::mq("INSERT INTO Courses_Pricing_Discounts (
						course_id, 
						user_id, 
						old_uid, 
						code, 
						percentage, 
						usage_limit, 
						start, 
						end, 
						added
					) VALUES (
						'".$discount['course_id']."',
						'0',
						'".$discount['old_uid']."',
						'".$discount['code']."',
						'".$discount['percentage']."',
						'".((!empty($discount['UsageLimit']) && $discount['UsageLimit'] !== 'NULL') ? (int)$discount['UsageLimit'] : 0)."',
						'".\DateTime::createFromFormat('d/m/Y H:i:s', $discount['StartDate'])->format('Y-m-d H:i:s')."',
						'".\DateTime::createFromFormat('d/m/Y H:i:s', $discount['EndDate'])->format('Y-m-d H:i:s')."',
						'".\DateTime::createFromFormat('d/m/Y H:i:s', $discount['added'])->format('Y-m-d H:i:s')."'
					)");
				}
			}
		} else {
			die("No Data.");
		}	
	}
	
	public function usersTable() {
		$csvData = self::processCSV('Users');
		
		if(!empty($csvData)) {
			foreach($csvData as $offset => $userData) {
				$password = self::decrypt($userData['PWD']);
				
				if(!empty($password)) {
					$password = $this->Security->password_hash($password, PASSWORD_DEFAULT);
					
					parent::mq("INSERT INTO Users (
						user_id, 
						user_ip,
						group_id, 
						user_first_name, 
						user_last_name,
						username, 
						username_clean, 
						user_email, 
						user_email_hash, 
						user_password, 
						user_newpasswd,
						user_actkey, 
						user_regdate, 
						user_avatar,
						user_banner,
						user_timezone, 
						user_fbid, 
						user_twid, 
						user_tcid, 
						user_dsid, 
						user_isactive
					) VALUES (
						'".$userData[key($userData)]."',
						NULL,
						'".$userData['UserGroupID']."',
						".((!empty($userData['FirstName']) && $userData['FirstName'] !== 'NULL') ? "'".parent::mres($userData['FirstName'])."'" : 'NULL').",
						".((!empty($userData['LastName']) && $userData['LastName'] !== 'NULL') ? "'".parent::mres($userData['LastName'])."'" : 'NULL').",
						NULL,
						NULL,
						'".$userData['LoginID']."',
						'".self::email_hash($userData['LoginID'])."',
						'".$password."',
						NULL,
						'".$userData['ActivationCode']."',
						'".\DateTime::createFromFormat('d/m/Y H:i:s', $userData['Date'])->format('Y-m-d H:i:s')."',
						".((!empty($userData['ImagePath']) && $userData['ImagePath'] !== 'NULL') ? "'/img/user/".$userData['ImagePath']."'" : 'NULL').",
						".((!empty($userData['BannerPath']) && $userData['BannerPath'] !== 'NULL') ? "'/img/user/".$userData['BannerPath']."'" : 'NULL').",
						'UTC',
						".((!empty($userData['FBID']) && $userData['FBID'] !== 'NULL') ? "'".$userData['FBID']."'" : 'NULL').",
						".((!empty($userData['TWID']) && $userData['TWID'] !== 'NULL') ? "'".$userData['TWID']."'" : 'NULL').",
						".((!empty($userData['TCID']) && $userData['TCID'] !== 'NULL') ? "'".$userData['TCID']."'" : 'NULL').",
						".((!empty($userData['DSID']) && $userData['DSID'] !== 'NULL') ? "'".$userData['DSID']."'" : 'NULL').",
						'".$userData['IsActive']."'
					) ON DUPLICATE KEY UPDATE
						user_first_name=".((!empty($userData['FirstName']) && $userData['FirstName'] !== 'NULL') ? "'".parent::mres($userData['FirstName'])."'" : 'NULL').", 
						user_last_name=".((!empty($userData['LastName']) && $userData['LastName'] !== 'NULL') ? "'".parent::mres($userData['LastName'])."'" : 'NULL').", 
						user_email='".$userData['LoginID']."', 
						user_email_hash='".self::email_hash($userData['LoginID'])."', 
						user_password='".$password."',
						user_avatar=".((!empty($userData['ImagePath']) && $userData['ImagePath'] !== 'NULL') ? "'".$userData['ImagePath']."'" : 'NULL').",
						user_banner=".((!empty($userData['BannerPath']) && $userData['BannerPath'] !== 'NULL') ? "'".$userData['BannerPath']."'" : 'NULL').",
						user_fbid=".((!empty($userData['FBID']) && $userData['FBID'] !== 'NULL') ? "'".$userData['FBID']."'" : 'NULL').", 
						user_twid=".((!empty($userData['TWID']) && $userData['TWID'] !== 'NULL') ? "'".$userData['TWID']."'" : 'NULL').", 
						user_tcid=".((!empty($userData['TCID']) && $userData['TCID'] !== 'NULL') ? "'".$userData['TCID']."'" : 'NULL').", 
						user_dsid=".((!empty($userData['DSID']) && $userData['DSID'] !== 'NULL') ? "'".$userData['DSID']."'" : 'NULL').", 
						user_isactive='".$userData['IsActive']."'");

					if(!empty($userData['ImagePath']) && $userData['ImagePath'] !== 'NULL') {
						$path = ABSPATH.'img/user/'.$userData['ImagePath'];

						if(!is_readable($path)) {
							echo "Avatar Issue: ".$userData['ImagePath'].CRLF;
						}
					}
					
					usleep(800);
				} else {
					echo 'User ' . $userData[key($userData)] . ' Password Blank'.CRLF;
				}
			}
		} else {
			die("No Data.");
		}	
	}

	private function userTransactions() {
		$csvData = self::processCSV('Transactions');
		
		if(!empty($csvData)) {
			$users = [];

			$plan = [
				'0' 	=> '0',
				'NULL'	=> '0',
				'1' 	=> '2',
				'2' 	=> '3'
			];

			foreach($csvData as $offset => $transactionData) {
				if(!array_key_exists($transactionData['UserID'], $users)) {
					$users[$transactionData['UserID']] = [
						'Subscription' 	=> [
							'Expired'	=> [],
							'Active'	=> []
						],
						'Items'			=> []
					];
				}

				// Check if the Transactions for a Sub
				if($transactionData['ItemObjectID'] == 1 || $transactionData['ItemObjectID'] == 2 || $transactionData['ItemObjectID'] == 14) {
					if((int)$transactionData['IsExpired'] == 1) {
						$users[$transactionData['UserID']]['Subscription']['Expired'][] = $transactionData;
					} else {
						$users[$transactionData['UserID']]['Subscription']['Active'][] = $transactionData;
					}
				} else {
					$users[$transactionData['UserID']]['Items'][] = $transactionData;
				}
			}

			//print_r($users);

			foreach($users as $user_id => $transaction) {
				if(!empty($transaction['Subscription']['Expired'])) {
					foreach($transaction['Subscription']['Expired'] as $expired) {
						$expiredOn = NULL;
						$invoiceDate = \DateTime::createFromFormat('d/m/Y H:i:s', $expired['SaleTblEnteredOn'])->format('Y-m-d H:i:s');
						$index = key($expired);

						if($expired['ItemObjectID'] != 14) {
							$expiredOn = date('Y-m-d H:i:s', strtotime($invoiceDate." +".(($expired['ItemObjectID'] == 2) ? '12' : '1')." month"));
						}

						$data = [
							'id'						=> (int)$expired[$index],
							'parent_transaction'		=> (!empty($expired['ParentSaleID']) && $expired['ParentSaleID'] !== 'NULL') ? $expired['ParentSaleID'] : 0,
							'InvoiceNo'					=> $expired['OrderNo'],
							'InvoiceDate'				=> $invoiceDate,
							'user_id'					=> (int)$expired['UserID'],
							'plan_id'					=> (int)($expired['ItemObjectID'] == 14) ? 4 : (int)$plan[$expired['ItemObjectID']],
							'PaymentMethod'				=> ($expired['PaymentMethodID'] == 1) ? 'paypal' : 'stripe',
							'PaymentIdentifier'			=> $expired['PaymentIdentifier'],
							'ServiceCustomerID'			=> (!empty($expired['ServiceCustomerID']) && $expired['ServiceCustomerID'] !== 'NULL') ? $expired['ServiceCustomerID'] : NULL,
							'ServiceTransactionID'		=> (!empty($expired['ServiceTransactionID']) && $expired['ServiceTransactionID'] !== 'NULL') ? $expired['ServiceTransactionID'] : NULL,
							'ServiceSubscriptionID' 	=> (!empty($expired['ServiceSubscriptionID']) && $expired['ServiceSubscriptionID'] !== 'NULL') ? $expired['ServiceSubscriptionID'] : NULL,
							'DiscountCode'				=> (!empty($expired['CouponID']) && $expired['CouponID'] !== 'NULL') ? $expired['CouponID'] : NULL,
							'CancelledOn'				=> (!empty($expired['CancelledOn']) && $expired['CancelledOn'] !== 'NULL') ? \DateTime::createFromFormat('d/m/Y H:i:s', $expired['CancelledOn'])->format('Y-m-d H:i:s') : NULL,
							'IsExpired'					=> ($expiredOn == NULL) ? 0 : 1,
							'ExpiredOn'					=> $expiredOn,
							'OrderStatus'				=> 5,
							'currency_code'				=> 'USD',
							'currency_value_original'	=> $expired['SetupPrice'],
							'currency_value'			=> $expired['Price']
						];

						$dataExport[$user_id]['Expired'][] = $data;

						//parent::mq("INSERT INTO Transactions ".parent::build_array('INSERT', $data));
					}
				}

				if(!empty($transaction['Subscription']['Active'])) {
					foreach($transaction['Subscription']['Active'] as $active) {
						$expiredOn = NULL;
						$invoiceDate = \DateTime::createFromFormat('d/m/Y H:i:s', $active['SaleTblEnteredOn'])->format('Y-m-d H:i:s');
						$index = key($active);

						if($active['ItemObjectID'] != 14) {
							if(strtotime($invoiceDate." +".(($active['ItemObjectID'] == 2) ? '12' : '1')." month") < time()) {
								$expiredOn = date('Y-m-d H:i:s', strtotime($invoiceDate." +".(($active['ItemObjectID'] == 2) ? '12' : '1')." month"));
							}

							if(!empty($active['CancelledOn']) && $active['CancelledOn'] !== 'NULL') {
								$expiredOn = \DateTime::createFromFormat('d/m/Y H:i:s', $active['CancelledOn'])->format('Y-m-d H:i:s');
							}
						} else {
							$expiredOn = NULL;
						}

						$data = [
							'id'						=> (int)$active[$index],
							'parent_transaction'		=> (!empty($active['ParentSaleID']) && $active['ParentSaleID'] !== 'NULL') ? $active['ParentSaleID'] : 0,
							'InvoiceNo'					=> $active['OrderNo'],
							'InvoiceDate'				=> $invoiceDate,
							'user_id'					=> (int)$active['UserID'],
							'plan_id'					=> ($active['ItemObjectID'] == 14) ? 4 : (int)$plan[$active['ItemObjectID']],
							'PaymentMethod'				=> ($active['PaymentMethodID'] == 1) ? 'paypal' : 'stripe',
							'PaymentIdentifier'			=> $active['PaymentIdentifier'],
							'ServiceCustomerID'			=> (!empty($active['ServiceCustomerID']) && $active['ServiceCustomerID'] !== 'NULL') ? $active['ServiceCustomerID'] : NULL,
							'ServiceTransactionID'		=> (!empty($active['ServiceTransactionID']) && $active['ServiceTransactionID'] !== 'NULL') ? $active['ServiceTransactionID'] : NULL,
							'ServiceSubscriptionID' 	=> (!empty($active['ServiceSubscriptionID']) && $active['ServiceSubscriptionID'] !== 'NULL') ? $active['ServiceSubscriptionID'] : NULL,
							'DiscountCode'				=> (!empty($active['CouponID']) && $active['CouponID'] !== 'NULL') ? $active['CouponID'] : NULL,
							'CancelledOn'				=> (!empty($active['CancelledOn']) && $active['CancelledOn'] !== 'NULL') ? \DateTime::createFromFormat('d/m/Y H:i:s', $active['CancelledOn'])->format('Y-m-d H:i:s') : NULL,
							'IsExpired'					=> ($expiredOn == NULL) ? 0 : 1,
							'ExpiredOn'					=> $expiredOn,
							'OrderStatus'				=> 5,
							'currency_code'				=> 'USD',
							'currency_value_original'	=> $active['SetupPrice'],
							'currency_value'			=> $active['Price']
						];

						if($expiredOn == NULL) {
							$dataExport[$user_id]['Active'][] = $data;
						} else {
							$dataExport[$user_id]['Expired'][] = $data;
						}

						//parent::mq("INSERT INTO Transactions ".parent::build_array('INSERT', $data));

						if($expiredOn == NULL) {
							if(empty($active[$index]) || $active[$index] == 'NULL') {
								print_r($active);
							} else {
								$data_subs = [
									'user_id'			=> (int)$active['UserID'],
									'plan_id'			=> (int)($active['ItemObjectID'] == 14) ? 4 : (int)$plan[$active['ItemObjectID']],
									'transaction_id'	=> (int)$active[$index]
								];

								$sql = parent::mq("SELECT * FROM Users_Subscriptions WHERE plan_id <= '".$data_subs['plan_id']."' AND user_id='".$data_subs['user_id']."'");

								if($sql->num_rows > 0) {
									//parent::mq("UPDATE Users_Subscriptions SET plan_id='".$data_subs['plan_id']."', transaction_id='".$data_subs['transaction_id']."' WHERE user_id='".$data_subs['user_id']."'");
								} else {
									//parent::mq("INSERT INTO Users_Subscriptions ".parent::build_array('INSERT', $data_subs));
								}
							}
						}
					}
				}

				if(!empty($transaction['Items'])) {
					foreach($transaction['Items'] as $item) {
						$index = key($item);

						if(empty($item[$index]) || $item[$index] == 'NULL') {
							print_r($item);
						} else {
							$data = [
								'id'						=> (int)$item[$index],
								'parent_transaction'		=> (!empty($item['ParentSaleID']) && $item['ParentSaleID'] !== 'NULL') ? $item['ParentSaleID'] : 0,
								'InvoiceNo'					=> $item['OrderNo'],
								'InvoiceDate'				=> $invoiceDate,
								'user_id'					=> (int)$item['UserID'],
								'plan_id'					=> 0,
								'PaymentMethod'				=> ($item['PaymentMethodID'] == 1) ? 'paypal' : 'stripe',
								'PaymentIdentifier'			=> $item['PaymentIdentifier'],
								'ServiceCustomerID'			=> (!empty($item['ServiceCustomerID']) && $item['ServiceCustomerID'] !== 'NULL') ? $item['ServiceCustomerID'] : NULL,
								'ServiceTransactionID'		=> (!empty($item['ServiceTransactionID']) && $item['ServiceTransactionID'] !== 'NULL') ? $item['ServiceTransactionID'] : NULL,
								'ServiceSubscriptionID' 	=> NULL,
								'DiscountCode'				=> (!empty($item['CouponID']) && $item['CouponID'] !== 'NULL') ? $item['CouponID'] : NULL,
								'CancelledOn'				=> (!empty($item['CancelledOn']) && $item['CancelledOn'] !== 'NULL') ? \DateTime::createFromFormat('d/m/Y H:i:s', $item['CancelledOn'])->format('Y-m-d H:i:s') : NULL,
								'IsExpired'					=> 0,
								'ExpiredOn'					=> NULL,
								'OrderStatus'				=> 5,
								'currency_code'				=> 'USD',
								'currency_value_original'	=> $item['SetupPrice'],
								'currency_value'			=> $item['Price']
							];

							echo "INSERT INTO Transactions ".parent::build_array('INSERT', $data). ';<br /><br />';

							$data = [
								'transaction_id'	=> (int)$item[$index],
								'user_id'			=> (int)$item['UserID'],
								'course_id'			=> (int)$item['ItemObjectID'],
								'original_value'	=> $item['SetupPrice'],
								'value'				=> $item['Price'],
								'added'				=> \DateTime::createFromFormat('d/m/Y H:i:s', $item['SaleTblEnteredOn'])->format('Y-m-d H:i:s')
							];
							
							//parent::mq("INSERT INTO Transactions_Items ".parent::build_array('INSERT', $data));
						}
					}
				}
			}

			//print_r($dataExport);
		} else {
			die("No Data.");
		}	
	}
}

new Installer;