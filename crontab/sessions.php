<?php

ini_set('max_execution_time', 0);
ini_set('date.timezone', 'UTC'); 
	
if (!defined('ABSPATH')) {
	define('ABSPATH', dirname(__FILE__) . '/../');
}

require_once(ABSPATH.'init.php');

class clearSessions extends \SenFramework\DB\Database {
    public function __construct() {
        parent::mq("DELETE FROM Sessions WHERE session_user_id='1' AND session_start <= '".strtotime("-1 day", time())."'");

        // Remove User Resets Older than 24 Hours
        parent::mq("DELETE FROM Users_Resets WHERE requested <= '".date("Y-m-d H:i:s", strtotime("-1 day", time()))."'");
    }
}

new clearSessions;