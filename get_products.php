<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Configuración
define('DOLIBARR_API_URL', 'https://modapiexl.saas2.doliondemand.fr/api/index.php');
define('DOLIBARR_API_KEY', 'fYGSn7ND5w215yfs6Z51p7mKh1UKfUuG');
define('ASSETS_DIR', __DIR__ . '/assets/');
define('DEBUG_MODE', true);

// Crear directorio de assets si no existe
if (!file_exists(ASSETS_DIR)) {
    mkdir(ASSETS_DIR, 0777, true);
}

function debug_log($message, $data = null) {
    if (DEBUG_MODE) {
        error_log(sprintf("[DEBUG] %s: %s", 
            $message, 
            $data !== null ? json_encode($data, JSON_UNESCAPED_UNICODE) : 'null'
        ));
    }
}

function downloadProductImage($productId) {
    $productsDir = ASSETS_DIR . 'products/'; // Definimos la ruta de products aquí
    
    // Crear directorio products si no existe
    if (!file_exists($productsDir)) {
        mkdir($productsDir, 0777, true);
    }

    $localPath = $productsDir . $productId . '.jpg';
    $webPath = 'assets/products/' . $productId . '.jpg';
    
    // Debug
    error_log("Ruta de productos: " . $productsDir);
    error_log("Ruta local de imagen: " . $localPath);
    
    // Si la imagen ya existe localmente, devolver la ruta web
    if (file_exists($localPath)) {
        return $webPath;
    }

    // URL de la imagen en Dolibarr
    $imageUrl = DOLIBARR_API_URL . '/products/' . $productId . '/photo?DOLAPIKEY=' . DOLIBARR_API_KEY;

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $imageUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0
    ]);

    $imageData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && !empty($imageData)) {
        if (file_put_contents($localPath, $imageData)) {
            return $webPath;
        }
    }

    return 'assets/placeholder.jpg';
}

function makeApiRequest($endpoint) {
    $url = DOLIBARR_API_URL . $endpoint;
    debug_log("Iniciando solicitud API", ['url' => $url]);
    
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
    $curlError = curl_errno($ch);
    $curlErrorMessage = curl_error($ch);
    
    if ($curlError) {
        curl_close($ch);
        throw new Exception("Error CURL: " . $curlErrorMessage);
    }
    
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("Error en la API de Dolibarr. Código HTTP: " . $httpCode);
    }
    
    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Error decodificando JSON: " . json_last_error_msg());
    }
    
    return $decoded;
}

function processProduct($product) {
    debug_log("Procesando producto", $product);
    
    if (!isset($product['id']) || !isset($product['label'])) {
        debug_log("Producto inválido - falta ID o label");
        return null;
    }

    // Descargar y obtener la ruta de la imagen
    $imagePath = downloadProductImage($product['id']);
    
    return [
        'id' => (int)$product['id'],
        'name' => strval($product['label'] ?? 'Producto sin nombre'),
        'description' => strval($product['description'] ?? 'Sin descripción'),
        'price' => number_format(floatval($product['price'] ?? 0), 2, '.', ''),
        'image_url' => $imagePath,
        'code' => strval($product['ref'] ?? ''),
        'category' => strval($product['category'] ?? 'Ropa'),
        'stock' => (int)($product['stock_reel'] ?? 0)
    ];
}

try {
    debug_log("Iniciando obtención de productos");
    
    $products = makeApiRequest('/products?sortfield=t.ref&sortorder=ASC&limit=100');
    
    if (!is_array($products)) {
        throw new Exception('La API no devolvió un array de productos válido');
    }
    
    debug_log("Productos obtenidos", ['count' => count($products)]);
    
    $processedProducts = array_filter(
        array_map(
            'processProduct',
            array_filter($products, function($product) {
                return isset($product['status']) && $product['status'] == '1';
            })
        )
    );
    
    if (empty($processedProducts)) {
        throw new Exception('No se encontraron productos activos');
    }
    
    // Obtener los últimos 3 productos como recomendados
    $recommended = array_slice($processedProducts, -3);
    
    $response = [
        'success' => true,
        'products' => array_values($processedProducts),
        'recommended' => $recommended,
        'debug' => DEBUG_MODE ? [
            'total_products' => count($products),
            'processed_products' => count($processedProducts),
            'recommended_products' => count($recommended)
        ] : null
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    debug_log("Respuesta enviada exitosamente");

} catch (Exception $e) {
    $error = [
        'success' => false,
        'message' => 'Error al cargar los productos: ' . $e->getMessage(),
        'debug' => DEBUG_MODE ? [
            'error_type' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTrace()
        ] : null
    ];
    
    debug_log("Error en la aplicación", $error);
    http_response_code(500);
    echo json_encode($error);
}