<?php
// Simple CORS-enabled proxy for BizimHesap API
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Configuration
$API_KEY = '6F4BAF303FA240608A39653824B6C495';
$API_URL = 'https://bizimhesap.com/api/product/getproductsasxml?apikey=' . $API_KEY;

// Fallback regex extraction function
function extractProductFromXMLString($xmlString) {
    $fields = [
        'stok_kod' => 'id',
        'urun_ad' => 'title',
        'kat_yolu' => 'category', 
        'satis_fiyat' => 'price',
        'stok' => 'quantity',
        'marka' => 'brand',
        'barkod' => 'barcode',
        'varyant' => 'variant'
    ];
    
    $product = [];
    
    foreach ($fields as $xmlField => $jsonField) {
        if (preg_match("/<{$xmlField}>(.*?)<\/{$xmlField}>/s", $xmlString, $matches)) {
            $value = trim(strip_tags($matches[1]));
            
            if ($jsonField === 'price') {
                $value = (float)str_replace(',', '.', $value);
            } elseif ($jsonField === 'quantity') {
                $value = (int)$value;
            }
            
            $product[$jsonField] = $value;
        }
    }
    
    // Add derived fields
    if (!empty($product['id'])) {
        $product['code'] = $product['id'];
    }
    
    if (!empty($product['price']) && $product['price'] > 0) {
        $product['buyingPrice'] = $product['price'] * 0.7;
    }
    
    // Return only if we have essential data
    return (!empty($product['id']) && !empty($product['title'])) ? $product : null;
}

try {
    // Use file_get_contents with context for simple HTTP request
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 30,
            'user_agent' => 'Trek-Sales-Hub/1.0'
        ]
    ]);
    
    $xmlData = file_get_contents($API_URL, false, $context);
    
    if ($xmlData === false) {
        throw new Exception('Failed to fetch data from API');
    }
    
    // Parse XML with libxml options to handle errors
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($xmlData, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_RECOVER);
    
    if ($xml === false) {
        // Try to extract partial data even if XML is malformed
        $errors = libxml_get_errors();
        $errorMsg = 'XML parsing errors: ';
        foreach ($errors as $error) {
            $errorMsg .= $error->message . ' ';
        }
        
        // Attempt regex extraction as fallback
        preg_match_all('/<urun>(.*?)<\/urun>/s', $xmlData, $matches);
        if (empty($matches[1])) {
            throw new Exception($errorMsg);
        }
        
        $products = [];
        foreach ($matches[1] as $urunData) {
            $product = extractProductFromXMLString($urunData);
            if ($product && $product['quantity'] > 0) {
                $products[] = $product;
            }
        }
    } else {
        $products = [];
        
        // Convert XML to JSON format
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
            
            // Add buying price estimate
            if ($product['price'] > 0) {
                $product['buyingPrice'] = $product['price'] * 0.7;
            }
            
            // Only include products with stock
            if ($product['quantity'] > 0 && !empty($product['title'])) {
                $products[] = $product;
            }
        }
    }
    
    // Return in expected format
    echo json_encode([
        'data' => [
            'products' => $products
        ],
        'count' => count($products)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>