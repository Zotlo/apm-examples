
<?php


require '../../autoload.php';

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
"providerId": 1001,
"language": "tr",
"packageId": "payrails_test",
"platform": "ios",
"subscriberPhoneNumber": "+905070000000",
"subscriberFirstname": "Yusuf",
"subscriberLastname": "Doğan",
"subscriberEmail": "mail@subscriber.com",
"subscriberId": "'.$subscriberId.'",
"subscriberIpAddress": "212.154.57.216",
"subscriberCountry": "US",
"redirectUrl": "https://zotlo.com",
"quantity": 1,
"discountPercent": 0
}';

$request = new \GuzzleHttp\Psr7\Request('POST', API_URL . 'payment/apm-init', $headers, $body);
$res = $client->sendAsync($request)->wait();
$data = $res->getBody();

$data = json_decode($data, true);

$transactionId = $data['result']['transactionId'];
$paymentHash = $data['result']['secureHash'];
$price = $data['result']['price'];
$currency = $data['result']['currency'];

foreach ($data['result']['provider']['methods'] as $method) {
    if($method['method'] == 'applePay') {
        $countryCode = $method['config']['parameters']['countryCode'];
        $merchantCapabilities = $method['config']['parameters']['merchantCapabilities'];
        $merchantIdentifier = $method['config']['parameters']['merchantIdentifier'];
        $supportedNetworks = $method['config']['parameters']['supportedNetworks'];
    }
}

} catch (Throwable $throwable) {
    var_dump($throwable->getMessage());
    die;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apple Pay Button</title>
    <style>
        #apple-pay-button {
            display: none; /* Butonun görünürlüğünü JavaScript ile kontrol edeceğiz */
        }
    </style>
</head>
<body>
<div id="apple-pay-button"></div>
<script src="https://applepay.cdn-apple.com/jsapi/v1/apple-pay-sdk.js"></script>


<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function () {
        if (window.ApplePaySession && ApplePaySession.canMakePayments()) {
            var applePayButton = document.getElementById('apple-pay-button');
            applePayButton.style.display = 'block'; // Apple Pay destekleniyorsa butonu göster

            var button = document.createElement('button');
            button.style.webkitAppearance = '-apple-pay-button';
            button.style.appearance = '-apple-pay-button';
            button.type = 'button';
            button.onclick = onApplePayButtonClicked;
            applePayButton.appendChild(button);
        }
    });

    function onApplePayButtonClicked() {
        if (window.ApplePaySession) {
            var paymentRequest = {
                countryCode: '<?php echo $countryCode; ?>',
                currencyCode: '<?php echo $currency; ?>',
                supportedNetworks: <?php echo json_encode($supportedNetworks); ?>,
                merchantCapabilities: <?php echo json_encode($merchantCapabilities); ?>,
                total: {
                label: 'Zotlo Demo',
                    amount: '<?php echo $price; ?>'
                }
            }

            var session = new ApplePaySession(2, paymentRequest);

            session.onvalidatemerchant = function (event) {
                const validationURL = event.validationURL;
                var promise = getSession(event.validationURL);
                promise.then(function (response) {
                    session.completeMerchantValidation(response.result.sessionData);
                });
            };

            /**
             * Makes an AJAX request to your application server with URL provided by Apple
             */
            function getSession(url) {
                return new Promise(function (resolve, reject) {
                    var xhr = new XMLHttpRequest;
                    var requestUrl = 'apmSession.php';

                    xhr.open('POST', requestUrl);

                    xhr.onload = function () {
                        if (this.status >= 200 && this.status < 300) {
                            return resolve(JSON.parse(xhr.response));
                        } else {
                            return reject({
                                status: this.status,
                                statusText: xhr.statusText
                            });
                        }
                    };

                    xhr.onerror = function () {
                        return reject({
                            status: this.status,
                            statusText: xhr.statusText
                        });
                    };

                    xhr.setRequestHeader('Content-Type', 'application/json');

                    return xhr.send(JSON.stringify({
                        url: url,
                        transactionId: '<?php echo $transactionId; ?>',
                        paymentHash: '<?php echo $paymentHash; ?>',
                        paymentMethod: 'applePay',
                    }));
                });
            };

            /**
             * This is called when user dismisses the payment modal
             */
            session.oncancel = (event) => {
                console.log(event)
                // Re-enable Apple Pay button
            };

            /**
             * Payment Authorization
             * Here you receive the encrypted payment data. You would then send it
             * on to your payment provider for processing, and return an appropriate
             * status in session.completePayment()
             */
            session.onpaymentauthorized = (event) => {
                const payment = event.payment;
                console.log(payment);
                // You can see a sample `payment` object in an image below.
                // Use the token returned in `payment` object to create the charge on your payment gateway.

                if (chargeCreationSucceeds) {
                    // Capture payment from Apple Pay
                    session.completePayment(ApplePaySession.STATUS_SUCCESS);
                    location.href = '/providers/googlepay/success.php';
                } else {
                    console.log("FAIL");
                    // Release payment from Apple Pay
                    session.completePayment(ApplePaySession.STATUS_FAILURE);
                }
            };

            /**
             * This will show up the modal for payments through Apple Pay
             */
            session.begin();
        }
    }

</script>

</body>
</html>




