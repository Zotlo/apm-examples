<?php

try {
    require "../../autoload.php";

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

    $_POST = json_decode(file_get_contents('php://input'), true);

    $body = '{
      "transactionId": "' . $_POST["transactionId"] . '",
       "paymentMethodNonce": "' . $_POST["paymentMethodNonce"] . '",
       "paymentMethod": "' . $_POST["paymentMethod"] . ' ",
       "secureHash": "' . $_POST["secureHash"] . '"
    }';

    try {
        $request = new \GuzzleHttp\Psr7\Request('POST', API_URL . 'payment/braintree-nonce', $headers, $body);
        $res = $client->sendAsync($request)->wait();
        header('Content-Type: application/json; charset=utf-8');
        echo $res->getBody();
    } catch (GuzzleHttp\Exception\ClientException $e) {
        header('Content-Type: application/json; charset=utf-8');
        $response = $e->getResponse();
        http_response_code($response->getStatusCode());
        echo $responseBodyAsString = $response->getBody()->getContents();
    }
} catch (Throwable $exception) {
    echo json_encode(['error' => $exception->getMessage()]);
}
