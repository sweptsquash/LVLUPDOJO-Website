<?php

namespace SenFramework;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;

class SenFramework extends \SenFramework\DB\Database
{	
	private $Routing;
	public $logger;
	public $twig;
	
	public static $countries = [
		"AF" => "Afghanistan",
		"AX" => "Åland Islands",
		"AL" => "Albania",
		"DZ" => "Algeria",
		"AS" => "American Samoa",
		"AD" => "Andorra",
		"AO" => "Angola",
		"AI" => "Anguilla",
		"AQ" => "Antarctica",
		"AG" => "Antigua and Barbuda",
		"AR" => "Argentina",
		"AM" => "Armenia",
		"AW" => "Aruba",
		"AU" => "Australia",
		"AT" => "Austria",
		"AZ" => "Azerbaijan",
		"BS" => "Bahamas",
		"BH" => "Bahrain",
		"BD" => "Bangladesh",
		"BB" => "Barbados",
		"BY" => "Belarus",
		"BE" => "Belgium",
		"BZ" => "Belize",
		"BJ" => "Benin",
		"BM" => "Bermuda",
		"BT" => "Bhutan",
		"BO" => "Bolivia",
		"BA" => "Bosnia and Herzegovina",
		"BW" => "Botswana",
		"BV" => "Bouvet Island",
		"BR" => "Brazil",
		"IO" => "British Indian Ocean Territory",
		"BN" => "Brunei Darussalam",
		"BG" => "Bulgaria",
		"BF" => "Burkina Faso",
		"BI" => "Burundi",
		"KH" => "Cambodia",
		"CM" => "Cameroon",
		"CA" => "Canada",
		"CV" => "Cape Verde",
		"KY" => "Cayman Islands",
		"CF" => "Central African Republic",
		"TD" => "Chad",
		"CL" => "Chile",
		"CN" => "China",
		"CX" => "Christmas Island",
		"CC" => "Cocos (Keeling) Islands",
		"CO" => "Colombia",
		"KM" => "Comoros",
		"CG" => "Congo",
		"CD" => "Congo, The Democratic Republic of The",
		"CK" => "Cook Islands",
		"CR" => "Costa Rica",
		"CI" => "Cote D'ivoire",
		"HR" => "Croatia",
		"CU" => "Cuba",
		"CY" => "Cyprus",
		"CZ" => "Czech Republic",
		"DK" => "Denmark",
		"DJ" => "Djibouti",
		"DM" => "Dominica",
		"DO" => "Dominican Republic",
		"EC" => "Ecuador",
		"EG" => "Egypt",
		"SV" => "El Salvador",
		"GQ" => "Equatorial Guinea",
		"ER" => "Eritrea",
		"EE" => "Estonia",
		"ET" => "Ethiopia",
		"FK" => "Falkland Islands (Malvinas)",
		"FO" => "Faroe Islands",
		"FJ" => "Fiji",
		"FI" => "Finland",
		"FR" => "France",
		"GF" => "French Guiana",
		"PF" => "French Polynesia",
		"TF" => "French Southern Territories",
		"GA" => "Gabon",
		"GM" => "Gambia",
		"GE" => "Georgia",
		"DE" => "Germany",
		"GH" => "Ghana",
		"GI" => "Gibraltar",
		"GR" => "Greece",
		"GL" => "Greenland",
		"GD" => "Grenada",
		"GP" => "Guadeloupe",
		"GU" => "Guam",
		"GT" => "Guatemala",
		"GG" => "Guernsey",
		"GN" => "Guinea",
		"GW" => "Guinea-bissau",
		"GY" => "Guyana",
		"HT" => "Haiti",
		"HM" => "Heard Island and Mcdonald Islands",
		"VA" => "Holy See (Vatican City State)",
		"HN" => "Honduras",
		"HK" => "Hong Kong",
		"HU" => "Hungary",
		"IS" => "Iceland",
		"IN" => "India",
		"ID" => "Indonesia",
		"IR" => "Iran, Islamic Republic of",
		"IQ" => "Iraq",
		"IE" => "Ireland",
		"IM" => "Isle of Man",
		"IL" => "Israel",
		"IT" => "Italy",
		"JM" => "Jamaica",
		"JP" => "Japan",
		"JE" => "Jersey",
		"JO" => "Jordan",
		"KZ" => "Kazakhstan",
		"KE" => "Kenya",
		"KI" => "Kiribati",
		"KP" => "Korea, Democratic People's Republic of",
		"KR" => "Korea, Republic of",
		"KW" => "Kuwait",
		"KG" => "Kyrgyzstan",
		"LA" => "Lao People's Democratic Republic",
		"LV" => "Latvia",
		"LB" => "Lebanon",
		"LS" => "Lesotho",
		"LR" => "Liberia",
		"LY" => "Libyan Arab Jamahiriya",
		"LI" => "Liechtenstein",
		"LT" => "Lithuania",
		"LU" => "Luxembourg",
		"MO" => "Macao",
		"MK" => "Macedonia, The Former Yugoslav Republic of",
		"MG" => "Madagascar",
		"MW" => "Malawi",
		"MY" => "Malaysia",
		"MV" => "Maldives",
		"ML" => "Mali",
		"MT" => "Malta",
		"MH" => "Marshall Islands",
		"MQ" => "Martinique",
		"MR" => "Mauritania",
		"MU" => "Mauritius",
		"YT" => "Mayotte",
		"MX" => "Mexico",
		"FM" => "Micronesia, Federated States of",
		"MD" => "Moldova, Republic of",
		"MC" => "Monaco",
		"MN" => "Mongolia",
		"ME" => "Montenegro",
		"MS" => "Montserrat",
		"MA" => "Morocco",
		"MZ" => "Mozambique",
		"MM" => "Myanmar",
		"NA" => "Namibia",
		"NR" => "Nauru",
		"NP" => "Nepal",
		"NL" => "Netherlands",
		"AN" => "Netherlands Antilles",
		"NC" => "New Caledonia",
		"NZ" => "New Zealand",
		"NI" => "Nicaragua",
		"NE" => "Niger",
		"NG" => "Nigeria",
		"NU" => "Niue",
		"NF" => "Norfolk Island",
		"MP" => "Northern Mariana Islands",
		"NO" => "Norway",
		"OM" => "Oman",
		"PK" => "Pakistan",
		"PW" => "Palau",
		"PS" => "Palestinian Territory, Occupied",
		"PA" => "Panama",
		"PG" => "Papua New Guinea",
		"PY" => "Paraguay",
		"PE" => "Peru",
		"PH" => "Philippines",
		"PN" => "Pitcairn",
		"PL" => "Poland",
		"PT" => "Portugal",
		"PR" => "Puerto Rico",
		"QA" => "Qatar",
		"RE" => "Reunion",
		"RO" => "Romania",
		"RU" => "Russian Federation",
		"RW" => "Rwanda",
		"SH" => "Saint Helena",
		"KN" => "Saint Kitts and Nevis",
		"LC" => "Saint Lucia",
		"PM" => "Saint Pierre and Miquelon",
		"VC" => "Saint Vincent and The Grenadines",
		"WS" => "Samoa",
		"SM" => "San Marino",
		"ST" => "Sao Tome and Principe",
		"SA" => "Saudi Arabia",
		"SN" => "Senegal",
		"RS" => "Serbia",
		"SC" => "Seychelles",
		"SL" => "Sierra Leone",
		"SG" => "Singapore",
		"SK" => "Slovakia",
		"SI" => "Slovenia",
		"SB" => "Solomon Islands",
		"SO" => "Somalia",
		"ZA" => "South Africa",
		"GS" => "South Georgia and The South Sandwich Islands",
		"ES" => "Spain",
		"LK" => "Sri Lanka",
		"SD" => "Sudan",
		"SR" => "Suriname",
		"SJ" => "Svalbard and Jan Mayen",
		"SZ" => "Swaziland",
		"SE" => "Sweden",
		"CH" => "Switzerland",
		"SY" => "Syrian Arab Republic",
		"TW" => "Taiwan, Province of China",
		"TJ" => "Tajikistan",
		"TZ" => "Tanzania, United Republic of",
		"TH" => "Thailand",
		"TL" => "Timor-leste",
		"TG" => "Togo",
		"TK" => "Tokelau",
		"TO" => "Tonga",
		"TT" => "Trinidad and Tobago",
		"TN" => "Tunisia",
		"TR" => "Turkey",
		"TM" => "Turkmenistan",
		"TC" => "Turks and Caicos Islands",
		"TV" => "Tuvalu",
		"UG" => "Uganda",
		"UA" => "Ukraine",
		"AE" => "United Arab Emirates",
		"GB" => "United Kingdom",
		"US" => "United States",
		"UM" => "United States Minor Outlying Islands",
		"UY" => "Uruguay",
		"UZ" => "Uzbekistan",
		"VU" => "Vanuatu",
		"VE" => "Venezuela",
		"VN" => "Viet Nam",
		"VG" => "Virgin Islands, British",
		"VI" => "Virgin Islands, U.S.",
		"WF" => "Wallis and Futuna",
		"EH" => "Western Sahara",
		"YE" => "Yemen",
		"ZM" => "Zambia",
		"ZW" => "Zimbabwe"
	];
	public static $acceptableImgExtensions = [
		'image/jpeg',
		'image/jpg',
		'image/gif',
		'image/png'
	];
	public static $ImgExtensions = [
		'image/jpeg' => 'jpeg',
		'image/jpg' => 'jpg',
		'image/gif' => 'gif',
		'image/png' => 'png'
	];
	public static $loadedConfig;
	
	public function __construct($senConfig) 
	{
		// Setup Application Logs
		$this->logger = [
			'site' 		=> new Logger('site'),
			'database' 	=> new Logger('database'),
			'debug' 	=> new Logger('debug'),
		];

		$this->logger['site']->pushHandler(new StreamHandler(LOG_DIR . '/site.log', Logger::DEBUG));
		$this->logger['site']->pushHandler(new FirePHPHandler());

		$this->logger['database']->pushHandler(new StreamHandler(LOG_DIR . '/database.log', Logger::DEBUG));
		$this->logger['database']->pushHandler(new FirePHPHandler());

		$this->logger['debug']->pushHandler(new StreamHandler(ABSPATH. '../debug.log', Logger::DEBUG));
		$this->logger['debug']->pushHandler(new FirePHPHandler());
		
		// Setup Twig Templating
		$loader = new \Twig_Loader_Filesystem(TPL_DIR);
		$this->twig = new \Twig_Environment($loader, [
			'debug' => (DEVELOP) ? true : false,
			'auto_reload' => (DEVELOP) ? true : false,
			'cache' => new \Twig_Cache_Filesystem(TPL_CACHE, \Twig_Cache_Filesystem::FORCE_BYTECODE_INVALIDATION),
			'autoescape' => false
		]);
		
		if(DEVELOP) {
			$this->twig->addExtension(new \Twig_Extension_Debug());
		}
		
		// Set Globals
		$GLOBALS['logger'] = $this->logger;
		$GLOBALS['twig'] = $this->twig;
		$GLOBALS['config'] = self::fetchConfig();

		$GLOBALS['phpbb'] = new \SenFramework\PHPBBFunctions;
		$GLOBALS['request'] = new \SenFramework\Request\Request;
		$GLOBALS['security'] = new \SenFramework\Security;
		$GLOBALS['sessions'] = new \SenFramework\Sessions;
		$GLOBALS['user'] = new \SenFramework\User;

		self::Setup();

		$GLOBALS['courses'] = new \SenFramework\Courses;
		$GLOBALS['cart'] = new \SenFramework\Cart;
		
		//if(DEVELOP) {
			set_error_handler([$this, "_errorHandler"], E_ALL);
		//}
	}
	
	public function __deconstruct() {
		parent::close();
	}
	
	private function Setup() {
		global $user;
		
		$user->session_begin();
		$user->setup();
		self::loadConfig();
	}
	
	public function loadConfig() {
		$config = array();
		
		$sql = parent::mq("SELECT * FROM Config");
		
		if(!empty($sql) && $sql->num_rows > 0) {
			while($row = parent::mfa($sql)) {
				$config[$row['config_name']] = $row['config_value'];
			}
		}
		
		$GLOBALS['config'] = $config;
	}
	
	/*
	 * Write a log entry
	 * @param string $msg Log Message
	 * @param string $log Log File
	 * @param string $type Log Entry Type
	 */
	public static function addLogEntry($msg, $log = 'site', $type = 'error') {
		global $logger, $request;
		
		if($msg !== NULL && $type !== NULL && $log !== NULL) {
			$request->enable_super_globals();
			
			$logger[$log]->$type($msg.LF);
			
			if ($_ENV['term']) {
				echo $msg.LF;
			}
			
			$request->disable_super_globals();
		}
	}

	/**
	 * Recursively delete a directory and its contents.
	 *
	 * @param string $target Directory to be deleted.
	 * @return void
	 */
	public static function recursiveDelete(string $target) {
		if(is_dir($target)) {
			$files = glob(rtrim($target, '/').'/*');

			foreach($files as $file) {
				self::recursiveDelete($file);
			}

			unset($files);

			@rmdir($target);
		} else if(is_file($target)) {
			chown($target, 0775);
			@unlink($target);
		}
	}
	
	public function _errorHandler($errno, $errstr, $errfile, $errline) {
		if (!(error_reporting() & $errno)) { // This error code is not included in error_reporting
			return;
		}

		$message = NULL;
		
		switch ($errno) {
			case E_USER_ERROR:
				$message = "[Fatal Error]: $errfile on line $errline. $errstr";
			break;

			case E_USER_WARNING:
				$message = "[Warning]: $errfile on line $errline. $errstr";
			break;

			case E_USER_NOTICE:
				$message = "[Notice]: $errfile on line $errline. $errstr";
			break;

			default:
				$message = "[Unknown Error]: $errfile on line $errline. $errstr";
			break;
		}
		
		$this->addLogEntry($message);
		
		return true;
	}	
	
	public static function utf8_normalize_nfc($strings) {
		if (extension_loaded('intl')) {
			if (empty($strings)) {
				return $strings;
			}

			if (!is_array($strings)) {
				if (normalizer_is_normalized($strings)) {
					return $strings;
				}
				
				return (string) normalizer_normalize($strings);
			} else {
				foreach ($strings as $key => $string) {
					if (is_array($string)) {
						foreach ($string as $_key => $_string) {
							if (normalizer_is_normalized($strings[$key][$_key])) {
								continue;
							}
							
							$strings[$key][$_key] = (string) normalizer_normalize($strings[$key][$_key]);
						}
					} else {
						if (normalizer_is_normalized($strings[$key])) {
							continue;
						}
						
						$strings[$key] = (string) normalizer_normalize($strings[$key]);
					}
				}
			}

			return $strings;
		} else {
			trigger_error(sprintf('PHP PECL intl extension not loaded.', '\SenFramework\SenFramework.php', '118'), E_USER_ERROR);
		}
	}
	
	/**
	  * Transforms a string to a friendly URL.
	  * @param string $url String to be transformed into friendly URL.
	  * @return string
	  */
	public static function createURL($url) {
		if($url !== NULL) {
			$string = preg_replace('/^-+|-+$/', '', mb_strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', strip_tags($url))));
			$string = rtrim($string, '-');
			
			return $string;
		} else {
			return;	
		}
	}
	
	public function formatTime($time) {
		$y = floor($time / (86400*365.25));
		$d = floor(($time - ($y*(86400*365.25))) / 86400);
		$h = intval(gmdate('G', $time));
		$m = intval(gmdate('i', $time));
		
		return [
			"y" => $y,
			"d" => $d,
			"h" => $h,
			"m" => $m
		];	
	}
	
	public static function Timezones($default = '') {
		global $user;
		static $timezones;
		
		if (!isset($timezones)) {
			$unsorted_timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);
			
			$timezones = array();
			
			foreach($unsorted_timezones as $timezone) {
				$tz = new \DateTimeZone($timezone);
				$dt = new \DateTime('now', $tz);
				$offset = $dt->getOffset();
				$timezones[$timezone] = [
					'offset' => $offset,
					'current' => $dt->format($user->date_format)
				];
			}
			
			unset($unsorted_timezones);

			// sort timezone by offset
			asort($timezones);
		}
		
		$timezone_list = $tz_select = array();
		$opt_group = $tz_select = NULL;
		
		foreach($timezones as $timezone => $tz) {
			$offset_prefix = $tz['offset'] < 0 ? '-' : '+';
			$offset_formatted = gmdate( 'H:i', abs($tz['offset']) );
			
			$title = "UTC ${offset_prefix}${offset_formatted} - ".$tz['current'];
			
			if ($opt_group != $tz['offset']) {
				$tz_select .= ($opt_group) ? '</optgroup>' : '';
				$tz_select .= '<optgroup label="' . $title . '">';
				$opt_group = $tz['offset'];
			}
			
			$title = "UTC ${offset_prefix}${offset_formatted} - " . $timezone;
			
			$selected = ($timezone === $default) ? ' selected="selected"' : '';
			$tz_select .= '<option title="' . $title . '" value="' . $timezone . '"' . $selected . '>' . $timezone . '</option>';
		}
		$tz_select .= '</optgroup>';
		
		return $tz_select;
	}
	
	/**
     * Encrypt Data
     *
     * @param string $data
     * @param string $key
     * @return string
     */
    public function encrypt($data, $key) {
        $iv = openssl_random_pseudo_bytes(16); // AES block size in CBC mode
        // Encryption
        $ciphertext = openssl_encrypt(
            $data,
            'AES-256-CBC',
            mb_substr($key, 0, 32, '8bit'),
            OPENSSL_RAW_DATA,
            $iv
        );
        // Authentication
        $hmac = hash_hmac(
            'SHA256',
            $iv . $ciphertext,
            mb_substr($key, 32, null, '8bit'),
            true
        );
        return $hmac . $iv . $ciphertext;
    }

    /**
     * Decrypt Data
     *
     * @param string $data
     * @param string $key
     * @return string
     */
    public function decrypt($data, $key) {
        $hmac       = mb_substr($data, 0, 32, '8bit');
        $iv         = mb_substr($data, 32, 16, '8bit');
        $ciphertext = mb_substr($data, 48, null, '8bit');
        // Authentication
        $hmacNew = hash_hmac(
            'SHA256',
            $iv . $ciphertext,
            mb_substr($key, 32, null, '8bit'),
            true
        );
        if (!self::hash_equals($hmac, $hmacNew)) {
            throw new \RuntimeException('Authentication failed');
        }
        // Decrypt
        return openssl_decrypt(
            $ciphertext,
            'AES-256-CBC',
            mb_substr($key, 0, 32, '8bit'),
            OPENSSL_RAW_DATA,
            $iv
        );
    }
	
	/**
     * Hash equals function for PHP 5.5+
     *
     * @param string $expected
     * @param string $actual
     * @return bool
     */
    public function hash_equals($expected, $actual) {
        $expected     = (string) $expected;
        $actual       = (string) $actual;
        if (function_exists('hash_equals')) {
            return hash_equals($expected, $actual);
        }
        $lenExpected  = mb_strlen($expected, '8bit');
        $lenActual    = mb_strlen($actual, '8bit');
        $len          = min($lenExpected, $lenActual);
        $result = 0;
        for ($i = 0; $i < $len; $i++) {
            $result |= ord($expected[$i]) ^ ord($actual[$i]);
        }
        $result |= $lenExpected ^ $lenActual;
        return ($result === 0);
    }
	
	public static function pagination($page = 1, $total_pages = 0, $url = NULL, $query = NULL) {
		global $request;
		
		$pagination = [];
		
		$path = getcwd();
		
		$prefix = ((DEVELOP) ? 'development' : 'www');
		$replace = ((DEVELOP) ? '/home/lvlupdojo/public_html/development.lvlupdojo.com' : '/home/lvlupdojo/public_html');
		
		$baseuri = rtrim("https://" . $prefix . ".lvlupdojo.com/" . str_replace($replace, '', $path), '/');
		
		if($total_pages > 0) {
			$range = 10;
			$range_min = ($range % 2 == 0) ? ($range / 2) - 1 : ($range - 1) / 2;
			$range_max = ($range % 2 == 0) ? $range_min + 1 : $range_min;
			
			$page_min = $page - $range_min;
			$page_max = $page + $range_max;
			
			$page_min = ($page_min < 1) ? 1 : $page_min;
			$page_max = ($page_max < ($page_min + $range - 1)) ? $page_min + $range - 1 : $page_max;
			
			if ($page_max > $total_pages) {
				$page_min = ($page_min > 1) ? $total_pages - $range + 1 : 1;
				$page_max = $total_pages;
			}
			
			$page_min = ($page_min < 1) ? 1 : $page_min;
			
			$pagination['html'] = '<nav aria-label="Page Navigation"><ul class="pagination justify-content-center">';
			
			if($page > $range && $page <= $total_pages) {
				$pagination['html'] .= '<li class="page-item"><a href="'.$baseuri.'/' . $url .'/p/1/' . $query . '" class="page-link" rel="bookmark" title="First Page"><<</a></li>';
			}
			
			if($page > 1) {
				$pagination['meta'] .= '<link rel="prev" href="'.$baseuri.'/' . $url .'/p/' . ($page - 1) . '/' . $query . '" />';	
				$pagination['html'] .= '<li class="page-item"><a href="'.$baseuri.'/' . $url .'/p/' . ($page - 1) . '/' . $query . '" class="page-link" rel="bookmark" title="Previous Page"><</a></li>';	
			}
			
			for ($i = $page_min;$i <= $total_pages;$i++) {
				if($i <= $page_max) {
					$pagination['html'] .= '<li' . (($i == $page) ? ' class="page-item active"' : '') . '><a href="'.$baseuri.'/' . $url .'/p/' . $i . '/' . $query . '" class="page-link" rel="bookmark" title="Page ' . $i . '">' . $i . '</a></li>';
				}
			}
			
			if($total_pages > 1 && $page < $total_pages) {
				$pagination['html'] .= '<li class="page-item"><a href="'.$baseuri.'/' . $url .'/p/' . ($page + 1) . '/' . $query . '" class="page-link" rel="bookmark" title="Next Page">></a></li>';
				$pagination['meta'] .= '<link rel="next" href="'.$baseuri.'/' . $url .'/p/' . ($page + 1) . '/' . $query . '" />';	
			}
			
			if($page_max < $total_pages) {
				$pagination['html'] .= '<li class="page-item"><a href="'.$baseuri.'/' . $url .'/p/' . $total_pages . '/' . $query . '" class="page-link" rel="bookmark" title="Last Page">>></a></li>';	
			}
			
			$pagination['html'] .= '</ul></nav>';
		} else {
			$pagination['html'] = '&nbsp;';
			$pagination['meta'] = NULL;
		}
		
		return $pagination;
	}
	
	public static function getIP() {
		global $request;
		
		if(!empty($request->variable('HTTP_CF_CONNECTING_IP', '', true, \SenFramework\Request\request_interface::SERVER))) {
			return htmlspecialchars((string) $request->variable('HTTP_CF_CONNECTING_IP', '', true, \SenFramework\Request\request_interface::SERVER));
		} else if($request->variable("HTTP_CLIENT_IP", '', true, \SenFramework\Request\request_interface::SERVER)) { 
			return $request->variable("HTTP_CLIENT_IP", '', true, \SenFramework\Request\request_interface::SERVER); 
		} else if($request->variable("HTTP_X_FORWARDED_FOR", '', true, \SenFramework\Request\request_interface::SERVER)) {
			return $request->variable("HTTP_X_FORWARDED_FOR", '', true, \SenFramework\Request\request_interface::SERVER); 
		} else if($request->variable("REMOTE_ADDR", '', true, \SenFramework\Request\request_interface::SERVER)) {
			return $request->variable("REMOTE_ADDR", '', true, \SenFramework\Request\request_interface::SERVER);
		} else {
			return "UNKNOWN";
		}
	}
	
	public static function dateFormats($default = '') {
		global $user;
		
		$formats = [
			"m/d/Y h:i a",
			"d/m/y H:i",
			"d M Y, H:i",
			"d M Y H:i",
			"M jS, 'y, H:i",
			"D M d, Y g:i a",
			"F jS, Y, g:i a",
		];
		
		$list = NULL;
		
		foreach($formats as $value) {
			$list .= '<option value="' . $value . '"' . (($default == $value) ? ' selected' : NULL) . '>' . $user->format_date(time(), $value) . ((strpos($value, '|') !== false) ? ' ' . $user->format_date(time(), $value) : '') . '</option>';
		}
		
		return $list;
	}
	
	public static function parse_num($k) {
		$p = 0;
		preg_match("/(\d{1,})([kmg]?)/i", trim($k), $r);
		if (isset($r) && isset($r[1])) {
			$p = $r[1];
			if (isset($r[2])) {
				switch(strtolower($r[2])) {
					case "g": $p *= 1024;
					case "m": $p *= 1024;
					case "k": $p *= 1024;
				}
			}
		}
		return $p;
	}
	
	public static function in_array_r($needle, $haystack, $strict = false) {
		foreach ($haystack as $item) {
			if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && self::in_array_r($needle, $item, $strict))) {
				return true;
			}
		}

		return false;
	}

	public function fetchConfig() {
		$sql = parent::mq("SELECT 
			* 
		FROM 
			Config 
		WHERE 
			config_name <> 'terms_text' 
		OR 
			config_name <> 'privacy_text' 
		OR 
			config_name <> 'cookie_text' 
		OR 
			config_name <> 'refund_text' 
		OR 
			config_name <> 'about_text'");

		if($sql->num_rows > 0) {
			$array = [];

			while($row = parent::mfa($sql)) {
				$array[$row['config_name']] = $row['config_value'];
			}

			return $array;
		}

		return NULL;
	}
	
	/*
	 * Handles Application Routing Requests & Page Rendering
	 * @return null
	 */
	public function Handle() 
	{
		global $twig;
		
		$this->Routing = new \SenFramework\Routing\Routing;
		$this->Routing->renderPage();
	}
}