<?php
// Debug API proxy response
header('Content-Type: application/json');

// Call our own API proxy and see what it returns
$api_url = 'https://video.trek-turkey.com/api-proxy.php?endpoint=/api/b2b/products';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo json_encode([
    'test_time' => date('Y-m-d H:i:s'),
    'api_url' => $api_url,
    'http_code' => $http_code,
    'curl_error' => $error ?: null,
    'response_length' => strlen($response),
    'response_preview' => substr($response, 0, 1000),
    'is_json' => json_decode($response) !== null,
    'json_decode_error' => json_last_error_msg()
], JSON_PRETTY_PRINT);
?>