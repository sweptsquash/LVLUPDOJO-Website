<?php

ini_set('max_execution_time', 0);
ini_set('date.timezone', 'UTC'); 
	
if (!defined('ABSPATH')) {
	define('ABSPATH', dirname(__FILE__) . '/../');
}

require_once(ABSPATH.'init.php');

class clearCart extends \SenFramework\DB\Database {
	public function __construct() {
        $sql = parent::mq("SELECT id FROM Cart WHERE added <= '".date("Y-m-d H:i:s", strtotime("-1 day", time()))."'");

        if($sql->num_rows > 0) {
            while($row = parent::mfa($sql)) {
                parent::mq("DELETE FROM Cart WHERE id='".$row['id']."'");
                parent::mq("DELETE FROM Cart_Items WHERE cart_id='".$row['id']."'");
            }
        }        

        unset($sql);
    }
}

new clearCart();