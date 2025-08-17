<?php
// Direct BizimHesap API Test
header('Content-Type: application/json');

$api_key = '6F4BAF303FA240608A39653824B6C495';
$url = 'https://bizimhesap.com/api/product/getproductsasxml?apikey=' . $api_key;

echo json_encode([
    'test_url' => $url,
    'timestamp' => date('Y-m-d H:i:s')
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/xml'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "\n\nHTTP Code: $http_code\n";
echo "Error: " . ($error ?: 'None') . "\n";
echo "Response Length: " . strlen($response) . "\n";
echo "First 500 chars:\n" . substr($response, 0, 500);

if ($response && strlen($response) > 0) {
    // Try parsing with NOCDATA to handle CDATA sections
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
    
    if ($xml !== false) {
        echo "\n\nXML parsed successfully!";
        echo "\nRoot element: " . $xml->getName();
        if (isset($xml->urun)) {
            echo "\nFound " . count($xml->urun) . " urun elements";
            if (count($xml->urun) > 0) {
                $first = $xml->urun[0];
                echo "\nFirst product: " . (string)$first->urun_ad;
                echo "\nFirst category: " . (string)$first->kat_yolu;
                echo "\nFirst price: " . (string)$first->satis_fiyat;
                echo "\nFirst stock: " . (string)$first->stok;
                
                // Convert to same format as API
                $testProduct = [
                    'id' => (string)$first->stok_kod,
                    'title' => (string)$first->urun_ad,
                    'category' => (string)$first->kat_yolu,
                    'price' => (float)str_replace(',', '.', (string)$first->satis_fiyat),
                    'stock' => (int)$first->stok,
                    'brand' => (string)$first->marka
                ];
                echo "\n\nConverted product JSON:\n" . json_encode($testProduct, JSON_PRETTY_PRINT);
            }
        } else {
            echo "\nNo urun elements found";
            echo "\nAvailable elements: ";
            foreach ($xml->children() as $child) {
                echo $child->getName() . " ";
            }
        }
    } else {
        echo "\n\nXML parsing failed!";
        $errors = libxml_get_errors();
        foreach ($errors as $error) {
            echo "\nXML Error: " . $error->message;
        }
    }
}
?>