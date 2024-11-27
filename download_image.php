<?php
header('Content-Type: application/json; charset=UTF-8');

define('DOLIBARR_API_URL', 'https://modapiexl.saas2.doliondemand.fr/api/index.php');
define('DOLIBARR_API_KEY', 'fYGSn7ND5w215yfs6Z51p7mKh1UKfUuG');
define('ASSETS_DIR', __DIR__ . '/assets/products/');

// Asegurarse de que el directorio existe
if (!file_exists(ASSETS_DIR)) {
    mkdir(ASSETS_DIR, 0777, true);
}

function downloadProductImage($productId) {
    $localPath = ASSETS_DIR . $productId . '.jpg';
    
    // Si la imagen ya existe localmente, devolver la ruta
    if (file_exists($localPath)) {
        return [
            'success' => true,
            'path' => 'assets/products/' . $productId . '.jpg'
        ];
    }

    // URL de la imagen en Dolibarr
    $imageUrl = DOLIBARR_API_URL . '/products/' . $productId . '/photo?DOLAPIKEY=' . DOLIBARR_API_KEY;

    // Descargar la imagen
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

    if ($httpCode !== 200 || empty($imageData)) {
        return [
            'success' => false,
            'message' => 'No se pudo descargar la imagen'
        ];
    }

    // Guardar la imagen localmente
    if (file_put_contents($localPath, $imageData)) {
        return [
            'success' => true,
            'path' => 'assets/products/' . $productId . '.jpg'
        ];
    }

    return [
        'success' => false,
        'message' => 'No se pudo guardar la imagen'
    ];
}

try {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('ID de producto no válido');
    }

    $productId = (int)$_GET['id'];
    $result = downloadProductImage($productId);

    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}