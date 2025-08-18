<?php
// Fixed API Proxy - Production Ready with proper encoding
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);

$api_key = '6F4BAF303FA240608A39653824B6C495';
$endpoint = $_GET['endpoint'] ?? '';

if (strpos($endpoint, 'products') === false) {
    echo json_encode(['error' => 'Invalid endpoint']);
    exit();
}

// Get data from BizimHesap
$url = 'https://bizimhesap.com/api/product/getproductsasxml?apikey=' . $api_key;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 120);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || empty($response)) {
    echo json_encode([
        'success' => false,
        'error' => 'BizimHesap API failed',
        'http_code' => $http_code
    ]);
    exit();
}

$products = [];
$count = 0;
// No limit - get all products

try {
    // Fix encoding issues first
    $response = trim($response);
    if (substr($response, 0, 3) === "\xEF\xBB\xBF") {
        $response = substr($response, 3); // Remove BOM
    }
    
    // Use regex to extract products - more reliable than XML parsing
    preg_match_all('/<urun>(.*?)<\/urun>/s', $response, $matches);
    
    foreach ($matches[1] as $urunContent) {
        // Process all products - no limit
        
        $product = [];
        
        // Extract all BizimHesap fields using regex
        $fields = [
            'stok_kod' => 'id',
            'barkod' => 'barcode', 
            'urun_ad' => 'title',
            'varyant' => 'variant',
            'marka' => 'brand',
            'stok' => 'stock',
            'satis_fiyat' => 'price',
            'alis_fiyat' => 'buyingPrice',
            'maliyet' => 'cost',
            'para_birim' => 'currency',
            'alis_para_birim' => 'buyingCurrency',
            'kdv' => 'vatRate',
            'detay' => 'description',
            'kat_yolu' => 'category',
            'resim' => 'image'
        ];
        
        foreach ($fields as $xmlField => $jsonField) {
            if (preg_match('/<' . $xmlField . '><!\[CDATA\[(.*?)\]\]><\/' . $xmlField . '>/', $urunContent, $fieldMatch)) {
                $value = trim($fieldMatch[1]);
            } elseif (preg_match('/<' . $xmlField . '>(.*?)<\/' . $xmlField . '>/', $urunContent, $fieldMatch)) {
                $value = trim($fieldMatch[1]);
            } else {
                $value = '';
            }
            
            // Type conversion
            if ($jsonField === 'price' || $jsonField === 'buyingPrice' || $jsonField === 'cost') {
                $value = (float)str_replace(',', '.', $value);
            } elseif ($jsonField === 'stock' || $jsonField === 'vatRate') {
                $value = (int)$value;
            }
            
            // Debug logging for first few products
            if ($count < 3 && ($jsonField === 'price' || $jsonField === 'stock' || $jsonField === 'buyingPrice' || $jsonField === 'currency')) {
                error_log("Debug PHP Proxy - Product $count: $jsonField = $value");
            }
            
            $product[$jsonField] = $value;
        }
        
        // Add code field
        $product['code'] = $product['id'] ?? '';
        
        // Only add if has essential fields
        if (!empty($product['id']) && !empty($product['title'])) {
            $products[] = $product;
            $count++;
        }
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Processing error: ' . $e->getMessage()
    ]);
    exit();
}

// Return response
echo json_encode([
    'success' => true,
    'data' => [
        'products' => $products
    ],
    'count' => count($products),
    'method' => 'regex parsing',
    'xml_size' => strlen($response)
], JSON_UNESCAPED_UNICODE);
?>