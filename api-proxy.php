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
    // Debug: Log raw response
    error_log("BizimHesap raw response: " . substr($response, 0, 500));
    
    // Clean response and enable internal errors for better debugging
    libxml_use_internal_errors(true);
    $response = trim($response);
    
    // Fix common XML issues
    $response = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $response);
    if (substr($response, 0, 3) === "\xEF\xBB\xBF") {
        $response = substr($response, 3);
    }
    
    // Convert XML to JSON for products
    $xml = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
    if ($xml !== false) {
        $products = [];
        
        // BizimHesap uses <urun> tags for products
        if (isset($xml->urun)) {
            foreach ($xml->urun as $product) {
                $products[] = [
                    'id' => (string)$product->stok_kod,
                    'title' => (string)$product->urun_ad,
                    'category' => (string)$product->kat_yolu,
                    'price' => (float)str_replace(',', '.', (string)$product->satis_fiyat),
                    'stock' => (int)$product->stok,
                    'code' => (string)$product->stok_kod,
                    'variant' => (string)$product->varyant,
                    'brand' => (string)$product->marka,
                    'barcode' => (string)$product->barkod,
                    'currency' => (string)$product->para_birim,
                    'vatRate' => (float)$product->kdv,
                    'image' => (string)$product->resim
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
        // Better error handling
        $xml_error = libxml_get_last_error();
        echo json_encode([
            'error' => 'Invalid XML response',
            'xml_error' => $xml_error ? $xml_error->message : 'Unknown XML error',
            'raw_response_preview' => substr($response, 0, 200),
            'response_length' => strlen($response)
        ]);
    }
} else {
    // Forward other responses as-is
    http_response_code($http_code);
    echo $response;
}
?>