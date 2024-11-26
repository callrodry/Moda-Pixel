<?php
// get_products.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

define('PIPEDRIVE_API_KEY', '125ed656837ef1cde6ea47d0f134e109a8b2ae6b');
define('PIPEDRIVE_API_URL', 'https://api.pipedrive.com/v1');

function getPipedriveProducts() {
    $url = PIPEDRIVE_API_URL . '/products?api_token=' . PIPEDRIVE_API_KEY;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("Error en la petición a Pipedrive: " . $error);
    }
    
    return json_decode($response, true);
}

function getProductPictures($productId) {
    $url = PIPEDRIVE_API_URL . '/products/' . $productId . '/pictures?api_token=' . PIPEDRIVE_API_KEY;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return null;
    }
    
    $pictures = json_decode($response, true);
    if (isset($pictures['data']) && !empty($pictures['data'])) {
        // Retornar la imagen en tamaño 512 o la primera disponible
        foreach ($pictures['data'] as $picture) {
            if (isset($picture['pictures'][512])) {
                return $picture['pictures'][512];
            }
        }
        // Si no hay imagen de 512, usar la primera disponible
        return $pictures['data'][0]['pictures'][array_key_first($pictures['data'][0]['pictures'])];
    }
    return null;
}

try {
    $pipedriveResponse = getPipedriveProducts();
    
    if (!isset($pipedriveResponse['success']) || !$pipedriveResponse['success']) {
        throw new Exception('Error al obtener productos de Pipedrive');
    }

    // Formatear productos para la respuesta
    $products = array_map(function($product) {
        $image = getProductPictures($product['id']);
        return [
            'id' => $product['id'],
            'name' => $product['name'],
            'description' => $product['description'] ?? 'Sin descripción',
            'price' => floatval($product['prices'][0]['price'] ?? $product['price'] ?? 0),
            'image_url' => $image,
            'code' => $product['code'] ?? '',
            'category' => $product['category'] ?? 'General',
            'unit' => $product['unit'] ?? 'pza',
            'active_flag' => $product['active_flag'] ?? true
        ];
    }, $pipedriveResponse['data']);

    // Filtrar solo productos activos
    $activeProducts = array_filter($products, function($product) {
        return $product['active_flag'];
    });

    // Seleccionar algunos productos como recomendados
    $recommended = array_slice($activeProducts, 0, 3); // Mostrar los primeros 3 productos como recomendados

    echo json_encode([
        'success' => true,
        'products' => array_values($activeProducts),
        'recommended' => $recommended
    ]);

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>