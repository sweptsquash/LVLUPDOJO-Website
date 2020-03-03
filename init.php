<?php

// Set Server to UTC
ini_set('date.timezone', 'UTC');

// Set Internal Encoding to UTF-8
mb_internal_encoding('UTF-8');

// Define Specific Locations
if (!defined('ABSPATH')) {
	define('ABSPATH', dirname(__FILE__) . '/');
}

define('CDNURI', '');
define('ROOT_PATH', ABSPATH . 'community/');
define('TPL_DIR', ABSPATH . 'theme/');

if (!defined('LOG_DIR')) {
	define('LOG_DIR', ABSPATH . 'logs/');
}

if (!defined('TPL_CACHE')) {
	define('TPL_CACHE', ABSPATH . 'cache/');
}

define('PDFTEMP', ABSPATH.'cache/pdf/');

if(!defined("ROUTING_MAP")) {
	define("ROUTING_MAP", ABSPATH . 'routing.json');
}

define('COOKIE_PREFIX', '');
define('COOKIE_DOMAIN', '');

// Social Logins
define('SOCIAL_TWITCH', true);
define('SOCIAL_TWITTER', true);
define('SOCIAL_FACEBOOK', true);
define('SOCIAL_DISCORD', true);

// API Keys
define('MANDRILL_API', '');

define('CAPTCHA', '');
define('CAPTCHA_SECRET', '');

define('STRIPE_API_VERSION', "2018-07-27");
define('STRIPE_KEY', '');
define('STRIPE_SECRET', '');
define('STRIPE_WEBHOOK_SECRET', '');
define('STRIPE_KEY_DEV', '');
define('STRIPE_SECRET_DEV', '');

define('PAYPAL_KEY', '');
define('PAYPAL_SECRET', '');
define('PAYPAL_KEY_DEV', '');
define('PAYPAL_SECRET_DEV', '');

define('TWITCH', 'zacc4bn3axsbga2pjoxtw2egteegc0');
define('TWITCH_SECRET', '0kzysqcs4vjaxrp61irw1xb76h1pvc');
define('TWITCH_DEV', '');
define('TWITCH_DEV_SECRET', '');

define('BITLYUSER', '');
define('BITLYAPI', '');

//define('DISCORD_APP_ID', '');
//define('DISCORD_APP_SECRET', '');

define('DISCORD_BOT_ID', '');
define('DISCORD_BOT_SECRET', '');
define('DISCORD_BOT_TOKEN', '');
define('DISCORD_SERVER_ID', );
define('DISCORD_ROLE_ID', );

define('FB_APP_ID', '');
define('FB_APP_SECRET', '');
define('FB_APP_TOKEN', '');

define('TWTTER_ID', '');
define('TWITTER_CONSUMER_KEY', '');
define('TWITTER_CONSUMER_SECRET', '');
define('TWITTER_TOKEN', '');
define('TWITTER_SECRET', '');

if ($_SERVER['HTTP_HOST'] == 'development.lvlupdojo.com') {
	define('DEVELOP', true);
} else {
	define('DEVELOP', false);
}

// Ease-of-use defines
define('LF', "\n");
define('CR', "\r");
define('CRLF', "\r\n");
define('TAB', "\t");

// Mysql db defines
define('MYSQL_HOST', 'localhost');
define('MYSQL_USER', '');
define('MYSQL_PASSWORD', '');
define('MYSQL_DATABASE', '');
define('MYSQL_PORT', 3306);

// Password Encryption Options
define('PASSWORD_BCRYPT', 1);
define('PASSWORD_DEFAULT', PASSWORD_BCRYPT);
define('PASSWORD_BCRYPT_DEFAULT_COST', 10);

define('USER_NORMAL', 0);
define('USER_INACTIVE', 1);
define('USER_IGNORE', 2);

// Autoload SenFramework
$loader = require(ABSPATH . 'vendor/autoload.php');

$senConfig = new \stdClass;

// Global Page Default
$senConfig->pageDefaults = [
	"name" => "LVLUP Dojo",
	"seperator" => "|",
	"keywords" => "make money gaming,how to stream become a streamer,professional gamer,become professional gamer,personal branding,streaming on mixer,Seth Abner Scump, Optic Scump, Optic Gaming, FaZe Clan, faze clayster, faze apex, faze courses, tsm zero, chile zero, gonzallo barrios, Nicolas Cole, cole, mather, build a personal brand, justin wong, street fighter, street fighter meta, make money on youtube, Tyler Ninja Blevins, Ninjas hyper, Ninja twitch, Ninja video course, DZ Live, DZ Live lvlup, dojo DZ, beam, DZ live, mixer, join esports org, join call of duty team, join lol team, set up obs, gaming, youtube thumbnails, gamer vlog, transitions/sfx/gfx, revenue, streams for gamers, esports revenue, lvlupdojo, lvlup, dojo, lvl up dojo, level up dojo, Gamer, Sensei, Dojo, Madness, Boomeo, streaming, youtuber, esports, Erho, katlife, kat_life, streamer erho, streamer,",
	"description" => "LVLUP Dojo is an online gaming community and video learning platform for competitive gamers, streamers and content creators. We partner with the top Twitch & Mixer broadcasters, YouTubers, Professional Gamers and eSports experts to show how you can go full-time in gaming and build a career that you love.",
	"image" => "/img/dojo-red-block.jpg",
	"social" => [
		"google" => '114596152161805516920',
		"youtube" => 'UCfpHP-Gx4O_ntwdPD7kS3FA',
		"twitter" => 'LVLUPDojo',
		"facebook" => 'lvlupdojo',
		"linkedin" => 'lvlupdojo',
		"twitch" => 'lvlupdojo',
		"instagram" => 'lvlupdojo',
		"discord" => 'lvlupdojo'
	]
];

if(file_exists(ROUTING_MAP) && is_readable(ROUTING_MAP)) {
	$routingFile = file_get_contents(ROUTING_MAP);
	
	if(!empty($routingFile)) {
		$routingFile = json_decode($routingFile, true);
		
		if(!empty($routingFile)) {
			$senConfig->routes = $routingFile['routes'];
			$senConfig->remapped = $routingFile['remapped'];

			if(array_key_exists('pageDefaults', $routingFile)) {
				foreach($routingFile['pageDefaults'] as $key => $value) {
					$senConfig->pageDefaults[$key] = $value;
				}
			}
			
			if(array_key_exists('errorPage', $routingFile)) {
				$senConfig->errorPage = $routingFile['errorPage'];
			}
		}
	}
	
	unset($routingFile);
} else {
	echo "Routing File missing or unreadable!";
	
	exit;
}