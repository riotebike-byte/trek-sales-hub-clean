<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// BizimHesap B2B API - Products endpoint
$token = '6F4BAF303FA240608A39653824B6C495';  // Correct token from Node.js proxy
$url = 'https://bizimhesap.com/api/b2b/products';

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'token: ' . $token,  // B2B API uses 'token' header, not 'Authorization: Bearer'
    'Content-Type: application/json',
    'User-Agent: Trek-Sales-Hub/1.0'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Return response
if ($response && $httpCode === 200) {
    echo $response;
} else {
    // Log error for debugging
    error_log("B2B API Error - HTTP $httpCode: $error");
    
    // Return error response
    echo json_encode([
        'resultCode' => 0,
        'errorText' => 'API Error: ' . ($error ?: "HTTP $httpCode"),
        'data' => ['products' => []]
    ]);
}
?>