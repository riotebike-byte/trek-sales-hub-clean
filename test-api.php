<?php
// API Test Script
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Test direct BizimHesap connection
$url = 'https://bizimhesap.com/api/v2/Products';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$info = curl_getinfo($ch);
$error = curl_error($ch);
curl_close($ch);

echo json_encode([
    'test_time' => date('Y-m-d H:i:s'),
    'url_tested' => $url,
    'http_code' => $info['http_code'],
    'error' => $error ?: null,
    'response_preview' => $response ? substr($response, 0, 200) : null,
    'server_info' => [
        'php_version' => PHP_VERSION,
        'curl_enabled' => function_exists('curl_init')
    ]
]);
?>