<?php

namespace SenFramework;

class CronTools {
	
	public function _curl($uri = NULL, $headers = NULL, $agent = NULL) {
		if(!empty($uri)) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $uri); 

			if(!empty($headers)) {			
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			}
			
			if(!empty($agent)) {			
				curl_setopt($ch, CURLOPT_USERAGENT, $agent);
			}

			curl_setopt($ch, CURLOPT_FAILONERROR,0); 
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); 
			curl_setopt($ch, CURLOPT_TIMEOUT,5); 

			$ret = json_decode(curl_exec($ch));
			curl_close($ch);
			
			return $ret;
		} else {
			return false;
		}		
	}	
}