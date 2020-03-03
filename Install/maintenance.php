<?php
	header($_SERVER['SERVER_PROTOCOL']." 503 Service Temporarily Unavailable");
	header("Status: 503 Service Temporarily Unavailable");
	header("Retry-After: ".gmdate('D, d M Y H:i:s', mktime(22,0,0,9,2,2018)).' GMT');
?>
<!doctype html>
<html lang="en">
    <head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="dns-prefetch" href="https://www.google.com" />
		<link rel="dns-prefetch" href="https://www.google-analytics.com" />
		<link rel="dns-prefetch" href="https://connect.facebook.net" />
		<link rel="dns-prefetch" href="https://cdnjs.cloudflare.com" />
		<link rel="dns-prefetch" href="https://player.vimeo.com" />
		<link rel="preload" href="/fonts/fa-brands-400.woff2" as="font" />
		<link rel="preload" href="/fonts/fa-solid-900.woff2" as="font" />
		<link rel="preload" href="/fonts/Poppins-Latin-700.woff2" as="font" />
		<title>Site Maintenance &bull; LVLUP Dojo</title>
		<link rel="apple-touch-icon" sizes="57x57" href="/img/Icon/apple-icon-57x57.png">
		<link rel="apple-touch-icon" sizes="60x60" href="/img/Icon/apple-icon-60x60.png">
		<link rel="apple-touch-icon" sizes="72x72" href="/img/Icon/apple-icon-72x72.png">
		<link rel="apple-touch-icon" sizes="76x76" href="/img/Icon/apple-icon-76x76.png">
		<link rel="apple-touch-icon" sizes="114x114" href="/img/Icon/apple-icon-114x114.png">
		<link rel="apple-touch-icon" sizes="120x120" href="/img/Icon/apple-icon-120x120.png">
		<link rel="apple-touch-icon" sizes="144x144" href="/img/Icon/apple-icon-144x144.png">
		<link rel="apple-touch-icon" sizes="152x152" href="/img/Icon/apple-icon-152x152.png">
		<link rel="apple-touch-icon" sizes="180x180" href="img/Icon/apple-icon-180x180.png">
		<meta name="apple-mobile-web-app-title" content="LVLUp Dojo" />
		<link rel="icon" type="image/png" sizes="192x192"  href="/img/Icon/android-icon-192x192.png">
		<link rel="icon" type="image/png" sizes="32x32" href="/img/Icon/favicon-32x32.png">
		<link rel="icon" type="image/png" sizes="96x96" href="/img/Icon/favicon-96x96.png">
		<link rel="icon" type="image/png" sizes="16x16" href="/img/Icon/favicon-16x16.png">
		<link rel="icon" href="/favicon.ico" type="image/x-icon" />
		<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
		<link rel="shortcut icon" href="/favicon.ico" type="image/ico" />
		<link rel="manifest" href="/manifest.json">
		<meta name="application-name" content="LVLUp Dojo" />
		<meta name="msapplication-TileColor" content="#131010">
		<meta name="msapplication-TileImage" content="/img/Icon/ms-icon-144x144.png">
		<meta name="theme-color" content="#131010">
		<meta name="author" content="LVLUP Dojo" />
		<link rel="stylesheet" href="/css/bootstrap-4.min.css?v=4.0.0" type="text/css" media="all" />
		<link rel="stylesheet" href="/css/application.css?v=0.1.1" type="text/css" media="all" />
		<link rel="stylesheet" href="/css/application.site.css?v=0.5.2" type="text/css" media="all" />
		<script src="/js/pace.js" type="text/javascript" async></script>
		<!-- Facebook Pixel Code -->
			<script>
				!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
				n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
				n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
				t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
				document,'script','https://connect.facebook.net/en_US/fbevents.js');
				fbq('init', '1597867147179722');
				fbq('track', 'PageView');
			</script>
		<!-- End Facebook Pixel Code -->
	</head>

	<body>
		<!-- Facebook Pixel Code -->
			<noscript>
				<img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=1597867147179722&ev=PageView&noscript=1" alt="" />
			</noscript>
		<!-- End Facebook Pixel Code -->

		<header class="page-nav fixed-top">
			<div class="navbar-social">
				<div class="container">
					<div class="row">
						<div class="col-md-12 text-center">
							<a href="https://www.facebook.com/lvlupdojo" class="nav-link" title="Facebook" rel="noopener">
								<i class="fab fa-facebook-f"></i>
							</a><a href="https://www.twitter.com/LVLUPDojo" class="nav-link" title="Twitter" rel="noopener">
								<i class="fab fa-twitter"></i>
							</a><a href="https://www.twitch.tv/lvlupdojo" class="nav-link" title="Twitch" rel="noopener">
								<i class="fab fa-twitch"></i>
							</a><a href="https://www.youtube.com/channel/UCfpHP-Gx4O_ntwdPD7kS3FA" class="nav-link" title="YouTube" rel="noopener">
								<i class="fab fa-youtube"></i>
							</a><a href="https://www.instagram/lvlupdojo" class="nav-link" title="Instagram" rel="noopener">
								<i class="fab fa-instagram"></i>
							</a><a href="https://plus.google.com/114596152161805516920" class="nav-link" title="Google Plus" rel="noopener">
								<i class="fab fa-google-plus-g"></i>
							</a><a href="https://discord.gg/lvlupdojo" class="nav-link" title="Discord" rel="noopener">
								<i class="fab fa-discord"></i>
							</a>
						</div>
					</div>
				</div>
			</div>
		</header>

		<section class="banner header-shadow" style="background-image:url(/img/Ninja-B12.png);">
			<div class="container">
				<div class="row justify-content-center">
					<div class="col-9 text-center">
						<img src="/img/lvlup-dojo-banner-logo.png" alt="" class="mb-2" style="width: 50%;height: auto;" width="" height="">
						<h2 class="mb-0">Sorry for the inconvenience but we&rsquo;re performing some maintenance at the moment.</h2>
					</div>
				</div>
			</div>
		</section>
		<section class="promo bg-dark-grey">
			<div class="container">
				<div class="row">
					<div class="col">
						<h3 class="mb-0">In The News</h3>
					</div>
				</div>
			</div>
		</section>		
		<div class="container mt-3 mb-5">
			<div class="row justify-content-center">
				<div class="col-xs-4 col-sm-4 col-md-2 text-center">
					<a href="https://www.forbes.com/sites/quora/2016/11/30/is-it-possible-to-get-paid-to-play-video-games/#3661c4ba6e5a" target="_blank" rel="noopener" title="Forbes">
						<img src="/img/references/forbes-01.png" alt="Forbes">
					</a>
				</div>
				<div class="col-xs-4 col-sm-4 col-md-2 text-center">
					<a href="https://www.inc.com/matthew-jones/this-startup-wants-to-solve-the-biggest-problem-in-esports.html" target="_blank" rel="noopener" title="Inc.">
						<img src="/img/references/Inc-01.png" alt="Inc.">
					</a>
				</div>
				<div class="col-xs-4 col-sm-4 col-md-2 text-center">
					<a href="https://www.influencive.com/5-crucial-lessons-digital-entrepreneurs-can-learn-professional-gamers/" target="_blank" rel="noopener" title="Influencive">
						<img src="/img/references/influencive-01.png" alt="Influencive">
					</a>
				</div>
				<div class="col-xs-6 col-sm-6 col-md-2 text-center">
					<a href="https://www.huffingtonpost.com/george-beall/5-reasons-parents-dont-co_b_13362488.html" target="_blank" rel="noopener" title="The Huffington Post">
						<img src="/img/references/huffpo-01.png" alt="The Huffington Post">
					</a>
				</div>
			</div>
		</div>
		<section class="bg-red diagonal-open-dark diagonal-close-dark text-dark py-5">
			<div class="container">
				<div class="row justify-content-center">
					<div class="col-6 text-center">
						<a href="https://www.udemy.com/user/lvlup-dojo/" class="btn btn-lg btn-outline" target="_blank" rel="noopener">
							Udemy Courses
						</a> 
						<a href="https://discord.gg/lvlupdojo" class="btn btn-lg btn-outline" target="_blank" rel="noopener">
							Join Our Discord
						</a>
					</div>
				</div>
			</div>
		</section>

		<footer class="footer">
			<div class="footnote text-center">
				<p>&copy; <?php echo date("Y"); ?> LVLUP Dojo. All Rights Reserved.</p>
			</div>
		</footer>

		<script type="text/javascript" src="/js/jquery-3.2.1.min.js"></script>
		<script type="text/javascript" src="/js/jquery-ui-1.12.1.min.js"></script>
		<script type="text/javascript" src="/js/bootstrap.bundle.min.js?v=4.0.0"></script>
		<script src="https://platform.twitter.com/widgets.js" async charset="utf-8"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/3.0.3/cookieconsent.min.js" async></script>
		<script async src="https://www.googletagmanager.com/gtag/js?id=UA-79934106-1"></script>
		<script type="text/javascript" nonce="YzY1NjQ3NTQyYWRiNDliODgxOTMwNDkzYzhiZjVmZTMg">
			function handleWindow() {
				var body = document.querySelector('body');

				if (window.innerWidth > body.clientWidth + 5) {
					body.classList.add('has-scrollbar');
					body.setAttribute('style', '--scroll-bar: ' + (window.innerWidth - body.clientWidth) + 'px');
				} else {
					body.classList.remove('has-scrollbar');
				}
			}

			handleWindow();

			$(function() {
				$(window).resize(function() {
					handleWindow();
				});

				if($(window).scrollTop() > 60) {
					$('.page-nav').addClass('page-nav-solid');
				}

				$(window).scroll(function() {
					if ($(this).scrollTop() <= 60) {
						$('.page-nav').removeClass('page-nav-solid');
					} else {
						$('.page-nav').addClass('page-nav-solid');
					}
				});
			});

			window.cookieconsent.initialise({
				"palette": {
					"popup": {
					"background": "#2a333e",
					"text": "#ffffff"
					},
					"button": {
					"background": "#2776dc",
					"text": "#ffffff"
					}
				},
				"content": {
					"message": "We use cookies to personalise your experience and ads on this site &amp; others. ",
					"link": "For more info, click here.",
					"href": "https://www.lvlupdojo.com/cookie-policy/"
				}
			});
			
			window.dataLayer = window.dataLayer || [];
			function gtag(){dataLayer.push(arguments);}
			gtag('js', new Date());

			gtag('config', 'UA-79934106-1', {'user_id': '0'});
			
			if (window.performance) {
				var timeSincePageLoad = Math.round(performance.now());
				
				gtag('event', 'timing_complete', {
					'name': 'load',
					'value': timeSincePageLoad
				});
			}
			
			!function(e,t,n,s,u,a){e.twq||(s=e.twq=function(){s.exe?s.exe.apply(s,arguments):s.queue.push(arguments);
			},s.version='1.1',s.queue=[],u=t.createElement(n),u.async=!0,u.src='//static.ads-twitter.com/uwt.js',
			a=t.getElementsByTagName(n)[0],a.parentNode.insertBefore(u,a))}(window,document,'script');

			twq('init','nz6qa');
			twq('track','PageView');
		</script>
	</body>
</html>
		