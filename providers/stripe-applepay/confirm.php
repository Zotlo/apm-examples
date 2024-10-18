<?php

require "../../autoload.php";

ini_set('display_errors', 0);

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
    'Language' => 'en'
];

$_POST = json_decode(file_get_contents('php://input'), true);

$body = '{
   "transactionId": "' . $_POST["transactionId"] . '",
   "secureHash": "' . $_POST["secureHash"] . '"
}';

//file_put_contents("confirm.json", $body);

try {
    $request = new \GuzzleHttp\Psr7\Request('POST', API_URL . 'payment/stripe-nonce', $headers, $body);
    $res = $client->sendAsync($request)->wait();

    header('Content-Type: application/json; charset=utf-8');
    $resBody =  $res->getBody();
    //file_put_contents("confirmResponse.json", $resBody);
    echo $resBody;
} catch (GuzzleHttp\Exception\ClientException $e) {
    header('Content-Type: application/json; charset=utf-8');
    $response = $e->getResponse();
    http_response_code($response->getStatusCode());
    echo $responseBodyAsString = $response->getBody()->getContents();
}
