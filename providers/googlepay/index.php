
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
        if($method['method'] == 'googlePay') {
            $gateway = $method['config']['tokenizationSpecification']['parameters']['gateway'];
            $gatewayMerchantId = $method['config']['tokenizationSpecification']['parameters']['gatewayMerchantId'];
            $stripePublishableKey = $method['config']['tokenizationSpecification']['parameters']['stripe:publishableKey'];
            $stripeVersion = $method['config']['tokenizationSpecification']['parameters']['stripe:version'];
            $type = $method['config']['tokenizationSpecification']['type'];
        }
    }

} catch (Throwable $throwable) {
    var_dump($throwable->getMessage());
    die;
}

?>


<div id="container"></div>

<script>

    /**
     * Define the version of the Google Pay API referenced when creating your
     * configuration
     *
     * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#PaymentDataRequest|apiVersion in PaymentDataRequest}
     */
    const baseRequest = {
        apiVersion: 2,
        apiVersionMinor: 0
    };

    /**
     * Card networks supported by your site and your gateway
     *
     * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#CardParameters|CardParameters}
     * @todo confirm card networks supported by your site and gateway
     */
    const allowedCardNetworks = ["AMEX", "DISCOVER", "INTERAC", "JCB", "MASTERCARD", "VISA"];

    /**
     * Card authentication methods supported by your site and your gateway
     *
     * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#CardParameters|CardParameters}
     * @todo confirm your processor supports Android device tokens for your
     * supported card networks
     */
    const allowedCardAuthMethods = ["PAN_ONLY", "CRYPTOGRAM_3DS"];

    /**
     * Identify your gateway and your site's gateway merchant identifier
     *
     * The Google Pay API response will return an encrypted payment method capable
     * of being charged by a supported gateway after payer authorization
     *
     * @todo check with your gateway on the parameters to pass
     * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#gateway|PaymentMethodTokenizationSpecification}
     */
    const tokenizationSpecification = {
        type: '<?php echo $type; ?>',
        parameters: {
            'gateway': "<?php echo $gateway; ?>",
            'gatewayMerchantId': "<?php echo $gatewayMerchantId; ?>",
            "stripe:publishableKey": "<?php echo $stripePublishableKey; ?>",
            "stripe:version": "<?php echo $stripeVersion; ?>"
        }
    };

    /**
     * Describe your site's support for the CARD payment method and its required
     * fields
     *
     * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#CardParameters|CardParameters}
     */
    const baseCardPaymentMethod = {
        type: 'CARD',
        parameters: {
            allowedAuthMethods: allowedCardAuthMethods,
            allowedCardNetworks: allowedCardNetworks
        }
    };

    /**
     * Describe your site's support for the CARD payment method including optional
     * fields
     *
     * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#CardParameters|CardParameters}
     */
    const cardPaymentMethod = Object.assign(
        {},
        baseCardPaymentMethod,
        {
            tokenizationSpecification: tokenizationSpecification
        }
    );

    /**
     * An initialized google.payments.api.PaymentsClient object or null if not yet set
     *
     * @see {@link getGooglePaymentsClient}
     */
    let paymentsClient = null;

    /**
     * Configure your site's support for payment methods supported by the Google Pay
     * API.
     *
     * Each member of allowedPaymentMethods should contain only the required fields,
     * allowing reuse of this base request when determining a viewer's ability
     * to pay and later requesting a supported payment method
     *
     * @returns {object} Google Pay API version, payment methods supported by the site
     */
    function getGoogleIsReadyToPayRequest() {
        return Object.assign(
            {},
            baseRequest,
            {
                allowedPaymentMethods: [baseCardPaymentMethod]
            }
        );
    }

    /**
     * Configure support for the Google Pay API
     *
     * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#PaymentDataRequest|PaymentDataRequest}
     * @returns {object} PaymentDataRequest fields
     */
    function getGooglePaymentDataRequest() {
        const paymentDataRequest = Object.assign({}, baseRequest);
        paymentDataRequest.allowedPaymentMethods = [cardPaymentMethod];
        paymentDataRequest.transactionInfo = getGoogleTransactionInfo();
        paymentDataRequest.merchantInfo = {
            // @todo a merchant ID is available for a production environment after approval by Google
            // See {@link https://developers.google.com/pay/api/web/guides/test-and-deploy/integration-checklist|Integration checklist}
            // merchantId: '12345678901234567890',
            merchantName: 'Example Merchant'
        };
        return paymentDataRequest;
    }

    /**
     * Return an active PaymentsClient or initialize
     *
     * @see {@link https://developers.google.com/pay/api/web/reference/client#PaymentsClient|PaymentsClient constructor}
     * @returns {google.payments.api.PaymentsClient} Google Pay API client
     */
    function getGooglePaymentsClient() {
        if (paymentsClient === null) {
            paymentsClient = new google.payments.api.PaymentsClient({environment: 'TEST'});
        }
        return paymentsClient;
    }

    /**
     * Initialize Google PaymentsClient after Google-hosted JavaScript has loaded
     *
     * Display a Google Pay payment button after confirmation of the viewer's
     * ability to pay.
     */
    function onGooglePayLoaded() {
        const paymentsClient = getGooglePaymentsClient();
        paymentsClient.isReadyToPay(getGoogleIsReadyToPayRequest())
            .then(function (response) {
                if (response.result) {
                    addGooglePayButton();
                    // @todo prefetch payment data to improve performance after confirming site functionality
                    // prefetchGooglePaymentData();
                }
            })
            .catch(function (err) {
                // show error in developer console for debugging
                console.error(err);
            });
    }

    /**
     * Add a Google Pay purchase button alongside an existing checkout button
     *
     * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#ButtonOptions|Button options}
     * @see {@link https://developers.google.com/pay/api/web/guides/brand-guidelines|Google Pay brand guidelines}
     */
    function addGooglePayButton() {
        const paymentsClient = getGooglePaymentsClient();
        const button =
            paymentsClient.createButton({
                onClick: onGooglePaymentButtonClicked,
                allowedPaymentMethods: [baseCardPaymentMethod]
            });
        document.getElementById('container').appendChild(button);
    }

    /**
     * Provide Google Pay API with a payment amount, currency, and amount status
     *
     * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#TransactionInfo|TransactionInfo}
     * @returns {object} transaction info, suitable for use as transactionInfo property of PaymentDataRequest
     */
    function getGoogleTransactionInfo() {
        return {
            countryCode: 'US',
            currencyCode: '<?php echo $currency; ?>',
            totalPriceStatus: 'FINAL',
            // set to cart total
            totalPrice: '<?php echo $price ?>'
        };
    }

    /**
     * Prefetch payment data to improve performance
     *
     * @see {@link https://developers.google.com/pay/api/web/reference/client#prefetchPaymentData|prefetchPaymentData()}
     */
    function prefetchGooglePaymentData() {
        const paymentDataRequest = getGooglePaymentDataRequest();
        // transactionInfo must be set but does not affect cache
        paymentDataRequest.transactionInfo = {
            totalPriceStatus: 'FINAL',
            currencyCode: '<?php echo $currency; ?>'
        };
        const paymentsClient = getGooglePaymentsClient();
        paymentsClient.prefetchPaymentData(paymentDataRequest);
    }

    /**
     * Show Google Pay payment sheet when Google Pay payment button is clicked
     */
    function onGooglePaymentButtonClicked() {
        const paymentDataRequest = getGooglePaymentDataRequest();
        paymentDataRequest.transactionInfo = getGoogleTransactionInfo();
        const paymentsClient = getGooglePaymentsClient();
        paymentsClient.loadPaymentData(paymentDataRequest)
            .then(function (paymentData) {
                // handle the response
                processPayment(paymentData);
            })
            .catch(function (err) {
                // show error in developer console for debugging
                console.error(err);
            });
    }

    function processPayment(paymentData) {
        console.log(paymentData);
        // show returned data in developer console for debugging
        // @todo pass payment token to your gateway to process payment
        // @note DO NOT save the payment credentials for future transactions,
        // unless they're used for merchant-initiated transactions with user
        // consent in place.
        paymentToken = JSON.parse(paymentData.paymentMethodData.tokenizationData.token);
        const promise = authorizePayment(paymentToken);
        promise.then(function (response) {
            console.log(response);
            if(response.result.response.isSuccess){
                location.href = '/providers/googlepay/success.php';
            }
        });

    }

    /**
     * Authorize GooglePay
     */
    function authorizePayment(token) {
        return new Promise(function (resolve, reject) {
            console.log("authorize Payment")
            console.log(token);
            var xhr = new XMLHttpRequest;
            var requestUrl = 'apm.php';

            xhr.open('POST', requestUrl);

            xhr.onload = function () {
                if (this.status >= 200 && this.status < 300) {
                    console.log("XHR Response");
                    console.log(xhr.response);
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
                token: JSON.stringify(token),
                transactionId: '<?php echo $transactionId; ?>',
                paymentHash: '<?php echo $paymentHash; ?>',
                paymentMethod: 'googlePay',
            }));
        })
    }
</script>
<script async
        src="https://pay.google.com/gp/p/js/pay.js"
        onload="onGooglePayLoaded()"></script>


