<?php
// Working API Proxy - Production Ready
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Set higher limits for large data
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);
error_reporting(0); // Disable error output to prevent JSON corruption

$api_key = '6F4BAF303FA240608A39653824B6C495'; // Match Node.js proxy key
$endpoint = $_GET['endpoint'] ?? '';

// Simple endpoint check
if (strpos($endpoint, 'products') === false) {
    echo json_encode(['error' => 'Invalid endpoint']);
    exit();
}

// Get products from BizimHesap
$url = 'https://bizimhesap.com/api/product/getproductsasxml?apikey=' . $api_key;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 120);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || empty($response)) {
    echo json_encode([
        'success' => false,
        'error' => 'API request failed',
        'http_code' => $http_code
    ]);
    exit();
}

// Parse XML and convert to JSON
$products = [];
$count = 0;
$maxProducts = 1000; // Get more products

try {
    // Use DOMDocument for more reliable parsing
    $dom = new DOMDocument();
    $dom->loadXML($response, LIBXML_NOCDATA | LIBXML_NOERROR | LIBXML_NOWARNING);
    
    $xpath = new DOMXPath($dom);
    $urunNodes = $xpath->query('//urun');
    
    foreach ($urunNodes as $urunNode) {
        if ($count >= $maxProducts) break;
        
        $product = [];
        
        // Extract each field
        $fields = [
            'stok_kod' => 'id',
            'urun_ad' => 'title',
            'kat_yolu' => 'category',
            'satis_fiyat' => 'price',
            'stok' => 'stock',
            'varyant' => 'variant',
            'marka' => 'brand',
            'barkod' => 'barcode',
            'para_birim' => 'currency',
            'kdv' => 'vatRate',
            'resim' => 'image'
        ];
        
        foreach ($fields as $xmlField => $jsonField) {
            $nodes = $xpath->query($xmlField, $urunNode);
            if ($nodes->length > 0) {
                $value = $nodes->item(0)->nodeValue;
                
                // Handle different field types
                if ($jsonField === 'price') {
                    $value = (float)str_replace(',', '.', $value);
                } elseif ($jsonField === 'stock' || $jsonField === 'vatRate') {
                    $value = (int)$value;
                } else {
                    $value = trim($value);
                }
                
                $product[$jsonField] = $value;
            }
        }
        
        // Also keep code field same as id
        if (isset($product['id'])) {
            $product['code'] = $product['id'];
        }
        
        // Add compatibility fields for dashboard
        if (isset($product['stock'])) {
            $product['quantity'] = $product['stock'];
        }
        
        // Add buyingPrice as 70% of selling price (estimated)
        if (isset($product['price']) && $product['price'] > 0) {
            $product['buyingPrice'] = $product['price'] * 0.7;
        }
        
        // Only add if we have essential fields
        if (!empty($product['id']) && !empty($product['title'])) {
            $products[] = $product;
            $count++;
        }
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'XML parsing error',
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
    'count' => count($products)
], JSON_UNESCAPED_UNICODE);
?>