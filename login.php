<?php
// login.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept, Authorization");

define('PIPEDRIVE_API_KEY', 'fYGSn7ND5w215yfs6Z51p7mKh1UKfUuG');
define('PIPEDRIVE_API_URL', 'https://api.pipedrive.com/v1');

function pipedriveRequest($endpoint, $method = 'GET', $data = null) {
    $url = PIPEDRIVE_API_URL . '/' . $endpoint . '?api_token=' . PIPEDRIVE_API_KEY;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("Error en la petición a Pipedrive: " . $error);
    }
    
    return json_decode($response, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Leer datos de la petición
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al decodificar JSON');
    }

    if (empty($input['email']) || empty($input['password'])) {
        throw new Exception('Email y contraseña son requeridos');
    }

    // Conectar a la base de datos
    $conn = new mysqli('localhost', 'root', '', 'moda_pixel');
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

    // Buscar usuario por email
    $stmt = $conn->prepare("SELECT id, nombre, email, password, pipedrive_id FROM clientes WHERE email = ?");
    $stmt->bind_param("s", $input['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        throw new Exception('Credenciales inválidas');
    }

    // Verificar contraseña
    if (!password_verify($input['password'], $user['password'])) {
        throw new Exception('Credenciales inválidas');
    }

    // Obtener información adicional de Pipedrive si es necesario
    if ($user['pipedrive_id']) {
        $pipedriveData = pipedriveRequest('persons/' . $user['pipedrive_id']);
        if (isset($pipedriveData['data'])) {
            $user['pipedrive_data'] = $pipedriveData['data'];
        }
    }

    // Iniciar sesión
    session_start();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['pipedrive_id'] = $user['pipedrive_id'];

    // Limpiar datos sensibles antes de enviar
    unset($user['password']);

    // Enviar respuesta
    echo json_encode([
        'success' => true,
        'message' => 'Inicio de sesión exitoso',
        'user' => $user
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>