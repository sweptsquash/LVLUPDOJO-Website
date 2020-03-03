<?php

namespace SenFramework\ACP;

class Courses extends \SenFramework\DB\Database {

    public $data;

    public function __construct($route = NULL, $query = NULL) {
        global $request, $senConfig, $user;

        $this->data['template_folder'] = 'acp/courses';
        $this->data['nav'] = 'courses';
        $this->data['single'] = true;

        $FilePond = new \SenFramework\FilePond\RequestHandler;   
        $Courses = new \SenFramework\Courses;
        $Courses->url = 'admin/courses';

        switch($route[2]) {
            default:
            case"p":
                $offset = intval($route[3]);
                            
                if($offset <= 0) {
                    $offset = 1;
                }
                
                $this->data['override']['title'] = 'Courses' . (($offset > 1) ? ' &bull; Page ' . $offset : NULL) . ' &bull; Admin Panel ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
                $this->data['template'] = 'list';
                
                $this->data['courses'] = $Courses->getCourses(NULL, $offset, 9);

                $this->data['pagination'] = $this->data['courses']['pagination'];
                $this->data['meta'] = $this->data['courses']['meta'];

                unset($this->data['courses']['pagination'], $this->data['courses']['meta']);
            break;

            case"c":
                $this->data['override']['title'] = 'Create Course &bull; Admin Panel ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
                $this->data['template'] = 'create';

                $categories = $Courses->getCategories();

                foreach($categories as $key => $value) {
                    $this->data['categories'][] = [
                        'id'	=>	$value['id'],
                        'name'	=>	$value['name']
                    ];

                    if(!empty($value['children'])) {
                        foreach($value['children'] as $ckey => $cvalue) {
                            $this->data['categories'][] = [
                                'id'	=>	$cvalue['id'],
                                'name'	=>	$value['name'] . ' > ' .$cvalue['name']
                            ];
                        }
                    }
                }

                $this->data['form'] = [
                    'title'			        =>	$request->raw_variable('courseTitle', ''),
                    'cost'                  =>  number_format(floatval($request->variable('courseCost', '15.00', true)), 2),
                    'banner'		        =>	$request->variable('bannerFile', '', true),
                    'banner_type'           =>  'limbo',
                    'thumbnail'		        =>	$request->variable('thumbnailFile', '', true),
                    'thumbnail_type'        =>  'limbo',
                    'mentor'		        =>	[
                        'id'		        =>	intval($request->variable('mentorID', '0', true)),
                        'mode'              =>  $request->variable('mentorMode', 'search', true),
                        'name'		        =>	$request->raw_variable('mentorCreateName', ''),
                        'avatar'            =>  $request->variable('mentorAvatarFile', '', true),
                        'avatar_type'       =>  'limbo',
                        'description'       =>  $request->raw_variable('mentorCreateDescription', ''),
                        'keywords'          =>  $request->variable('mentorCreateKeywords', '', true)
                    ],
                    'category'		        =>	$request->variable('courseCategory', '1', true),
                    'published'		        =>	intval($request->variable('coursePublished', '0', true)),
                    'description'	        =>	$request->raw_variable('courseDescription', ''),
                    'keywords'		        =>	$request->variable('courseKeywords', '', true),
                    'materials'             =>  NULL,
                    'total_lessons'         =>  intval($request->variable('lessons', '0', true)),
                    'total_workbooks'       =>  intval($request->variable('workbooks', '0', true)),
                    'total_materials'       =>  0
                ];

                $this->data['form']['slug']            = \SenFramework\SenFramework::createURL($this->data['form']['title']);
                $this->data['form']['total_materials'] = $this->data['form']['total_lessons'] + $this->data['form']['total_workbooks'];

                if($this->data['form']['total_lessons'] > 0) {
                    for($i = 1; $i <= $this->data['form']['total_lessons']; $i++) {
                        $order = intval($request->variable('lessonOrder'.$i, ''));

                        if(!empty($order)) {
                            $this->data['form']['materials'][$order] = [
                                'title'             => $request->raw_variable('lessonTitle'.$i, ''),
                                'published'         => $request->variable('lessonPublished'.$i, '', true),
                                'free'              => $request->variable('lessonFree'.$i, '', true),
                                'type'              => intval($request->variable('lessonType'.$i, '', true)),
                                'source'            => $request->variable('lessonSource'.$i, '', true),
                                'duration'          => $request->variable('lessonDuration'.$i, '', true),
                                'description'       => $request->raw_variable('lessonDescription'.$i, ''),
                                'keywords'          => $request->variable('lessonKeywords'.$i, '', true),
                                'thumbnail'         => $request->variable('lessonThumbnailFile'.$i, '', true),
                                'thumbnail_type'    => 'limbo',
                                'order'             => $request->variable('lessonOrder'.$i, '', true)
                            ];
                        }
                    }
                }

                if($this->data['form']['total_workbooks'] > 0) {
                    for($i = 1; $i <= $this->data['form']['total_workbooks']; $i++) {
                        $order = intval($request->variable('materialOrder'.$i, '', true));

                        if(!empty($order)) {
                            $this->data['form']['materials'][$order] = [
                                'title'             => $request->raw_variable('materialTitle'.$i, ''),
                                'published'         => $request->variable('materialPublished'.$i, '', true),
                                'free'              => $request->variable('materialFree'.$i, '', true),
                                'type'              => intval($request->variable('materialType'.$i, '', true)),
                                'workbook'          => $request->variable('materialWorkbookFile'.$i, '', true),
                                'workbook_type'     => 'limbo',
                                'order'             => $request->variable('materialOrder'.$i, '', true)
                            ];
                        }
                    }
                }

                if(is_array($this->data['form']['materials'])) {
                    ksort($this->data['form']['materials']);
                }

                if($request->is_set_post('createCourse')) {
                    if(empty($this->data['form']['title'])) {
                        $this->data['error'][] = 'No Course Title Provided.';
                    }

                    if($this->data['form']['mentor']['mode'] == 'create') {
                        if(empty($this->data['form']['mentor']['name'])) {
                            $this->data['error'][] = 'No Mentor Name Provided.';
                        }
                    } else {
                        if(empty($this->data['form']['mentor']['id'])) {
                            $this->data['error'][] = 'No Mentor Provided.';
                        }
                    }

                    if(!empty($this->data['form']['materials'])) {
                        foreach($this->data['form']['materials'] as $key => $material) {
                            if(empty($this->data['form']['materials'][$key]['title'])) { 
                                $this->data['error'][] = (($this->data['form']['materials'][$key]['type'] != 3 && $this->data['form']['materials'][$key]['type'] != 5) ? 'Lesson' : 'Workbook') .' '.$key.' no title provided.';
                            }

                            if($this->data['form']['materials'][$key]['type'] != 3 && $this->data['form']['materials'][$key]['type'] != 5) {
                                if(empty($this->data['form']['materials'][$key]['source'])) {
                                    $this->data['error'][] = 'Lesson '.$key.' no source provided.';
                                }

                                if(empty($this->data['form']['materials'][$key]['duration'])) {
                                    $this->data['error'][] = 'Lesson '.$key.' no duration provided.';
                                }
                            } else {
                                if(empty($this->data['form']['materials'][$key]['workbook'])) {
                                    $this->data['error'][] = 'Workbook '.$key.' missing PDF File.';
                                }
                            }
                        }
                    }

                    if(!isset($this->data['error'])) {
                        // Create Course
                        $course = [
                            'name'              => $this->data['form']['title'],
                            'slug'              => $this->data['form']['slug'],
                            'mentor'            => ($this->data['form']['mentor']['mode'] == 'create') ? 0 : $this->data['form']['mentor']['id'], 
                            'excerpt'           => str_replace(['"', "'"], ['&quot;', '&#39;'], substr(strip_tags($this->data['form']['description']), 0, 160)),
                            'description'       => $this->data['form']['description'],
                            'keywords'			=> $this->data['form']['keywords'], 
                            'categories'		=> $this->data['form']['category'], 
                            'thumbnail'			=> $this->data['form']['thumbnail'], 
                            'banner'			=> $this->data['form']['banner'], 
                            'published'			=> $this->data['form']['published'],
                            'publishDate'       => date("Y-m-d H:i:s")
                        ];

                        parent::mq("INSERT INTO Courses ".parent::build_array('INSERT', $course));
                        $courseID = parent::lastId();

                        if($FilePond->isFileId($this->data['form']['thumbnail'])) {
                            $file = $FilePond->getTempFile($this->data['form']['thumbnail']);

                            if(!empty($file)) {
                                $result = $FilePond->save($this->data['form']['thumbnail'], 'img/course/'.$courseID.'/');

                                if($result) {
                                    $this->data['form']['thumbnail'] = '/img/course/'.$courseID.'/'.$file['name'];
                                    $this->data['form']['thumbnail_type'] = 'local';
                                }
                            }
                        }

                        if($FilePond->isFileId($this->data['form']['banner'])) {
                            $file = $FilePond->getTempFile($this->data['form']['banner']);

                            if(!empty($file)) {
                                $result = $FilePond->save($this->data['form']['banner'], 'img/course/'.$courseID.'/');

                                if($result) {
                                    $this->data['form']['banner'] = '/img/course/'.$courseID.'/'.$file['name'];
                                    $this->data['form']['banner_type'] = 'local';
                                }
                            }
                        }

                        $pricing = [
                            'course_id'     => $courseID,
                            'currency'      => 'usd',
                            'price'         => $this->data['form']['cost']
                        ];

                        parent::mq("INSERT INTO Courses_Pricing ".parent::build_array('INSERT', $pricing));

                        // Create Course & Lesson Folders
                        mkdir(ABSPATH . 'img/course/'.$courseID, 0755, true);
                        mkdir(ABSPATH . 'img/lessons/'.$courseID, 0755, true);
                        mkdir(ABSPATH . 'docs/courses/'.$courseID, 0755, true);
                        mkdir(ABSPATH . 'docs/courses/'.$courseID.'/workbooks', 0755, true);

                        $courseUpdate = [
                            'thumbnail' => $this->data['form']['thumbnail'],
                            'banner'    => $this->data['form']['banner']
                        ];

                        // Create Mentor
                        if($this->data['form']['mentor']['mode'] == 'create') {
                            $mentor = [
                                'name'          => $this->data['form']['mentor']['name'],
                                'slug'          => \SenFramework\SenFramework::createURL($this->data['form']['mentor']['name']),
                                'description'   => (!empty($this->data['form']['mentor']['description'])) ? $this->data['form']['mentor']['description'] : NULL,
                                'keywords'      => (!empty($this->data['form']['mentor']['keywords'])) ? $this->data['form']['mentor']['keywords'] : NULL
                            ];

                            if($FilePond->isFileId($this->data['form']['mentor']['avatar'])) {
                                $file = $FilePond->getTempFile($this->data['form']['mentor']['avatar']);

                                if(!empty($file)) {
                                    $result = $FilePond->save($this->data['form']['mentor']['avatar'], 'img/course/'.$courseID.'/');

                                    if($result) {
                                        $mentor['avatar'] = $this->data['form']['mentor']['avatar'] = '/img/course/'.$courseID.'/'.$file['name'];
                                        $this->data['form']['mentor']['avatar_type'] = 'local';
                                    }
                                }
                            }

                            parent::mq("INSERT INTO Courses_Mentors ".parent::build_array('INSERT', $mentor));
                            $this->data['form']['mentor']['id'] = parent::lastId();
                            $this->data['form']['mentor']['mode'] = 'search';

                            $courseUpdate['mentor'] = $this->data['form']['mentor']['id'];
                        }

                        parent::mq("UPDATE Courses SET ".parent::build_array('UPDATE', $courseUpdate)." WHERE id='".parent::mres($courseID)."'");

                        if(!empty($this->data['form']['materials'])) {
                            $lastLesson = 0;
                            
                            foreach($this->data['form']['materials'] as $key => $material) {
                                if($material['type'] == 3 || $material['type'] == 5) {
                                    if($FilePond->isFileId($material['workbook'])) {
                                        $file = $FilePond->getTempFile($material['workbook']);
            
                                        if(!empty($file)) {
                                            $result = $FilePond->save($material['workbook'], 'docs/courses/'.$courseID.'/workbooks/');
            
                                            if($result) {
                                                $material['source'] = $file['name'];
                                                $this->data['form']['materials'][$key]['workbook'] = '/docs/courses/'.$courseID.'/workbooks/'.$file['name'];
                                                $this->data['form']['materials'][$key]['workbook_type'] = 'local';
                                            }
                                        }
                                    }
                                } else {
                                    if($FilePond->isFileId($material['thumbnail'])) {
                                        $file = $FilePond->getTempFile($material['thumbnail']);
            
                                        if(!empty($file)) {
                                            $result = $FilePond->save($material['thumbnail'], 'img/lessons/'.$courseID.'/');
            
                                            if($result) {
                                                $material['thumbnail'] = $this->data['form']['materials'][$key]['thumbnail'] = '/img/lessons/'.$courseID.'/'.$file['name'];
                                                $this->data['form']['materials'][$key]['thumbnail_type'] = 'local';
                                            }
                                        }
                                    }
                                }

                                $lesson = [
                                    'course_id'     => $courseID,
                                    'parent_id'     => ($material['type'] == 3 || $material['type'] == 5) ? (($material['order'] == 1) ? 0 : $lastLesson) : 0,
                                    'name'          => $material['title'],
                                    'slug'          => \SenFramework\SenFramework::createURL($material['title']),
                                    'type'          => $material['type'],
                                    'excerpt'       => (isset($material['description']) && !empty($material['description'])) ? str_replace(['"', "'"], ['&quot;', '&#39;'], substr(strip_tags($material['description']), 0, 160)) : NULL,
                                    'description'   => (isset($material['description']) && !empty($material['description'])) ? $material['description'] : NULL,
                                    'keywords'      => (isset($material['keywords']) && !empty($material['keywords'])) ? $material['keywords'] : NULL,
                                    'thumbnail'     => (isset($material['thumbnail']) && !empty($material['thumbnail'])) ? $material['thumbnail'] : NULL,
                                    'source'        => $material['source'],
                                    'published'     => $material['published'],
                                    'free'          => $material['free'],
                                    'orderNo'       => $material['order']
                                ];

                                if($material['published'] == 1) {
                                    $lesson['publishDate'] = date("Y-m-d H:i:s");
                                }

                                if($material['type'] != 3 && $material['type'] != 5) {
                                    $time = explode(':', $material['duration']);

                                    $lesson['duration'] = $time[0] * 3600 + $time[1] * 60 + $time[2];
                                }

                                parent::mq("INSERT INTO Courses_Materials ".parent::build_array('INSERT', $lesson));
                                $lastLesson = parent::lastId();
                            }

                            // ZIP All Course Assets
                            if($this->data['form']['total_workbooks'] > 0) {
                                $zipFile = new \PhpZip\ZipFile();
                                $zipFile->setCompressionLevel(\PhpZip\ZipFile::LEVEL_BEST_SPEED);
                                $zipFile->addDir(ABSPATH.'docs/courses/'.$courseID.'/workbooks', "workbooks/", \PhpZip\ZipFile::METHOD_STORED)->saveAsFile(ABSPATH.'docs/courses/'.$courseID.'/workbooks/CourseAssets.zip');
                                $zipFile->close();
                            }
                        }

                        $this->data['success'] = true;
                    }			
                }
            break;

            case"e":
                $course = $Courses->getCourse(intval($route[3]));

                if(!empty($course)) {
                    $this->data['override']['title'] = 'Editting "'.$course['name'].'" Course &bull; Admin Panel ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
                    $this->data['template'] = 'edit';

                    $courseID = $course['id'];

                    $categories = $Courses->getCategories();

                    foreach($categories as $key => $value) {
                        $this->data['categories'][] = [
                            'id'	=>	$value['id'],
                            'name'	=>	$value['name']
                        ];

                        if(!empty($value['children'])) {
                            foreach($value['children'] as $ckey => $cvalue) {
                                $this->data['categories'][] = [
                                    'id'	=>	$cvalue['id'],
                                    'name'	=>	$value['name'] . ' > ' .$cvalue['name']
                                ];
                            }
                        }
                    }

                    $cost = $Courses->getCoursePricing($course['id']);

                    $materials = $this->getCourseMaterials($course['id']);
    
                    $this->data['form'] = [
                        'title'			        =>	$request->raw_variable('courseTitle', $course['name']),
                        'cost'                  =>  number_format(floatval($request->variable('courseCost', (string)$cost['price'], true)), 2),
                        'banner'		        =>	$request->variable('bannerFile', $course['banner'], true),
                        'banner_type'           =>  'local',
                        'thumbnail'		        =>	$request->variable('thumbnailFile', $course['thumbnail'], true),
                        'thumbnail_type'        =>  'local',
                        'mentor'		        =>	[
                            'id'		        =>	intval($request->variable('mentorID', (string)((isset($course['mentor'])) ? $course['mentor']['mid'] : 0), true)),
                            'mode'              =>  $request->variable('mentorMode', 'search', true),
                            'name'		        =>	$request->raw_variable('mentorCreateName', ((isset($course['mentor'])) ? $course['mentor']['name'] : '')),
                            'avatar'            =>  $request->variable('mentorAvatarFile', ((isset($course['mentor'])) ? $course['mentor']['avatar'] : ''), true),
                            'avatar_type'       =>  'local',
                            'description'       =>  $request->raw_variable('mentorCreateDescription', ((isset($course['mentor'])) ? $course['mentor']['about'] : '')),
                            'keywords'          =>  $request->variable('mentorCreateKeywords', ((isset($course['mentor'])) ? $course['mentor']['keywords'] : ''), true)
                        ],
                        'category'		        =>	$request->variable('courseCategory', (string)$course['categories'], true),
                        'published'		        =>	intval($request->variable('coursePublished', (string)$course['published'], true)),
                        'description'	        =>	$request->raw_variable('courseDescription', $course['description']),
                        'keywords'		        =>	$request->variable('courseKeywords', $course['keywords'], true),
                        'materials'             =>  NULL,
                        'total_lessons'         =>  intval($request->variable('lessons', (isset($materials['lesson'])) ? (string)count($materials['lesson']) : '0', true)),
                        'total_workbooks'       =>  intval($request->variable('workbooks', (isset($materials['workbook'])) ? (string)count($materials['workbook']) : '0', true)),
                        'total_materials'       =>  0
                    ];

                    $this->data['form']['slug']            = \SenFramework\SenFramework::createURL($this->data['form']['title']);
                    $this->data['form']['total_materials'] = $this->data['form']['total_lessons'] + $this->data['form']['total_workbooks'];
    
                    if($this->data['form']['total_lessons'] > 0) {
                        for($i = 1; $i <= $this->data['form']['total_lessons']; $i++) {
                            $order = intval($request->variable('lessonOrder'.$i, (string)((isset($materials['lesson'][$i])) ? $materials['lesson'][$i]['order'] : $i), true));
    
                            if(!empty($order)) {
                                $this->data['form']['materials'][$order] = [
                                    'id'                => intval($request->variable('lessonID'.$i, (string)((isset($materials['lesson'][$i])) ? $materials['lesson'][$i]['id'] : 0), true)),
                                    'title'             => $request->raw_variable('lessonTitle'.$i, ((isset($materials['lesson'][$i])) ? $materials['lesson'][$i]['title'] : '')),
                                    'published'         => intval($request->variable('lessonPublished'.$i, (string)((isset($materials['lesson'][$i])) ? $materials['lesson'][$i]['published'] : 0), true)),
                                    'free'              => intval($request->variable('lessonFree'.$i, (string)((isset($materials['lesson'][$i])) ? $materials['lesson'][$i]['free'] : 0), true)),
                                    'type'              => intval($request->variable('lessonType'.$i, (string)((isset($materials['lesson'][$i])) ? $materials['lesson'][$i]['type'] : 2), true)),
                                    'source'            => $request->variable('lessonSource'.$i, (string)((isset($materials['lesson'][$i])) ? $materials['lesson'][$i]['source'] : ''), true),
                                    'duration'          => $request->variable('lessonDuration'.$i, (string)((isset($materials['lesson'][$i])) ? $materials['lesson'][$i]['duration'] : ''), true),
                                    'description'       => $request->raw_variable('lessonDescription'.$i, (string)((isset($materials['lesson'][$i])) ? $materials['lesson'][$i]['description'] : '')),
                                    'keywords'          => $request->variable('lessonKeywords'.$i, (string)((isset($materials['lesson'][$i])) ? $materials['lesson'][$i]['keywords'] : ''), true),
                                    'thumbnail'         => $request->variable('lessonThumbnailFile'.$i, (string)((isset($materials['lesson'][$i])) ? $materials['lesson'][$i]['thumbnail'] : ''), true),
                                    'thumbnail_type'    => $request->variable('lessonThumbnailFileType'.$i, (string)((isset($materials['lesson'][$i])) ? $materials['lesson'][$i]['thumbnail_type'] : ''), true),
                                    'order'             => $order
                                ];
                            }
                        }
                    }
    
                    if($this->data['form']['total_workbooks'] > 0) {
                        for($i = 1; $i <= $this->data['form']['total_workbooks']; $i++) {
                            $order = intval($request->variable('materialOrder'.$i, (string)((isset($materials['workbook'][$i])) ? $materials['workbook'][$i]['order'] : $i), true));
    
                            if(!empty($order)) {
                                $this->data['form']['materials'][$order] = [
                                    'id'                => intval($request->variable('materialID'.$i, (string)((isset($materials['workbook'][$i])) ? $materials['workbook'][$i]['id'] : 0), true)),
                                    'title'             => $request->raw_variable('materialTitle'.$i, ((isset($materials['workbook'][$i])) ? $materials['workbook'][$i]['title'] : '')),
                                    'published'         => intval($request->variable('materialPublished'.$i, (string)((isset($materials['workbook'][$i])) ? $materials['workbook'][$i]['published'] : 0), true)),
                                    'free'              => intval($request->variable('materialFree'.$i, (string)((isset($materials['workbook'][$i])) ? $materials['workbook'][$i]['free'] : 0), true)),
                                    'type'              => intval($request->variable('materialType'.$i, (string)((isset($materials['workbook'][$i])) ? $materials['workbook'][$i]['type'] : 0), true)),
                                    'workbook'          => $request->variable('materialWorkbookFile'.$i, (string)((isset($materials['workbook'][$i])) ? $materials['workbook'][$i]['workbook'] : ''), true),
                                    'workbook_type'     => $request->variable('materialWorkbookFileType'.$i, (string)((isset($materials['workbook'][$i])) ? $materials['workbook'][$i]['workbook_type'] : ''), true),
                                    'order'             => $order
                                ];
                            }
                        }
                    }
    
                    if(is_array($this->data['form']['materials'])) {
                        ksort($this->data['form']['materials']);
                    }

                    if($request->is_set_post('updateCourse')) {
                        if(empty($this->data['form']['title'])) {
                            $this->data['error'][] = 'No Course Title Provided.';
                        }
    
                        if($this->data['form']['mentor']['mode'] == 'create') {
                            if(empty($this->data['form']['mentor']['name'])) {
                                $this->data['error'][] = 'No Mentor Name Provided.';
                            }
                        } else {
                            if(empty($this->data['form']['mentor']['id'])) {
                                $this->data['error'][] = 'No Mentor Provided.';
                            }
                        }
    
                        if(!empty($this->data['form']['materials'])) {
                            foreach($this->data['form']['materials'] as $key => $material) {
                                if(empty($this->data['form']['materials'][$key]['title'])) { 
                                    $this->data['error'][] = (($this->data['form']['materials'][$key]['type'] != 3 && $this->data['form']['materials'][$key]['type'] != 5) ? 'Lesson' : 'Workbook') .' '.$key.' no title provided.';
                                }
    
                                if($this->data['form']['materials'][$key]['type'] != 3 && $this->data['form']['materials'][$key]['type'] != 5) {
                                    if(empty($this->data['form']['materials'][$key]['source'])) {
                                        $this->data['error'][] = 'Lesson '.$key.' no source provided.';
                                    }
    
                                    if(empty($this->data['form']['materials'][$key]['duration'])) {
                                        $this->data['error'][] = 'Lesson '.$key.' no duration provided.';
                                    }
                                } else {
                                    if(empty($this->data['form']['materials'][$key]['workbook'])) {
                                        $this->data['error'][] = 'Workbook '.$key.' missing PDF File.';
                                    }
                                }
                            }
                        }
    
                        if(!isset($this->data['error'])) {
                            $courseData = [
                                'name'              => $this->data['form']['title'],
                                'mentor'            => ($this->data['form']['mentor']['mode'] == 'create') ? 0 : $this->data['form']['mentor']['id'], 
                                'excerpt'           => str_replace(['"', "'"], ['&quot;', '&#39;'], substr(strip_tags($this->data['form']['description']), 0, 160)),
                                'description'       => $this->data['form']['description'],
                                'keywords'			=> $this->data['form']['keywords'], 
                                'categories'		=> $this->data['form']['category'], 
                                'thumbnail'			=> $this->data['form']['thumbnail'], 
                                'banner'			=> $this->data['form']['banner'], 
                                'published'			=> $this->data['form']['published'],
                                'publishDate'       => date("Y-m-d H:i:s")
                            ];

                            if($course['published'] == 0) {
                                $courseData['slug'] = $this->data['form']['slug'];
                            }
    
                            if($FilePond->isFileId($this->data['form']['thumbnail'])) {
                                $file = $FilePond->getTempFile($this->data['form']['thumbnail']);
    
                                if(!empty($file)) {
                                    $result = $FilePond->save($this->data['form']['thumbnail'], 'img/course/'.$courseID.'/');
    
                                    if($result) {
                                        $courseData['thumbnail'] = $this->data['form']['thumbnail'] = '/img/course/'.$courseID.'/'.$file['name'];
                                        $this->data['form']['thumbnail_type'] = 'local';
                                    }
                                }
                            }
    
                            if($FilePond->isFileId($this->data['form']['banner'])) {
                                $file = $FilePond->getTempFile($this->data['form']['banner']);
    
                                if(!empty($file)) {
                                    $result = $FilePond->save($this->data['form']['banner'], 'img/course/'.$courseID.'/');
    
                                    if($result) {
                                        $courseData['banner'] = $this->data['form']['banner'] = '/img/course/'.$courseID.'/'.$file['name'];
                                        $this->data['form']['banner_type'] = 'local';
                                    }
                                }
                            }

                            parent::mq("UPDATE Courses SET ".parent::build_array('UPDATE', $courseData)." WHERE id='".parent::mres($courseID)."'");

                            $pricing = [
                                'price' => $this->data['form']['cost']
                            ];
    
                            parent::mq("UPDATE Courses_Pricing SET ".parent::build_array('UPDATE', $pricing)." WHERE course_id='".parent::mres($courseID)."'");

                            if(!empty($this->data['form']['materials'])) {
                                $lastLesson = 0;
                                
                                foreach($this->data['form']['materials'] as $key => $material) {
                                    if($material['type'] == 3 || $material['type'] == 5) {
                                        if($FilePond->isFileId($material['workbook'])) {
                                            $file = $FilePond->getTempFile($material['workbook']);
                
                                            if(!empty($file)) {
                                                $result = $FilePond->save($material['workbook'], 'docs/courses/'.$courseID.'/workbooks/');
                
                                                if($result) {
                                                    $material['source'] = $file['name'];
                                                    $this->data['form']['materials'][$key]['workbook'] = '/docs/courses/'.$courseID.'/workbooks/'.$file['name'];
                                                    $this->data['form']['materials'][$key]['workbook_type'] = 'local';
                                                }
                                            }
                                        }
                                    } else {
                                        if($FilePond->isFileId($material['thumbnail'])) {
                                            $file = $FilePond->getTempFile($material['thumbnail']);
                
                                            if(!empty($file)) {
                                                $result = $FilePond->save($material['thumbnail'], 'img/lessons/'.$courseID.'/');
                
                                                if($result) {
                                                    $material['thumbnail'] = $this->data['form']['materials'][$key]['thumbnail'] = '/img/lessons/'.$courseID.'/'.$file['name'];
                                                    $this->data['form']['materials'][$key]['thumbnail_type'] = 'local';
                                                }
                                            }
                                        }
                                    }
    
                                    $lesson = [
                                        'course_id'     => $courseID,
                                        'parent_id'     => ($material['type'] == 3 || $material['type'] == 5) ? (($material['order'] == 1) ? 0 : $lastLesson) : 0,
                                        'name'          => $material['title'],
                                        'slug'          => \SenFramework\SenFramework::createURL($material['title']),
                                        'type'          => $material['type'],
                                        'excerpt'       => (isset($material['description']) && !empty($material['description'])) ? str_replace(['"', "'"], ['&quot;', '&#39;'], substr(strip_tags($material['description']), 0, 160)) : NULL,
                                        'description'   => (isset($material['description']) && !empty($material['description'])) ? $material['description'] : NULL,
                                        'keywords'      => (isset($material['keywords']) && !empty($material['keywords'])) ? $material['keywords'] : NULL,
                                        'thumbnail'     => (isset($material['thumbnail']) && !empty($material['thumbnail'])) ? $material['thumbnail'] : NULL,
                                        'source'        => $material['source'],
                                        'published'     => $material['published'],
                                        'free'          => $material['free'],
                                        'orderNo'       => $material['order']
                                    ];
    
                                    if($material['published'] == 1) {
                                        $lesson['publishDate'] = date("Y-m-d H:i:s");
                                    }
    
                                    if($material['type'] != 3 && $material['type'] != 5) {
                                        $time = explode(':', $material['duration']);
    
                                        $lesson['duration'] = $time[0] * 3600 + $time[1] * 60 + $time[2];
                                    }
    
                                    if($material['id'] == 0) { 
                                        parent::mq("INSERT INTO Courses_Materials ".parent::build_array('INSERT', $lesson));
                                        $this->data['form']['materials'][$key]['id'] = $lastLesson = parent::lastId();
                                    } else {
                                        parent::mq("UPDATE Courses_Materials SET ".parent::build_array('UPDATE', $lesson)." WHERE id='".parent::mres($material['id'])."'");
                                    }
                                }
    
                                // ZIP All Course Assets
                                if($this->data['form']['total_workbooks'] > 0) {
                                    if(file_exists(ABSPATH.'docs/courses/'.$courseID.'/workbooks/CourseAssets.zip')) {
                                        @unlink(ABSPATH.'docs/courses/'.$courseID.'/workbooks/CourseAssets.zip');
                                    }

                                    $zipFile = new \PhpZip\ZipFile();
                                    $zipFile->setCompressionLevel(\PhpZip\ZipFile::LEVEL_BEST_SPEED);
                                    $zipFile->addDir(ABSPATH.'docs/courses/'.$courseID.'/workbooks', "workbooks/", \PhpZip\ZipFile::METHOD_STORED)->saveAsFile(ABSPATH.'docs/courses/'.$courseID.'/workbooks/CourseAssets.zip');
                                    $zipFile->close();
                                }
                            }
    
                            $this->data['success'] = true;
                        }
                    }
                } else {
                    $this->data['triggererror'] = '404';
                }
            break;

            case"d":
                $course = $Courses->getCourse(intval($route[3]));

                if(!empty($course)) {
                    $this->data['override']['title'] = 'Delete "'.$course['name'].'" Course &bull; Admin Panel ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
                    $this->data['template'] = 'delete';

                    $this->data['course'] = $course;

                    if($request->is_set_post('removeCourse')) {
                        // Delete Course Assets & Folders
                        \SenFramework\SenFramework::recursiveDelete(ABSPATH . 'img/course/'.$course['id']);
                        \SenFramework\SenFramework::recursiveDelete(ABSPATH . 'docs/courses/'.$course['id']);

                        parent::mq("DELETE FROM Courses WHERE id='".parent::mres($course['id'])."'");
                        parent::mq("DELETE FROM Courses_Materials WHERE course_id='".parent::mres($course['id'])."'");
                        parent::mq("DELETE FROM Courses_Pricing WHERE course_id='".parent::mres($course['id'])."'");

                        $this->data['success'] = true;
                    }
                } else {
                    $this->data['triggererror'] = '404';
                }
            break;
        }
    }

    private function getCourseMaterials(int $courseID): array {
        $materials = array();

        $sql = parent::mq("SELECT * FROM Courses_Materials WHERE course_id='".parent::mres($courseID)."' ORDER BY orderNo ASC");
		
		if($sql->num_rows > 0) {
            $orderNo = 0;
            $lesson = 1;
            $workbook = 1;

            while($row = parent::mfa($sql)) {
                if(intval($row['orderNo']) == 0) {
                    $lsql = parent::mq("SELECT orderNo FROM Courses_Materials WHERE parent_id='".parent::mres($row['parent_id'])."' ORDER BY id ASC");

                    if($lsql->num_rows > 0) {
                        $r = parent::mfa($lsql);

                        $orderNo = intval($r['orderNo']) + 1;
                    }
                } else {
                    $orderNo = (int)$row['orderNo'];
                }

                if((int)$row['type'] == 3 || (int)$row['type'] == 5) {
                    $type = 'workbook';
                    $offset = $workbook;
                } else {
                    $type = 'lesson';
                    $offset = $lesson;
                }

                $materials[$type][$offset] = [
                    'id'            => (int)$row['id'],
                    'type'          => (int)$row['type'],
                    'title'         => $row['name'],
                    'slug'          => $row['slug'],
                    'description'   => $row['description'],
                    'published'     => (int)$row['published'],
                    'free'          => (int)$row['free'],
                    'order'         => (int)$orderNo
                ];

                if((int)$row['type'] == 3 || (int)$row['type'] == 5) {
                    $materials[$type][$offset]['workbook'] = '/docs/courses/'.$row['course_id'].'/workbooks/'.$row['source'];
                    $materials[$type][$offset]['workbook_type'] = 'local';

                    $workbook++;
                } else {
                    $materials[$type][$offset]['thumbnail'] = $row['thumbnail'];
                    $materials[$type][$offset]['thumbnail_type'] = 'local';
                    $materials[$type][$offset]['duration'] = $this->secondsToTime($row['duration']);
                    $materials[$type][$offset]['source'] = $row['source'];

                    $lesson++;
                }
            }
        }

        return $materials;
    }

    private function secondsToTime($sec){
        $days       = intval($sec/86400);
        $hours      = intval(($sec/3600) - ($days*24));
        $minutes    = intval(($sec - (($days*86400)+ ($hours*3600)))/60);
        $seconds    = $sec - (($days*86400)+($hours*3600)+($minutes * 60));
        
        return (($hours < 10) ? '0'.$hours : $hours).':'.(($minutes < 10) ? '0'.$minutes : $minutes).':'.(($seconds < 10) ? '0'.$seconds : $seconds);
    }
}