<?php

namespace SenFramework\Controllers;

class Legal extends \SenFramework\DB\Database {
	
	public $data;
	
	public function __construct($route, $query) {
        global $request;

        switch($route[0]) {
            default:
                $this->data['triggererror'] = '404';
            break;

            case"refund-policy":
                $sql = parent::mq("SELECT config_value FROM Config WHERE config_name='refund_text'");
                $row = parent::mfa($sql);

                $this->data['legal'] = [
                    'title'         => 'Refund Policy',
                    'nav'           => 'refund',
                    'text'          => $row['config_value']
                ];
            break;

            case"cookie-policy":
                $sql = parent::mq("SELECT config_value FROM Config WHERE config_name='cookie_text'");
                $row = parent::mfa($sql);

                $this->data['legal'] = [
                    'title'         => 'Cookie Policy',
                    'nav'           => 'cookie',
                    'text'          => $row['config_value']
                ];
            break;

            case"privacy-policy":
                $sql = parent::mq("SELECT config_value FROM Config WHERE config_name='privacy_text'");
                $row = parent::mfa($sql);

                $this->data['legal'] = [
                    'title'         => 'Privacy Policy',
                    'nav'           => 'privacy',
                    'text'          => $row['config_value']
                ];
            break;

            case"terms-and-conditions":
                $sql = parent::mq("SELECT config_value FROM Config WHERE config_name='terms_text'");
                $row = parent::mfa($sql);

                $this->data['legal'] = [
                    'title'         => 'Terms &amp; Conditions',
                    'nav'           => 'terms',
                    'text'          => $row['config_value']
                ];
            break;
        }
    }
}