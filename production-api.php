<?php
// Production-ready API proxy with multiple fallback methods
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Configuration
define('API_KEY', '6F4BAF303FA240608A39653824B6C495');
define('API_URL', 'https://bizimhesap.com/api/product/getproductsasxml?apikey=' . API_KEY);

// Error reporting off for production
error_reporting(0);
ini_set('display_errors', 0);

// Function to fetch data using multiple methods
function fetchData($url) {
    // Method 1: cURL (most reliable)
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Trek-Sales-Hub/1.0');
        
        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && !empty($data)) {
            return $data;
        }
    }
    
    // Method 2: file_get_contents with context
    if (ini_get('allow_url_fopen')) {
        $opts = [
            'http' => [
                'method' => 'GET',
                'timeout' => 30,
                'user_agent' => 'Trek-Sales-Hub/1.0',
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ];
        
        $context = stream_context_create($opts);
        $data = @file_get_contents($url, false, $context);
        
        if (!empty($data)) {
            return $data;
        }
    }
    
    return false;
}

// Extract product from XML string using regex
function extractProduct($xmlStr) {
    $product = [];
    
    // Extract fields with regex
    if (preg_match('/<stok_kod>([^<]*)<\/stok_kod>/', $xmlStr, $m)) {
        $product['id'] = trim($m[1]);
        $product['code'] = trim($m[1]);
    }
    
    if (preg_match('/<urun_ad>([^<]*)<\/urun_ad>/', $xmlStr, $m)) {
        $product['title'] = trim($m[1]);
    }
    
    if (preg_match('/<kat_yolu>([^<]*)<\/kat_yolu>/', $xmlStr, $m)) {
        $product['category'] = trim($m[1]);
    }
    
    if (preg_match('/<satis_fiyat>([^<]*)<\/satis_fiyat>/', $xmlStr, $m)) {
        $product['price'] = (float)str_replace(',', '.', trim($m[1]));
    }
    
    if (preg_match('/<stok>([^<]*)<\/stok>/', $xmlStr, $m)) {
        $product['quantity'] = (int)trim($m[1]);
    }
    
    if (preg_match('/<marka>([^<]*)<\/marka>/', $xmlStr, $m)) {
        $product['brand'] = trim($m[1]);
    }
    
    if (preg_match('/<barkod>([^<]*)<\/barkod>/', $xmlStr, $m)) {
        $product['barcode'] = trim($m[1]);
    }
    
    if (preg_match('/<varyant>([^<]*)<\/varyant>/', $xmlStr, $m)) {
        $product['variant'] = trim($m[1]);
    }
    
    // Add buying price estimate
    if (!empty($product['price']) && $product['price'] > 0) {
        $product['buyingPrice'] = $product['price'] * 0.7;
    }
    
    // Validate product
    if (!empty($product['id']) && !empty($product['title']) && 
        !empty($product['quantity']) && $product['quantity'] > 0) {
        return $product;
    }
    
    return null;
}

// Main execution
try {
    // Fetch XML data
    $xmlData = fetchData(API_URL);
    
    if ($xmlData === false) {
        throw new Exception('Unable to fetch data from API');
    }
    
    $products = [];
    
    // Try SimpleXML first (if available)
    if (function_exists('simplexml_load_string')) {
        libxml_use_internal_errors(true);
        $xml = @simplexml_load_string($xmlData, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_RECOVER);
        
        if ($xml !== false && isset($xml->urun)) {
            foreach ($xml->urun as $urun) {
                $product = [
                    'id' => (string)$urun->stok_kod,
                    'code' => (string)$urun->stok_kod,
                    'title' => (string)$urun->urun_ad,
                    'category' => (string)$urun->kat_yolu,
                    'price' => (float)str_replace(',', '.', (string)$urun->satis_fiyat),
                    'quantity' => (int)$urun->stok,
                    'brand' => (string)$urun->marka,
                    'barcode' => (string)$urun->barkod,
                    'variant' => (string)$urun->varyant
                ];
                
                if ($product['price'] > 0) {
                    $product['buyingPrice'] = $product['price'] * 0.7;
                }
                
                if ($product['quantity'] > 0 && !empty($product['title'])) {
                    $products[] = $product;
                }
            }
        }
    }
    
    // Fallback to regex if SimpleXML failed or no products found
    if (empty($products)) {
        // Extract products using regex
        preg_match_all('/<urun>(.*?)<\/urun>/s', $xmlData, $matches);
        
        foreach ($matches[1] as $urunXml) {
            $product = extractProduct($urunXml);
            if ($product !== null) {
                $products[] = $product;
            }
        }
    }
    
    // Output response
    echo json_encode([
        'data' => [
            'products' => $products
        ],
        'count' => count($products),
        'success' => true
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'success' => false,
        'data' => [
            'products' => []
        ]
    ]);
}
?>