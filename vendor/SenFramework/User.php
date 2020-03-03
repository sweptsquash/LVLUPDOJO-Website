<?php

namespace SenFramework;

class User extends \SenFramework\Sessions {
	
	public $data;
	public $timezone;
	public $date_format;
	public $security;
	
	public function __construct() {
		$this->security = new \SenFramework\Security;
	}
	
	public function setup() {
		if ($this->data['is_registered'] !== true) {
			$user_date_format = $this->data['user_dateformat'];
			$user_timezone = $this->data['user_timezone'];
		} else {
			$user_date_format = 'm/d/Y h:i a';
			$user_timezone = 'America/Los_Angeles';
		}
		
		$this->date_format = $user_date_format;
		
		try {
			$this->timezone = new \DateTimeZone($user_timezone);
		} catch (\Exception $e) {
			$this->timezone = new \DateTimeZone('UTC');
		}		
	}
	
	public function format_date($epoch, $format = false, $time = true) {
		$date = new \DateTime('@' . (int) $epoch, $this->timezone);
		
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
			return $date->format($this->date_format);
		}		
	}
	
	public function Login($username, $password, $autologin = 0) {
		global $phpbb;
		
		if(!$username) {
			return array(
				'status' => 'error',
				'message' => 'No Username Supplied'
			);
		}
		
		$password = trim($password);
		
		if(!$password) {
			return array(
				'status' => 'error',
				'message' => 'No Password Supplied'
			);
		}
		
		$username_clean = $phpbb->utf8_clean_string($username);
		
		$sql = parent::mq("SELECT * FROM Users WHERE username_clean='" . parent::mres($username_clean) . "' OR user_email='" . parent::mres($username) . "'");
		$row = parent::mfa($sql);
			
		if($this->ip || $this->forwarded_for) {
			$sql = parent::mq("SELECT COUNT(*) AS attempts FROM Users_login_attempts WHERE attempt_time > ".(time() - (int)21600)." AND attempt_ip='".parent::mres($this->ip)."'");
			$result = parent::mfa($sql);

			$attempts = (int)$result['attempts'];

			$attempt_data = array(
				'attempt_ip'			=> $this->ip,
				'attempt_browser'		=> trim(substr($this->browser, 0, 149)),
				'attempt_forwarded_for'	=> $this->forwarded_for,
				'attempt_time'			=> time(),
				'user_id'				=> ($row) ? (int) $row['user_id'] : 0,
				'username'				=> $username,
				'username_clean'		=> $username_clean,
			);

			parent::mq("INSERT INTO Users_login_attempts ".parent::build_array('INSERT', $attempt_data));
		} else {
			$attempts = 0;
		}

		if (!$row) {
			if ($attempts >= 5) {
				return array(
					'status'		=> 'attempts',
					'message'		=> 'You exceeded the maximum allowed number of login attempts. In addition to your username and password you now also have to solve the CAPTCHA below.',
					'user_row'		=> array('user_id' => 0),
				);
			}

			return array(
				'status'	=> 'error',
				'message'	=> 'You have specified an incorrect username. Please check your username and try again.',
				'user_row'	=> array('user_id' => 0),
			);
		} else {		
			if ($row['user_type'] == USER_INACTIVE) {
				return array(
					'status'		=> 'error',
					'message'		=> 'This account has been deactivated by an Admin, please <a href="/contact-us/">contact support</a>.',
					'user_row'		=> $row,
				);
			} else {
				if($this->security->password_verify($password, $row['user_password'])) {
					parent::mq("DELETE FROM Users_login_attempts WHERE user_id='".$row['user_id']."'");

					if ($row['user_login_attempts'] != 0) {
						parent::mq("UPDATE Users SET user_login_attempts='0' WHERE user_id='".$row['user_id']."'");	
					}

					if($row['group_id'] == 7) {
						return array(
							'status'		=> 'error',
							'message'		=> 'The specified user is currently inactive.',
							'user_row'		=> $row,
						);
					}
					
					$result = $this->session_create($row['user_id'], $autologin);
					
					if ($result === true) {
						return array(
							'status'		=> 'success',
							'message'		=> false,
							'user_row'		=> $row
						);
					} else {
						return array(
							'status'		=> 'error',
							'message'		=> 'An error occurred during the session creation process.',
							'user_row'		=> $row,
						);
					}
				} else {
					if(!empty($row['user_id'])) {
						parent::mq("UPDATE Users SET user_login_attempts = user_login_attempts + 1 WHERE user_id='".$row['user_id']."' AND user_login_attempts < 5");
					}

					// Give status about wrong password...
					return array(
						'status'		=> ($attempts >= 5) ? 'attempts' : 'error',
						'message'		=> 'You have specified an incorrect password. Please check your password and try again.',
						'user_row'		=> $row,
					);
				}
			}
		}
	}	

	public function randomPassword(int $length = 16) {
		$result = NULL;
		
		if($length > 6) {
			$seed = str_split('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()');
			shuffle($seed);
			foreach (array_rand($seed, $length) as $k) {
				$result .= $seed[$k];
			}
		}
		
		if (!preg_match('/[!@#$%^&*()]/', $result)) {
			$seed = str_split('!@#$%^&*()');
			
			$pre = $end = NULL;
			
			foreach (array_rand($seed, 6) as $k) {
				if($k < 3) {
					$pre .= $seed[$k];
				} else {
					$end .= $seed[$k];	
				}
			}
			
			$result = $pre . $result . $end;
		}
		
		return $result;
	}
}