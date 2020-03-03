<?php

namespace SenFramework\ACP;

class Dashboard extends \SenFramework\DB\Database {

    public $data;

    public function __construct() {
        global $senConfig;

        $this->data['override']['title'] = 'Admin Panel ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
        $this->data['template'] = 'home';
        $this->data['single'] = true;
        
        $this->data['analytics'] = [
            'subscribers'		=> [
                'total'			=> 0,
                'new'			=> 0
            ],
            'orders'			=> [
                'total'			=> 0,
                'new'			=> 0
            ],
            'users'				=> [
                'total'			=> 0,
                'new'			=> 0
            ],
            'reviews'			=> [
                'total'			=> 0,
                'new'			=> 0
            ]
        ];

        $start_time = new \DateTime();
		$start_time->modify('-1 month');

        $monthAgo = $start_time->format('Y-m-d H:i:s');

        $sql = parent::mq("SELECT COUNT(*) as count FROM Users_Subscriptions");
        $row = parent::mfa($sql);

        $this->data['analytics']['subscribers']['total'] = $row['count'];

        unset($sql, $row);

        $sql = parent::mq("SELECT COUNT(*) as count FROM Users_Subscriptions AS us INNER JOIN Transactions AS t ON t.user_id=us.user_id WHERE t.InvoiceDate >= '".$monthAgo."'");
        $row = parent::mfa($sql);

        $this->data['analytics']['subscribers']['new'] = $row['count'];

        unset($sql, $row);

        $sql = parent::mq("SELECT COUNT(*) as count FROM Transactions_Items");
        $row = parent::mfa($sql);

        $this->data['analytics']['orders']['total'] = $row['count'];

        unset($sql, $row);

        $sql = parent::mq("SELECT COUNT(*) as count FROM Transactions_Items WHERE added >= '".$monthAgo."'");
        $row = parent::mfa($sql);

        $this->data['analytics']['orders']['new'] = $row['count'];

        unset($sql, $row);

        $sql = parent::mq("SELECT COUNT(*) as count FROM Users");
        $row = parent::mfa($sql);

        $this->data['analytics']['users']['total'] = $row['count'];

        unset($sql, $row);

        $sql = parent::mq("SELECT COUNT(*) as count FROM Users WHERE user_regdate >= '".$monthAgo."'");
        $row = parent::mfa($sql);

        $this->data['analytics']['users']['new'] = $row['count'];

        unset($sql, $row);
        
        $sql = parent::mq("SELECT COUNT(*) as count FROM Courses_Reviews");
        $row = parent::mfa($sql);

        $this->data['analytics']['reviews']['total'] = $row['count'];

        unset($sql, $row);

        $sql = parent::mq("SELECT COUNT(*) as count FROM Courses_Reviews WHERE posted >= '".$monthAgo."'");
        $row = parent::mfa($sql);

        $this->data['analytics']['reviews']['new'] = $row['count'];

        unset($sql, $row);
    }
}