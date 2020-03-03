$(function() {
    var stripeHandler = StripeCheckout.configure({
        key: paymentConfig.stripe,
        image: 'https://www.lvlupdojo.com/img/dojo-logo-128x128.png',
        name: 'LVLUP Dojo',
        allowRememberMe: false,
        zipCode: true,
        billingAddress: true,
        locale: 'auto',
        token: function(token) {
            swal({
                title: "Processing...",
                icon: "info",
                button: false,
                text: "Please wait while we process your order."
            });

            $('input[name="paymentToken"]').val(token.id);
            $('input[name="paymentEmail"]').val(token.email);
            $('form[name="cartCheckout"]').submit();
        }
    });

    $('input[name="paymentMethod"]').change(function() {
        var service = $(this).val();

        switch(service) {
            default:
            case"stripe":
                $('#paypal-button-container').removeClass('d-inline-block').removeClass('align-middle').addClass('d-none');
                $('button[name="proceedCheckout"]').removeClass('d-none');
            break;

            case"paypal":
                $('#paypal-button-container').removeClass('d-none').addClass('d-inline-block').addClass('align-middle');
                $('button[name="proceedCheckout"]').addClass('d-none');
            break;
        }
    });

    $('#proceedCheckoutBtn').click(function(e) {
        e.preventDefault();

        var paymentMethod = $('input[name="paymentMethod"]:checked').val(),
            paymentTotal = 100 * parseFloat(paymentDetails.amount.total);

        switch(paymentMethod) {
            default:
            case"stripe":
                stripeHandler.open({
                    name: 'LVLUP Dojo',
                    email: paymentDetails.email,
                    description: paymentDetails.description,
                    currency: paymentDetails.amount.currency,
                    amount: paymentTotal
                });
            break;
        }
    });

    paypal.Button.render({
        env: 'production',
        client: {
            production: paymentConfig.paypal
        },
        style: {
            label: 'paypal',
            size:  'medium',
            shape: 'pill',
            color: 'blue',
            tagline: false
        },
        commit: true,
        payment: function(data, actions) {
            return actions.payment.create({
                intent: "sale",
                payer: {
                    payment_method: "paypal",
                    payer_info: {
                        email: paymentDetails.email
                    }
                },
                transactions: [{
                    amount: {
                        total: paymentDetails.amount.total,
                        currency: paymentDetails.amount.currency
                    },
                    description: paymentDetails.description,
                    item_list: {
                        items: paymentDetails.items
                    }
                }],
                redirect_urls: {
                    cancel_url: 'https://www.lvlupdojo.com/cart/'
                }
            });
        },
        onAuthorize: function(data, actions) {
            return actions.payment.execute().then(function() {
                if(data.state == 'approved') {
                    $('input[name="paymentToken"]').val(data.id);
                    $('input[name="paymentEmail"]').val(data.payer.payer_info.email);
                    $('input[name="paymentID"]').val(data.payer.payer_info.payer_id);
                    $('form[name="cartCheckout"]').submit();
                } else {
                    swal("Error", "Something went wrong processing you're payment", "error");
                }
            });
        },
        onError: function (error) {
            swal("Error", "Something went wrong processing you're payment", "error");
        },
        onCancel: function(data, actions) {
            actions.redirect();
        }
    }, '#paypal-button-container');

    window.addEventListener('popstate', function() {
        stripeHandler.close();
    });
});