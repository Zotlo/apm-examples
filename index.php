<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zotlo Ödeme Sağlayıcıları</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
        }

        h1 {
            color: #333;
        }

        div {
            margin-bottom: 20px;
        }

        h2 {
            color: #555;
        }

        .payment-button {
            display: inline-block;
            padding: 10px 20px;
            font-size: 16px;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid #3498db;
            color: #3498db;
            border-radius: 5px;
            transition: background-color 0.3s, color 0.3s, transform 0.3s;
        }

        /* Butona tıklandığında efekt uygula */
        .payment-button:hover {
            background-color: #3498db;
            color: #fff;
            transform: scale(1.05);
            transition: transform 0.5s;
        }
    </style>
</head>
<body>

<h1>Ödeme Sağlayıcıları</h1>

<div>
    <a href="/providers/stripe/index.php" target="_blank" class="payment-button">
        <img src="/static/providers/stripe.png" alt="Stripe Logo" width="150" height="50">
    </a>
</div>

<div>
    <a href="/providers/braintree/index.php" target="_blank" class="payment-button">
        <img src="/static/providers/braintree.png" alt="Braintree Logo" width="150" height="50">
    </a>
</div>


<div>
    <a href="/providers/googlepay/index.php" target="_blank" class="payment-button">
        <img src="/static/providers/gpay.png" alt="Braintree Logo" width="150" height="50">
    </a>
</div>

<div>
    <a href="/providers/applepay/index.php" target="_blank" class="payment-button">
        <img src="/static/providers/apay.png" alt="Braintree Logo" width="150" height="50">
    </a>
</div>

--- Stripe ApplePay & GooglePay
<div>
    <a href="/providers/stripe-applepay/index.php" target="_blank" class="payment-button">
        <img src="/static/providers/apay.png" alt="Braintree Logo" width="150" height="50">
    </a>
</div>
</body>
</html>
