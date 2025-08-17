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
$api_key = '6F4BAF303FA240608A39653824B6C495';

// Get the requested endpoint
$endpoint = $_GET['endpoint'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Map internal endpoints to BizimHesap endpoints
$endpoint_mapping = [
    '/api/b2b/products' => '/api/product/getproductsasxml',
    '/api/v2/Products' => '/api/product/getproductsasxml',
    'products' => '/api/product/getproductsasxml',
    '/api/b2b/warehouses' => '/api/warehouse/getwarehouses',
    '/api/v2/Warehouses' => '/api/warehouse/getwarehouses', 
    'warehouses' => '/api/warehouse/getwarehouses',
    '/api/b2b/inventory' => '/api/inventory/getinventory',
    '/api/v2/Inventory' => '/api/inventory/getinventory',
    'inventory' => '/api/inventory/getinventory'
];

// Convert endpoint
$bizimhesap_endpoint = $endpoint_mapping[$endpoint] ?? $endpoint;

// Security check - allow mapped endpoints
if (!isset($endpoint_mapping[$endpoint])) {
    http_response_code(403);
    echo json_encode(['error' => 'Endpoint not allowed: ' . $endpoint]);
    exit();
}

// Build the full URL with API key
$url = $bizimhesap_api_base . $bizimhesap_endpoint . '?apikey=' . $api_key;

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

// Handle BizimHesap XML response
if ($http_code === 200 && strpos($bizimhesap_endpoint, 'getproductsasxml') !== false) {
    // Convert XML to JSON for products
    $xml = simplexml_load_string($response);
    if ($xml !== false) {
        $products = [];
        
        if (isset($xml->Table)) {
            foreach ($xml->Table as $product) {
                $products[] = [
                    'id' => (string)$product->Id,
                    'title' => (string)$product->ProductName,
                    'category' => (string)$product->CategoryName,
                    'price' => (float)$product->SalesPrice,
                    'stock' => (int)$product->Quantity,
                    'code' => (string)$product->ProductCode,
                    'warehouseId' => (string)$product->WarehouseId
                ];
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'products' => $products
            ],
            'count' => count($products)
        ]);
    } else {
        echo json_encode(['error' => 'Invalid XML response']);
    }
} else {
    // Forward other responses as-is
    http_response_code($http_code);
    echo $response;
}
?>