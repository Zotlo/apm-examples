<?php

require '../../autoload.php';

try {

    $headers = [
        'AccessKey' => API_KEY,
        'AccessSecret' => API_SECRET,
        'Content-Type' => 'application/json',
        'ApplicationId' => API_APP_ID,
        'Language' => 'en',
    ];


    $client = new \GuzzleHttp\Client(
        [
            'verify' => false
        ]
    );
    $subscriberId = \Ramsey\Uuid\Uuid::uuid4()->toString();

    $body = '{
      "providerId": 710,
      "language": "tr",
      "packageId": "braintree_package",
      "platform": "ios",
      "subscriberPhoneNumber": "+905070000000",
      "subscriberFirstname": "Yusuf",
      "subscriberLastname": "Doğan",
      "subscriberEmail": "subscriber@mail.com",
      "subscriberId": "' . $subscriberId . '",
      "subscriberIpAddress": "212.154.57.216",
      "subscriberCountry": "US",
      "redirectUrl": "https://zotlo.com",
      "quantity": 1
    }';

    $request = new \GuzzleHttp\Psr7\Request('POST', API_URL . 'payment/braintree-form', $headers, $body);
    $res = $client->sendAsync($request)->wait();
    $data = $res->getBody();

    $data = json_decode($data, true);

    $token = $data['result']['form']['token'];
    $secureHash = $data['result']['form']['secureHash'];
    $transactionId = $data['result']['form']['transactionId'];
    $amount = $data['result']['form']['amount'];
    $currency = $data['result']['form']['currency'];
    $email = 'subscriber@mail.com';
    $locale = 'tr_TR';

} catch (Throwable $exception) {
    var_dump($exception->getMessage());
    die;
}
?>

<html>
<head>
    <title>Braintree</title>
    <script src="https://js.braintreegateway.com/web/dropin/1.33.7/js/dropin.min.js"></script>
    <script src="https://js.braintreegateway.com/web/3.88.4/js/client.min.js"></script>
    <script src="https://js.braintreegateway.com/web/3.88.4/js/data-collector.min.js"></script>
    <script src="https://js.braintreegateway.com/web/3.88.4/js/paypal-checkout.min.js"></script>
    <script src="https://js.braintreegateway.com/web/3.88.4/js/google-payment.min.js"></script>
    <script src="https://js.braintreegateway.com/web/3.88.4/js/apple-pay.min.js"></script>
    <script src="https://pay.google.com/gp/p/js/pay.js"></script>
</head>
<body>

<div>
    <div id="dropin-container"></div>
    <div id="paypal-button"></div>
    <div className="text-center">
        <button type="submit" className="purchase" id="submit-button">Complete</button>
    </div>
</div>

<script type="text/javascript">
    const form = document.getElementById('payment-form');

    var submitButton = document.querySelector('#submit-button');
    var thredStatus = false;

    var paymentMethods = 'card,paypal,applePay,googlePay';
    paymentMethods = paymentMethods.split(',');

    var braintreeParams = {
        authorization: '<?php echo $token; ?>',
        container: '#dropin-container',
        threeDSecure: thredStatus,
        currencyCode: '<?php echo $currency; ?>',
        vaultManager: true,
        locale: "<?php echo $locale; ?>",
        card: {
            cardholderName: {
                required: true
            }
        },
        paymentOptionPriority: paymentMethods
    }

    var googlePay = {
        googlePayVersion: 2,
        transactionInfo: {
            currencyCode: '<?php echo $currency; ?>',
            totalPriceStatus: 'FINAL',
            totalPrice: '<?php echo $amount ?>'
        }
    };

    braintreeParams['googlePay'] = googlePay;

    braintreeParams['applePay'] = {
        displayName: 'ApplePay',
        paymentRequest: {
            total: {
                label: 'Zotlo ApplePay',
                amount: "<?php echo $amount; ?>",
            },
            locale: "en",
            currencyCode: "<?php echo $currency; ?>"
        }
    };

    braintreeParams['paypal'] = {
        flow: 'vault',
        locale: "<?php echo $locale; ?>",
    };

    braintree.dropin.create(braintreeParams).then(function (dropinInstance) {
        submitButton.addEventListener('click', function (e) {
            e.preventDefault();
            dropinInstance.requestPaymentMethod({
                threeDSecure: {
                    amount:  <?php echo $amount ?>,
                    currencyCode: '<?php echo $currency; ?>',
                    email: '<?php echo $email; ?>'
                }
            }).then(function (payload) {

                console.log(payload);

                // paymentMethodNonce Token On Braintree
                console.log('paymentMethodNonce: ' + payload.nonce);
                // paymentMethod On Braintree (creditCard, paypal..)
                console.log('paymentMethod: ' + payload.type);

                const data = {
                    "transactionId": "<?php echo $transactionId ?>",
                    "paymentMethodNonce": payload.nonce,
                    "deviceData": "{\"correlation_id\":\"<?php echo $transactionId ?>\"}",
                    "paymentMethod": payload.type,
                    "secureHash": "<?php echo $secureHash ?>"
                };

                fetch("/providers/braintree/capture.php", {
                    method: "POST", // or 'PUT'
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify(data),
                })
                    .then((response) => response.json())
                    .then((data) => {
                        console.log("Success:", data);
                        location.href = '/providers/braintree/success.php';
                    })
                    .catch((error) => {
                        console.log(error);
                        console.error("Error:", error);
                    });
            }).catch(function (error) {
                console.log(error)
            });
        });
    }).catch(function (err) {
        //console.error(err);
    });

    braintree.client.create({
        authorization: '<?php echo $token; ?>',
    }, function (err, clientInstance) {
        braintree.dataCollector.create({
            client: clientInstance
        }, function (err, dataCollectorInstance) {
            if (err) {

                console.log('Error', err);
                alert(err);

                return;
            }
            console.log(dataCollectorInstance.deviceData);
        });
    });
</script>
</body>
</html>