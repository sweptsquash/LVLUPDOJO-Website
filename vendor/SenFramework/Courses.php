<?php

namespace SenFramework;

class Courses extends \SenFramework\DB\Database {
	
	public $url = 'courses';
	private $Billing;

	public function __construct() {
		$this->Billing = new \SenFramework\Billing();
	}
	
	/*
	 *	@param $slug int|string Course ID or Slug
	 *  @return array
	 */
	public function getCourse($slug = NULL) {
		global $request, $cart;

		$data = NULL;
		
		if(!empty($slug)) {
			if(is_int($slug)) {
				$Where = "Courses.id='".parent::mres($slug)."'";
			} else {
				$Where = "Courses.slug='".parent::mres($slug)."'";
			}

			$sql = parent::mq("SELECT 
				Courses.id,
				Courses.name,
				Courses.slug,
				Courses.excerpt,
				Courses.description,
				Courses.keywords,
				Courses.thumbnail,
				Courses.banner,
				Courses.rating,
				Courses.publishDate,
				Courses.published,
				Courses.categories,
				Courses_Mentors.id as MentorID,
				Courses_Mentors.user_id as MentorUID,
				Courses_Mentors.name as MentorName,
				Courses_Mentors.slug as MentorSlug,
				Courses_Mentors.avatar as MentorAvatar,
				Courses_Mentors.description as MentorAbout,
				Courses_Mentors.keywords as MentorKeywords
			FROM
				Courses
			LEFT JOIN
				Courses_Mentors
			ON
				Courses_Mentors.id=Courses.mentor
			WHERE 
				".$Where);
			
			if(!empty($sql) && $sql->num_rows > 0) {
				$data = parent::mfa($sql);

				$code = $request->variable('discount', '', \SenFramework\Request\request_interface::GET);
			
				if(!empty($code)) {
					$_SESSION['Discount'] = $code;
				}

				$data = array_merge($data, self::getCourseMaterial($data['id']));
				
				$data['reviewCount'] = 0;
				
				$hours = floor($data['total'] / 3600);
				$mins = floor($data['total'] / 60 % 60);
				
				$data['hours'] = number_format($hours.'.'.$mins, 1);

				$data['mentor'] = [
					'mid'		=> $data['MentorID'],
					'id'		=> $data['MentorUID'],
					'name'		=> $data['MentorName'],
					'slug'  	=> $data['MentorSlug'],
					'avatar' 	=> $data['MentorAvatar'],
					'about'		=> $data['MentorAbout'],
					'keywords'	=> $data['MentorKeywords']
				];
				
				unset($data['total'], $data['MentorName'], $data['MentorSlug'], $data['MentorAvatar'], $data['MentorAbout']);

				$data['owned'] = $this->Billing->productOwned($data['id']);
				$data['inCart'] = $cart->itemInCart($data['id']);

				if($data['owned']) {
					$data['progress'] = self::getCourseProgress($data['id']);
				} else {
					$data['cost'] = self::getCoursePricing($data['id']);
				}
			}
		}
		
		return $data;
	}

	/**
	 * Get a specified courses pricing.
	 *
	 * @param integer $courseID
	 * @return array
	 */
	public function getCoursePricing(int $courseID = 0): array {
		$data = [];

		if($courseID !== 0) {
			$sql = parent::mq("SELECT * FROM Courses_Pricing WHERE currency='usd' AND course_id='" . parent::mres($courseID) . "'");

			if($sql->num_rows > 0) {
                $row = parent::mfa($sql);

                $data = [
                    'price'         => number_format($row['price'], 2),
                    'currency'      => $row['currency'],
                    'currency_code' => '&dollar;'
				];
				
				list($number, $decimal) = explode('.', (string)$data['price']);

				if($decimal === '00') {
					$data['price'] = $number;
				}

				unset($number, $decimal);

                $discount = self::getCourseDiscount($row['course_id'], $data['price']);

                if(!empty($discount)) {
                    $data = array_merge($data, $discount);
                }
            }
		}

		return $data;
	}

	public function getCourseDiscount(int $courseID = 0, float $originalCost = 0): array {
        global $request, $user;

		$data = [];

        if($courseID !== 0) {
			$discount = [];

            $sql = parent::mq("SELECT 
                * 
            FROM 
                Courses_Pricing_Discounts 
            WHERE
				course_id='".parent::mres($courseID)."' 
			AND 
				code IS NULL
            AND
            (
                start <= NOW() 
            AND 
                end >= NOW()
            ) LIMIT 1");

            if($sql->num_rows > 0) {
                $row = parent::mfa($sql);

                $data = [
                    'discount'              => $row['percentage'],
					'discount_value'        => number_format($originalCost - ($originalCost * ($row['percentage'] / 100)), 2),
					'discount_starts'		=> $user->format_date(strtotime($row['start']), $user->data['user_dateformat']),
					'doscount_starts_iso'	=> date('c', strtotime($row['start'])),
                    'discount_ends'         => $user->format_date(strtotime($row['end']), $user->data['user_dateformat']),
                    'discount_ends_iso'     => date('c', strtotime($row['end']))
				];
				
				if(!empty($discount)) {
					$percentage = $row['percentage'] + $discount['percentage'];

					$data['discount_value'] = number_format($originalCost - ($originalCost * ($percentage / 100)), 2); 
				}
            }
        }

        return $data;
	}
	
	private function getDiscountCode(string $discountCode): bool {
		if(!empty($discountCode)) {
			$sql = parent::mq("SELECT id FROM Courses_Pricing_Discounts WHERE code='".parent::mres($discountCode)."' AND end > NOW()");

			if($sql->num_rows > 0) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * Get Overall Course Progress
	 *
	 * @param int $courseID
	 * @return array
	 */
	public function getCourseProgress(int $courseID) {
		global $user;

		$data = [];
		
		if(!empty($courseID) && $user->data['user_id'] !== 1) {
			$sql = parent::mq("SELECT slug, duration, type FROM Courses_Materials WHERE course_id='".parent::mres($courseID)."' AND (type='1' OR type='2' OR type='4')");

			if($sql->num_rows > 0) {
				$courseDuration = $lessonsWatched = 0;

				$intro = NULL;

				while($row = parent::mfa($sql)) {
					$courseDuration += intval($row['duration']);

					if(empty($intro) && $row['type'] == 1) {
						$intro = $row['slug'];
					}
				}

				$sql = parent::mq("SELECT lesson_id, progress, timestamp FROM Courses_Progress WHERE user_id='".$user->data['user_id']."' AND course_id='".parent::mres($courseID)."' ORDER BY lesson_id ASC");

				if($sql->num_rows > 0) {
					while($row = parent::mfa($sql)) {
						$lessonsWatched += intval($row['timestamp']);

						$lsql = parent::mq("SELECT slug, duration FROM Courses_Materials WHERE id='".$row['lesson_id']."' AND course_id='".parent::mres($courseID)."'");

						if($lsql->num_rows > 0) {
							$lrow = parent::mfa($lsql);

							$data['lessonPercent'] = number_format((intval($row['timestamp']) / intval($lrow['duration'])) * 100, 2);

							if(floatval($row['progress']) < 1.00) {
								$data['lesson'] = $lrow['slug'];
							}
						}
					}
				} else {
					$data['new'] = true;
					$data['lesson'] = $intro;
				}
				
				if($courseDuration > 0) {
					$data['percent'] = number_format(($lessonsWatched / $courseDuration) * 100, 2);
				} else {
					$data['percent'] = 0;
				}
			}
		}

		return $data;
	}
	
	public function getCourseLesson($courseID = 0, $lesson = NULL) {
		$data = NULL;
		
		if(!empty($courseID) && !empty($lesson)) {
			$sql = parent::mq("SELECT * FROM Courses_Materials WHERE published='1' AND course_id='".parent::mres($courseID)."' AND slug='".parent::mres($lesson)."'");
		
			if(!empty($sql) && $sql->num_rows > 0) {
				$data = parent::mfa($sql);
				$data['orderNo'] = intval($data['orderNo']) - 1;

				$data['progress'] = self::getCourseLessonProgress($courseID, $data['id']);
				
				$data['books'] = NULL;
				
				$ssql = parent::mq("SELECT * FROM Courses_Materials WHERE published='1' AND parent_id='" . $data['id'] . "' AND course_id='".parent::mres($courseID)."' ORDER BY orderNo ASC");
				
				if(!empty($ssql) && $ssql->num_rows > 0) {
					while($rrow = parent::mfa($ssql)) {
						$data['books']['lesson'] = $rrow;
					}
				}

				$ssql = parent::mq("SELECT * FROM Courses_Materials WHERE published='1' AND parent_id='0' AND type='5' AND course_id='".parent::mres($courseID)."' ORDER BY orderNo ASC");
				
				if(!empty($ssql) && $ssql->num_rows > 0) {
					while($rrow = parent::mfa($ssql)) {
						$data['books']['course'] = $rrow;
					}
				}
			}
		}
		
		return $data;
	}

	/**
	 * getCourseLessonProgress
	 *
	 * @param int $courseID
	 * @param int $lessonID
	 * @return array
	 */
	public function getCourseLessonProgress(int $courseID, int $lessonID) {
		global $user;

		$data = [];
		
		if(!empty($courseID) && !empty($lessonID) && $user->data['user_id'] !== 1) {
			$sql = parent::mq("SELECT progress, timestamp FROM Courses_Progress WHERE user_id='".$user->data['user_id']."' AND course_id='".parent::mres($courseID)."' AND lesson_id='".parent::mres($lessonID)."'");

			if($sql->num_rows > 0) {
				$data = parent::mfa($sql);
				$data['percent'] = number_format($data['progress'] * 100, 2);
				$data['finished'] = (floatval($data['progress']) == 1.00) ? true : false;

				unset($data['progress']);
			}
		}

		return $data;
	}
	
	public function getCourseMaterial(int $courseID = 0): array {
		global $user;
		
		$data = array();
		
		$sql = parent::mq("SELECT id, name, slug, type, excerpt, thumbnail, free, duration, source, orderNo FROM Courses_Materials WHERE published='1' AND parent_id='0' AND course_id='".parent::mres($courseID)."' ORDER BY orderNo ASC");
		
		if(!empty($sql) && $sql->num_rows > 0) {
			$i = 0;
			
			$data['lessonWorkbook'] = 0;
			$data['courseWorkbook'] = 0;
			
			while($row = parent::mfa($sql)) {
				if($row['type'] == 1) {
					$data['intro'] = [
						'thumbnail' => $row['thumbnail'],
						'source' 	=> $row['source']
					];
				}
				
				$data['lessons'][$i] = $row;
				$data['lessons'][$i]['orderNo'] = intval($data['lessons'][$i]['orderNo']) - 1;
				
				unset($data['lessons'][$i]['free']);
				
				$hours = floor($row['duration'] / 3600);
				$mins = floor($row['duration'] / 60 % 60);
				$secs = floor($row['duration'] % 60);
				
				$data['lessons'][$i]['duration'] = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
				
				$data['total'] += $row['duration'];
				
				if($row['free'] == 1) {
					$data['lessons'][$i]['unlocked'] = true;
				} else {
					$data['lessons'][$i]['unlocked'] = $this->Billing->productOwned($courseID);
				}
				
				$type = intval($row['type']);
						
				if($type == 3) {
					$data['lessonWorkbook']++;
				} else if($type == 5) {
					$data['courseWorkbook']++;
				}
				
				$ssql = parent::mq("SELECT id, name, slug, type, source, free FROM Courses_Materials WHERE published='1' AND parent_id='" . $row['id'] . "' AND course_id='".parent::mres($courseID)."' ORDER BY orderNo ASC");
				
				if(!empty($ssql) && $ssql->num_rows > 0) {
					$x = 0;
					
					while($rrow = parent::mfa($ssql)) {
						$data['lessons'][$i]['resources'][$x] = $rrow;
						
						unset($data['lessons'][$i]['resources'][$x]['free']);
						
						if($rrow['free'] == 1) {
							$data['lessons'][$i]['resources'][$x]['unlocked'] = true;
						} else {
							$data['lessons'][$i]['resources'][$x]['unlocked'] = $this->Billing->productOwned($courseID);
						}
						
						$type = intval($rrow['type']);
						
						if($type == 3) {
							$data['lessonWorkbook']++;
						} else if($type == 5) {
							$data['courseWorkbook']++;
						}
						
						$x++;
					}
				}
				
				$i++;
			}
		}
		
		return $data;
	}

	public function getCourses($attributes = NULL, $offset = 1, $limit = 32) {
		global $user;

		$Order = 'Courses.publishDate DESC ';
		$Where = NULL;
		
		if(!empty($attributes)) {
			if(array_key_exists('order', $attributes)) {
				
				
				unset($attributes['order']);
			} else {
				$Order = 'Courses.publishDate DESC ';
			}
			
			foreach($attributes as $key => $value) {
				$Where .= $value['operator'] . " ";
				
				switch($value['method']) {
					default:
					case"EQUALS":
					case"EXACT":
						$Where .= $key."='".parent::mres($value['value'])."' ";
					break;
						
					case"LIKE":
						$Where .= $key." LIKE '%".parent::mres($value['value'])."%'";
					break;
						
					case"FIND_IN_SET":
						$Where .= "FIND_IN_SET('".parent::mres($value['value'])."', ".$key.") ";
					break;
				}
			}
		}
		
		$offset = intval(parent::mres($offset));
		
		// Ensure user can't trigger sql error
		if($offset <= 0) {
			$offset = 1;
		}
		
		$total = 0;
		
		$sqlCount = parent::mq("SELECT 
				COUNT(*) as total
			FROM 
				Courses 
			".((!empty($Where)) ? " WHERE ".$Where : "")."
			ORDER BY
				".$Order);
		
		if(!empty($sqlCount) && $sqlCount->num_rows > 0) {
			$results = parent::mfa($sqlCount);
			$total = $results['total'];
		}
		
		$sql = parent::mq("SELECT 
			Courses.id,
			Courses.name,
			Courses.slug,
			Courses.excerpt,
			Courses.thumbnail,
			Courses.categories,
			Courses.published,
			Courses.publishDate,
			Users.username AS mentor
		FROM
			Courses
		LEFT JOIN
			Users
		ON
			Users.user_id=Courses.mentor
		".((!empty($Where)) ? " WHERE ".$Where : "")."
		ORDER BY
			".$Order."
		LIMIT " . (($offset - 1) * parent::mres($limit)) . " , " . parent::mres($limit));
		
		$i = 0;
		
		if(!empty($sql) && $sql->num_rows > 0) {
			while ($row = parent::mfa($sql)) {				
				$data[$i] = $row;
				$data[$i]['publishDate'] = $user->format_date(strtotime($data[$i]['publishDate']), $user->data['user_dateformat']);				
				$data[$i]['categories'] = [];

				$data[$i]['owned'] = $this->Billing->productOwned($data[$i]['id']);

				if($data[$i]['owned']) {
					$data[$i]['progress'] = self::getCourseProgress($data[$i]['id']);
				} else {
					$data[$i]['cost'] = self::getCoursePricing($data[$i]['id']);
				}
				
				if(!empty($row['categories'])) {
					$categories = explode(',', $row['categories']);
					
					foreach($categories as $key => $category) {
						$data[$i]['categories'][] = self::getCategory((int)$category);
					}
				}
				
				$i++;		
			}
			
			$pagination = \SenFramework\SenFramework::pagination($offset, ceil($total / $limit), $this->url);

			$data['pagination'] = $pagination['html'];
			$data['meta'] = $pagination['meta'];
		}
		
		return $data;
	}
	
	public function getPublishedCourses($attributes = NULL, $offset = 1, $limit = 9) {
		global $user;

		$Order = 'Courses.publishDate DESC ';
		$Where = NULL;
		
		if(!empty($attributes)) {
			if(array_key_exists('order', $attributes)) {
				
				
				unset($attributes['order']);
			} else {
				$Order = 'Courses.publishDate DESC ';
			}
			
			foreach($attributes as $key => $value) {
				$Where .= $value['operator'] . " ";
				
				switch($value['method']) {
					default:
					case"EQUALS":
					case"EXACT":
						$Where .= $value['column']."='".parent::mres($value['value'])."' ";
					break;
						
					case"LIKE":
						$Where .= $value['column']." LIKE '%".parent::mres($value['value'])."%'";
					break;
						
					case"FIND_IN_SET":
						$Where .= "FIND_IN_SET('".parent::mres($value['value'])."', ".$value['column'].") ";
					break;
				}
			}
		}
		
		$offset = intval(parent::mres($offset));
		
		// Ensure user can't trigger sql error
		if($offset <= 0) {
			$offset = 1;
		}
		
		$total = 0;
		
		$sqlCount = parent::mq("SELECT 
				COUNT(*) as total
			FROM 
				Courses 
			WHERE
				Courses.published='1' ".$Where."
			ORDER BY
				".$Order);
		
		if(!empty($sqlCount) && $sqlCount->num_rows > 0) {
			$results = parent::mfa($sqlCount);
			$total = $results['total'];
		}
		
		$sql = parent::mq("SELECT 
			Courses.id,
			Courses.name,
			Courses.slug,
			Courses.excerpt,
			Courses.thumbnail,
			Courses.categories,
			Courses.publishDate,
			Users.username AS mentor
		FROM
			Courses
		LEFT JOIN
			Users
		ON
			Users.user_id=Courses.mentor
		WHERE
			Courses.published='1' ".$Where."
		ORDER BY
			".$Order."
		LIMIT " . (($offset - 1) * parent::mres($limit)) . " , " . parent::mres($limit));
		
		$i = 0;
		
		if(!empty($sql) && $sql->num_rows > 0) {
			while ($row = parent::mfa($sql)) {				
				$data[$i] = $row;

				$data[$i]['excerpt'] = rtrim($data[$i]['excerpt'], ' ').'...';

				$data[$i]['publishDate'] = $user->format_date(strtotime($data[$i]['publishDate']), $user->data['user_dateformat']);	
				$data[$i]['publishDateUnix'] = strtotime($data[$i]['publishDate']);
				$data[$i]['categories'] = [];

				$data[$i]['owned'] = $this->Billing->productOwned($data[$i]['id']);

				if($data[$i]['owned']) {
					$data[$i]['progress'] = self::getCourseProgress($data[$i]['id']);
				} else {
					$data[$i]['cost'] = self::getCoursePricing($data[$i]['id']);
				}
				
				if(!empty($row['categories'])) {
					$categories = explode(',', $row['categories']);
					
					foreach($categories as $key => $category) {
						$data[$i]['categories'][] = self::getCategory((int)$category);
					}
				}
				
				$i++;		
			}
			
			$pagination = \SenFramework\SenFramework::pagination($offset, ceil($total / $limit), $this->url);

			$data['pagination'] = $pagination['html'];
			$data['meta'] = $pagination['meta'];
		}
		
		return $data;		
	}
	
	public function getTags() {
		$data = NULL;
		
		$sql = parent::mq("SELECT id, keywords FROM Courses WHERE keywords <> ''");
		
		if(!empty($sql) && $sql->num_rows > 0) {
			$tagCount = $tagData = [];
			
			while($row = parent::mfa($sql)) {
				if(!empty($row['keywords'])) {
					$keywords = explode(',',$row['keywords']);
					
					foreach($keywords as $index => $value) {
						if(!empty($value)) {
							$tagCount[strtolower($value)] += 1;
							$tagData[strtolower($value)] = $value;
						}
					}
				}
			}
		}
		
		$sql = parent::mq("SELECT id, keywords FROM Courses_Materials WHERE keywords <> ''");
		
		if(!empty($sql) && $sql->num_rows > 0) {
			while($row = parent::mfa($sql)) {
				if(!empty($row['keywords'])) {
					$keywords = explode(',',$row['keywords']);

					foreach($keywords as $index => $value) {
						if(!empty($value)) {
							$tagCount[strtolower($value)] += 1;
							$tagData[strtolower($value)] = $value;
						}
					}
				}
			}
		}
		
		if(!empty($tagCount)) {
			arsort($tagCount);
			
			foreach($tagCount as $key => $count) {
				$data[$key] = [
					'name' => $tagData[$key],
					'count' => $count
				];
			}
		}

		return $data;
	}
	
	public function getCategory($category = 0, $includeCount = false) {
		$data = NULL;
		
		if(is_int($category)) {
			$Where = "id='".parent::mres($category)."'";
		} else {
			$Where = "slug='".parent::mres($category)."'";
		}
		
		$sql = parent::mq("SELECT * FROM Categories WHERE ".$Where);
		
		if(!empty($sql) && $sql->num_rows > 0) {
			$data = parent::mfa($sql);
		}
		
		return $data;
	}
	
	public function getCategories($includeCount = false) {
		$data = NULL;
		
		$i = 0;
		
		$sql = parent::mq("SELECT * FROM Categories WHERE parent='1' ORDER BY LOWER(name) ASC");
		
		if(!empty($sql) && $sql->num_rows > 0) {
			while($row = parent::mfa($sql)) {
				$data[$i] = $row;
				
				if($includeCount) {
					$data[$i]['count'] = self::getCategoryProductCount($row['id']);
				}
				
				$csql = parent::mq("SELECT * FROM Categories WHERE parent='0' AND parent_id='".$row['id']."' ORDER BY LOWER(name) ASC");
		
				if(!empty($csql) && $csql->num_rows > 0) {
					$x = 0;
					
					while($crow = parent::mfa($csql)) {
						$data[$i]['children'][$x] = $crow;
						
						if($includeCount) {
							$data[$i]['children'][$x]['count'] = self::getCategoryProductCount($crow['id']);
						}
						
						$x++;
					}
				}
				
				$i++;
			}
		}
		
		return $data;
	}
	
	public function getCategoryProductCount($category = 0) {
		$count = 0;
					
		$SqlCount = parent::mq("SELECT COUNT(*) as total FROM Courses WHERE Courses.published='1' AND FIND_IN_SET('".parent::mres($category)."', Courses.categories)");

		if(!empty($SqlCount) && $SqlCount->num_rows > 0) {
			$results = parent::mfa($SqlCount);
			$count = $results['total'];
		}
		
		return $count;
	}

	public function getCourseWorkbook($courseID, string $book) {
		$data = [];

		if(!empty($courseID) && !empty($book)) {
			if($book == 'course-book') {
				$Where = "cm.type='5'";
			} else {
				$book = explode('-', $book, 3);

				$Where = "cm.slug='".parent::mres($book[2])."' AND cm.type='3'";
			}

			if(!empty($Where)) {
				if(is_int($courseID)) {
					$Where .= " AND Courses.id='".parent::mres($courseID)."'";
				} else {
					$Where .= " AND Courses.slug='".parent::mres($courseID)."'";
				}

				$sql = parent::mq("SELECT cm.course_id, Courses.slug AS CourseSlug, cm.slug, cm.source, cm.free FROM Courses_Materials AS cm INNER JOIN Courses ON Courses.id=cm.course_id WHERE ".$Where);

				if($sql->num_rows > 0) {
					$row = parent::mfa($sql);

					$unlocked = (($row['free']) ? true : $this->Billing->productOwned($row['course_id']));

					if($unlocked) {
						$path = ABSPATH.'docs/courses/'.$row['course_id'].'/workbooks/';

						if(is_readable($path.$row['source'])) {
							$data = [
								'file'		=> $path.$row['source'],
								'filename'	=> 'Course-'.$row['CourseSlug'].'-'.(($row['type'] == 3) ? 'lesson-'.$row['slug'] : 'course-book').'.pdf',
								'filesize'	=> filesize($path.$row['source'])
							];
						}
					}
				}
			}
		}

		return $data;
	}

	public function getCourseAssets($courseID) {
		$data = [];

		if(!empty($courseID)) {
			if(is_int($courseID)) {
				$Where = "id='".parent::mres($courseID)."'";
			} else {
				$Where = "slug='".parent::mres($courseID)."'";
			}

			$sql = parent::mq("SELECT id, slug FROM Courses WHERE ".$Where);

			if($sql->num_rows > 0) {
				$row = parent::mfa($sql);

				if($this->Billing->productOwned($row['id'])) {
					$file = ABSPATH.'docs/courses/'.$row['id'].'/workbooks/CourseAssets.zip';

					if(is_readable($file)) {
						$data = [
							'file'		=> $file,
							'filename'	=> 'Course-'.$row['slug'].'.zip',
							'filesize'	=> filesize($file)
						];
					}
				}
			}
		}

		return $data;
	}
	
	/**
	 * getMentors
	 *
	 * @param int $offset Page offset
	 * @param int $limit Number of mentors per page
	 * @return array
	 */
	public function getMentors(int $offset = 1, int $limit = 32) {
		$data = [];

		$offset = intval(parent::mres($offset));
		
		// Ensure user can't trigger sql error
		if($offset <= 0) {
			$offset = 1;
		}
		
		$total = 0;
		
		$sqlCount = parent::mq("SELECT 
				COUNT(*) as total
			FROM 
				Courses_Mentors
			WHERE 
				active='1'
			ORDER BY
				name
			ASC");
		
		if(!empty($sqlCount) && $sqlCount->num_rows > 0) {
			$results = parent::mfa($sqlCount);
			$total = $results['total'];
		}
		
		$sql = parent::mq("SELECT 
			*
		FROM
			Courses_Mentors
		WHERE 
			active='1'
		ORDER BY
			name
		ASC LIMIT " . (($offset - 1) * parent::mres($limit)) . " , " . parent::mres($limit));
		
		$i = 0;
		
		if(!empty($sql) && $sql->num_rows > 0) {
			while ($row = parent::mfa($sql)) {
				$data[$i] = $row;

				$data[$i]['description'] = str_replace(['"', "'"], ['&quot;', '&#39;'], substr(strip_tags($data[$i]['description']), 0, 160));

				$i++;
			}

			$pagination = \SenFramework\SenFramework::pagination($offset, ceil($total / $limit), $this->url);

			$data['pagination'] = $pagination['html'];
			$data['meta'] = $pagination['meta'];
		}

		return $data;
	}

	public function getMentor(string $mentor, int $oldID = 0) {
		$data = [];

		if(!empty($mentor) || !empty($oldID)) {
			if($oldID != 0) {
				$Where = "old_id='".parent::mres($oldID)."'";
			} else {
				$Where = "slug='".parent::mres($mentor)."'";
			}

			$sql = parent::mq("SELECT * FROM Courses_Mentors WHERE ".$Where." AND active='1'");

			if(!empty($sql) && $sql->num_rows > 0) {
				$data = parent::mfa($sql);
			}
		}

		return $data;
	}

	public function userReviewed(int $courseID) {
		global $user;

		if(!empty($courseID)) {
			$sql = parent::mq("SELECT * FROM Courses_Reviews WHERE course_id='".parent::mres($courseID)."'AND user_id='".$user->data['user_id']."'");

			if($sql->num_rows > 0) {
				return true;
			}
		}

		return false;
	}

	/**
	 * getCourseReviews
	 *
	 * @param int $courseID
	 * @return array
	 */
	public function getCourseReviews(int $courseID): array {
		$data = [
			'scoring' 	=> [
				0	=> 0,
				1	=> 0,
				2	=> 0,
				3	=> 0,
				4	=> 0,
				5	=> 0
			],
			'reviews'	=> []
		];

		$r = 0;

		if(!empty($courseID)) {
			$sql = parent::mq("SELECT 
				CR.id,
				CR.rating,
				CR.review,
				CR.posted,
				U.username,
				U.username_clean,
				U.user_first_name,
				U.user_last_name,
				U.user_avatar AS avatar 
			FROM 
				Courses_Reviews AS CR 
			INNER JOIN 
				Users AS U 
			ON 
				U.user_id=CR.user_id 
			WHERE 
				course_id='".parent::mres($courseID)."' 
			ORDER BY 
				posted 
			DESC");

			if($sql->num_rows > 0) {
				while($row = parent::mfa($sql)) {
					$data['scoring'][number_format($row['rating'], 0)] += 1;

					$data['reviews'][$r] = $row;
					$data['reviews'][$r]['name'] = ((!empty($row['user_first_name'])) ? $row['user_first_name'] .((!empty($row['user_last_name'])) ? ' '.$row['user_last_name'] : '') : $row['username']);

					unset($data['reviews'][$r]['username'], $data['reviews'][$r]['user_first_name'], $data['reviews'][$r]['user_last_name']);

					$r++;
				}

				foreach($data['scoring'] as $key => $value) {
					$data['scoring'][$key] = ($value * 100) / $sql->num_rows;
				}
			}
		}

		return $data;
	}
}