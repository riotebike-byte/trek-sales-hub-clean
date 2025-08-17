<?php
// Simplified API Proxy for BizimHesap
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Set memory limit and execution time
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 120);

// API Configuration
$api_key = '6F4BAF303FA240608A39653824B6C495';
$endpoint = $_GET['endpoint'] ?? '';

// Map endpoints
$endpoint_mapping = [
    '/api/b2b/products' => 'products',
    'products' => 'products'
];

if (!isset($endpoint_mapping[$endpoint])) {
    echo json_encode(['error' => 'Invalid endpoint']);
    exit();
}

// Get products from BizimHesap
$url = 'https://bizimhesap.com/api/product/getproductsasxml?apikey=' . $api_key;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/xml']);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo json_encode(['error' => 'API request failed', 'message' => $error]);
    exit();
}

if ($http_code !== 200) {
    echo json_encode(['error' => 'API returned HTTP ' . $http_code]);
    exit();
}

// Parse XML with XMLReader for memory efficiency
$products = [];
$maxProducts = 500; // Limit for performance

try {
    $reader = new XMLReader();
    if (!$reader->XML($response)) {
        throw new Exception('Could not load XML');
    }
    
    while ($reader->read()) {
        if ($reader->nodeType == XMLReader::ELEMENT && $reader->localName == 'urun') {
            $urun_xml = $reader->readOuterXML();
            $urun = simplexml_load_string($urun_xml, 'SimpleXMLElement', LIBXML_NOCDATA);
            
            if ($urun !== false) {
                // Extract product data
                $product = [
                    'id' => (string)$urun->stok_kod,
                    'title' => (string)$urun->urun_ad,
                    'category' => (string)$urun->kat_yolu,
                    'price' => (float)str_replace(',', '.', (string)$urun->satis_fiyat),
                    'stock' => (int)$urun->stok,
                    'code' => (string)$urun->stok_kod,
                    'variant' => (string)$urun->varyant,
                    'brand' => (string)$urun->marka,
                    'barcode' => (string)$urun->barkod,
                    'currency' => (string)$urun->para_birim,
                    'vatRate' => (float)$urun->kdv,
                    'image' => (string)$urun->resim
                ];
                
                // Filter for bike products
                $categoryLower = strtolower($product['category']);
                $titleLower = strtolower($product['title']);
                
                // Include relevant products
                if (strpos($categoryLower, 'bisiklet') !== false ||
                    strpos($categoryLower, 'madone') !== false ||
                    strpos($categoryLower, 'fx') !== false ||
                    strpos($categoryLower, 'ds') !== false ||
                    strpos($categoryLower, 'marlin') !== false ||
                    strpos($categoryLower, 'kask') !== false ||
                    strpos($categoryLower, 'lastik') !== false ||
                    strpos($titleLower, 'madone') !== false ||
                    strpos($titleLower, 'fx') !== false ||
                    strpos($titleLower, 'ds') !== false ||
                    strpos($titleLower, 'marlin') !== false ||
                    strpos($titleLower, 'trek') !== false ||
                    strpos($titleLower, 'bontrager') !== false) {
                    
                    $products[] = $product;
                }
                
                // Stop after max products
                if (count($products) >= $maxProducts) {
                    break;
                }
            }
        }
    }
    
    $reader->close();
    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'XML parsing failed',
        'message' => $e->getMessage()
    ]);
    exit();
}

// Return successful response
echo json_encode([
    'success' => true,
    'data' => [
        'products' => $products
    ],
    'count' => count($products),
    'total_xml_size' => strlen($response),
    'method' => 'XMLReader with filtering'
]);
?>