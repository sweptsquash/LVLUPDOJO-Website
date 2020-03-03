<?php

namespace SenFramework\Controllers;

class About extends \SenFramework\DB\Database {
	
	public $data = [
		'nav' => 'about-us'		
	];
	
	public function __construct() {
		global $request;
		
		$sql = parent::mq("SELECT * FROM Team WHERE active='1'");
		
		if(!empty($sql) && $sql->num_rows > 0) {
			while($row = parent::mfa($sql)) {
				$this->data['team'][] = $row;		
			}
		}
	}
	
}