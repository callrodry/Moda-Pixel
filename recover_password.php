<?php
// recover_password.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Leer datos de la petición
    $input = json_decode(file_get_contents('php://input'), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error en el formato de datos');
    }

    // Validar campos requeridos
    if (empty($input['email']) || empty($input['phone']) || empty($input['birthdate'])) {
        throw new Exception('Todos los campos son requeridos');
    }

    // Conectar a la base de datos
    $conn = new mysqli('localhost', 'root', '', 'moda_pixel');
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");

    // Buscar usuario por email, teléfono y fecha de nacimiento
    $stmt = $conn->prepare("SELECT id, email FROM clientes WHERE email = ? AND telefono = ? AND fecha_nacimiento = ?");
    $stmt->bind_param("sss", $input['email'], $input['phone'], $input['birthdate']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        throw new Exception('No se encontró una cuenta con esos datos');
    }

    // Generar token temporal (válido por 1 hora)
    $token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Guardar token en la base de datos
    $stmt = $conn->prepare("UPDATE clientes SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
    $stmt->bind_param("ssi", $token, $expiry, $user['id']);
    $stmt->execute();

    // Aquí normalmente enviarías un email con el link de recuperación
    // Por ahora solo simulamos el envío
    $resetLink = "http://localhost/Moda/reset_password.html?token=" . $token;

    // En producción, aquí iría el código para enviar el email
    // Por ahora solo logueamos el link
    error_log("Reset link para {$user['email']}: $resetLink");

    echo json_encode([
        'success' => true,
        'message' => 'Se han enviado las instrucciones de recuperación a tu correo'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>