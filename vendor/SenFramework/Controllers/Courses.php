<?php

namespace SenFramework\Controllers;

class Courses extends \SenFramework\DB\Database {
	
	public $data = [
		'nav' => 'courses'		
	];
	
	public function __construct($route = NULL, $query = NULL)  {
		global $request, $senConfig, $user, $courses, $security, $phpbb;
		
		switch($route[1]) {
			default:
				if(empty($route[1]) || $route[1] === 'p') {
					$offset = intval($route[2]);
					
					if($offset <= 0) {
						$offset = 1;
					}

					if(isset($query['Name']) && isset($query['i'])) {
						if(!empty($query['i'])) {
							$id = intval($query['i']);
	
							if(is_int($id)) {
								$oldCourse = $courses->getCourse($id);
	
								if(!empty($oldCourse)) {
									header("Location: https://www.lvlupdojo.com/courses/".$oldCourse['slug']."/", true, 301);
									exit;
								}
							}
						}
					}
					
					$this->data['override']['title'] = 'Courses' . (($offset > 1) ? ' &bull; Page ' . $offset : NULL) . ' ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
					$this->data['title'] = 'Courses';
					
					$this->data['tags'] = $courses->getTags();
					$this->data['categories'] = $courses->getCategories(true);
					$this->data['courses'] = $courses->getPublishedCourses(NULL, $offset);
					$this->data['pagination'] = $this->data['courses']['pagination'];
					$this->data['meta'] = $this->data['courses']['meta'];
					
					unset($this->data['courses']['pagination'], $this->data['courses']['meta']);
				} else {
					if(is_numeric($route[1])) {
						$courseID = intval($route[1]);
					} else {
						$courseID = $route[1];
					}

					$this->data['product'] = $courses->getCourse($courseID);
					
					if(!empty($this->data['product'])) {						
						$this->data['override']['description'] = $this->data['product']['excerpt'];

						if(!empty($this->data['product']['keywords'])) {
							$this->data['override']['keywords'] = $senConfig->pageDefaults['keywords'].$this->data['product']['keywords'];
						}

						if(!empty($this->data['product']['banner'])) {
							$this->data['override']['image'] = $this->data['product']['banner'];
						}

						$this->data['product']['reviewed'] = $courses->userReviewed($this->data['product']['id']);

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
							while($row = parent::mfa($sql)) {
								$this->data['pricing'][$row['id']] = $row;

								if(isset($_SESSION['Discount']) && !empty($this->data['discountPlans']) && array_key_exists($row['id'], $this->data['discountPlans'])) {
									$this->data['pricing'][$row['id']]['cost'] = number_format($row['cost'] - ($row['cost'] * ($this->data['discountPlans'][$row['id']]['percentage'] / 100)), 2);
								}
							}
						}

						switch($route[2]) {
							default:
								$this->data['template'] = 'view';
								$this->data['override']['title'] = $this->data['product']['name'] . ' ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];

								$reviews = $courses->getCourseReviews(intval($this->data['product']['id']));

								$this->data['product']['reviewCount'] = count($reviews['reviews']);
								$this->data['product']['scoring'] = $reviews['scoring'];
								$this->data['product']['reviews'] = $reviews['reviews'];
							break;
								
							case"l":
								if(!empty($route[3])) {
									$this->data['lesson'] = $courses->getCourseLesson(intval($this->data['product']['id']), $route[3]);
									
									if(!empty($this->data['lesson'])) {
										if($this->data['product']['owned'] == true || $this->data['lesson']['free'] == true) {
											$this->data['template'] = 'lesson';
											$this->data['override']['title'] = $this->data['product']['name'] . ' ' . (($this->data['lesson']['name'] != 'Intro' && $this->data['lesson']['name'] != 'Outro') ? 'Lesson '.$this->data['lesson']['orderNo'] : '') . ': ' . $this->data['lesson']['name'] . ' ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
										} else {
											unset($this->data['lesson']);

											$this->data['noaccess'] = true;

											$this->data['template'] = 'view';
											$this->data['override']['title'] = $this->data['product']['name'] . ' ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
										}
									} else {
										$this->data['triggererror'] = '404';
									}
								} else {
									header($request->server("SERVER_PROTOCOL") . " 301 Moved Permanently"); 
									header("Location: /courses/".$this->data['product']['slug']."/", true, 301);
									exit;
								}
							break;
								 
							case"download":
								$this->data['template'] = 'download';
								$this->data['override']['title'] = $this->data['product']['name'] . ' ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
								
								if($route[3] != 'assets') {
									$workbook = $courses->getCourseWorkbook(intval($this->data['product']['id']), $route[3]);

									if(!empty($workbook['file'])) {
										header("Content-type: application/pdf");
										header('Content-Transfer-Encoding: binary');
										header("Content-Length: " . $workbook['filesize']);
										header("Content-Disposition: attachment; filename=\"" . $workbook['filename'] . "\"");
										header("Pragma: no-cache"); 
    									header("Expires: 0"); 								
										readfile($workbook['file']);

										exit;
									} else {
										$this->data['triggererror'] = '400';
									}
								} else {
									if($this->data['product']['owned'] == true) {
										$assets = $courses->getCourseAssets(intval($this->data['product']['id']));

										if(!empty($assets['file'])) {
											header("Content-type: application/zip");
											header('Content-Transfer-Encoding: binary');
											header("Content-Length: " . $assets['filesize']);
											header("Content-Disposition: attachment; filename=\"" . $assets['filename'] . "\"");
											header("Pragma: no-cache"); 
											header("Expires: 0"); 								
											readfile($assets['file']);

											exit;
										} else {
											$this->data['triggererror'] = '400';
										}
									} else {
										$this->data['triggererror'] = '400';
									}
								}
							break;
						}
					} else {
						$this->data['triggererror'] = '404';
					}
				}
			break;

			case"preview":
				if($user->data['is_registered'] && ($user->data['group_id'] == 1 || $user->data['group_id'] == 2 || $user->data['group_id'] == 5)) {
					if(is_numeric($route[2])) {
						$courseID = intval($route[2]);
					} else {
						$courseID = $route[2];
					}

					$this->data['product'] = $courses->getCourse($courseID);
					
					if(!empty($this->data['product'])) {						
						$this->data['override']['description'] = $this->data['product']['excerpt'];
						$this->data['preview'] = true;

						if(!empty($this->data['product']['keywords'])) {
							$this->data['override']['keywords'] = $senConfig->pageDefaults['keywords'].$this->data['product']['keywords'];
						}

						if(!empty($this->data['product']['banner'])) {
							$this->data['override']['image'] = $this->data['product']['banner'];
						}

						$this->data['product']['reviewed'] = $courses->userReviewed($this->data['product']['id']);

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
							while($row = parent::mfa($sql)) {
								$this->data['pricing'][$row['id']] = $row;

								if(isset($_SESSION['Discount']) && !empty($this->data['discountPlans']) && array_key_exists($row['id'], $this->data['discountPlans'])) {
									$this->data['pricing'][$row['id']]['cost'] = number_format($row['cost'] - ($row['cost'] * ($this->data['discountPlans'][$row['id']]['percentage'] / 100)), 2);
								}
							}
						}

						switch($route[3]) {
							default:
								$this->data['template'] = 'view';
								$this->data['override']['title'] = "Previewing: " . $this->data['product']['name'] . ' ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];

								$reviews = $courses->getCourseReviews(intval($this->data['product']['id']));

								$this->data['product']['reviewCount'] = count($reviews['reviews']);
								$this->data['product']['scoring'] = $reviews['scoring'];
								$this->data['product']['reviews'] = $reviews['reviews'];
							break;
								
							case"l":
								$this->data['lesson'] = $courses->getCourseLesson(intval($this->data['product']['id']), $route[3]);
								
								if(!empty($this->data['lesson'])) {
									if($this->data['product']['owned'] == true || $this->data['lesson']['free'] == true) {
										$this->data['template'] = 'lesson';
										$this->data['override']['title'] = $this->data['product']['name'] . ' ' . (($this->data['lesson']['name'] != 'Intro' && $this->data['lesson']['name'] != 'Outro') ? 'Lesson '.$this->data['lesson']['orderNo'] : '') . ': ' . $this->data['lesson']['name'] . ' ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
									} else {
										unset($this->data['lesson']);

										$this->data['noaccess'] = true;

										$this->data['template'] = 'view';
										$this->data['override']['title'] = $this->data['product']['name'] . ' ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
									}
								} else {
									$this->data['triggererror'] = '404';
								}
							break;
								 
							case"download":
								if($route[4] != 'assets') {
									$workbook = $courses->getCourseWorkbook(intval($this->data['product']['id']), $route[3]);

									if(!empty($workbook['file'])) {
										header("Content-type: application/pdf");
										header('Content-Transfer-Encoding: binary');
										header("Content-Length: " . $workbook['filesize']);
										header("Content-Disposition: attachment; filename=\"" . $workbook['filename'] . "\"");
										header("Pragma: no-cache"); 
    									header("Expires: 0"); 								
										readfile($workbook['file']);

										exit;
									} else {
										$this->data['triggererror'] = '400';
									}
								} else {
									$assets = $courses->getCourseAssets(intval($this->data['product']['id']));

									if(!empty($assets['file'])) {
										header("Content-type: application/zip");
										header('Content-Transfer-Encoding: binary');
										header("Content-Length: " . $assets['filesize']);
										header("Content-Disposition: attachment; filename=\"" . $assets['filename'] . "\"");
										header("Pragma: no-cache"); 
    									header("Expires: 0"); 								
										readfile($assets['file']);

										exit;
									} else {
										$this->data['triggererror'] = '400';
									}
								}
							break;
						}
					} else {
						$this->data['triggererror'] = '404';
					}
				} else {
					header("Location: https://".((DEVELOP) ? 'development.lvlupdojo.com' : 'www.lvlupdojo.com').'/courses/');
					exit;
				}
			break;

			case"m":
				if(empty($route[2]) || $route[2] === 'p') {
					$offset = intval($route[3]);
					
					if($offset <= 0) {
						$offset = 1;
					}
					
					$this->data['override']['title'] = 'Course Mentors' . (($offset > 1) ? ' &bull; Page ' . $offset : NULL) . ' ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
					$this->data['title'] = 'Course Mentors';
					
					$this->data['tags'] = $courses->getTags();
					$this->data['categories'] = $courses->getCategories(true);

					$this->data['mentors'] = $courses->getMentors($offset);
					$this->data['pagination'] = $this->data['mentors']['pagination'];
					$this->data['meta'] = $this->data['mentors']['meta'];
					$this->data['depth'] = 'mentors';
					
					unset($this->data['mentors']['pagination'], $this->data['mentors']['meta']);
				} else {
					$mentor = $courses->getMentor($route[2]);
					
					if(!empty($mentor)) {
						$offset = intval($route[4]);
					
						if($offset <= 0) {
							$offset = 1;
						}

						$courses->url = 'courses/m/'.$mentor['slug'];

						$this->data['override']['title'] = $mentor['name'] . ' Courses' . (($offset > 1) ? ' &bull; Page ' . $offset : NULL) . ' ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
						$this->data['override']['keywords'] = $mentor['keywords'].', '.$senConfig->pageDefaults['keywords'];
						$this->data['override']['description'] = str_replace(['"', "'"], ['&quot;', '&#39;'], substr(strip_tags($mentor['description']), 0, 160));

						$this->data['title'] = $mentor['name'] . ' Courses';
						
						$this->data['tags'] = $courses->getTags();
						$this->data['categories'] = $courses->getCategories(true);
						$this->data['depth'] = 'mentor';

						$Where = [
							[
								'operator'	=> "AND",
								'method' 	=> "EQUALS",
								'column'	=> 'Courses.mentor',
								'value'		=> $mentor['id']
 							]
						];

						$this->data['mentor'] = $mentor;
						$this->data['courses'] = $courses->getPublishedCourses($Where, $offset);
						$this->data['pagination'] = $this->data['courses']['pagination'];
						$this->data['meta'] = $this->data['courses']['meta'];
						
						unset($this->data['courses']['pagination'], $this->data['courses']['meta']);
					} else {
						$this->data['triggererror'] = '404';
					}
				}				
			break;
				
			case"c":
				$category = $courses->getCategory($route[2]);
				
				if(!empty($category)) {
					$offset = intval($route[4]);
					
					if($offset <= 0) {
						$offset = 1;
					}
					
					$this->data['override']['title'] = $category['name'] . ' Courses' . (($offset > 1) ? ' &bull; Page ' . $offset : NULL) . ' ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
					
					$courses->url = 'courses/c/'.$category['slug'];
					
					$this->data['title'] = $category['name'] . ' Courses';
					
					if($category['parent'] === 0) {
						$parentCategory = $courses->getCategory($category['parent_id']);
						
						$this->data['breadcrumbs'][] = [
							'text'	=> $parentCategory['name'],
							'url'	=> $parentCategory['slug']
						];
					}
					
					$this->data['breadcrumbs'][] = [
						'text'		=> $category['name'],
						'active'	=> true
					];
					
					$this->data['banner'] = $category['banner'];
					$this->data['activeCategory'] = $category['id'];
					
					$this->data['tags'] = $courses->getTags();
					$this->data['categories'] = $courses->getCategories(true);
					$this->data['courses'] = $courses->getPublishedCourses([
						[
							'value' 	=> $category['id'], 
							'method' 	=> 'FIND_IN_SET', 
							'column'	=> 'Courses.categories',
							'operator' 	=> 'AND']
					], $offset);
					
					$this->data['pagination'] = $this->data['courses']['pagination'];
					$this->data['meta'] = $this->data['courses']['meta'];
					
					unset($this->data['courses']['pagination'], $this->data['courses']['meta']);
				} else {
					$this->data['triggererror'] = '404';
				}
			break;

			case"apply-to-be-a-teacher":
				$this->data['template'] = 'teacher';
				$this->data['override']['title'] = 'Apply To Be A Teacher ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];

				$recaptcha = new \ReCaptcha\ReCaptcha(CAPTCHA_SECRET);

				$this->data['form'] = [
					'name'					=> $request->variable('name', (($user->data['is_registered']) ? ((!empty($user->data['user_first_name'])) ? $user->data['user_first_name'].' '.$user->data['user_last_name'] : ((!empty($user->data['username'])) ? $user->data['username'] : '')) : ''), true),
					'email'					=> $request->variable('email', (($user->data['is_registered']) ? ((!empty($user->data['user_email'])) ? $user->data['user_email'] : '') : ''), true),
					'subject' 				=> $request->variable('subject', '', true),
					'reason'				=> $request->variable('reason', '', true),
					'sample'				=> $request->variable('sample', '', true),
					'captcha_response'		=> $request->variable('g-recaptcha-response', '', true),
					'CSRF' 					=> [
						$request->variable('CSRFName', '', true),
						$request->variable('CSRFToken', '', true)
					]
				];

				if((!$request->is_set_post('CSRFName') || !$request->is_set_post('CSRFToken'))) {
					$this->data['form']['CSRF'] = $security->generate_csrf_token('applyTeacher');
				}

				if($request->is_set_post('applyTeacher')) {
					if(empty($this->data['form']['email'])) {
						$this->data['error'][] = 'No name was supplied.';
					} else {
						$email = strtolower($this->data['form']['email']);

						if (!preg_match('/^' . $phpbb->get_preg_expression('email') . '$/i', $email)) {
							$this->data['error'][] = 'Email address supplied is considered invalid, please supply another.';
						}
					}

					if(empty($this->data['form']['name'])) {
						$this->data['error'][] = 'No name was supplied.';
					}

					if(empty($this->data['form']['subject'])) {
						$this->data['error'][] = 'No subject was supplied.';
					}

					if(empty($this->data['form']['reason'])) {
						$this->data['error'][] = 'No reason was supplied.';
					}

					if(empty($this->data['error']) && $security->validate_csrf_token($this->data['form']['CSRF'][0], $this->data['form']['CSRF'][1], false)) {
						$resp = $recaptcha->verify($this->data['form']['captcha_response'], $ip); 

						if ($resp->isSuccess()) {
							$mail = new \PHPMailer\PHPMailer\PHPMailer;
                            
                            $mail->isSendmail();
                            
                            $mail->setFrom('noreply@lvlupdojo.com', 'LVLUP Dojo');
                        
                            $mail->addAddress('pizza@lvlupdojo.com', 'LVLUP Dojo');
                            
                            $mail->Subject = 'LVLUP Dojo: Apply To Be A Teacher Request';
                            
                            $mail->CharSet = 'utf-8';
                            
                            $mail->Body = 'Apply To Be A Teacher Request Information\r\nName: '.$this->data['form']['name'].'\r\nEmail: '.$this->data['form']['email'].'\r\nSubject: '.$this->data['form']['subject'].'\r\nReason: \r\n'.$this->data['form']['reason'].'\r\n'.((!empty($this->data['form']['sample'])) ? 'Sample Work: '.$this->data['form']['sample'] : NULL);
                            
                            if (!$mail->send()) {
                                $this->data['error'][] = "Mailer Error: " . $mail->ErrorInfo;
                            } else {
                                $this->data['success'] = 'Thank you for you application to become a course teacher, we\'ll get back to you as soon as possible.';
                            }
						} else {
							$captchaErrors = NULL;

							foreach ($resp->getErrorCodes() as $code) {
								$captchaErrors .= '<tt>' . $code . '</tt> ';	 
							}
							
							$this->data['error'][] = 'reCAPTCHA returned the following error: ' . $captchaErrors . '';
							
							$captcha = false;
						}
					}
				}
				
			break;
		}
	}
	
}