<?php

ini_set('max_execution_time', 0);
ini_set('date.timezone', 'UTC'); 
	
if (!defined('ABSPATH')) {
	define('ABSPATH', dirname(__FILE__) . '/../');
}

require_once(ABSPATH.'init.php');

class clearSubscriptions extends \SenFramework\DB\Database {
	public function __construct() {
        $sql = parent::mq("SELECT 
            *
        FROM 
            Users_Subscriptions 
        INNER JOIN 
            Transactions 
        ON 
            Transactions.id=Users_Subscriptions.transaction_id
        INNER JOIN
            Pricing
        ON
            Pricing.id=Users_Subscriptions.plan_id
        WHERE
            Transactions.plan_id <> 0
        AND 
            Transactions.OrderStatus <> 5
        AND (
            Transactions.IsExpired='1' 
        OR
            Transactions.CancelledOn <> NULL
            )        
        ORDER BY 
            Users_Subscriptions.transaction_id 
        DESC");

        if($sql->num_rows > 0) {
            while($row = parent::mfa($sql)) {
                $period = ($prow['period'] == 'Year') ? "12" : "1";
                $expires = strtotime($row['InvoiceDate']." +".$period." month");

                if($expires < time()) {
                    parent::mq("DELETE FROM Users_Subscriptions WHERE user_id='".parent::mres($row['user_id'])."' AND transaction_id='".parent::mres($row['transaction_id'])."'");

                    $update = [
                        'OrderStatus' => ($row['OrderStatus'] != 7 && $row['OrderStatus'] != 8) ? 7 : $row['OrderStatus']
                    ];

                    if(($row['IsExpired'] == 1 && empty($row['ExpiredOn'])) || ($row['IsExpired'] = 0 && empty($row['ExpiredOn']))) {
                        $update['ExpiredOn'] = date("Y-m-d H:i:s", $expires);
                    }

                    parent::mq("UPDATE Transactions SET ".parent::build_array('UPDATE', $update)." WHERE id='".parent::mres($row['transaction_id'])."'");
                }
            }
        }
    }
}

new clearSubscriptions();