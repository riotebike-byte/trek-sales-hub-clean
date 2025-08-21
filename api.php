<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Parse request path to determine endpoint
$requestUri = $_SERVER['REQUEST_URI'];
$endpoint = '';

if (strpos($requestUri, '/api/chat') !== false) {
    $endpoint = 'chat';
} elseif (strpos($requestUri, '/api/refresh') !== false) {
    $endpoint = 'refresh';
} else {
    // Default to B2B products API
    $endpoint = 'products';
}

// Configuration
$OPENAI_API_KEY = getenv('OPENAI_API_KEY') ?: 'your-openai-api-key-here';
$SALES_API_URL = "http://localhost:3002/api/b2b/products";

function loadSalesData() {
    global $SALES_API_URL;
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($SALES_API_URL, false, $context);
    
    if ($response === false) {
        return [];
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['data']['products'])) {
        return $data['data']['products'];
    }
    
    return [];
}

function loadTrekProducts() {
    $url = 'https://www.trekbisiklet.com.tr/output/8582384479';
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'method' => 'GET'
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        return [];
    }
    
    $products = [];
    
    try {
        $xml = new SimpleXMLElement($response);
        
        foreach ($xml->item as $item) {
            $name = (string)$item->rootlabel;
            $stock = (string)$item->stockAmount;
            
            if (!empty($name) && !empty($stock) && $stock > 0) {
                $price = (string)$item->priceTaxWithCur;
                $products[] = [
                    'name' => $name,
                    'stock' => 'stokta',
                    'price' => !empty($price) ? $price : 'Fiyat bilgisi yok'
                ];
            }
        }
    } catch (Exception $e) {
        error_log("Trek XML parsing error: " . $e->getMessage());
    }
    
    return $products;
}

function calculateProfitMetrics($product) {
    $buyingPrice = floatval($product['buyingPrice'] ?? 0);
    $sellingPrice = floatval($product['price'] ?? 0);
    $quantity = intval($product['quantity'] ?? 0);
    
    $taxRate = 35; // Default
    $category = strtoupper($product['category'] ?? '');
    
    if (strpos($category, 'DS') !== false || strpos($category, 'POMPA') !== false) {
        $taxRate = 70;
    } elseif (strpos($category, 'AKSESUAR') !== false) {
        $taxRate = 40;
    }
    
    $landedCost = $buyingPrice * (1 + $taxRate / 100);
    $margin = $sellingPrice > 0 ? (($sellingPrice - $landedCost) / $sellingPrice * 100) : 0;
    $profitPotential = ($sellingPrice - $landedCost) * $quantity;
    
    return [
        'margin' => $margin,
        'profit_potential' => $profitPotential,
        'landed_cost' => $landedCost
    ];
}

function searchProducts($query, $salesData, $trekProducts) {
    $results = [];
    $queryLower = strtolower($query);
    
    // Search sales data (limit to 20)
    $count = 0;
    foreach ($salesData as $product) {
        if ($count >= 20) break;
        
        $title = strtolower($product['title'] ?? '');
        if (strpos($title, $queryLower) !== false) {
            $metrics = calculateProfitMetrics($product);
            
            $result = [
                'source' => 'Sales Data',
                'title' => $product['title'] ?? '',
                'price' => '€' . number_format(floatval($product['price'] ?? 0), 2),
                'stock' => $product['quantity'] ?? 0,
                'category' => $product['category'] ?? 'GENEL'
            ];
            
            if ($metrics) {
                $result['margin'] = '%' . number_format($metrics['margin'], 1);
                $result['profit_potential'] = '€' . number_format($metrics['profit_potential'], 0);
            }
            
            $results[] = $result;
            $count++;
        }
    }
    
    // Search Trek products (limit to 10)
    $count = 0;
    foreach ($trekProducts as $product) {
        if ($count >= 10) break;
        
        if (strpos(strtolower($product['name']), $queryLower) !== false) {
            $results[] = [
                'source' => 'Trek Catalog',
                'title' => $product['name'],
                'price' => $product['price'],
                'stock' => $product['stock']
            ];
            $count++;
        }
    }
    
    return $results;
}

function getSalesSummary($salesData) {
    if (empty($salesData)) {
        return "Henüz sales verisi yüklenmedi.";
    }
    
    $totalProducts = count($salesData);
    $totalStock = array_sum(array_map(function($p) { return intval($p['quantity'] ?? 0); }, $salesData));
    
    $profitProducts = [];
    foreach ($salesData as $product) {
        $metrics = calculateProfitMetrics($product);
        if ($metrics) {
            $profitProducts[] = $metrics;
        }
    }
    
    if (!empty($profitProducts)) {
        $avgMargin = array_sum(array_column($profitProducts, 'margin')) / count($profitProducts);
        $totalProfit = array_sum(array_column($profitProducts, 'profit_potential'));
        
        return "📊 SALES ANALYTICS:\n" .
               "• Toplam Ürün: " . number_format($totalProducts) . "\n" .
               "• Toplam Stok: " . number_format($totalStock) . " adet\n" .
               "• Ortalama Kar Marjı: %" . number_format($avgMargin, 1) . "\n" .
               "• Toplam Kar Potansiyeli: €" . number_format($totalProfit, 0);
    }
    
    return "Toplam $totalProducts ürün analiz edildi.";
}

function generateAIResponse($message, $context = '') {
    global $OPENAI_API_KEY;
    
    if ($OPENAI_API_KEY === 'your-openai-api-key-here') {
        return "OpenAI API key henüz yapılandırılmamış. Lütfen OPENAI_API_KEY environment variable'ını ayarlayın.";
    }
    
    $salesData = loadSalesData();
    $trekProducts = loadTrekProducts();
    
    $fullContext = "Sen Trek Sales Hub'ın AI asistanısın. " . count($salesData) . " sales ürünü ve " . count($trekProducts) . " katalog ürünü erişimin var.\n\n" .
                   $context . "\n\n" .
                   "Kullanıcı mesajı: $message\n\n" .
                   "Bu bilgileri kullanarak detaylı, yardımsever ve veri odaklı bir cevap ver. Türkçe konuş.";
    
    $payload = [
        'model' => 'gpt-5-chat-latest',
        'messages' => [
            ['role' => 'system', 'content' => 'Sen Trek bisiklet satış verilerini analiz eden AI asistansın. Türkçe konuşuyorsun.'],
            ['role' => 'user', 'content' => $fullContext]
        ],
        'max_tokens' => 500,
        'temperature' => 0.7
    ];
    
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $OPENAI_API_KEY
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response === false || $httpCode !== 200) {
        return "API Hatası: $httpCode";
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['choices'][0]['message']['content'])) {
        return $data['choices'][0]['message']['content'];
    }
    
    return "Unexpected API response format";
}

// Handle endpoints
switch ($endpoint) {
    case 'chat':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $message = $input['message'] ?? '';
        
        if (empty($message)) {
            http_response_code(400);
            echo json_encode(['error' => 'No message provided']);
            exit;
        }
        
        $salesData = loadSalesData();
        $trekProducts = loadTrekProducts();
        
        // Search for products
        $products = searchProducts($message, $salesData, $trekProducts);
        
        // Build context
        $context = '';
        if (!empty($products)) {
            $context .= "\n\nÜRÜN ARAMA SONUÇLARI:\n";
            foreach (array_slice($products, 0, 5) as $product) {
                $context .= "• {$product['title']} ({$product['source']}) - {$product['price']}\n";
            }
        }
        
        // Add analytics if requested
        $analyticsWords = ['analiz', 'rapor', 'kar', 'profit'];
        $messageWords = explode(' ', strtolower($message));
        if (array_intersect($analyticsWords, $messageWords)) {
            $context .= "\n\n" . getSalesSummary($salesData);
        }
        
        // Generate AI response
        $aiResponse = generateAIResponse($message, $context);
        
        echo json_encode([
            'response' => $aiResponse,
            'products' => array_slice($products, 0, 10),
            'stats' => [
                'sales_products' => count($salesData),
                'trek_products' => count($trekProducts)
            ]
        ]);
        break;
        
    case 'refresh':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        $salesData = loadSalesData();
        $trekProducts = loadTrekProducts();
        
        echo json_encode([
            'success' => true,
            'sales_loaded' => !empty($salesData),
            'trek_loaded' => !empty($trekProducts),
            'sales_count' => count($salesData),
            'trek_count' => count($trekProducts)
        ]);
        break;
        
    default:
        // Default B2B products API - Use original BizimHesap proxy logic
        $token = '6F4BAF303FA240608A39653824B6C495';
        $url = 'https://bizimhesap.com/api/b2b/products';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'token: ' . $token,
            'Content-Type: application/json',
            'User-Agent: Trek-Sales-Hub/1.0'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($response && $httpCode === 200) {
            echo $response;
        } else {
            error_log("B2B API Error - HTTP $httpCode: $error");
            echo json_encode([
                'resultCode' => 0,
                'errorText' => 'API Error: ' . ($error ?: "HTTP $httpCode"),
                'data' => ['products' => []]
            ]);
        }
        break;
}
?>