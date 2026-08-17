<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$db = new mysqli('127.0.0.1', 'root', '', 'liztogo');
$res = $db->query("SELECT gateway_parameters FROM gateways WHERE alias = 'MercadoPago'");
$row = $res->fetch_assoc();
$param = json_decode($row['gateway_parameters']);
$accessToken = $param->access_token->value ?? ($param->access_token ?? '');

echo "Access Token retrieved: " . $accessToken . "\n\n";

$ch = curl_init('https://api.mercadopago.com/v1/payment_methods');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false, // Try with SSL verification disabled
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $accessToken,
    ],
]);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "HTTP Status Code: $http_code\n";
echo "Curl Error: $curl_error\n";
echo "Response: $response\n";
