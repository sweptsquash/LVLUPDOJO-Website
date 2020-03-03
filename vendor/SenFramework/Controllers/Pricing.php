<?php

namespace SenFramework\Controllers;

class Pricing extends \SenFramework\DB\Database {
	
	public $data = [
		'nav' => 'pricing'		
	];
	
	public function __construct() {
		global $request;

		if(isset($_SESSION['Discount'])) {
			$dsql = parent::mq("SELECT * FROM Pricing_Discounts WHERE code='".parent::mres($_SESSION['Discount'])."' AND (start <= NOW() AND end >= NOW())");

			if($dsql->num_rows > 0) {
				while($drow = parent::mfa($dsql)) {									
					$this->data['discountPlans'][$drow['pricing_id']] = [
						'id' => $drow['id'],
						'percentage' => $drow['percentage']
					];
				}
			} else {
				unset($_SESSION['Discount']);
			}
		}

		$sql = parent::mq("SELECT * FROM Pricing_Options ORDER BY id ASC");

		if(!empty($sql) && $sql->num_rows > 0) {
			while($row = parent::mfa($sql)) {
				$this->data['pricingOptions'][$row['id']] = $row;	

				unset($this->data['pricingOptions'][$row['id']]['pricing_ids']);

				$plans = explode(',', $row['pricing_ids']);

				foreach($plans as $key => $value) {
					$this->data['pricingOptions'][$row['id']]['plans'][$value] = 1;
				}

				unset($plans);
			}
		}
		
		$sql = parent::mq("SELECT * FROM Pricing WHERE active='1' AND published='1' ORDER BY OrderNo ASC");
		
		if(!empty($sql) && $sql->num_rows > 0) {
			while($row = parent::mfa($sql)) {
				$this->data['pricing'][$row['id']] = $row;		

				if(isset($_SESSION['Discount']) && !empty($this->data['discountPlans']) && array_key_exists($row['id'], $this->data['discountPlans'])) {
					$this->data['pricing'][$row['id']]['cost'] = number_format($row['cost'] - ($row['cost'] * ($this->data['discountPlans'][$row['id']]['percentage'] / 100)), 2);
				}
			}
		}
	}
	
}