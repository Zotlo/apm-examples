<?php

require '../../autoload.php';
header('Permissions-Policy: payment=allow');

try {

    $client = new \GuzzleHttp\Client(
        [
            'verify' => false
        ]
    );

    $headers = [
        'AccessKey' => API_KEY,
        'AccessSecret' => API_SECRET,
        'Content-Type' => 'application/json',
        'ApplicationId' => API_APP_ID,
        'Language' => 'en',
    ];

    $subscriberId = \Ramsey\Uuid\Uuid::uuid4()->toString();

    $body = '{
        "providerId": 3,
        "language": "en",
        "packageId": "stripe_monthly",
        "platform": "ios",
        "subscriberFirstname": "Yusuf",
        "subscriberLastname": "Dogan",
        "packageCountry": "US",
        "subscriberEmail": "subscriber@mail.com",
        "subscriberId": "' . $subscriberId . '",
        "subscriberIpAddress": "123.45.6.78",
        "subscriberCountry": "US",
        "redirectUrl": "https://google.com",
        "quantity": 1,
        "discountPercent": 0
    }';

    $request = new \GuzzleHttp\Psr7\Request('POST', API_URL . 'payment/stripe-form', $headers, $body);
    $res = $client->sendAsync($request)->wait();
    $data = $res->getBody();
    $data = json_decode($data, true);

    $token = $data['result']['form']['token'];
    $initializeToken = $data['result']['form']['initializeToken'];
    $secureHash = $data['result']['form']['secureHash'];
    $transactionId = $data['result']['form']['transactionId'];
    $amount = $data['result']['form']['amount'];
    $currency = $data['result']['form']['currency'];
    $locale = 'tr_TR';

} catch (\Throwable $throwable) {
    var_dump($throwable->getMessage());
    die;
}
?>

<html>
<head>
    <title>Stripe</title>
    <script src="https://js.stripe.com/v3/"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <meta allow=“payment”>
</head>
<body>

<div>
    <div id="express-checkout-element">
        <!-- Mount the Express Checkout Element here -->
    </div>
</div>


<script>
    document.addEventListener("DOMContentLoaded", () => {
        const stripe = Stripe("<?php echo $initializeToken; ?>");
        console.log("<?php echo $initializeToken; ?>")

        initialize();

        // Fetches a payment intent and captures the client secret
        async function initialize() {
            const appearance = {
                theme: 'stripe',
                variables: {
                    borderRadius: '36px',
                }
            }
            const expressCheckoutOptions = {
                buttonHeight: 50,
                paymentMethods: {
                    'amazonPay': 'never',
                    'applePay': 'always',
                    'googlePay': 'always',
                    'link': 'never',
                    'paypal': 'never'
                }
            }
            const elements = stripe.elements({clientSecret: "<?php echo $token; ?>"});

            const expressCheckoutElement = elements.create(
                'expressCheckout',
                expressCheckoutOptions
            )
            expressCheckoutElement.mount('#express-checkout-element');
            expressCheckoutElement.on('click', (event) => {
                const options = {};
                event.resolve(options);
            });

            expressCheckoutElement.on('confirm', function(event) {

                const data = {
                    "transactionId": "<?php echo $transactionId ?>",
                    "secureHash": "<?php echo $secureHash ?>"
                };

                // @TODO Important !! Redirect: 'if_required' kullanımına detaylı bakılmalı,
                // Return_url'e dönüldüğünde backend'ten, dönülmediğinde doğrudan buradan api isteği atılabilir.
                stripe.confirmPayment({
                    elements,
                    redirect: 'if_required'
                }).then(function (result) {
                    if (result.error) {
                        console.log(result)
                        // This point will only be reached if there is an immediate error when
                        // confirming the payment. Otherwise, your customer will be redirected to
                        // your `return_url`. For some payment methods like iDEAL, your customer will
                        // be redirected to an intermediate site first to authorize the payment, then
                        // redirected to the `return_url`.
                    } else {
                        fetch("/providers/stripe/confirm.php", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                            },
                            body: JSON.stringify(data),
                        })
                            .then((response) => response.json())
                            .then((data) => {
                                console.log("Success:", data);
                                location.href = '/providers/stripe/success.php';
                            })
                            .catch((error) => {
                                console.error("Error:", error);
                            });
                    }
                });
            });

            const expressCheckoutDiv = document.getElementById('express-checkout-element');
            expressCheckoutDiv.style.visibility = 'hidden';

            expressCheckoutElement.on('ready', ({availablePaymentMethods}) => {
                console.log(availablePaymentMethods);
                if (!availablePaymentMethods) {
                    // No buttons will show
                } else {
                    // Optional: Animate in the Element
                    expressCheckoutDiv.style.visibility = 'initial';
                }
            });
        }

    });
</script>

</body>
</html>