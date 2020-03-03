<?php

namespace SenFramework\Controllers;

class API extends \SenFramework\DB\Database
{
	public $data = [];
	private $responses;
	
	public function __construct($route = NULL, $query = NULL) {
		global $config, $senConfig, $auth, $request, $user, $phpbb, $cart;
		
		$this->data['override']['json'] = true;
		
		$this->responses = [
			'unauthorized' => NULL
		];
		
		$this->responses['unauthorized'] = new \StdClass;
		$this->responses['unauthorized']->result = 'error';
		$this->responses['unauthorized']->message = 'Unauthorized Access.';
		
		$this->responses['unexpected'] = new \StdClass;
		$this->responses['unexpected']->result = 'error';
		$this->responses['unexpected']->message = 'An unexpected error occured.';
		
		$this->responses['success'] = new \StdClass;
		$this->responses['success']->result = 'success';
		$this->responses['success']->dateRange = [];
		$this->responses['success']->data = [];
		
		switch($route[1]) {
			default:
				$this->data['response'] = $this->responses['unauthorized'];
			break;

			case"agreement":
				if($request->is_ajax()) {
					if(isset($route[2]) && !empty($route[2])) {
						switch($route[2]) {
							case"prepare":
								if($user->data['user_id'] !== 1) {
									$billing = new \SenFramework\Billing;

									$data = [
										'selected'		=> (int)$request->variable('paymentPlan', '0'),
										'discountID' 	=> (int)$request->variable('couponID', '0'),
										'method'    	=> $request->variable('paymentMethod', ''),
										'token'     	=> $request->variable('paymentToken', ''),
										'email'     	=> $request->variable('paymentEmail', '')
									];

									try {
										$result = new \StdClass;
										$result->result = 'success';
										$result->ApprovalURL = $billing->PaypalPrepareAgreement($data);

										$this->data['response'] = $result;
									} catch (\Exception $e) {
										$this->responses['unexpected']->message = $e->getMessage();

										header($request->server("SERVER_PROTOCOL") . " 400 Bad Request", true, 400);
										$this->data['response'] = $this->responses['unexpected'];
									}									
								} else {
									header($request->server("SERVER_PROTOCOL") . " 400 Bad Request", true, 400);
									$this->data['response'] = $this->responses['unauthorized'];
								}								
							break;
						}
					} else {
						header($request->server("SERVER_PROTOCOL") . " 400 Bad Request", true, 400);
						$this->data['response'] = $this->responses['unexpected'];
					}
				} else {
					header($request->server("SERVER_PROTOCOL") . " 400 Bad Request", true, 400);
					$this->data['response'] = $this->responses['unexpected'];
				}
			break;

			case"discount":
				if($request->is_ajax()) {
					switch($route[2]) {
						default:
						case"pricing":
							$code = $request->variable('code', '', true);

							if(!empty($code)) {
								$sql = parent::mq("SELECT
									Pricing.cost,
									Pricing_Discounts.id, 
									Pricing_Discounts.pricing_id, 
									Pricing_Discounts.percentage 
								FROM 
									Pricing_Discounts 
								INNER JOIN
									Pricing
								ON
									Pricing.id=Pricing_Discounts.pricing_id	
								WHERE 
								(
									Pricing_Discounts.start <= NOW() 
								AND 
									Pricing_Discounts.end >= NOW()
								) 
								AND 
									Pricing_Discounts.code='".parent::mres($code)."'");

								if($sql->num_rows > 0) {
									$data = new \StdClass;
									$data->plans = [];

									while($row = parent::mfa($sql)) {
										$data->plans[$row['pricing_id']] = [
											'id'	=> $row['id'],
											'cost'	=> number_format($row['cost'] - ($row['cost'] * ($row['percentage'] / 100)), 2)
										];

										list($number, $decimal) = explode('.', (string)$data->plans[$row['pricing_id']]['cost']);

										if($decimal === '00') {
											$data->plans[$row['pricing_id']]['cost'] = $number;
										}

										unset($number, $decimal);
									}

									$this->data['response'] = $data;
								} else {
									header($request->server("SERVER_PROTOCOL") . " 400 Bad Request", true, 400);
									$this->data['response'] = $this->responses['unexpected'];
								}
							} else {
								header($request->server("SERVER_PROTOCOL") . " 400 Bad Request", true, 400);
								$this->data['response'] = $this->responses['unexpected'];
							}
						break;

						case"courses":

						break;
					}
				} else {
					header($request->server("SERVER_PROTOCOL") . " 400 Bad Request", true, 400);

					$this->data['response'] = $this->responses['unauthorized'];
				}
			break;

			case"cart":
				if($request->is_ajax()) {
					if(!empty($route[2])) {
						switch($route[2]) {
							default:
								$this->data['response'] = $this->responses['unexpected'];
							break;

							case"add":
								$data = array(
									'itemID'    => $request->variable('itemID', '', true)
								);

								$itemID = (is_numeric($data['itemID'])) ? intval($data['itemID']) : 0;

								$this->data['response'] = $cart->addItem($itemID);
							break;

							case"remove":
								$data = array(
									'itemID'    => $request->variable('itemID', '', true)
								);

								$itemID = (is_numeric($data['itemID'])) ? intval($data['itemID']) : 0;
								
								$this->data['response'] = $cart->removeItem($itemID);
							break;

							case"clear":
								$this->data['response'] = $cart->clearCart();
							break;
						}
					} else {
						$this->data['response'] = $this->responses['unexpected'];
					}
				} else {
					$this->data['response'] = $this->responses['unauthorized'];
				}
			break;

			case"course":
				if($user->data['user_id'] !== 1) {
					if($request->is_ajax()) {
						if(!empty($route[2])) {
							switch($route[2]) {
								default:
									$this->data['response'] = $this->responses['unexpected'];
								break;

								case"lesson":
									if($user->data['group_id'] == 1 || $user->data['group_id'] == 2 || $user->data['group_id'] == 3) {
										switch($route[3]) {
											default:
												$this->data['response'] = $this->responses['unexpected'];
											break;

											case"remove":
												$response = new \stdClass();
												$lesson = $request->variable('lesson', '', true);

												if(!empty($lesson) && is_numeric($lesson)) {
													parent::mq("DELETE FROM Courses_Materials WHERE id='".parent::mres($lesson)."'");

													$response->result = 'success';
												} else {
													$response->result = 'error';
													$response->message = 'The Lesson ID Passed was not numeric.';
												}

												$this->data['response'] = $response;
											break;
										}
									} else {
										$this->data['response'] = $this->responses['unauthorized'];
									}
								break;

								case"review":
									$response = new \stdClass();

									$sql_ary = [
										'course_id'		=> 	$request->variable('course', '', true),
										'user_id'		=>	$user->data['user_id'],
										'user_ip'		=>  \SenFramework\SenFramework::getIP(),
										'rating'		=>	number_format(floatval($request->variable('course_rating', '', true)), 2),
										'review'		=>  strip_tags($request->raw_variable('review', ''), '<br><p><i><u><s><small>')
									];

									if(empty($sql_ary['rating'])) {
										$sql_ary['rating'] = '0.00';
									}

									if(!empty($sql_ary['course_id'])) {
										if(!empty($sql_ary['review'])) {
											$Billing = new \SenFramework\Billing();

											if($Billing->productOwned($sql_ary['course_id'])) {
												parent::mq("INSERT INTO Courses_Reviews ".parent::build_array('INSERT', $sql_ary));

												$ratings = [
													0 => 0,
													1 => 0,
													2 => 0,
													3 => 0,
													4 => 0,
													5 => 0
												];

												$sql = parent::mq("SELECT rating FROM Courses_Reviews WHERE course_id='".parent::mres($sql_ary['course_id'])."'");

												if($sql->num_rows > 0) {
													while($row = parent::mfa($sql)) {
														$rating[number_format($row['rating'], 0)] += 1;
													}
												}

												$totalRatings = 0;

												foreach($rating as $stars => $votes) {
													$totalRatings += $stars * $votes;
												}

												parent::mq("UPDATE Courses SET rating='".number_format(($totalRatings / $sql->num_rows), 2)."' WHERE id='".parent::mres($sql_ary['course_id'])."'");

												$response->result = 'success';
											} else {
												$response->result = 'error';
												$response->message = 'You do not have access to this course.';
											}
										} else {
											$response->result = 'error';
											$response->message = 'No Review Provided.';
										}

										$this->data['response'] = $response;
									} else {
										$this->data['response'] = $this->responses['unexpected'];
									}
								break;

								case"progress":
									$response = new \stdClass();

									$sql_ary = [
										'user_id'		=>	$user->data['user_id'],
										'course_id'		=> 	$request->variable('course', '', true),
										'lesson_id'		=>  $request->variable('lesson', '', true),
										'progress'		=>  number_format(floatval($request->variable('progress', '', true)), 2),
										'timestamp'		=>	$request->variable('time', '', true),
									];

									if(!empty($sql_ary['course_id']) && !empty($sql_ary['lesson_id'])) {
										$sql = parent::mq("SELECT 
											Courses_Materials.id,
											Courses_Materials.course_id 
										FROM 
											Courses 
										INNER JOIN 
											Courses_Materials 
										ON 
											Courses_Materials.course_id=Courses.id 
										WHERE 
											Courses_Materials.id='".parent::mres($sql_ary['lesson_id'])."' 
										AND 
											Courses.id='".parent::mres($sql_ary['course_id'])."'");

										if($sql->num_rows > 0) {
											$sql = parent::mq("SELECT * FROM Courses_Progress WHERE course_id='".parent::mres($sql_ary['course_id'])."' AND lesson_id='".parent::mres($sql_ary['lesson_id'])."'");

											if($sql->num_rows > 0) {
												$ids = [
													$sql_ary['course_id'],
													$sql_ary['lesson_id']
												];

												unset($sql_ary['user_id'], $sql_ary['course_id'], $sql_ary['lesson_id']);

												$sql_ary['last_visit'] = date("Y-m-d H:i:s");

												parent::mq("UPDATE 
													Courses_Progress 
												SET 
													".parent::build_array('UPDATE', $sql_ary). " 
												WHERE 
													user_id='".$user->data['user_id']."'
												AND
													course_id='".parent::mres($ids[0])."' 
												AND 
													lesson_id='".parent::mres($ids[1])."'");
											} else {
												parent::mq("INSERT INTO Courses_Progress ".parent::build_array('INSERT', $sql_ary));
											}

											$response->result = 'success';
										} else {
											$response->result = 'error';
										}
									} else {
										$response->result = 'error';
									}

									$this->data['response'] = $response;
								break;
							}
						} else {
							$this->data['response'] = $this->responses['unexpected'];
						}
					} else {
						$this->data['response'] = $this->responses['unauthorized'];
					}
				} else {
					$this->data['response'] = $this->responses['unauthorized'];
				}
			break;

			case"newsletter":
				if($request->is_ajax()) {
					$security = new \SenFramework\Security();

					$response = new \stdClass();

					$this->data['form'] = [
						'EmailAddress' 			=> $request->variable('EmailAddress', '', true),
						'CSRF' 					=> [
							$request->variable('CSRFName', '', true),
							$request->variable('CSRFToken', '', true)
						]
					];

					if(($request->is_set_post('CSRFName') && $request->is_set_post('CSRFToken'))) {							
						if(!empty($this->data['form']['CSRF'][0]) && !empty($this->data['form']['CSRF'][1])) {

							$email = strtolower($this->data['form']['EmailAddress']);

							if (!preg_match('/^' . $phpbb->get_preg_expression('email') . '$/i', $email)) {
								$response->result = 'error';
								$response->message[] = 'Email address supplied is considered invalid, please supply another.';
							}

							if(empty($response->message) && $security->validate_csrf_token($this->data['form']['CSRF'][0], $this->data['form']['CSRF'][1], false)) {
								$sql = parent::mq("SELECT EmailAddress, IsActive FROM Newsletter_Subscribers WHERE LOWER(EmailAddress)='".parent::mres($email)."'");
								
								if($sql->num_rows == 0) {
									parent::mq("INSERT INTO Newsletter_Subscribers (EmailAddress) VALUE ('".parent::mres($email)."')");

									$response->result = 'success';
								} else {
									$row = parent::mfa($sql);

									if($row['IsActive'] == 0) {
										parent::mq("UPDATE Newsletter_Subscribers SET IsActive='1' WHERE LOWER(EmailAddress)='".parent::mres($email)."'");
									}

									$response->result = 'error';
									$response->message[] = 'Email address is already subscribed to our newsletter.';
								}
							} else {
								$response->result = 'error';
								$response->message[] = 'CSRF Token Mismatch! Bruteforce attempt logged.';
							}
						} else {
							$response->result = 'error';
							$response->message[] = 'CSRF Token Mismatch! Bruteforce attempt logged.';
						}

						$this->data['response'] = $response;
					} else {
						$this->data['response'] = $this->responses['unexpected'];
					}
				} else {
					$this->data['response'] = $this->responses['unexpected'];
				}
			break;

			case"mentors":
				if($user->data['user_id'] !== 1) {
					if($request->is_ajax()) {
						$mentors = array();
						
						if(!empty($route[2])) {
							$param = urldecode($route[2]);

							$sql = parent::mq("SELECT * FROM Courses_Mentors WHERE LOWER(name) LIKE '" . parent::mres($param) . "%'");

							if($sql->num_rows > 0) {
								while($row = parent::mfa($sql)) {
									$mentors[] = [
										"id"			=> (int)$row['id'],
										"user_id"		=> (int)$row['user_id'],
										"name"			=> (string)utf8_encode($row['name']),
										"description"	=> (string)utf8_encode($row['description']),
										"keywords"		=> explode(',',utf8_encode($row['keywords'])),										
										"avatar"		=> (string)(!empty($row['avatar'])) ? $row['avatar'] : '/img/user/User-160x160.png'
									];
								}
							}
						}

						$this->data['response'] = $mentors;
					} else {
						$this->data['response'] = $this->responses['unauthorized'];
					}
				} else {
					$this->data['response'] = $this->responses['unauthorized'];
				}
			break;

			case"users":
				if($user->data['user_id'] !== 1) {
					if($request->is_ajax()) {
						switch($route[2]) {
							default:
								if(!empty($route[2])) {
									$param = urldecode($route[2]);

									$user_row = NULL;
				
									$sql = parent::mq("SELECT user_id, username, user_first_name, user_last_name, user_avatar FROM Users WHERE username_clean LIKE '" . parent::mres($phpbb->utf8_clean_string($param)) . "%' OR user_email LIKE '" . parent::mres($param) . "%'");

									if($sql->num_rows > 0) {
										$user_row = [];

										while($row = parent::mfa($sql)) {
											$user_row[] = [
												"id"		=> $row['user_id'],
												"username"	=> $row['username'],
												"name"		=> $row['user_first_name'] . ' ' . $row['user_last_name'],
												"avatar"	=> (!empty($row['user_avatar'])) ? $row['user_avatar'] : '/img/user/User-160x160.png'
											];
										}
									}

									$this->data['response'] = $user_row;
								} else {
									$this->data['response'] = NULL;
								}
							break;

							case"listusers":
								if($user->data['group_id'] == 1 || $user->data['group_id'] == 2 || $user->data['group_id'] == 3) {
									$users = array("data" => array());

									$sql = parent::mq("SELECT user_id, username, user_type, user_email, user_first_name, user_last_name, user_regdate, user_last_visit, user_avatar FROM Users WHERE user_id <> 1 ORDER BY LOWER(user_email) ASC");
							
									if($sql->num_rows > 0) {
										while($row = parent::mfa($sql)) {
											$users['data'][] = array(
												'id'			=> 	$row['user_id'],
												'active'		=>	($row['user_type'] == USER_INACTIVE) ? 0 : 1,
												'username'		=> 	[
													'display'	=> 	utf8_encode(((!empty($row['user_first_name'])) ? $row['user_first_name'].((!empty($row['user_last_name'])) ? ' '.$row['user_last_name'] : NULL) : ((!empty($row['username'])) ? $row['username'] : NULL))),
													'search'	=>	utf8_encode($row['username'])
												],
												'email'			=>	strtolower($row['user_email']),
												'avatar'		=>	(!empty($row['user_avatar'])) ? $row['user_avatar'] : '/img/user/User-160x160.png',
												'regdate'		=> 	$user->format_date(strtotime($row['user_regdate'])),
												'lastvisit'		=>  [
													'display'	=>	($row['user_last_visit'] > 0 ) ? $user->format_date($row['user_last_visit']) : 'N/A',
													'sort'		=>  $row['user_last_visit']
												]
											);
										}
									}

									$this->data['response'] = $users;
								} else {
									$this->data['response'] = $this->responses['unauthorized'];
								}
							break;
						}
					} else {
						$this->data['response'] = $this->responses['unauthorized'];
					}
				} else {
					$this->data['response'] = $this->responses['unauthorized'];
				}
			break;
				
			case"sessions":
				if($user->data['user_id'] !== 1) {
					if ($request->is_set_post('method')) {
						$data = [
							'method' => $request->variable('method', '', true),
							'session' => $request->variable('session', '', true)
						];
						
						if((!empty($data['method']) && !empty($data['session'])) && $data['method'] == 'delete') {
							parent::mq("DELETE FROM Session_Keys WHERE user_id='".$user->data['user_id']."' AND key_id LIKE '".parent::mres($data['session']). "%'");
							
							$this->data['response'] = new \StdClass;
							$this->data['response']->result = 'success';							
						} else {
							$this->data['response'] = $this->responses['unexpected'];
						}
					} else {
						$this->data['response'] = $this->responses['unexpected'];
					}
				} else {
					$this->data['response'] = $this->responses['unauthorized'];
				}
			break;

			case"upload":
				if($user->data['user_id'] !== 1) {
					$maxSize = min(\SenFramework\SenFramework::parse_num(ini_get("upload_max_filesize")), \SenFramework\SenFramework::parse_num(ini_get("post_max_size")));
					define("UPLOAD_FILE_DIR", "/home/lvlupdojo/public_html/");

					switch($route[2]) {
						default:
							$this->data['response'] = $this->responses['unexpected'];
						break;

						case"avatar":
							if ($request->is_set_post('method')) {
								$data = [
									'method' => $request->variable('method', '')
								];
								
								if(!empty($data['method'])) {
									switch($data['method']) {
										default:
											$this->data['response'] = $this->responses['unexpected'];
										break;
											
										case"upload":
											$file = $request->file('avatar_upload_file');
											
											if(!empty($file['name']) && $file['name'] !== 'none') {
												$errors = NULL;
												
												if(($file['size'] >= 12000000) || ($file["size"] == 0)) {
													$errors[] = 'File must be less than 12 megabytes.';
												}
												
												$filename = explode(".", $file["name"]);
												$fileTmp = $file['tmp_name'];
												
												$finfo = finfo_open(FILEINFO_MIME_TYPE);
												$mimeType = finfo_file($finfo, $fileTmp);
												
												$extension = strtolower(end($filename));
												$allowedExts = array("jpeg", "jpg", "png", "gif");
												$allowedMimeTypes = array("image/jpeg", "image/pjpeg", "image/x-png", "image/png", "image/gif");

												if (!in_array($mimeType, $allowedMimeTypes) || !in_array($extension, $allowedExts)) {
													$errors[] = 'Invalid file type. Only JPG, PNG and GIF types are accepted.';
												}
							
												list($width, $height) = getimagesize($fileTmp);
												
												if(empty($errors)) {
													$filename = '/img/user/'.$user->data['user_id'].'-'.base64_encode($user->data['user_id'].md5(time())).'.'.$extension;
													
													move_uploaded_file($fileTmp, UPLOAD_FILE_DIR.$filename);
													
													if(file_exists(UPLOAD_FILE_DIR.$filename)) {
														$result = array(
															'user_avatar' => $filename,
															'user_avatar_width' => $width,
															'user_avatar_height' => $height,
														);

														parent::mq("UPDATE 
															Users 
														SET 
															".parent::build_array('UPDATE', $result)." 
														WHERE 
															user_id = '".(int) $user->data['user_id']."'");												
														
														$this->data['response'] = new \StdClass;
														$this->data['response']->result = 'success';
														$this->data['response']->avatar = 'https://www.lvlupdojo.com' . $filename;	
													} else {
														$this->data['response'] = $this->responses['unexpected'];
														$this->data['response']->message = 'An unexpected issue occured during the upload process.';
													}
												} else {
													$this->data['response'] = $this->responses['unexpected'];
													$this->data['response']->message = $errors;
												}										
											} else {
												$this->data['response'] = $this->responses['unexpected'];
												$this->data['response']->message = 'No image was supplied.';									
											}									
										break;
									}
								} else {
									$this->data['response'] = $this->responses['unexpected'];
								}
							} else {
								$this->data['response'] = $this->responses['unexpected'];
							}
						break;

						case"course":
							$file = $request->file('file');

							$response = new \StdClass;

							try {
								if(!empty($file['name']) && $file['name'] !== 'none') {
									$fileData = [];

									$time = time();

									$year = $user->format_date($time,"Y");
									$year = (empty($year)) ? date("Y", $time) : $year;
									$month = $user->format_date($time,"m");
									$month = (empty($month)) ? date("m",$time) : $month;

									if(!is_dir(UPLOAD_FILE_DIR . "/img/uploads/" . $year . "/")) {
										mkdir(UPLOAD_FILE_DIR . "/img/uploads/" . $year . "/");
									}

									if(!is_dir(UPLOAD_FILE_DIR . "/img/uploads/" . $year . "/" . $month . "/")) {
										mkdir(UPLOAD_FILE_DIR . "/img/uploads/" . $year . "/" . $month . "/");
									}

									if(($file['size'] >= $maxSize) || ($file["size"] == 0)) {
										throw new \Exception('File must be less than 12 megabytes.');
									}

									$filename = explode(".", $file["name"]);
									$fileTmp = $file['tmp_name'];

									$finfo = finfo_open(FILEINFO_MIME_TYPE);
									$mimeType = finfo_file($finfo, $fileTmp);

									$extension = strtolower(end($filename));
									$allowedExts = array("gif", "jpeg", "jpg", "png", "svg", "blob");
									$allowedMimeTypes = array("image/gif", "image/jpeg", "image/pjpeg", "image/x-png", "image/png", "image/svg+xml");
									$imagickFormat = [
										'jpeg' => 'jpeg',
										'jpg' => 'jpeg',
										'png' => 'png'
									];

									if (!in_array($mimeType, $allowedMimeTypes) || !in_array($extension, $allowedExts)) {
										throw new \Exception("File does not meet the validation.");
									}

									$name = $time.'-'.sha1(str_replace('.', '-', $file["name"]). '-'.$user->data['user_id'].'-'.$time).'.'.$extension;

									if($extension == 'blob' || $extension == 'gif' || $extension == 'svg') {
										move_uploaded_file($file["tmp_name"], UPLOAD_FILE_DIR.'/uploads/'.$year.'/'.$month.'/'.$name);
									} else {	
										$tmpName = $time.'-'.sha1('tmp-'.$user->data['user_id'].'-'.$time).'-tmp.'.$extension;
										$tempFile = UPLOAD_FILE_DIR.'/img/tmp/'.$tmpName;

										move_uploaded_file($file["tmp_name"], $tempFile);

										try {
											$image = new \Imagick(realpath($tempFile));						

											$profiles = $image->getImageProfiles("icc", true);
											$image->stripImage();

											if(!empty($profiles)) {
												$image->profileImage("icc", $profiles['icc']);
											}

											$image->setImageFormat($imagickFormat[$extension]);

											if($imagickFormat[$extension] == 'jpeg') {
												$image->setInterlaceScheme(\Imagick::INTERLACE_PLANE);
												$image->setColorspace(\Imagick::COLORSPACE_RGB);
												$image->setSamplingFactors(array('2x2', '1x1', '1x1'));
												$image->setImageCompression(\Imagick::COMPRESSION_JPEG);
												$image->setImageCompressionQuality(85);
											} else {
												$image->setImageCompression(\Imagick::COMPRESSION_UNDEFINED);
												$image->setImageCompressionQuality(0);
											}

											$image->writeImage(UPLOAD_FILE_DIR.'/img/uploads/'.$year."/".$month."/".$name);

											$response->link = '/img/uploads/'.$year."/".$month."/".$name;

											$image->destroy();
										} catch(\ImagickException $e) {
											throw new \Exception("Imagick Threw an error: " . $e->getMessage());
										}

										unlink($tempFile);
									}
								} else {
									throw new \Exception('No file provided.');
								}
							} catch(\Exception $e) {
								$response->result = 'error';
								$response->message = $e->getMessage();
							}
							
							$this->data['response'] = $response;
						break;
					}
				} else {
					$this->data['response'] = $this->responses['unauthorized'];
				}
			break;
		}
	}
}