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
    $xml = simplexml_load_string($response);
    if ($xml !== false) {
        echo "\n\nXML parsed successfully!";
        echo "\nRoot element: " . $xml->getName();
        if (isset($xml->urun)) {
            echo "\nFound " . count($xml->urun) . " urun elements";
            if (count($xml->urun) > 0) {
                $first = $xml->urun[0];
                echo "\nFirst product: " . (string)$first->urun_adi;
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