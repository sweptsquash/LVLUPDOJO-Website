<?php

/**
*
* This file uses part of the phpBB Forum Software package.
*
* @copyright (c) phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace SenFramework;

class Sessions extends \SenFramework\DB\Database {
	
	var $cookie_data = array();
	var $data = array();
	var $browser = '';
	var $forwarded_for = '';
	var $host = '';
	var $session_id = '';
	var $ip = '';
	var $load = 0;
	var $time_now = 0;
	
	public function session_begin() {
		global $request, $phpbb;
		
		$this->time_now				= time();
		$this->cookie_data			= array('u' => 0, 'k' => '');
		$this->browser				= $request->header('User-Agent');
		$this->referer				= $request->header('Referer');
		$this->forwarded_for		= $request->header('X-Forwarded-For');
		
		if ($request->is_set(COOKIE_PREFIX.'_sid', \SenFramework\Request\request_interface::COOKIE) || $request->is_set(COOKIE_PREFIX.'_u', \SenFramework\Request\request_interface::COOKIE)) {
			$this->cookie_data['u'] 	= $request->variable(COOKIE_PREFIX.'_u', 0, false, \SenFramework\Request\request_interface::COOKIE);
			$this->cookie_data['k'] 	= $request->variable(COOKIE_PREFIX.'_k', '', false, \SenFramework\Request\request_interface::COOKIE);
			$this->session_id 			= $request->variable(COOKIE_PREFIX.'_sid', '', false, \SenFramework\Request\request_interface::COOKIE);

			if (empty($this->session_id)) {
				$this->session_id = session_id();
				
				$this->cookie_data = array('u' => 0, 'k' => '');
			}
		} else {
			$this->session_id = session_id();
		}
		
		$ip = htmlspecialchars_decode($request->server('REMOTE_ADDR'));
		$ip = preg_replace('# {2,}#', ' ', str_replace(',', ' ', $ip));
		
		// split the list of IPs
		$ips = explode(' ', trim($ip));

		// Default IP if REMOTE_ADDR is invalid
		$this->ip = '127.0.0.1';

		foreach ($ips as $ip) {
			if (function_exists('SenFramework\\PHPBBFunctions\\phpbb_ip_normalise')) {
				// Normalise IP address
				$ip = $phpbb->phpbb_ip_normalise($ip);

				if (empty($ip)) {
					// IP address is invalid.
					break;
				}

				// IP address is valid.
				$this->ip = $ip;

				// Skip legacy code.
				continue;
			}

			if (preg_match($phpbb->get_preg_expression('ipv4'), $ip)) {
				$this->ip = $ip;
			} else if (preg_match($phpbb->get_preg_expression('ipv6'), $ip)) {
				// Quick check for IPv4-mapped address in IPv6
				if (stripos($ip, '::ffff:') === 0) {
					$ipv4 = substr($ip, 7);

					if (preg_match($phpbb->get_preg_expression('ipv4'), $ipv4)) {
						$ip = $ipv4;
					}
				}

				$this->ip = $ip;
			} else {
				// We want to use the last valid address in the chain
				// Leave foreach loop when address is invalid
				break;
			}
		}
		
		if (!empty($this->session_id)) {
			$sql = parent::mq("SELECT 
				u.*, 
				s.* 
			FROM 
				Sessions s, 
				Users u
			WHERE 
				u.user_type <> 1 
			AND 
				s.session_id = '".parent::mres($this->session_id)."'
			AND 
				u.user_id = s.session_user_id");
			
			if(!empty($sql) && $sql->num_rows > 0) {
				$this->data = parent::mfa($sql);
				$this->data['user_id'] = (int) $this->data['user_id'];
				$this->data['session_autologin'] = (int)$this->data['session_autologin'];
				$this->data['session_time'] = (int)$this->data['session_time'];
				$this->data['group_id'] = (int)$this->data['group_id'];

				if(isset($this->data['user_id'])) {
					if (strpos($this->ip, ':') !== false && strpos($this->data['session_ip'], ':') !== false) {
						$s_ip = $phpbb->short_ipv6($this->data['session_ip'], 4);
						$u_ip = $phpbb->short_ipv6($this->ip, 4);
					} else {
						$s_ip = implode('.', array_slice(explode('.', $this->data['session_ip']), 0, 4));
						$u_ip = implode('.', array_slice(explode('.', $this->ip), 0, 4));
					}
					
					$s_browser = trim(strtolower(substr($this->data['session_browser'], 0, 149)));
					$u_browser = trim(strtolower(substr($this->browser, 0, 149)));
					
					$s_forwarded_for = substr($this->data['session_forwarded_for'], 0, 254);
					$u_forwarded_for = substr($this->forwarded_for, 0, 254);
					
					if ($u_ip === $s_ip && $s_browser === $u_browser && $s_forwarded_for === $u_forwarded_for) {
						$session_expired = false;
						
						$ret = self::validate_session($this->data);
						
						if ($ret !== null && !$ret) {
							$session_expired = true;
						}
						
						if (!$session_expired) {
							// Check the session length timeframe if autologin is not enabled.
							// Else check the autologin length... and also removing those having autologin enabled but no longer allowed board-wide.
							if (!$this->data['session_autologin']) {
								if ($this->data['session_time'] < $this->time_now - 3660) {
									$session_expired = true;
								}
							} else if ($this->data['session_time'] < $this->time_now - 86460) {
								$session_expired = true;
							}
						}
						
						if (!$session_expired) {
							$this->data['is_registered'] = ($this->data['user_id'] !== 1) ? true : false;

							$gsql = parent::mq("SELECT id, name FROM Users_Groups WHERE id='".$this->data['group_id']."'");

							$this->data['group'] = parent::mfa($gsql);
							$this->data['group']['id'] = (int)$this->data['group']['id'];
							$this->data['name'] = $this->data['user_first_name'] . ' ' . $this->data['user_last_name'];

							$nsql = parent::mq("SELECT id FROM Newsletter_Subscribers WHERE EmailAddress='".parent::mres(strtolower($this->data['user_email']))."' AND IsActive='1'");
							$this->data['user_newsletter'] = ($nsql->num_rows > 0) ? 1 : 0;
							
							$billing = new \SenFramework\Billing();
							$this->data['subscription'] = $billing->subscriptionStatus($this->data['user_id'], $this->data['user_dateformat']);

							return true;
						}
					}					
				}				
			}
		}
		
		return $this->session_create();
	}
	
	private function validate_session($user_data) {
		return;
	}
	
	public function session_create($user_id = false, $persist_login = false) {
		global $request, $phpbb;
		
		//$this->data = array();
		$billing = new \SenFramework\Billing();
		
		if ($user_id !== false && isset($this->data['user_id']) && $this->data['user_id'] !== $user_id) {
			$this->data = array();
		}

		if (isset($this->data['user_id'])) {
			$this->cookie_data['k'] = '';
			$this->cookie_data['u'] = $this->data['user_id'];
		}
		
		if (isset($this->cookie_data['k']) && $this->cookie_data['k'] && $this->cookie_data['u'] && empty($this->data)) {
			$sql = parent::mq("SELECT 
					u.*
				FROM 
					Users u, 
					Session_Keys k
				WHERE 
					u.user_id = '" . (int) $this->cookie_data['u'] . "'
				AND 
					k.user_id = u.user_id
				AND 
					k.key_id = '" . parent::mres(md5($this->cookie_data['k'])) . "'
				AND
					u.user_type <> 1");
			
			if(!empty($sql) && $sql->num_rows > 0) {
				$user_data = parent::mfa($sql);

				if ($user_id === false || (isset($user_data['user_id']) && $user_id == $user_data['user_id'])) {
					$this->data = $user_data;
					$this->data['user_id'] = (int) $this->data['user_id'];
					$this->data['session_autologin'] = (int)$this->data['session_autologin'];
					$this->data['session_time'] = (int)$this->data['session_time'];
					$this->data['group_id'] = (int)$this->data['group_id'];

					$gsql = parent::mq("SELECT id, name FROM Users_Groups WHERE id='".$this->data['group_id']."'");

					$this->data['group'] = parent::mfa($gsql);
					$this->data['group']['id'] = (int)$this->data['group']['id'];
					$this->data['name'] = $this->data['user_first_name'] . ' ' . $this->data['user_last_name'];

					$nsql = parent::mq("SELECT id FROM Newsletter_Subscribers WHERE EmailAddress='".parent::mres(strtolower($this->data['user_email']))."' AND IsActive='1'");
					$this->data['user_newsletter'] = ($nsql->num_rows > 0) ? 1 : 0;
					$this->data['subscription'] = $billing->subscriptionStatus($this->data['user_id'], $this->data['user_dateformat']);
				}
			}
		}
		
		if ($user_id !== false && empty($this->data)) {
			$this->cookie_data['k'] = '';
			$this->cookie_data['u'] = $user_id;

			$sql = parent::mq("SELECT 
				*
			FROM 
				Users
			WHERE 
				user_id = '".parent::mres($this->cookie_data['u'])."'");
			
			$this->data = parent::mfa($sql);			
			$this->data['subscription'] = $billing->subscriptionStatus($this->data['user_id'], $this->data['user_dateformat']);
		}
		
		if (!is_array($this->data) || !count($this->data)) {
			$this->cookie_data['k'] = '';
			$this->cookie_data['u'] = 1;

			$sql = parent::mq("SELECT 
				*
			FROM 
				Users
			WHERE 
				user_id='".parent::mres($this->cookie_data['u'])."'");
			
			$this->data = parent::mfa($sql);
		}

		$this->data['user_id'] = (int) $this->data['user_id'];
		$this->data['group_id'] = (int)$this->data['group_id'];
		
		if ($this->data['user_id'] !== 1) {
			$this->data['session_last_visit'] = (isset($this->data['session_time']) && $this->data['session_time']) ? $this->data['session_time'] : (($this->data['user_lastvisit']) ? $this->data['user_lastvisit'] : time());
		} else {
			$this->data['session_last_visit'] = $this->time_now;
		}
		
		$this->data['is_registered'] = ($this->data['user_id'] !== 1) ? true : false;
		
		$session_autologin = (($this->cookie_data['k'] || $persist_login) && $this->data['is_registered']) ? true : false;
		
		$sql_ary = array(
			'session_user_id'		=> (int) $this->data['user_id'],
			'session_start'			=> (int) $this->time_now,
			'session_last_visit'	=> (int) $this->data['session_last_visit'],
			'session_time'			=> (int) $this->time_now,
			'session_browser'		=> (string) trim(substr($this->browser, 0, 149)),
			'session_forwarded_for'	=> (string) $this->forwarded_for,
			'session_ip'			=> (string) $this->ip,
			'session_autologin'		=> ($session_autologin) ? 1 : 0,
		);
		
		parent::mq("DELETE FROM Sessions WHERE session_id='".parent::mres($this->session_id)."' AND session_user_id='1'");
		
		if (empty($this->data['session_id'])) {
			$this->data['session_created'] = true;
		}
		
		$oldSessionID = $this->session_id;
		$this->session_id = $this->data['session_id'] = md5($phpbb->unique_id());

		$sql_ary['session_id'] = (string) $this->session_id;
		
		parent::mq("INSERT INTO Sessions ".parent::build_array('INSERT', $sql_ary));
						  
		if ($session_autologin) {
			$this->set_login_key();
		}
		
		$this->data = array_merge($this->data, $sql_ary);

		// Update Cart
		if($this->data['is_registered']) {
			parent::mq("UPDATE Cart SET session_id='".parent::mres($this->data['session_id'])."', user_id='".parent::mres($this->data['user_id'])."' WHERE user_id='1' AND session_id='".parent::mres($oldSessionID)."'");
		}
		
		$cookie_expire = $this->time_now + 31536000;

		$this->set_cookie('u', $this->cookie_data['u'], $cookie_expire);
		$this->set_cookie('k', $this->cookie_data['k'], $cookie_expire);
		$this->set_cookie('sid', $this->session_id, $cookie_expire);

		unset($cookie_expire);
		
		return true;
	}
	
	/**
	* Kills a session
	*
	* This method does what it says on the tin. It will delete a pre-existing session.
	* It resets cookie information (destroying any autologin key within that cookie data)
	* and update the users information from the relevant session data. It will then
	* grab guest user information.
	*/
	public function session_kill($new_session = true)
	{
		global $phpbb;

		$sql = parent::mq("DELETE FROM 
			Sessions
		WHERE 
			session_id = '" . parent::mres($this->session_id) . "'
		AND 
			session_user_id = '" . (int) $this->data['user_id'] . "'");

		if ($this->data['user_id'] !== 1) {
			// Delete existing session, update last visit info first!
			if (!isset($this->data['session_time'])) {
				$this->data['session_time'] = time();
			}
			
			parent::mq("UPDATE Users SET user_ip='".$this->ip."', user_last_visit='".(int)$this->data['session_time']."' WHERE user_id='".(int)$this->data['user_id']."'");

			if ($this->cookie_data['k']) {
				parent::mq("DELETE FROM Session_Keys WHERE user_id='".(int)$this->data['user_id']."' AND key_id='".parent::mres(md5($this->cookie_data['k']))."'");
			}

			// Reset the data array
			$sql = parent::mq("SELECT * FROM Users WHERE user_id='1'");
			
			$this->data = parent::mfa($sql);
		}

		$cookie_expire = $this->time_now - 31536000;
		$this->set_cookie('u', '', $cookie_expire);
		$this->set_cookie('k', '', $cookie_expire);
		$this->set_cookie('sid', '', $cookie_expire);
		unset($cookie_expire);

		// To make sure a valid session is created we create one for the anonymous user
		if ($new_session) {
			$this->session_create(1);
		}

		return true;
	}
	
	/**
	* Set/Update a persistent login key
	*
	* This method creates or updates a persistent session key. When a user makes
	* use of persistent (formerly auto-) logins a key is generated and stored in the
	* DB. When they revisit with the same key it's automatically updated in both the
	* DB and cookie. Multiple keys may exist for each user representing different
	* browsers or locations. As with _any_ non-secure-socket no passphrase login this
	* remains vulnerable to exploit.
	*/
	public function set_login_key($user_id = false, $key = false, $user_ip = false)
	{
		global $phpbb;

		$user_id = ($user_id === false) ? $this->data['user_id'] : $user_id;
		$user_ip = ($user_ip === false) ? $this->ip : $user_ip;
		$key = ($key === false) ? (($this->cookie_data['k']) ? $this->cookie_data['k'] : false) : $key;

		$key_id =  $phpbb->unique_id(hexdec(substr($this->session_id, 0, 8)));

		$sql_ary = array(
			'key_id'		=> (string) md5($key_id),
			'last_ip'		=> (string) $user_ip,
			'last_login'	=> (int) time()
		);

		if (!$key)
		{
			$sql_ary += array(
				'user_id'	=> (int) $user_id
			);
		}

		if ($key) {
			$sql = 'UPDATE Session_Keys
				SET ' . parent::build_array('UPDATE', $sql_ary) . '
				WHERE user_id = ' . (int) $user_id . "
					AND key_id = '" . parent::mres(md5($key)) . "'";
		} else {
			$sql = 'INSERT INTO Session_Keys ' . parent::build_array('INSERT', $sql_ary);
		}
		
		parent::mq($sql);

		$this->cookie_data['k'] = $key_id;

		return false;
	}
	
	/**
	* Sets a cookie
	*
	* Sets a cookie of the given name with the specified data for the given length of time. If no time is specified, a session cookie will be set.
	*
	* @param string $name		Name of the cookie, will be automatically prefixed with the phpBB cookie name. track becomes [cookie_name]_track then.
	* @param string $cookiedata	The data to hold within the cookie
	* @param int $cookietime	The expiration time as UNIX timestamp. If 0 is provided, a session cookie is set.
	*/
	public function set_cookie($name, $cookiedata, $cookietime)
	{
		// If headers are already set, we just return
		if (headers_sent()) {
			return;
		}

		$name_data = rawurlencode(COOKIE_PREFIX.'_' . $name) . '=' . rawurlencode($cookiedata);
		$expire = gmdate('D, d-M-Y H:i:s \\G\\M\\T', $cookietime);
		$domain = (!COOKIE_DOMAIN || COOKIE_DOMAIN == '127.0.0.1' || strpos(COOKIE_DOMAIN, '.') === false) ? '' : '; domain=' . COOKIE_DOMAIN;

		header('Set-Cookie: ' . $name_data . (($cookietime) ? '; expires=' . $expire : '') . '; path=/' . $domain . '; secure; HttpOnly', false);
	}
}