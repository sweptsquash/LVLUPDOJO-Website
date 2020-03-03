<?php

namespace SenFramework\Controllers;

class Profile extends \SenFramework\DB\Database {
	
	public function __construct($route = NULL, $query = NULL)  {
        global $request, $senConfig, $user;
        
        if(!empty($query['Name']) && !empty($query['i'])) {
            $Courses = new \SenFramework\Courses;

            $mentor = $Courses->getMentor('', intval($query['i']));

            if(!empty($mentor)) {
                header($request->server("SERVER_PROTOCOL") . " 301 Moved Permanently"); 
                header("Location: /courses/m/".$mentor['slug']."/", true, 301);
                
                exit;
            } else {
                $this->data['triggererror'] = '404';
            }
        } else {
            $sql = parent::mq("SELECT code FROM Pricing_Discounts WHERE code='".parent::mres($route[1])."'");

            if($sql->num_rows > 0) {
                $row = parent::mfa($sql);

                $_SESSION['Discount'] = $row['code'];

                header("Location: https://www.lvlupdojo.com/courses/");
            } else {
                $this->data['triggererror'] = '404';
            }
        }
    }
}