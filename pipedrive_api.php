<?php
// pipedrive_api.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept, Authorization");

define('PIPEDRIVE_API_KEY', '125ed656837ef1cde6ea47d0f134e109a8b2ae6b');
define('PIPEDRIVE_API_URL', 'https://api.pipedrive.com/v1');

function pipedriveRequest($endpoint, $method = 'POST', $data = null) {
    $url = PIPEDRIVE_API_URL . '/' . $endpoint . '?api_token=' . PIPEDRIVE_API_KEY;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
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
    // Conectar a la base de datos
    $conn = new mysqli('localhost', 'root', '', 'moda_pixel');
    if ($conn->connect_error) {
        throw new Exception("Error de conexión a la BD: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

    // Leer datos
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al decodificar JSON: ' . json_last_error_msg());
    }

    // Validar campos requeridos
    if (empty($input['name']) || empty($input['email']) || empty($input['password']) || 
        empty($input['birthdate']) || empty($input['gender'])) {
        throw new Exception('Todos los campos marcados son requeridos');
    }

    // Hash de la contraseña
    $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);

    // Crear persona en Pipedrive con campos adicionales
    $personData = [
        'name' => $input['name'],
        'email' => [['value' => $input['email'], 'primary' => true]],
        'phone' => [['value' => $input['phone'] ?? '', 'primary' => true]],
        'add_time' => date('Y-m-d H:i:s'),
        'visible_to' => 3, // Visible para todos
        'custom_fields' => [
            'fecha_nacimiento' => $input['birthdate'],
            'sexo' => $input['gender']
        ]
    ];
    
    $pipedriveResponse = pipedriveRequest('persons', 'POST', $personData);
    
    if (!isset($pipedriveResponse['success']) || !$pipedriveResponse['success']) {
        throw new Exception('Error al crear contacto en Pipedrive');
    }

    $pipedrive_id = $pipedriveResponse['data']['id'];

    // Guardar en la base de datos local con los nuevos campos
    $stmt = $conn->prepare("INSERT INTO clientes (nombre, email, password, telefono, fecha_nacimiento, sexo, pipedrive_id, fecha_registro) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    
    if (!$stmt) {
        throw new Exception("Error en la preparación de la consulta: " . $conn->error);
    }

    $stmt->bind_param("ssssssi", 
        $input['name'],
        $input['email'],
        $hashedPassword,
        $input['phone'],
        $input['birthdate'],
        $input['gender'],
        $pipedrive_id
    );

    if (!$stmt->execute()) {
        throw new Exception("Error al insertar datos: " . $stmt->error);
    }

    $local_id = $stmt->insert_id;
    $stmt->close();

    // Crear deal en Pipedrive con información adicional
    $dealData = [
        'title' => "Lead Web - " . $input['name'],
        'person_id' => $pipedrive_id,
        'stage_id' => 1,
        'status' => 'open',
        'visible_to' => 3,
        'add_time' => date('Y-m-d H:i:s')
    ];
    
    $dealResponse = pipedriveRequest('deals', 'POST', $dealData);

    // Cerrar conexión
    $conn->close();

    // Enviar respuesta con todos los datos
    echo json_encode([
        'success' => true,
        'message' => 'Cliente registrado exitosamente',
        'data' => [
            'local_id' => $local_id,
            'pipedrive_id' => $pipedrive_id,
            'name' => $input['name'],
            'email' => $input['email'],
            'phone' => $input['phone'] ?? '',
            'birthdate' => $input['birthdate'],
            'gender' => $input['gender']
        ]
    ]);

} catch (Exception $e) {
    error_log("Error en el proceso: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>