<?php

namespace SenFramework\Routing;

class Routing 
{
	private $originalRequest;
	private $originalQuery;
	public $route;
	public $query;
	public $data;
	
	private $prefix;
	private $isAdmin = false;
	
	public function __construct() 
	{
		global $request, $logger;
		
		$this->originalRequest = $request->server('REQUEST_URI');
		
		$uri = null;
		
		if(($p = strpos($this->originalRequest, '?')) !== false)  {
			$q = substr($this->originalRequest, ($p + 1));
				
			if(!empty($q)) {
				$qa = explode('&amp;', $q);
				
				foreach($qa as $key => $value) {
					$k = explode('=', $value);
					
					if(!empty($k)) {
						$this->query[$k[0]] = urldecode($k[1]);	
					}
					
					unset($k);
				}
				
				unset($qa);
			}
			
			$uri = substr($this->originalRequest, 0, $p); // trim query string
			
			unset($p);
		}
		
		$this->route = array_values(array_filter(explode('/', strtolower(((!empty($uri)) ? $uri : $this->originalRequest)))));

		if(isset($this->route[0]) && $this->route[0] == 'admin') {
			$this->isAdmin = true;
		}
		
		unset($uri);
		
		if(DEVELOP) {
			$this->prefix = 'development';
		} else {
			switch($request->server('HTTP_HOST')) {
				default:
					$this->prefix = 'www';
				break;
			}
		}
		
		self::handle();
	}
	
	public function generateMetadata($title = NULL, $usesep = true, $keywords = NULL, $description = NULL, $image = NULL, $author = NULL)
	{
		global $config, $senConfig, $request, $user, $lang;
		
		$security = new \SenFramework\Security();

		$ip = \SenFramework\SenFramework::getIP();

		if($ip != 'UNKNOWN') {
			$country = \geoip_country_code_by_name($ip);
			
			// Fix for new IPs
			if(empty($country)) {
				$country = 'US';
			}
		} else {
			$country = 'US';
		}
		
		$path = getcwd();
		
		if(DEVELOP) {
			$replace = '/home/lvlupdojo/public_html/development.lvlupdojo.com';
		} else {
			switch($request->server('HTTP_HOST')) {
				default:
					$replace = '/home/lvlupdojo/public_html';
				break;
			}
		}
		
		$headers = [			
			"rooturi" => "https://" . ((DEVELOP) ? "development.lvlupdojo.com" : "www.lvlupdojo.com"),
			"baseuri" => rtrim("https://" . ((DEVELOP) ? "development.lvlupdojo.com" : "www.lvlupdojo.com")."/" . str_replace($replace, '', $path), '/'),
			"cdnuri" => CDNURI,
			"dev" => DEVELOP,
			"platforms"	=> [
				'twitch' => SOCIAL_TWITCH,
				'discord' => SOCIAL_DISCORD,
				'facebook' => SOCIAL_FACEBOOK,
				'twitter' => SOCIAL_TWITTER
			],
			"page" => [
				"name" => $senConfig->pageDefaults['name'],
				"nav" => $this->route[0],
				"title" => (!empty($title)) ? (($usesep) ? $title . ' ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'] : $title) : $senConfig->pageDefaults['name'],
				"keywords" => (!empty($keywords)) ? $keywords: $senConfig->pageDefaults['keywords'],
				"description" => (!empty($description)) ? $description : $senConfig->pageDefaults['description'],
				"image" => ((!empty($image)) ? $image : $senConfig->pageDefaults['image']),
				"url" => ltrim($this->originalRequest, '/'),
				"author" =>  ((!empty($author)) ? $author : $senConfig->pageDefaults['author']),
				"year" => date("Y"),
				"social" => [
					"links" => NULL
				]
			],
			"user" => $user->data,
			"countries" => \SenFramework\SenFramework::$countries,
			"visitors_country" => $country,
			"config"			=> [
				'recaptcha_key' 	=> CAPTCHA,
				'twitch'			=> TWITCH,
				'twitch_dev'		=> TWITCH_DEV,
				'stripe_key'		=> STRIPE_KEY,
				'stripe_key_dev'	=> STRIPE_KEY_DEV,
				'paypal_key'		=> PAYPAL_KEY,
				'paypal_key_dev'	=> PAYPAL_KEY_DEV
			]
		];

		$headers['config'] = array_merge($config, $headers['config']);
		
		if($user->data['is_registered'] !== true) {
			if($this->params[0] !== 'login' && $this->query['method'] !== 'ajax') {
				$headers['userForm'] = [
					'login' 	=> $security->generate_csrf_token('Login'),
					'register' 	=> $security->generate_csrf_token('Register')
				];
			}
		}

		$headers['userForm']['newsletter'] = $security->generate_csrf_token('Newsletter');
		
		$link = [
			"google" => 'https://plus.google.com/',
			"youtube" => 'https://www.youtube.com/channel/',
			"twitter" => 'https://www.twitter.com/',
			"facebook" => 'https://www.facebook.com/',
			"linkedin" => 'https://www.linkedin.com/company/',
			"twitch" => 'https://www.twitch.tv/',
			"instagram" => 'https://www.instagram.com/',
			"discord" => 'https://discord.gg/'
		];
		
		if(!empty($senConfig->pageDefaults['social']) && is_array($senConfig->pageDefaults['social'])) {
			foreach($senConfig->pageDefaults['social'] as $key => $value) {
				if(!empty($value)) {
					$headers['page']['social'][$key] = ($key === 'twitter') ? $value : $link[$key] . $value;
					$headers['page']['social']['links'] .= '"' . $link[$key] . $value . '/",';
				}
			}

			$headers['page']['social']['links'] = rtrim($headers['page']['social']['links'], ',');
		}
		
		return $headers;
	}
	
	public function handle()
	{
		global $request, $logger, $twig, $senConfig;
		
		if(!empty($senConfig->routes)) {
			$page = NULL;
			
			if(empty($this->route[0])) {
				$page = 'home';
			} else if(array_key_exists($this->route[0], $senConfig->routes)) {
				$page = $this->route[0];
			} else if(array_key_exists($this->route[0], $senConfig->remapped)) {
				$queryStr = '';

				if(!empty($this->query)) {
					$queryStr = '?';

					foreach($this->query as $key => $value) {
						$queryStr .= $key.'='.urlencode($value).'&';
					}

					$queryStr = rtrim($queryStr, '&');
				}

				header("Location: https://" . ((DEVELOP) ? "development.lvlupdojo.com" : "www.lvlupdojo.com") . "/".(($senConfig->remapped[$this->route[0]] !== 'home') ? $senConfig->remapped[$this->route[0]]."/".$queryStr : NULL), true, 301);
				exit;
				
				//$page = $senConfig->remapped[$this->route[0]];
			}
			
			if(!empty(trim($page))) {
				if($page === 'api') {
					$this->data['json'] = true;
				} else {
					$this->data = self::generateMetadata(
						$senConfig->routes[$page]['title'], 
						$senConfig->routes[$page]['title_seperator'], 
						$senConfig->routes[$page]['keywords'], 
						$senConfig->routes[$page]['description']
					);
					
					$this->data['template'] = $senConfig->routes[$page]['template'];
					$this->data['template_folder'] = $senConfig->routes[$page]['template_folder'];
				}
				
				if(!empty(trim($senConfig->routes[$page]['controller']))) {
					$controller = "\\SenFramework\\" . $senConfig->routes[$page]['controller'];
					
					$control = new $controller($this->route, $this->query);
					
					if(!empty($control->data)) {
						if(isset($control->data['override'])) {
							foreach($control->data['override'] as $key => $value) {
								if($key !== 'json') {
									$this->data['page'][$key] = $value;
								} else {
									$this->data['json'] = $value;
								}
							}
							
							unset($control->data['override']);
						}						
						
						$this->data = array_merge($this->data, $control->data);
						
						if(!empty($control->data['triggererror'])) {
							$this->handleError($control->data['triggererror']);
						}
					}
				}
			} else {
				// Remapped Old URLs that contained Query Strings
				switch($this->route[0]) {
					default:
						self::handleError('404');
					break;

					case"ninja":
						header($request->server("SERVER_PROTOCOL") . " 301 Moved Permanently"); 
						header("Location: /courses/growing-your-stream/", true, 301);
						exit;
					break;

					case"build_a_team":
						header($request->server("SERVER_PROTOCOL") . " 301 Moved Permanently"); 
						header("Location: /courses/build-a-team/", true, 301);
						exit;
					break;

					case"build_a_personal_brand":
						header($request->server("SERVER_PROTOCOL") . " 301 Moved Permanently"); 
						header("Location: /courses/build-a-personal-brand/", true, 301);
						exit;
					break;

					case"manipulate_the_meta":
						header($request->server("SERVER_PROTOCOL") . " 301 Moved Permanently"); 
						header("Location: /courses/manipulating-the-meta/", true, 301);
						exit;
					break;
						
					case"course":
						$name = \SenFramework\SenFramework::createURL($this->query['Name']);
						
						if(!empty($name)) {
							$Courses = new \SenFramework\Courses;
							
							$result = $Courses->getCourse($name);
							
							if(!empty($result)) {
								header($request->server("SERVER_PROTOCOL") . " 301 Moved Permanently"); 
								header("Location: /courses/".$result['slug']."/", true, 301);
								
								exit;
							} else {
								self::handleError('404');
							}
						} else {
							header($request->server("SERVER_PROTOCOL") . " 301 Moved Permanently");
							header("Location: /courses/", true, 301);	
							exit;
						}						
					break;

					// Old Profile links handled by Profile Controller.
				}
			}
		} else {
			self::handleError('500');
		}
	}
	
	public function handleError($error = '404')
	{
		global $request, $logger, $user;
		
		switch($error) {
			default:
				
			break;
				
			case"400":
				header($request->server("SERVER_PROTOCOL") . " 400 Bad Request", true, 400);

				$this->data = self::generateMetadata("Setup Misconfiguration");
				
				$this->data['title'] = '400 Bad Request';
				$this->data['type'] = 400;
				$this->data['message'] = 'Oh Dear, A Bad Request was made. Sorry about that we\'ve logged it for our admins to look at.';

				if($this->isAdmin) {
					$this->data['template_folder'] = 'acp';
				}

				$this->data['template'] = 'error';
			break;
				
			case"404":
				header($request->server("SERVER_PROTOCOL") . " 404 Not Found", true, 404);
				
				$this->data = self::generateMetadata("Oops, This Page Could Not Be Found!");
				
				$this->data['title'] = 'Oops, This Page Could Not Be Found!';
				$this->data['type'] = 404;
				$this->data['message'] = 'The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.';

				if($this->isAdmin) {
					$this->data['template_folder'] = 'acp';
				}

				$this->data['template'] = 'error';
			break;
				
			case"500":
				header($request->server("SERVER_PROTOCOL") . " 500 Internal Server Error", true, 500);
				
				$this->data = self::generateMetadata("Internal Server Error");
				
				$this->data['title'] = 'Internal Server Error';
				$this->data['type'] = 500;
				$this->data['message'] = 'The web server is returning an internal error.';

				if($this->isAdmin) {
					$this->data['template_folder'] = 'acp';
				}

				$this->data['template'] = 'error';
			break;
		}
		
		$ip = \SenFramework\SenFramework::getIP();
		
		\SenFramework\SenFramework::addLogEntry('[Error] - [' . $error . '] - [User: ' . $user->data['user_id'] . ' | IP: ' . $ip . '] - Page Request - ' . $this->originalRequest);
	}
	
	public function renderPage()
	{
		global $request, $logger, $twig;
		
		if($this->data['json']) {
			header('Content-Type: application/json');
			
			$page = json_encode($this->data['response'], JSON_UNESCAPED_UNICODE);
		} else if($this->data['xml']) {
			header('Content-Type: text/xml');
			
			$page = $this->data['response'];
		} else {
			if($this->data['template'] !== NULL && file_exists(TPL_DIR . ((!empty($this->data['template_folder'])) ? $this->data['template_folder'] . '/' : NULL) . $this->data['template'] . '.html')) {
				$page = $twig->render(((!empty($this->data['template_folder'])) ? $this->data['template_folder'] . '/' : NULL) . $this->data['template'] . '.html', $this->data);
			} else {
				$this->HandleError('404');

				$page = $twig->render(((isset($senConfig->errorPage)) ? $senConfig->errorPage : (($this->isAdmin) ? 'acp/' : NULL).'error').'.html', $this->data);
			}
		}
		
		echo $page;
	}
}