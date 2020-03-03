<?php

namespace SenFramework\ACP;

class Team extends \SenFramework\DB\Database {

    public $data;

    public function __construct($route = NULL, $query = NULL) {
        global $request, $senConfig, $user, $phpbb;

        $security = new \SenFramework\Security();
		$mailer = new \SenFramework\Mailer();

        $this->data['template_folder'] = 'acp/team';
        $this->data['nav'] = 'team';
        $this->data['single'] = true;

        switch($route[2]) {
            default:        
                $this->data['override']['title'] = 'Team &bull; Admin Panel ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
                $this->data['template'] = 'list';

                $offset = intval($route[3]);
					
                if($offset <= 0) {
                    $offset = 1;
                }

                $this->data['team'] = $this->fetchTeam($offset);
                $this->data['pagination'] = $this->data['team']['pagination'];
				$this->data['meta'] = $this->data['team']['meta'];
					
				unset($this->data['team']['pagination'], $this->data['team']['meta']);                
            break;

            case"c":
                $this->data['override']['title'] = 'Add Team Member &bull; Admin Panel ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
                $this->data['template'] = 'edit';
                $this->data['mode'] = 'create';

                if($request->is_set_post('submit')) {
                    $memberData = $this->data['member'] = [
                        'name'          => $request->variable('name', $member['name'], true),
                        'active'        => intval($request->variable('active', (string)$member['active'], true)),
                        'position'      => $request->variable('position', $member['position'], true),
                        'avatar'        => $request->variable('avatarFile', $member['avatar'], true),
                        'avatar_type'   => 'local',
                        'twitter'       => $request->variable('twitter', $member['twitter'], true),
                        'linkedin'      => $request->variable('linkedin', $member['linkedin'], true)
                    ];

                    if(empty($this->data['member']['name'])) {
                        $this->data['error'][] = 'No name was provided.';
                    }

                    if(empty($this->data['member']['position'])) {
                        $this->data['error'][] = 'No position was provided.';
                    }

                    if(empty($this->data['member']['avatar'])) {
                        $this->data['error'][] = 'No avatar was provided.';
                    }

                    if(!isset($this->data['error'])) {
                        if($this->data['member']['avatar'] !== $member['avatar']) {
                            $this->data['member']['avatar_type'] = 'limbo';

                            if($FilePond->isFileId($this->data['member']['avatar'])) {
                                $file = $FilePond->getTempFile($this->data['member']['avatar']);

                                if(!empty($file)) {
                                    $result = $FilePond->save($this->data['member']['avatar'], 'img/content/');

                                    if($result) {
                                        $memberData['avatar'] = $this->data['member']['avatar'] = '/img/content/'.$file['name'];
                                        $this->data['member']['avatar_type'] = 'local';
                                    }
                                }
                            }
                        }

                        unset($memberData['avatar_type']);

                        parent::mq("INSERT INTO Team ".parent::build_array('INSERT', $memberData));

                        $this->data['success'] = true;
                    }
                }                
            break;

            case"e":
                $id = intval($route[3]);

                $member = $this->fetchMember($id);

                if(!empty($member)) {
                    $this->data['override']['title'] = 'Editting "'.$member['name'].'" Team Member &bull; Admin Panel ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
                    $this->data['template'] = 'edit';
                    $this->data['mode'] = 'edit';

                    $this->data['member'] = $member;
                    $this->data['member']['avatar_type'] = 'local';
                    
                    if($request->is_set_post('submit')) {
                        $memberData = $this->data['member'] = [
                            'name'          => $request->variable('name', $member['name'], true),
                            'active'        => intval($request->variable('active', (string)$member['active'], true)),
                            'position'      => $request->variable('position', $member['position'], true),
                            'avatar'        => $request->variable('avatarFile', $member['avatar'], true),
                            'avatar_type'   => 'local',
                            'twitter'       => $request->variable('twitter', $member['twitter'], true),
                            'linkedin'      => $request->variable('linkedin', $member['linkedin'], true)
                        ];

                        if($this->data['member']['avatar'] !== $member['avatar']) {
                            $this->data['member']['avatar_type'] = 'limbo';

                            if($FilePond->isFileId($this->data['member']['avatar'])) {
                                $file = $FilePond->getTempFile($this->data['member']['avatar']);
    
                                if(!empty($file)) {
                                    $result = $FilePond->save($this->data['member']['avatar'], 'img/content/');
    
                                    if($result) {
                                        $memberData['avatar'] = $this->data['member']['avatar'] = '/img/content/'.$file['name'];
                                        $this->data['member']['avatar_type'] = 'local';
                                    }
                                }
                            }
                        }

                        unset($memberData['avatar_type']);

                        parent::mq("UPDATE Team SET ".parent::build_array('UPDATE', $memberData." WHERE id='".parent::mres($id)."'"));

                        $this->data['success'] = true;
                    }                    
                } else {
                    $this->data['triggererror'] = '404';
                }
            break;

            case"d":
                $id = intval($route[3]);

                $member = $this->fetchMember($id);

                if(!empty($member)) {
                    $this->data['override']['title'] = 'Remove Team Member "'.$member['name'].'" &bull; Admin Panel ' . $senConfig->pageDefaults['seperator'] . ' ' . $senConfig->pageDefaults['name'];
                    $this->data['template'] = 'delete';

                    $this->data['member'] = $member;

                    if($request->is_set_post('removeTeam')) {
                        parent::mq("DELETE FROM Team WHERE id='".parent::mres($id)."'");

                        $this->data['success'] = true;
                    }
                } else {
                    $this->data['triggererror'] = '404';
                }
            break;
        }
    }

    private function fetchTeam(int $offset = 1) {
        $members = array();

        $total = 0;
        $limit = 9;
		
		$sqlCount = parent::mq("SELECT 
				COUNT(*) as total
			FROM 
				Team
			ORDER BY
                LOWER(name)
            ASC");
		
		if(!empty($sqlCount) && $sqlCount->num_rows > 0) {
			$results = parent::mfa($sqlCount);
			$total = $results['total'];
		}
		
		$sql = parent::mq("SELECT 
			*
		FROM
			Team
		ORDER BY
            LOWER(name)
        ASC 
        LIMIT " . (($offset - 1) * parent::mres($limit)) . " , " . parent::mres($limit));
        
        if(!empty($sql) && $sql->num_rows > 0) {
			while ($row = parent::mfa($sql)) {
                $members[] = $row;
            }

            $pagination = \SenFramework\SenFramework::pagination($offset, ceil($total / $limit), 'admin/team');

			$members['pagination'] = $pagination['html'];
			$members['meta'] = $pagination['meta'];
        }

        return $members;
    }

    private function fetchMember(int $id = 0): array {
        $member = array();
        
        if(!empty($id)) {
            $sql = parent::mq("SELECT * FROM Team WHERE id='".parent::mres($id)."'");

            if($sql->num_rows > 0) {
                $member = parent::mfa($sql);
            }
        }

        return $member;
    }
}