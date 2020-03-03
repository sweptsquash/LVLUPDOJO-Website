<?php

namespace SenFramework\Controllers;

class Home extends \SenFramework\DB\Database {
	
	public $data;
	
	public function __construct() {
		global $request, $user;
		
		$Courses = new \SenFramework\Courses;
		
		$this->data['courses'] = $Courses->getPublishedCourses(NULL, 1, 8);
		
		unset($this->data['courses']['pagination'], $this->data['courses']['meta']);		
	}
}