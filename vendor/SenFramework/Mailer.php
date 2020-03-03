<?php

namespace SenFramework;

class Mailer {

    private $mandrill;

    public function SendMail(string $template, string $subject, array $data = [], array $variables = []) {
        try {
            $path = ABSPATH.'theme/email/'.$template.'.html';

            if(!empty($template) && is_readable($path)) {
                $html = file_get_contents($path);

                if(empty($data)) {
                    throw new \Exception('No receipts information supplied.');
                }

                if(!empty($variables)) {
                    $html = str_replace(array_keys($variables), $variables, $html);
                }

                $mail_params = [
                    'html'          => $html,
                    'text'          => strip_tags($html),
                    'subject'       => $subject,
                    'from_email'    => 'pizza@lvlupdojo.com',
                    'from_name'     => 'LVLUP Dojo',
                    'to'            => [
                        [
                            'email' => $data['email'],
                            'name'  => $data['name'],
                            'type'  => 'to'
                        ]
                    ],
                    'headers' => ['Reply-To' => 'pizza@lvlupdojo.com']
                ];

                if(!empty($data['user_id'])) {
                    $mail_params['recipient_metadata'] = [
                        [
                            'rcpt' => $data['email'],
                            'values' => array('user_id' => $data['user_id'])
                        ]
                    ];
                }

                if(!empty($data['attachments'])) {
                    $mail_params['attachments'] = $data['attachments'];
                }

                try {
                    $this->mandrill = new \Mandrill(MANDRILL_API);

                    $result = $this->mandrill->messages->send($mail_params);

                    return 'sent';
                } catch(\Mandrill_Error $e) {
                    throw new \Exception($e->getMessage());
                }
            } else {
                throw new \Exception('No Template Defined.');
            }
        } catch (\Exception $e) {
            return '\SenFramework\Mailer: ' . $e->getMessage();
        }
    }

}