<?php

ini_set('max_execution_time', 0);
ini_set('date.timezone', 'UTC'); 
	
if (!defined('ABSPATH')) {
	define('ABSPATH', dirname(__FILE__) . '/../');
}

require_once(ABSPATH.'init.php');

class discordRole extends \SenFramework\DB\Database {
	public function __construct() {
        $billing = new \SenFramework\Billing;
        $discord = new \RestCord\DiscordClient(['token' => DISCORD_BOT_TOKEN]);

        $sql = parent::mq("SELECT user_id, group_id, user_dsid FROM Users ORDER BY user_id DESC");

        while($row = parent::mfa($sql)) {
            $user_row = [
                'user_id'   => (int)$row['user_id'],
                'user_dsid' => (int)$row['user_dsid'],
                'group_id'  => (int)$row['group_id']
            ];

            if(!empty($user_row['user_dsid']) && ($user_row['group_id'] != 1 && $user_row['group_id'] != 2 && $user_row['group_id'] != 3)) {
                $member =  NULL;

                try {
                    $member = $discord->guild->getGuildMember([
                        'guild.id'      => DISCORD_SERVER_ID,
                        'user.id'       => $user_row['user_dsid']
                    ]);
                } catch(\Exception $e) {}

                if(!empty($member)) {
                    $subStatus = $billing->subscriptionStatus($user_row['user_id'], 'U');

                    if($subStatus['id'] == 1) {
                        if($this->hasRole($member->roles)) {
                            try {
                                $discord->guild->removeGuildMemberRole([
                                    'guild.id'      => DISCORD_SERVER_ID,
                                    'user.id'       => $user_row['user_dsid'],
                                    'role.id'       => DISCORD_ROLE_ID
                                ]);
                            } catch(\Exception $e) {}
                        }
                    } else {
                        if(!$this->hasRole($member->roles)) {
                            try {
                                $discord->guild->addGuildMemberRole([
                                    'guild.id'      => DISCORD_SERVER_ID,
                                    'user.id'       => $user_row['user_dsid'],
                                    'role.id'       => DISCORD_ROLE_ID
                                ]);
                            } catch(\Exception $e) {}
                        }
                    }
                }
            }      
            
            sleep(1);
        }
    }

    private function hasRole(array $roles) {
        if(!empty($roles)) {
            foreach($roles as $role) {
                if($role == DISCORD_ROLE_ID) {
                    return true;
                }
            }
        }

        return false;
    }
}

new discordRole;