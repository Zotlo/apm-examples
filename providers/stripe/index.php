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
        "providerId": 903,
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

} catch (Throwable $throwable) {
    var_dump($throwable);
    die;
}
?>

<html>
<head>
    <title>Stripe</title>

    <style>
        /* Variables */
        form {
            margin-top: 10px;
            align-self: center;
        }

        .hidden {
            display: none;
        }

        #payment-message {
            color: rgb(105, 115, 134);
            font-size: 16px;
            line-height: 20px;
            padding-top: 12px;
            text-align: center;
        }

        #payment-element {
            margin-bottom: 10px;
        }

        /* Buttons and links */
        button {
            background: #5469d4;
            font-family: Arial, sans-serif;
            color: #ffffff;
            border-radius: 4px;
            border: 0;
            padding: 12px 16px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: block;
            transition: all 0.2s ease;
            box-shadow: 0px 4px 5.5px 0px rgba(0, 0, 0, 0.07);
            width: 100%;
        }

        button:hover {
            filter: contrast(115%);
        }

        button:disabled {
            opacity: 0.5;
            cursor: default;
        }

        /* spinner/processing state, errors */
        .spinner,
        .spinner:before,
        .spinner:after {
            border-radius: 50%;
        }

        .spinner {
            color: #ffffff;
            font-size: 22px;
            text-indent: -99999px;
            margin: 0px auto;
            position: relative;
            width: 20px;
            height: 20px;
            box-shadow: inset 0 0 0 2px;
            -webkit-transform: translateZ(0);
            -ms-transform: translateZ(0);
            transform: translateZ(0);
        }

        .spinner:before,
        .spinner:after {
            position: absolute;
            content: "";
        }

        .spinner:before {
            width: 10.4px;
            height: 20.4px;
            background: #5469d4;
            border-radius: 20.4px 0 0 20.4px;
            top: -0.2px;
            left: -0.2px;
            -webkit-transform-origin: 10.4px 10.2px;
            transform-origin: 10.4px 10.2px;
            -webkit-animation: loading 2s infinite ease 1.5s;
            animation: loading 2s infinite ease 1.5s;
        }

        .spinner:after {
            width: 10.4px;
            height: 10.2px;
            background: #5469d4;
            border-radius: 0 10.2px 10.2px 0;
            top: -0.1px;
            left: 10.2px;
            -webkit-transform-origin: 0px 10.2px;
            transform-origin: 0px 10.2px;
            -webkit-animation: loading 2s infinite ease;
            animation: loading 2s infinite ease;
        }

        @-webkit-keyframes loading {
            0% {
                -webkit-transform: rotate(0deg);
                transform: rotate(0deg);
            }
            100% {
                -webkit-transform: rotate(360deg);
                transform: rotate(360deg);
            }
        }

        @keyframes loading {
            0% {
                -webkit-transform: rotate(0deg);
                transform: rotate(0deg);
            }
            100% {
                -webkit-transform: rotate(360deg);
                transform: rotate(360deg);
            }
        }

        @media only screen and (max-width: 600px) {
            form {
                width: 80vw;
                min-width: initial;
            }
        }

    </style>
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>

<div>
    <form id="payment-form">
        <div id="payment-message" class="hidden"></div>
        <div id="payment-element">
            <!--Stripe.js injects the Payment Element-->
        </div>
        <button id="submit">
            <div class="spinner hidden" id="spinner"></div>
            <span id="button-text">Pay now</span>
        </button>
    </form>
</div>


<script>
    document.addEventListener("DOMContentLoaded", () => {
        const stripe = Stripe("<?php echo $initializeToken; ?>", {
            locale: 'tr'
        });

        let elements;

        initialize();
        checkStatus();

        document
            .querySelector("#payment-form")
            .addEventListener("submit", handleSubmit);

        // Fetches a payment intent and captures the client secret
        async function initialize() {
            elements = stripe.elements({clientSecret: "<?php echo $token; ?>"});

            const paymentElementOptions = {
                layout: "accordion",
            };

            const paymentElement = elements.create("payment", paymentElementOptions);
            paymentElement.mount("#payment-element");
        }

        async function handleSubmit(e) {
            e.preventDefault();
            setLoading(true);

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
                    // This point will only be reached if there is an immediate error when
                    // confirming the payment. Otherwise, your customer will be redirected to
                    // your `return_url`. For some payment methods like iDEAL, your customer will
                    // be redirected to an intermediate site first to authorize the payment, then
                    // redirected to the `return_url`.
                    if (result.error.type === "card_error" || result.error.type === "validation_error") {
                        showMessage(error.message);
                    } else {
                        showMessage("An unexpected error occurred.");
                    }
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
                setLoading(false);
            });
        }

        // Fetches the payment intent status after payment submission
        async function checkStatus() {
            const clientSecret = new URLSearchParams(window.location.search).get(
                "payment_intent_client_secret"
            );

            if (!clientSecret) {
                return;
            }

            const {paymentIntent} = await stripe.retrievePaymentIntent(clientSecret);

            switch (paymentIntent.status) {
                case "succeeded":
                    showMessage("Payment succeeded!");
                    break;
                case "processing":
                    showMessage("Your payment is processing.");
                    break;
                case "requires_payment_method":
                    showMessage("Your payment was not successful, please try again.");
                    break;
                default:
                    showMessage("Something went wrong.");
                    break;
            }
        }

        // ------- UI helpers -------

        function showMessage(messageText) {
            const messageContainer = document.querySelector("#payment-message");

            messageContainer.classList.remove("hidden");
            messageContainer.textContent = messageText;

            setTimeout(function () {
                messageContainer.classList.add("hidden");
                messageText.textContent = "";
            }, 4000);
        }

        // Show a spinner on payment submission
        function setLoading(isLoading) {
            if (isLoading) {
                // Disable the button and show a spinner
                document.querySelector("#submit").disabled = true;
                document.querySelector("#spinner").classList.remove("hidden");
                document.querySelector("#button-text").classList.add("hidden");
            } else {
                document.querySelector("#submit").disabled = false;
                document.querySelector("#spinner").classList.add("hidden");
                document.querySelector("#button-text").classList.remove("hidden");
            }
        }

    });
</script>

</body>
</html>