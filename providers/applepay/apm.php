<?php

require '../../autoload.php';

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
    "transactionId":  "' . $_POST["transactionId"] . '",
    "paymentHash":  "' . $_POST["paymentHash"] . '",
    "providerToken":  "' .base64_encode($_POST["providerToken"]). '",
	"paymentMethod": "applePay"
}';

file_put_contents("apmRequest.json", $body);

try {
    $request = new \GuzzleHttp\Psr7\Request('POST', API_URL . 'payment/apm', $headers, $body);
    $res = $client->sendAsync($request)->wait();
    $response = $res->getBody();
    file_put_contents("apmResponse.log", $response);
    echo $response;
} catch (GuzzleHttp\Exception\ClientException $e) {
    header('Content-Type: application/json; charset=utf-8');
    $response = $e->getResponse()->getBody()->getContents();
    file_put_contents("apmRequestError.log", $response);

    http_response_code($response->getStatusCode());
    echo $responseBodyAsString = $response->getBody()->getContents();
} catch (Throwable $exception) {
    file_put_contents("apmRequestException.log", $exception->getMessage());

}


