<?php

namespace SenFramework;

class Cart extends \SenFramework\DB\Database {
    public function __construct() {
        self::fetchCart();
    }

    public function fetchCart() {
        global $request, $user, $security;

        $data = [];

        /*if($user->data['user_id'] !== 1) {
            parent::mq("UPDATE Cart SET user_id='".parent::mres($user->data['user_id'])."' WHERE user_id='1' AND session_id='".parent::mres($user->data['session_id'])."'");
        }*/

        $sql = parent::mq("SELECT id FROM Cart WHERE session_id='".parent::mres($user->data['session_id'])."' AND user_id='".$user->data['user_id']."'");

        if($sql->num_rows > 0) {
            $row = parent::mfa($sql);

            $data = [
                'id'        => $row['id'],
                'items'     => [],
                'cost'      => 0,
                'total'     => self::itemCount($row['id']),
            ];

        } else {
            $cartSession = (isset($user->data['session_id']) && !empty($user->data['session_id'])) ? $user->data['session_id'] : session_id();

            $sql_ary = [
                'session_id'    => $cartSession,
                'user_id'       => (int)$user->data['user_id']
            ];

            parent::mq("INSERT INTO Cart ".parent::build_array('INSERT', $sql_ary));

            $data = [
                'id'        => parent::lastId(),
                'items'     => [],
                'cost'      => 0,
                'total'     => 0,
            ];
        }
        
        $user->data['cart'] = $data;
        $user->data['cart']['items'] = $this->fetchCartItems();
    }

    public function destoryCart(int $cartID = 0) {
        global $user;

        if(!empty($cartID) && $user->data['user_id'] !== 1) {
            $sql = parent::mq("SELECT id FROM Cart WHERE id='".parent::mres($cartID)."' AND user_id='".parent::mres($user->data['user_id'])."'");

            if($sql->num_rows > 0) {
                parent::mq("DELETE FROM Cart WHERE id='".parent::mres($cartID)."'");
                parent::mq("DELETE FROM Cart_Items WHERE cart_id='".parent::mres($cartID)."'");
            }
        }
    }

    public function itemCount(int $cartID = 0): int {
        if(!empty($cartID)) {
            $sql = parent::mq("SELECT count(*) AS total FROM Cart_Items WHERE Cart_Items.cart_id='".parent::mres($cartID)."'");

            if($sql->num_rows > 0) {
                $count = parent::mfa($sql);

                return (int)$count['total'];
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

    public function fetchCartItems(int $cartID = 0) {
        global $user, $courses;

        $data = [];

        if(empty($cartID)) {
            $cartID = $user->data['cart']['id'];
        }

        if(!empty($cartID)) {
            $sql = parent::mq("SELECT 
                Courses.id,
                Courses.name,
                Courses.slug,
                Courses.thumbnail,
                Courses_Mentors.user_id as MentorID,
				Courses_Mentors.name as MentorName,
				Courses_Mentors.slug as MentorSlug,
				Courses_Mentors.avatar as MentorAvatar,
				Courses_Mentors.description as MentorAbout
            FROM 
                Cart_Items 
            INNER JOIN 
                Courses
            ON 
                Cart_Items.course_id=Courses.id
            LEFT JOIN
				Courses_Mentors
			ON
				Courses_Mentors.id=Courses.mentor
            WHERE 
                Cart_Items.cart_id='".parent::mres($cartID)."'");

            if($sql->num_rows > 0) {
                $cost = 0;

                while($row = parent::mfa($sql)) {
                    $pricing = $courses->getCoursePricing($row['id']);

                    $data[] = [
                        'id'            =>  $row['id'],
                        'name'          =>  $row['name'],
                        'slug'          =>  $row['slug'],
                        'thumbnail'     =>  $row['thumbnail'],
                        'cost'          =>  $pricing,
                        'mentor'        =>  [
                            'id'		=>  $row['MentorID'],
                            'name'		=>  $row['MentorName'],
                            'slug'  	=>  $row['MentorSlug'],
                            'avatar' 	=>  $row['MentorAvatar'],
                            'about'		=>  $row['MentorAbout']
                        ]
                    ];

                    $cost += ((isset($pricing['discount_value']) && !empty($pricing['discount_value'])) ? $pricing['discount_value'] : $pricing['price']);
                }

                $user->data['cart']['cost'] = number_format($cost, 2);
            }
        }

        return $data;
    }

    public function itemInCart(int $itemID = 0) {
        if(!empty($itemID)) {
            $sql = parent::mq("SELECT id FROM Cart_Items WHERE course_id='" . parent::mres($itemID) . "' AND cart_id='".parent::mres($user->data['cart']['id'])."'");

            if($sql->num_rows > 0) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }

        return false;
    }

    public function addItem(int $itemID = 0) {
        global $courses, $user;

        $response = array();

        $inCart = self::itemInCart($itemID);

        if(!empty($itemID) && !$inCart) {
            $sql = parent::mq("SELECT id, slug FROM Courses WHERE id='".parent::mres($itemID)."' AND published='1'");

            if($sql->num_rows > 0) {
                $row = parent::mfa($sql);
                
                $response['result'] = 'success';

                $sql_ary = [
                    'cart_id'          =>  $user->data['cart']['id'],
                    'course_id'        =>  $row['id']
                ];

                parent::mq("INSERT INTO Cart_Items ".parent::build_array('INSERT', $sql_ary));
            } else {
                $response['result'] = 'error';
                $response['message'] = "No course with the id ".$id." was found.";
            }
        } else {
            if(!$inCart) {
                $response['result'] = 'success';
            } else {
                $response['result'] = 'error';
                $response['message'] = "No course id was provided.";
            }
        }

        return $response;
    }

    public function removeItem(int $itemID = 0) {
        global  $user;

        $response = array();

        if(!empty($itemID)) {
            parent::mq("DELETE FROM Cart_Items WHERE cart_id='".parent::mres($user->data['cart']['id'])."' AND course_id='".parent::mres($itemID)."'");

            $response['result'] = 'success';
        } else {
            $response['result'] = 'error';
            $response['message'] = "No course id was provided.";
        }

        return $response;
    }

    public function clearCart(): array {
        parent::mq("DELETE FROM Cart_Items WHERE cart_id='".parent::mres($user->data['cart']['id'])."'");

        $response = array();
        $response['result'] = 'success';

        return $response;
    }
}