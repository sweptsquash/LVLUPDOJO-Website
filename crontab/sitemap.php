<?php

ini_set('max_execution_time', 0);
ini_set('date.timezone', 'UTC'); 
	
if (!defined('ABSPATH')) {
	define('ABSPATH', dirname(__FILE__) . '/../');
}

require_once(ABSPATH.'init.php');

$SenFramework = new \SenFramework\SenFramework($senConfig);

class CronSitemap {
	
	public function __construct() {
        $time = date('c');

        $Courses    = new \SenFramework\Courses;

        $sitemap = '<?xml version="1.0" encoding="UTF-8"?>
                    <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
                        <url>
                            <loc>https://www.lvlupdojo.com/</loc>
                            <lastmod>' . $time . '</lastmod>
                            <changefreq>always</changefreq>
                            <priority>1.00</priority>
                        </url>
                        <url>
                            <loc>https://www.lvlupdojo.com/courses/</loc>
                            <lastmod>' . $time . '</lastmod>
                            <changefreq>always</changefreq>
                            <priority>1.00</priority>
                        </url>';

        // Courses
        $publishedCourses = $Courses->getPublishedCourses(NULL, 1, 32);
        unset($publishedCourses['pagination'], $publishedCourses['meta']);

        foreach($publishedCourses as $key => $course) {
            $sitemap .= '<url>
                            <loc>https://www.lvlupdojo.com/courses/' . $course['slug'] . '/</loc>
                            <lastmod>' . date('c', strtotime($course['publishDateUnix'])) . '</lastmod>
                            <changefreq>yearly</changefreq>
                            <priority>0.95</priority>
                        </url>';
        }

        // Courses Categories
        $Categories = $Courses->getCategories();

        foreach($Categories as $key => $category) {
            $sitemap .= '<url>
                            <loc>https://www.lvlupdojo.com/courses/c/' . $category['slug'] . '/</loc>
                            <lastmod>' . $time . '</lastmod>
                            <changefreq>yearly</changefreq>
                            <priority>0.90</priority>
                        </url>';
        }

        // Course Mentors
        $mentors = $Courses->getMentors();
        unset($mentors['pagination'], $mentors['meta']);

        foreach($mentors as $key => $mentor) {
            $sitemap .= '<url>
                            <loc>https://www.lvlupdojo.com/courses/m/' . $mentor['slug'] . '/</loc>
                            <lastmod>' . date('c', strtotime($mentor['updated'])) . '</lastmod>
                            <changefreq>yearly</changefreq>
                            <priority>0.95</priority>
                        </url>';
        }


                        $sitemap .= '
                        <url>
                            <loc>https://www.lvlupdojo.com/about-us/</loc>
                            <lastmod>2018-08-31T00:00:00+00:00</lastmod>
                            <changefreq>yearly</changefreq>
                            <priority>0.95</priority>
                        </url>
                        <url>
                            <loc>https://www.lvlupdojo.com/pricing/</loc>
                            <lastmod>2018-08-31T00:00:00+00:00</lastmod>
                            <changefreq>yearly</changefreq>
                            <priority>0.95</priority>
                        </url>
                        <url>
                            <loc>https://www.lvlupdojo.com/terms-and-conditions/</loc>
                            <lastmod>2018-08-31T00:00:00+00:00</lastmod>
                            <changefreq>yearly</changefreq>
                            <priority>0.95</priority>
                        </url>
                        <url>
                            <loc>https://www.lvlupdojo.com/privacy-policy/</loc>
                            <lastmod>2018-08-31T00:00:00+00:00</lastmod>
                            <changefreq>yearly</changefreq>
                            <priority>0.95</priority>
                        </url>
                        <url>
                            <loc>https://www.lvlupdojo.com/cookie-policy/</loc>
                            <lastmod>2018-08-31T00:00:00+00:00</lastmod>
                            <changefreq>yearly</changefreq>
                            <priority>0.95</priority>
                        </url>
                        <url>
                            <loc>https://www.lvlupdojo.com/contact-us/</loc>
                            <lastmod>2018-08-31T00:00:00+00:00</lastmod>
                            <changefreq>never</changefreq>
                            <priority>0.50</priority>
                        </url>';


        $sitemap .= '</urlset>';
		
		$fp = fopen(ABSPATH.'sitemap.xml', 'w');
		fwrite($fp, $sitemap);
		fclose($fp);

		unset($fp, $sitemap, $Courses);
		
		exit;

    }
}

$sitemap = new CronSitemap();