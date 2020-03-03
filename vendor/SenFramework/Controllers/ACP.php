<?php

namespace SenFramework\Controllers;

class ACP extends \SenFramework\DB\Database {

	public $data;
	
	public function __construct($route = NULL, $query = NULL)  {
		global $request, $senConfig, $user;

		if(!$user->data['is_registered']) {
			header("Location: https://".((DEVELOP) ? 'development.lvlupdojo.com' : 'www.lvlupdojo.com').'/');
			exit;
		} else {
			if($user->data['group_id'] !== 1 && $user->data['group_id'] !== 2) {
				header("Location: https://".((DEVELOP) ? 'development.lvlupdojo.com' : 'www.lvlupdojo.com').'/');
				exit;
			}
		}
		
		$routes = [
			'home',
			'login',
			'analytics',
			'courses',
			'mentors',
			'pricing',
			'users',
			'team',
			'config'
		];

		$path = (key_exists(1, $route)) ? $route[1] : 'home';
		
		if(in_array($path, $routes)) {
			switch($path) {
				default:
					$ACPDash = new \SenFramework\ACP\Dashboard;
					$this->data = $ACPDash->data;
				break;			
				
				/*case"login":
				case"sign-in":
					$this->data['single'] = false;
					
				break;*/

				case"analytics":

				break;
				
				case"courses":
					$ACPCourses = new \SenFramework\ACP\Courses($route, $query);					
					$this->data = $ACPCourses->data;
				break;

				case"mentors":
					$ACPMentors = new \SenFramework\ACP\Mentors($route, $query);					
					$this->data = $ACPMentors->data;
				break;

				case"pricing":
					$ACPPricing = new \SenFramework\ACP\Pricing($route, $query);
					$this->data = $ACPPricing->data;
				break;

				case"users":
					$ACPUsers = new \SenFramework\ACP\Users($route, $query);
					$this->data = $ACPUsers->data;
				break;

				case"team":
					$ACPTeam = new \SenFramework\ACP\Team($route, $query);
					$this->data = $ACPTeam->data;
				break;

				case"config":
					$this->data['template_folder'] = 'acp/config';
					$this->data['nav'] = 'config';
					$this->data['single'] = true;

					switch($route[2]) {
						case"about":
							$this->data['override']['title'] = 'Configuring About Us &bull; Admin Panel ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
							$this->data['template'] = 'about';

							$defaults = [
								'text' => '',
								'video' => ''
							];

							$sql = parent::mq("SELECT * FROM Config WHERE config_name='about_text' OR config_name='about_video'");

							if($sql->num_rows > 0) {
								while($row = parent::mfa($sql)) {
									if($row['config_name'] == 'about_text') {
										$defaults['text'] = $row['config_value'];
									} else if($row['config_name'] == 'about_video') {
										$defaults['video'] = $row['config_value'];
									}
								}
							}

							$this->data['form'] = [
								'text'			=>	$request->raw_variable('text', $defaults['text']),
								'video'			=> 	$request->variable('video', $defaults['video'], true)
							];

							if($request->is_set_post('updateConfig')) {
								parent::mq("UPDATE SET Config config_value='".parent::mres($this->data['form']['text'])."' WHERE config_name='about_text'");
								parent::mq("UPDATE SET Config config_value='".parent::mres($this->data['form']['video'])."' WHERE config_name='about_video'");

								$this->data['success'] = 'About Us, Updated.';
							}
						break;

						case"legal":
							$this->data['override']['title'] = 'Configuring Legal Text &bull; Admin Panel ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
							$this->data['template'] = 'legal';

							$defaults = [
								'refund' => '',
								'cookie' => '',
								'privacy' => '',
								'terms'	=> ''
							];

							$sql = parent::mq("SELECT * FROM Config WHERE config_name='refund_text' OR config_name='cookie_text' OR config_name='privacy_text' OR config_name='terms_text'");

							if($sql->num_rows > 0) {
								while($row = parent::mfa($sql)) {
									if($row['config_name'] == 'refund_text') {
										$defaults['refund']	= $row['config_value'];
									} else if($row['config_name'] == 'cookie_text') {
										$defaults['cookie'] = $row['config_value'];
									} else if($row['config_name'] == 'privacy_text') {
										$defaults['privacy'] = $row['config_value'];
									} else if($row['config_name'] == 'terms_text') {
										$defaults['terms'] = $row['config_value'];
									}
								}
							}

							$this->data['form'] = [
								'refund'			=> 	$request->raw_variable('refund', (string)$defaults['refund']),
								'cookie'			=> 	$request->raw_variable('cookie', (string)$defaults['cookie']),
								'privacy'			=> 	$request->raw_variable('privacy', (string)$defaults['privacy']),
								'terms'				=> 	$request->raw_variable('terms', (string)$defaults['terms'])
							];

							if($request->is_set_post('updateConfig')) {
								parent::mq("UPDATE Config SET ".parent::build_array('UPDATE', ['config_value' => $this->data['form']['refund']])." WHERE config_name='refund_text'");
								parent::mq("UPDATE Config SET ".parent::build_array('UPDATE', ['config_value' => $this->data['form']['cookie']])." WHERE config_name='cookie_text'");
								parent::mq("UPDATE Config SET ".parent::build_array('UPDATE', ['config_value' => $this->data['form']['privacy']])." WHERE config_name='privacy_text'");
								parent::mq("UPDATE Config SET ".parent::build_array('UPDATE', ['config_value' => $this->data['form']['terms']])." WHERE config_name='terms_text'");

								$this->data['success'] = 'Yawn, Legal Text Updated.';
							}
						break;

						case"defaults":
						default:
							$this->data['override']['title'] = 'Configuring Defaults Text &bull; Admin Panel ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
							$this->data['template'] = 'defaults';

							$this->data['config'] = array();

							$sql = parent::mq("SELECT * FROM Config");

							if($sql->num_rows > 0) {
								while($row = parent::mfa($sql)) {
									$this->data['config'][$row['config_name']] = $row['config_value'];
								}
								
								$this->data['config']['default_banner_type'] = 'local';
								$this->data['config']['home_banner_type'] = 'local';
							}
								
							if($request->is_set_post('updateConfig')) {
								$configData = [
									'company_address' 		=> $request->variable('contact', $this->data['config']['company_address'], true),
									'contact_email'			=> $request->variable('company', $this->data['config']['contact_email'], true),
									'default_banner'		=> $request->variable('bannerFile', $this->data['config']['default_banner'], true),
									'home_banner'			=> $request->variable('homeFile', $this->data['config']['home_banner'], true),
								];

								foreach($configData as $key => $row) {
									if(empty($row)) {
										$field = explode('_', $key);

										$this->data['error'][] = ucfirst($field[0]).' '.ucfirst($field[1]).' was left empty.';
									}
								}

								if($configData['default_banner'] !== $this->data['config']['default_banner']) {
									$this->data['config']['default_banner_type'] = 'limbo';
		
									if($FilePond->isFileId($this->data['config']['default_banner'])) {
										$file = $FilePond->getTempFile($this->data['config']['default_banner']);
			
										if(!empty($file)) {
											$result = $FilePond->save($this->data['config']['default_banner'], 'img/content/');
			
											if($result) {
												$configData['default_banner'] = $this->data['config']['default_banner'] = '/img/content/'.$file['name'];
												$this->data['config']['default_banner_type'] = 'local';
											}
										}
									}
								}

								if($configData['home_banner'] !== $this->data['config']['home_banner']) {
									$this->data['config']['home_banner_type'] = 'limbo';
		
									if($FilePond->isFileId($this->data['config']['home_banner'])) {
										$file = $FilePond->getTempFile($this->data['config']['home_banner']);
			
										if(!empty($file)) {
											$result = $FilePond->save($this->data['config']['home_banner'], 'img/content/');
			
											if($result) {
												$configData['home_banner'] = $this->data['config']['home_banner'] = '/img/content/'.$file['name'];
												$this->data['config']['home_banner_type'] = 'local';
											}
										}
									}
								}

								$this->data['config'] = array_merge($this->data['config'], $configData);

								if(!isset($this->data['error'])) {
									foreach($configData as $key => $row) {
										parent::mq("UPDATE Config SET config_value='".parent::mres($row)."' WHERE config_name='".parent::mres($key)."'");
									}

									$this->data['success'] = true;
								}
							}							
						break;
					}
				break;
			}
		} else {
			
		}
	}	
}