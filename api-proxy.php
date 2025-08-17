<?php
// Trek Sales Hub - PHP API Proxy
// Alternative to Node.js for BizimHesap Integration

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// API Configuration
$bizimhesap_api_base = 'https://bizimhesap.com';
// Token gerekmez - BizimHesap public endpoints kullanıyoruz

// Get the requested endpoint
$endpoint = $_GET['endpoint'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Allowed endpoints for security
$allowed_endpoints = [
    '/api/b2b/products',
    '/api/b2b/warehouses',
    '/api/b2b/inventory',
    '/api/b2b/orders',
    '/api/b2b/customers',
    '/api/v2/Products',
    '/api/v2/Warehouses',
    '/api/v2/Inventory',
    '/api/v2/Orders',
    '/api/v2/Customers'
];

// Security check
$is_allowed = false;
foreach ($allowed_endpoints as $allowed) {
    if (strpos($endpoint, $allowed) === 0) {
        $is_allowed = true;
        break;
    }
}

if (!$is_allowed) {
    http_response_code(403);
    echo json_encode(['error' => 'Endpoint not allowed']);
    exit();
}

// Build the full URL
$url = $bizimhesap_api_base . $endpoint;

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Set headers for BizimHesap API
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

// Handle POST requests
if ($request_method === 'POST') {
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents('php://input'));
}

// Execute request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Handle errors
if ($error) {
    http_response_code(500);
    echo json_encode(['error' => 'API request failed', 'message' => $error]);
    exit();
}

// Forward the response
http_response_code($http_code);
echo $response;
?>