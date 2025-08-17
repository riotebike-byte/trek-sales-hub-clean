<?php
// Simple XML test with memory and error handling
header('Content-Type: text/plain');

ini_set('memory_limit', '256M');
ini_set('max_execution_time', 120);

$api_key = '6F4BAF303FA240608A39653824B6C495';
$url = 'https://bizimhesap.com/api/product/getproductsasxml?apikey=' . $api_key;

echo "Testing XML parsing step by step...\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response length: " . strlen($response) . " bytes\n";
echo "Memory usage: " . memory_get_usage(true) / 1024 / 1024 . " MB\n\n";

if ($response) {
    // Try to find XML structure issues
    echo "Checking XML structure...\n";
    
    // Check if it starts with proper XML declaration
    if (strpos($response, '<?xml') === 0) {
        echo "✓ Has XML declaration\n";
    } else {
        echo "✗ No XML declaration\n";
    }
    
    // Check if it has urunler root
    if (strpos($response, '<urunler>') !== false) {
        echo "✓ Has <urunler> root\n";
    } else {
        echo "✗ No <urunler> root\n";
    }
    
    // Count urun elements roughly
    $urun_count = substr_count($response, '<urun>');
    echo "Found approximately $urun_count products\n\n";
    
    // Try parsing just first 50KB to see if that works
    echo "Testing with first 50KB only...\n";
    $small_response = substr($response, 0, 50000);
    
    // Make sure we end with complete XML
    $last_urun_end = strrpos($small_response, '</urun>');
    if ($last_urun_end !== false) {
        $small_response = substr($small_response, 0, $last_urun_end + 7);
        $small_response .= '</urunler>';
        
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($small_response, 'SimpleXMLElement', LIBXML_NOCDATA);
        
        if ($xml !== false) {
            echo "✓ Small XML parsed successfully!\n";
            echo "Products in small sample: " . count($xml->urun) . "\n";
            
            if (count($xml->urun) > 0) {
                $first = $xml->urun[0];
                echo "\nFirst product details:\n";
                echo "- Code: " . (string)$first->stok_kod . "\n";
                echo "- Name: " . (string)$first->urun_ad . "\n";
                echo "- Category: " . (string)$first->kat_yolu . "\n";
                echo "- Price: " . (string)$first->satis_fiyat . "\n";
                echo "- Stock: " . (string)$first->stok . "\n";
                echo "- Brand: " . (string)$first->marka . "\n";
                
                echo "\n✓ Field extraction working!\n";
            }
        } else {
            echo "✗ Even small XML failed to parse\n";
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                echo "XML Error: " . trim($error->message) . "\n";
            }
        }
    } else {
        echo "✗ Could not create valid small XML sample\n";
    }
}

echo "\nMemory usage after: " . memory_get_usage(true) / 1024 / 1024 . " MB\n";
echo "Peak memory: " . memory_get_peak_usage(true) / 1024 / 1024 . " MB\n";
?>