<?php
// Reverb HTTP API test - send event directly
$key = '1u5lccdxgferldz4syrg';
$secret = 'khsixbsedu2bpj0lfm9k';
$appId = '159053';

$body = '{"name":"test","data":"{\"msg\":\"hola\"}","channels":["private-delivery-order.4"]}';
$ts = (string) time();
$sig = hash_hmac('sha256', "$key:$ts:$body", $secret);

$url = "http://127.0.0.1:8083/apps/$appId/events";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        "auth-key: $key",
        "auth-timestamp: $ts",
        "auth-signature: $sig",
    ],
]);

$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

echo "HTTP $code: " . ($err ?: $resp) . "\n";
echo "Sent with key=$key ts=$ts sig=$sig\n";
echo "Body: $body\n";
