<?php

namespace SenFramework\ACP;

class Mentors extends \SenFramework\DB\Database {

    public $data;

    public function __construct($route = NULL, $query = NULL) {
        global $request, $senConfig, $user;

        $this->data['template_folder'] = 'acp/mentors';
        $this->data['nav'] = 'mentors';
        $this->data['single'] = true;

        $FilePond = new \SenFramework\FilePond\RequestHandler;   

        switch($route[2]) {
            default:
            case"p":
                $offset = intval($route[3]);
                                        
                if($offset <= 0) {
                    $offset = 1;
                }

                $limit = 9;
                
                $this->data['override']['title'] = 'Course Mentors' . (($offset > 1) ? ' &bull; Page ' . $offset : NULL) . ' &bull; Admin Panel ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
                $this->data['template'] = 'list';

                $total = 0;
                
                $sqlCount = parent::mq("SELECT 
                        COUNT(*) as total
                    FROM 
                        Courses_Mentors
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
                ORDER BY
                    name
                ASC 
                LIMIT " . (($offset - 1) * parent::mres($limit)) . " , " . parent::mres($limit));
                
                $i = 0;
                
                if(!empty($sql) && $sql->num_rows > 0) {
                    while ($row = parent::mfa($sql)) {				
                        $this->data['mentors'][] = $row;	
                    }
                    
                    $pagination = \SenFramework\SenFramework::pagination($offset, ceil($total / $limit), 'admin/mentors');
        
                    $this->data['pagination'] = $pagination['html'];
                    unset($pagination['meta']);
                }
            break;

            case"c":
                $this->data['override']['title'] = 'Create Course Mentor ' . $row['name'] . ' &bull; Admin Panel ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
                $this->data['template'] = 'create';

                $this->data['form'] = [
                    'name'          => $request->variable('name', ''),
                    'user'          => [
                        'id'        => $request->variable('mentorID', ''),
                        'name'      => $request->variable('user', '')
                    ],
                    'avatar'        => $request->variable('avatarFile', ''),
                    'avatar_type'   => 'local',
                    'description'   => $request->raw_variable('description', ''),
                    'keywords'      => $request->variable('keywords', ''),
                    'active'        => $request->variable('active', '')
                ];

                if($request->is_set_post('createMentor')) {
                    if(empty($this->data['form']['name'])) {
                        $this->data['error'][] = 'Mentors name not supplied.';
                    }

                    if(str_replace('https://'.((DEVELOP) ? 'lvlupdojo.senshudo.tv' : 'www.lvlupdojo.com'), '', $this->data['form']['avatar']) !== NULL) {
                        $this->data['form']['avatar_type'] = 'limbo';
                    }

                    if(!isset($this->data['error'])) {
                        $mentor = [
                            'name'          => $this->data['form']['name'],
                            'description'   => $this->data['form']['description'],
                            'keywords'      => $this->data['form']['keywords'],
                            'active'        => $this->data['form']['active'],
                            'updated'       => date('Y-m-d H:i:s')
                        ];

                        if(!empty($this->data['form']['avatar'])) {
                            if(str_replace('https://'.((DEVELOP) ? 'lvlupdojo.senshudo.tv' : 'www.lvlupdojo.com'), '', $this->data['form']['avatar']) !== $row['avatar']) {
                                if($FilePond->isFileId($this->data['form']['avatar'])) {
                                    $file = $FilePond->getTempFile($this->data['form']['avatar']);
    
                                    if(!empty($file)) {
                                        $result = $FilePond->save($this->data['form']['avatar'], 'img/course/');
    
                                        if($result) {
                                            $mentor['avatar'] = $this->data['form']['avatar'] = '/img/course/'.$file['name'];
                                        }
                                    }
                                }
                            }
                        }

                        parent::mq("INSERT INTO Courses_Mentors ".parent::build_array('INSERT', $mentor));

                        $this->data['success'] = true;
                    }
                }
            break;

            case"e":
                if(!empty($route[3])) {
                    $sql = parent::mq("SELECT * FROM Courses_Mentors WHERE id='".parent::mres($route[3])."'");

                    if($sql->num_rows > 0) {
                        $row = parent::mfa($sql);

                        $this->data['override']['title'] = 'Editing Course Mentor ' . $row['name'] . ' &bull; Admin Panel ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
                        $this->data['template'] = 'edit';

                        $this->data['form'] = [
                            'name'          => $request->variable('name', $row['name']),
                            'user'          => [
                                'id'        => $request->variable('mentorID', $row['user_id']),
                                'name'      => $request->variable('user', $username)
                            ],
                            'avatar'        => $request->variable('avatarFile', $row['avatar']),
                            'avatar_type'   => 'local',
                            'description'   => $request->raw_variable('description', $row['description']),
                            'keywords'      => $request->variable('keywords', $row['keywords']),
                            'active'        => $request->variable('active', (string)$row['active'])
                        ];

                        if($request->is_set_post('updateMentor')) {
                            if(empty($this->data['form']['name'])) {
                                $this->data['error'][] = 'Mentors name not supplied.';
                            }

                            if(str_replace('https://'.((DEVELOP) ? 'lvlupdojo.senshudo.tv' : 'www.lvlupdojo.com'), '', $this->data['form']['avatar']) !== $row['avatar']) {
                                $this->data['form']['avatar_type'] = 'limbo';
                            }

                            if(!isset($this->data['error'])) {
                                $mentor = [
                                    'name'          => $this->data['form']['name'],
                                    'description'   => $this->data['form']['description'],
                                    'keywords'      => $this->data['form']['keywords'],
                                    'active'        => $this->data['form']['active'],
                                    'updated'       => date('Y-m-d H:i:s')
                                ];

                                if(!empty($this->data['form']['avatar'])) {
                                    if(str_replace('https://'.((DEVELOP) ? 'lvlupdojo.senshudo.tv' : 'www.lvlupdojo.com'), '', $this->data['form']['avatar']) !== $row['avatar']) {
                                        if($FilePond->isFileId($this->data['form']['avatar'])) {
                                            $file = $FilePond->getTempFile($this->data['form']['avatar']);
            
                                            if(!empty($file)) {
                                                $result = $FilePond->save($this->data['form']['avatar'], 'img/course/');
            
                                                if($result) {
                                                    $mentor['avatar'] = $this->data['form']['avatar'] = '/img/course/'.$file['name'];
                                                }
                                            }
                                        }
                                    }
                                }

                                parent::mq("UPDATE Courses_Mentors SET ".parent::build_array('UPDATE', $mentor)." WHERE id='".parent::mres($row['id'])."'");

                                $this->data['success'] = true;
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
    }
}