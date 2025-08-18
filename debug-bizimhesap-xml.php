<?php
// Debug BizimHesap XML Response
header('Content-Type: text/plain; charset=utf-8');

$api_key = '6F4BAF303FA240608A39653824B6C495';
$url = 'https://bizimhesap.com/api/product/getproductsasxml?apikey=' . $api_key;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
curl_close($ch);

echo "=== BizimHesap XML Response Debug ===\n\n";

// Show first 2000 characters
echo "First 2000 characters:\n";
echo substr($response, 0, 2000) . "\n\n";

// Extract and show first product structure
if (preg_match('/<urun>(.*?)<\/urun>/s', $response, $match)) {
    echo "=== FIRST PRODUCT XML STRUCTURE ===\n";
    echo $match[1] . "\n\n";
    
    // Extract all field names
    echo "=== ALL FIELD NAMES IN FIRST PRODUCT ===\n";
    preg_match_all('/<([a-zA-Z_]+)>/', $match[1], $fieldMatches);
    $fields = array_unique($fieldMatches[1]);
    sort($fields);
    
    foreach ($fields as $field) {
        echo "- $field\n";
    }
}

// Show response size
echo "\n=== RESPONSE INFO ===\n";
echo "Total response size: " . strlen($response) . " characters\n";
echo "Number of products: " . substr_count($response, '<urun>') . "\n";
?>