<?php

namespace SenFramework\Controllers;

class Contact extends \SenFramework\DB\Database {
	
	public $data;
	
	public function __construct() {
        global $request, $phpbb;

        $security = new \SenFramework\Security();
        $ip = \SenFramework\SenFramework::getIP();
        $recaptcha = new \ReCaptcha\ReCaptcha(CAPTCHA_SECRET);

        $this->data['form'] = [
            'first_name'            => substr(strip_tags($request->variable('first_name', '', true)), 0, 255),
            'last_name'             => substr(strip_tags($request->variable('last_name', '', true)), 0, 255),
            'email'                 => $request->variable('email', '', true),
            'message'               => substr(strip_tags($request->variable('message', '', true)), 0, 16384),
            'captcha_response'		=> $request->variable('g-recaptcha-response', '', true),
            'CSRF' 					=> [
                $request->variable('CSRFName', '', true),
                $request->variable('CSRFToken', '', true)
            ]
        ];

        if(empty($this->data['form']['CSRF'][0])) {
            $this->data['form']['CSRF'] = $security->generate_csrf_token('Contact');
        }

        if($request->is_set_post('sendContact')) {
            if(!empty($this->data['form']['CSRF'][0]) && !empty($this->data['form']['CSRF'][1])) {
                if(empty($this->data['form']['email'])) {
                    $this->data['error'][] = 'No Email Address Provided.';
                }

                if (!preg_match('/^' . $phpbb->get_preg_expression('email') . '$/i', strtolower($this->data['form']['email']))) {
                    $this->data['error'][] = 'Invalid email address provided.';
                }

                if(!isset($this->data['error'])) {
                    if($security->validate_csrf_token($this->data['form']['CSRF'][0], $this->data['form']['CSRF'][1], false)) {
                        $resp = $recaptcha->verify($this->data['form']['captcha_response'], $ip); 
                        
                        if ($resp->isSuccess()) {
                            $mail = new \PHPMailer\PHPMailer\PHPMailer;
                            
                            $mail->isSendmail();
                            
                            $mail->setFrom('noreply@lvlupdojo.com', 'LVLUP Dojo');
                        
                            $mail->addAddress('pizza@lvlupdojo.com', 'LVLUP Dojo');
                            
                            $mail->Subject = 'LVLUP Dojo: Contact Form Message';
                            
                            $mail->CharSet = 'utf-8';
                            
                            $mail->Body = 'Contact Form Submission\n\n First Name: '.$this->data['form']['first_name'].'\r\n Last Name: '.$this->data['form']['last_name'].'\r\n Message:\r\n'.$this->data['form']['message'];
                            
                            if (!$mail->send()) {
                                $this->data['error'][] = "Mailer Error: " . $mail->ErrorInfo;
                            } else {
                                $this->data['success'] = 'Thank you for contacting us, we\'ll get back to you as soon as possible.';
                            }
                        } else {
                            $this->data['error']['captcha'] = 'reCAPTCHA returned the following error: <ul>';

                            foreach ($resp->getErrorCodes() as $code) {
                                $this->data['error']['captcha'] .= '<li>'.$code.'</li>';	 
                            }

                            $this->data['error']['captcha'] .= '</ul>';
                        }
                    } else {
                        $this->data['error'][] = 'No CSRF Token Provided! Bruteforce attempt logged.';
                    }
                }
            } else {
                $this->data['error'][] = 'No CSRF Token Provided! Bruteforce attempt logged.';
            }
        }        
    }
}