<?php

namespace SenFramework\ACP;

class Pricing extends \SenFramework\DB\Database {

    public $data;

    public function __construct($route = NULL, $query = NULL) {
        global $request, $senConfig, $user;

        $this->data['template_folder'] = 'acp/pricing';
        $this->data['nav'] = 'pricing';
        $this->data['single'] = true;

        switch($route[2]) {
            default:
            case"p":
                $offset = intval($route[3]);
                                        
                if($offset <= 0) {
                    $offset = 1;
                }

                $limit = 32;
                
                $this->data['override']['title'] = 'Subscription Plans' . (($offset > 1) ? ' &bull; Page ' . $offset : NULL) . ' &bull; Admin Panel ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
                $this->data['template'] = 'subscription_list';

                $total = 0;
		
                $sqlCount = parent::mq("SELECT count(*) AS total FROM Pricing");
                
                if(!empty($sqlCount) && $sqlCount->num_rows > 0) {
                    $results = parent::mfa($sqlCount);
                    $total += $results['total'];
                }

                

                
            break;

            case"c":

            break;

            case"e":

            break;

            // Discount Codes
            case"discount":
                switch($route[3]) {
                    default:
                    case"p":
                        $offset = intval($route[4]);
                                    
                        if($offset <= 0) {
                            $offset = 1;
                        }

                        $limit = 32;
                        
                        $this->data['override']['title'] = 'Discounts' . (($offset > 1) ? ' &bull; Page ' . $offset : NULL) . ' &bull; Admin Panel ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
                        $this->data['template'] = 'discounts_list';

                        $total = 0;
		
                        $sqlCount = parent::mq("SELECT count(*) AS total FROM (SELECT * FROM Pricing_Discounts AS a UNION ALL SELECT * FROM Courses_Pricing_Discounts AS b) c");
                        
                        if(!empty($sqlCount) && $sqlCount->num_rows > 0) {
                            $results = parent::mfa($sqlCount);
                            $total += $results['total'];
                        }

                        $sql = parent::mq("SELECT * FROM (SELECT id, 'Plan' AS Type, pricing_id AS Item, code, percentage, usage_limit, start, end, added FROM Pricing_Discounts) AS a UNION ALL SELECT * FROM (SELECT id, 'Course', course_id, code, percentage, usage_limit, start, end, added FROM Courses_Pricing_Discounts) AS b ORDER BY added DESC LIMIT " . (($offset - 1) * parent::mres($limit)) . ", ".parent::mres($limit));

                        if($sql->num_rows > 0) {
                            while($row = parent::mfa($sql)) {
                                if($row['Type'] === 'Plan') {
                                    $isql = parent::mq("SELECT id, name FROM Pricing WHERE id='".parent::mres($row['Item'])."'");
                                    $irow = parent::mfa($isql);
                                    
                                    $item = '<a href="/admin/pricing/e/'.$irow['id'].'/">'.$row['Type'].': '.$irow['name'].'</a>';
                                } else {
                                    $isql = parent::mq("SELECT id, name FROM Courses WHERE id='".parent::mres($row['Item'])."'");
                                    $irow = parent::mfa($isql);
                                    
                                    $item = '<a href="/admin/courses/e/'.$irow['id'].'/">'.$row['Type'].': '.$irow['name'].'</a>';
                                }

                                $this->data['discounts'][$row['Type'].'-'.$row['id']] = [
                                    'id'            => $row['id'],
                                    'type'          => strtolower($row['Type']),
                                    'item'          => $item,
                                    'code'          => $row['code'],
                                    'percentage'    => $row['percentage'],
                                    'limit'         => $row['usage_limit'],
                                    'start'         => $user->format_date(strtotime($row['start']), $user->data['user_dateformat']),
                                    'end'           => $user->format_date(strtotime($row['end']), $user->data['user_dateformat']),
                                    'added'         => $user->format_date(strtotime($row['added']), $user->data['user_dateformat']),
                                    'editable'      => (strtotime($row['end']) > time()) ? 'true' : 'false'
                                ]; 
                            }

                            $pagination = \SenFramework\SenFramework::pagination($offset, ceil($total / $limit), 'admin/pricing/discount');

                            $this->data['pagination'] = $pagination['html'];

                            unset($pagination);
                        }
        
                    break;
        
                    case"c":
                        $this->data['override']['title'] = 'Create Discount &bull; Admin Panel ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
                        $this->data['template'] = 'discounts_create';

                        $sql = parent::mq("SELECT id, name FROM Courses ORDER BY name ASC");
                        
                        while($row = parent::mfa($sql)) {
                            $this->data['courses'][] = $row;
                        }

                        $sql = parent::mq("SELECT id, name FROM Pricing WHERE active='1' AND published='1' AND id <> 1 ORDER BY OrderNo ASC");
                        
                        while($row = parent::mfa($sql)) {
                            $this->data['plans'][] = $row;
                        }

                        $now = new \DateTime();
                        $now->setTime(0,0,0,0);

                        $defaultDate = clone $now;

                        $this->data['form'] = [
                            'code'          => $request->variable('code', ''),
                            'percentage'    => floatval($request->variable('percentage', '0')),
                            'start'         => $request->variable('start', $defaultDate->format('Y-m-d')),
                            'end'           => $request->variable('end', $defaultDate->modify('+1 month')->format('Y-m-d')),
                            'for'           => $request->variable('for', '0'),
                            'courses'       => $request->raw_variable('courses', [], \SenFramework\Request\request_interface::POST),
                            'plans'         => $request->raw_variable('plans', [], \SenFramework\Request\request_interface::POST)
                        ];
                        
                        if($request->is_set_post('submitDiscount')) {
                            $start = new \DateTime();
                            $start = $start->createFromFormat('Y-m-d', $this->data['form']['start']);
                            $start->setTime(0,0,0,0);

                            $end = new \DateTime();
                            $end = $end->createFromFormat('Y-m-d', $this->data['form']['end']);
                            $end->setTime(0,0,0,0);

                            if($this->data['form']['percentage'] == 0) {
                                $this->data['error'][] = 'Percentage must be higher than zero.';
                            }

                            if(empty($this->data['form']['start'])) {
                                $this->data['error'][] = 'No start date provided.';
                            } else {                            
                                if($start->format('U') < $now->format('U')) {
                                    $this->data['error'][] = 'Start date can not be in the past.';
                                }
                            }

                            if(empty($this->data['form']['end'])) {
                                $this->data['error'][] = 'No end date provided.';
                            } else {
                                if($end->format('U') <= $now->format('U')) {
                                    $this->data['error'][] = 'End date can not be in the past or set to the current date.';
                                } else if($end->format('U') == $start->format('U')) {
                                    $this->data['error'][] = 'End date can not the same as the start date.';
                                }
                            }

                            if($this->data['form']['for'] == 0 && empty($this->data['form']['courses'])) {
                                $this->data['error'][] = 'No courses selected.';
                            }

                            if($this->data['form']['for'] == 1 && empty($this->data['form']['plans'])) {
                                $this->data['error'][] = 'No plans selected.';
                            }

                            if(!isset($this->data['error'])) {
                                if($this->data['form']['for'] == 0) {
                                    foreach($this->data['form']['courses'] as $key => $value) {
                                        $discount = [
                                            'code'          => (!empty($this->data['form']['code'])) ? $this->data['form']['code'] : NULL,
                                            'percentage'    => $this->data['form']['percentage'],
                                            'course_id'     => $value,
                                            'start'         => $start->format('Y-m-d H:i:s'),
                                            'end'           => $end->format('Y-m-d H:i:s')
                                        ];

                                        parent::mq("INSERT INTO Courses_Pricing_Discounts ".parent::build_array('INSERT', $discount));
                                    }

                                    $this->data['type'] = 'Course';
                                } else {
                                    foreach($this->data['form']['plans'] as $key => $value) {
                                        $discount = [
                                            'code'          => (!empty($this->data['form']['code'])) ? $this->data['form']['code'] : NULL,
                                            'percentage'    => $this->data['form']['percentage'],
                                            'pricing_id'    => $value,
                                            'start'         => $start->format('Y-m-d H:i:s'),
                                            'end'           => $end->format('Y-m-d H:i:s')
                                        ];

                                        parent::mq("INSERT INTO Pricing_Discounts ".parent::build_array('INSERT', $discount));
                                    }

                                    $this->data['type'] = 'Pricing';
                                }

                                $this->data['success'] = true;
                            }
                        }        
                    break;

                    case"e":
                        if(!empty($route[4])) {
                            $discount = explode('-', $route[4]);
                            $table = NULL;

                            if($discount[0] == 'plan') {
                                $table = 'Pricing_Discounts';
                            } else if($discount[0] == 'course') {
                                $table = 'Courses_Pricing_Discounts';
                            }

                            if(!empty($table) && isset($discount[1])) {
                                $sql = parent::mq("SELECT * FROM ".$table." WHERE id='".parent::mres($discount[1])."'");

                                if($sql->num_rows > 0) {
                                    $row = parent::mfa($sql);

                                    $this->data['override']['title'] = 'Update Discount &bull; Admin Panel ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
                                    $this->data['template'] = 'discounts_edit';

                                    $sql = parent::mq("SELECT id, name FROM Courses ORDER BY name ASC");
                                    
                                    while($crow = parent::mfa($sql)) {
                                        $this->data['courses'][] = $crow;
                                    }

                                    $sql = parent::mq("SELECT id, name FROM Pricing WHERE active='1' AND published='1' AND id <> 1 ORDER BY OrderNo ASC");
                                    
                                    while($prow = parent::mfa($sql)) {
                                        $this->data['plans'][] = $prow;
                                    }

                                    $rstart = $rend = $now = new \DateTime();
                                    $now->setTime(0,0,0,0);

                                    $courses = $plans = [];

                                    if($discount[0] == 'plan') {
                                        $plans = [$row['id']];
                                    } else if($discount[0] == 'course') {
                                        $courses = [$row['id']];
                                    } 

                                    $rstart = $rstart->createFromFormat('Y-m-d H:i:s', $row['start']);
                                    $rend = $rend->createFromFormat('Y-m-d H:i:s', $row['end']);

                                    $this->data['form'] = [
                                        'code'          => $request->variable('code', $row['code']),
                                        'percentage'    => floatval($request->variable('percentage', (string)number_format($row['percentage'], 2))),
                                        'start'         => $request->variable('start', $rstart->format('Y-m-d')),
                                        'end'           => $request->variable('end', $rend->format('Y-m-d')),
                                        'for'           => $request->variable('for', ($discount[0] == 'plan') ? '1' : '0'),
                                        'courses'       => $request->raw_variable('courses', $courses, \SenFramework\Request\request_interface::POST),
                                        'plans'         => $request->raw_variable('plans', $plans, \SenFramework\Request\request_interface::POST)
                                    ];

                                    if($request->is_set_post('submitDiscount')) {
                                        $start = new \DateTime();
                                        $start = $start->createFromFormat('Y-m-d', $this->data['form']['start']);
                                        $start->setTime(0,0,0,0);

                                        $end = new \DateTime();
                                        $end = $end->createFromFormat('Y-m-d', $this->data['form']['end']);
                                        $end->setTime(0,0,0,0);

                                        if($this->data['form']['percentage'] == 0) {
                                            $this->data['error'][] = 'Percentage must be higher than zero.';
                                        }

                                        if(empty($this->data['form']['start'])) {
                                            $this->data['error'][] = 'No start date provided.';
                                        } else {
                                            
                                        
                                            if($start->format('U') < $now->format('U')) {
                                                $this->data['error'][] = 'Start date can not be in the past.';
                                            }
                                        }

                                        if(empty($this->data['form']['end'])) {
                                            $this->data['error'][] = 'No end date provided.';
                                        } else {
                                            if($end->format('U') <= $now->format('U')) {
                                                $this->data['error'][] = 'End date can not be in the past or set to the current date.';
                                            } else if($end->format('U') == $start->format('U')) {
                                                $this->data['error'][] = 'End date can not the same as the start date.';
                                            }
                                        }

                                        if($this->data['form']['for'] == 0 && empty($this->data['form']['courses'])) {
                                            $this->data['error'][] = 'No courses selected.';
                                        }

                                        if($this->data['form']['for'] == 1 && empty($this->data['form']['plans'])) {
                                            $this->data['error'][] = 'No plans selected.';
                                        }

                                        if(!in_array($row['id'], $this->data['form'][($discount[0] == 'plan') ? 'plans' : 'courses'])) {
                                            $this->data['error'][] = 'You can\'t remove the previous discounted '.$discount[0].'.';
                                        }

                                        if(!isset($this->data['error'])) {
                                            if($this->data['form']['for'] == 0) {
                                                foreach($this->data['form']['courses'] as $key => $value) {
                                                    $sql_ary = [
                                                        'code'          => (!empty($this->data['form']['code'])) ? $this->data['form']['code'] : NULL,
                                                        'percentage'    => $this->data['form']['percentage'],
                                                        'course_id'     => $value,
                                                        'start'         => $start->format('Y-m-d H:i:s'),
                                                        'end'           => $end->format('Y-m-d H:i:s')
                                                    ];
            
                                                    if($value == $row['id']) {
                                                        parent::mq("UPDATE Courses_Pricing_Discounts SET ".parent::build_array('UPDATE', $sql_ary)." WHERE id='".parent::mres($row['id'])."'");
                                                    } else {
                                                        parent::mq("INSERT INTO Courses_Pricing_Discounts ".parent::build_array('INSERT', $sql_ary));
                                                    }
                                                }
            
                                                $this->data['type'] = 'Course';
                                            } else if($this->data['form']['for'] == 1) {
                                                foreach($this->data['form']['plans'] as $key => $value) {
                                                    $sql_ary = [
                                                        'code'          => (!empty($this->data['form']['code'])) ? $this->data['form']['code'] : NULL,
                                                        'percentage'    => $this->data['form']['percentage'],
                                                        'pricing_id'    => $value,
                                                        'start'         => $start->format('Y-m-d H:i:s'),
                                                        'end'           => $end->format('Y-m-d H:i:s')
                                                    ];
            
                                                    if($value == $row['id']) {
                                                        parent::mq("UPDATE Pricing_Discounts SET ".parent::build_array('UPDATE', $sql_ary)." WHERE id='".parent::mres($row['id'])."'");
                                                    } else {
                                                        parent::mq("INSERT INTO Pricing_Discounts ".parent::build_array('INSERT', $sql_ary));
                                                    }
                                                }
            
                                                $this->data['type'] = 'Course';
                                            }
            
                                            $this->data['success'] = true;
                                        }
                                    }
                                } else {
                                    $this->data['triggererror'] = '404';
                                }
                            } else {
                                $this->data['triggererror'] = '404';
                            }
                        } else {
                            $this->data['triggererror'] = '404';
                        }
                    break;
                }
            break;

            // Course Pricing
            case"courses":
                $Courses = new \SenFramework\Courses;
                $Courses->url = 'admin/pricing/courses';

                switch($route[3]) {
                    default:
                    case"p":
                        $offset = intval($route[4]);
                                
                        if($offset <= 0) {
                            $offset = 1;
                        }
                        
                        $this->data['override']['title'] = 'Course Pricing' . (($offset > 1) ? ' &bull; Page ' . $offset : NULL) . ' &bull; Admin Panel ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
                        $this->data['template'] = 'courses_list';
                        
                        $this->data['courses'] = $Courses->getCourses(NULL, $offset, 9);

                        $this->data['pagination'] = $this->data['courses']['pagination'];
                        $this->data['meta'] = $this->data['courses']['meta'];
        
                        unset($this->data['courses']['pagination'], $this->data['courses']['meta']);

                        foreach($this->data['courses'] as $key => $value) {
                            $this->data['courses'][$key]['cost'] = $Courses->getCoursePricing($value['id']);
                        } 
                    break;

                    case"e":
                        if(!empty($route[4])) {
                            $this->data['course'] = $Courses->getCourse((int)$route[4]);

                            if(!empty($this->data['course'])) {
                                $this->data['course']['cost'] = $Courses->getCoursePricing($this->data['course']['id']);

                                $this->data['override']['title'] = 'Editting "'.$this->data['course']['name'].'" Course &bull; Admin Panel ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
                                $this->data['template'] = 'courses_edit';

                                $this->data['form'] = [
                                    'cost'  => $request->variable('cost', (string)$this->data['course']['cost']['price'])
                                ];

                                if($request->is_set_post('updatePricing')) {
                                    if(!empty($this->data['form']['cost'])) {
                                        $cost = number_format($this->data['form']['cost'], 2);

                                        parent::mq("UPDATE Courses_Pricing SET price='".parent::mres($cost)."' WHERE course_id='".parent::mres($this->data['course']['id'])."'");

                                        $this->data['success'] = true;
                                    } else {
                                        $this->data['error'][] = 'Cost can not be empty or zero.';
                                    }
                                }                                
                            } else {
                                $this->data['triggererror'] = '404';
                            }
                        } else {
                            $this->data['triggererror'] = '404';
                        }
                    break;
                }
            break;
        }
    }
}