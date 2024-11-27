<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

define('DOLIBARR_API_URL', 'https://modapiexl.saas2.doliondemand.fr/api/index.php');
define('DOLIBARR_API_KEY', 'fYGSn7ND5w215yfs6Z51p7mKh1UKfUuG');

function makeApiRequest($endpoint) {
    $url = DOLIBARR_API_URL . $endpoint;
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'DOLAPIKEY: ' . DOLIBARR_API_KEY
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        curl_close($ch);
        throw new Exception("Error CURL: " . curl_error($ch));
    }
    
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("Error en la API de Dolibarr. Código HTTP: " . $httpCode);
    }
    
    return json_decode($response, true);
}

try {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('ID de producto no válido');
    }

    $productId = (int)$_GET['id'];
    
    // Obtener el producto de Dolibarr
    $product = makeApiRequest('/products/' . $productId);
    
    if (!$product) {
        throw new Exception('Producto no encontrado');
    }

    // Descargar la imagen del producto
    $imageResponse = file_get_contents("http://localhost/moda/download_image.php?id=" . $productId);
    $imageData = json_decode($imageResponse, true);
    
    $imagePath = $imageData['success'] ? $imageData['path'] : 'assets/placeholder.jpg';
    
    // Procesar y formatear los datos del producto
    $processedProduct = [
        'id' => (int)$product['id'],
        'name' => $product['label'] ?? 'Sin nombre',
        'description' => $product['description'] ?? 'Sin descripción',
        'price' => number_format(floatval($product['price'] ?? 0), 2, '.', ''),
        'image_url' => $imagePath,
        'code' => $product['ref'] ?? '',
        'stock' => (int)($product['stock_reel'] ?? 0)
    ];
    
    echo json_encode([
        'success' => true,
        'product' => $processedProduct
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}