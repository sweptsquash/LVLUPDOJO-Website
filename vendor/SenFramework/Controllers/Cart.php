<?php

namespace SenFramework\Controllers;

class Cart extends \SenFramework\DB\Database {

    public $data;

    public function __construct($route = NULL, $query = NULL)  {
        global $request, $senConfig, $user, $cart;
        
        $this->data['addressbarTrack'] = 'false';
		
		switch($route[1]) {
            default:
                $this->data['items'] = $cart->fetchCartItems($user->data['cart']['id']);
                $this->data['cost'] = $user->data['cart']['cost'];
            break;

            case"checkout":
                if(!$user->data['is_registered']) {
                    $_SESSION['redirect'] = '/cart/checkout/';
                    header("Location: https://".((DEVELOP) ? 'development.lvlupdojo.com' : 'www.lvlupdojo.com')."/sign-in/");
                    exit;
                }

                $this->data['template'] = 'checkout';

                if($request->is_set_post('paymentToken')) {
                    $Transactions = new \SenFramework\Billing;

                    $checkout = [
                        'method'    => $request->variable('paymentMethod', ''),
                        'token'     => $request->variable('paymentToken', ''),
                        'email'     => $request->variable('paymentEmail', '')
                    ];

                    $success = false;
                    $error = false;
                    $message = NULL;
                    $description = NULL;

                    if($checkout['method'] === 'stripe') {
                        foreach($user->data['cart']['items'] as $item) {
                            $description .= $item['name'].', ';
                        }

                        $description = rtrim($description, ', ');

                        \Stripe\Stripe::setApiKey((DEVELOP) ? STRIPE_SECRET_DEV : STRIPE_SECRET);
                        \Stripe\Stripe::setClientId((DEVELOP) ? STRIPE_KEY_DEV : STRIPE_KEY);
                        \Stripe\Stripe::setApiVersion(STRIPE_API_VERSION);

                        $customer = \Stripe\Customer::create(array(
                            'email' => $checkout['email'],
                            'source'  => $checkout['token']
                        )); 

                        $charge = \Stripe\Charge::create(array(
                            'customer'      => $customer->id,
                            'amount'        => 100 * $user->data['cart']['cost'],
                            'currency'      => 'usd',
                            'description'   => $description
                        ));
                        
                        if($charge['status'] === 'succeeded') {
                            $invoice = $Transactions->processCart([
                                'method'     => 'stripe',
                                'email'      => $checkout['email'],
                                'card'       => $charge['source']['last4'],
                                'card_brand' => $charge['source']['brand'],
                                'id'         => $charge['id'],
                                'payer_id'   => $customer->id
                            ]);

                            if(!empty($invoice)) {
                                $_SESSION['invoiceID'] = $invoice;

                                $success = true;
                            } else {
                                $this->data['error'] = true;
                            }
                        } else {
                            $this->data['error'] = true;
                        }
                    } else {
                        $invoice = $Transactions->processCart([
                            'method'     => 'paypal',
                            'email'      => $checkout['email'],
                            'id'         => $checkout['token'],
                            'payer_id'   => $checkout['email']
                        ]);

                        if(!empty($invoice)) {
                            $_SESSION['invoiceID'] = $invoice;

                            $success = true;
                        } else {
                            $this->data['error'] = true;
                        }
                    }

                    if($success) {
                        header("Location: https://" . ((DEVELOP) ? 'development.lvlupdojo.com' : 'www.lvlupdojo.com') . "/cart/thank-you/");
                        exit;
                    }
                }                
            break;

            case"thank-you":
                if(!$user->data['is_registered']) {
                    $_SESSION['redirect'] = '/cart/';
                    header("Location: https://".((DEVELOP) ? 'development.lvlupdojo.com' : 'www.lvlupdojo.com')."/sign-in/");
                    exit;
                }

                $this->data['template'] = 'thankyou';

                if(isset($_SESSION['invoiceID']) && !empty($_SESSION['invoiceID'])) {
                    $this->data['invoice_number'] = $_SESSION['invoiceID'];

                    unset($_SESSION['invoiceID']);
                }
            break;
        }
    }
}
