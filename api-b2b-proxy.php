<?php
// B2B API Proxy - Production Ready for BizimHesap B2B API
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);

$api_key = '6F4BAF303FA240608A39653824B6C495';
$base_url = 'https://bizimhesap.com/api';

// Get the requested endpoint from URL path
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Remove script name from path to get the API endpoint
$script_name = $_SERVER['SCRIPT_NAME'];
$endpoint = str_replace($script_name, '', $path);

// Remove leading slash
$endpoint = ltrim($endpoint, '/');

// Build the target URL
$target_url = $base_url . '/' . $endpoint;

// Prepare headers for BizimHesap API
$headers = [
    'token: ' . $api_key,
    'Content-Type: application/json',
    'User-Agent: Trek-Sales-Hub/1.0'
];

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $target_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 120);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Execute request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Handle errors
if ($error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'cURL error: ' . $error,
        'endpoint' => $endpoint
    ]);
    exit();
}

if ($http_code !== 200) {
    http_response_code($http_code);
    echo json_encode([
        'success' => false,
        'error' => 'HTTP error: ' . $http_code,
        'endpoint' => $endpoint
    ]);
    exit();
}

// Return the response
echo $response;
?>