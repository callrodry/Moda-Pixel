<?php
// process_order.php

header('Content-Type: application/json');

require_once 'Database.php';
require_once 'config.php';

// Definir las credenciales de PayPal
define('PAYPAL_CLIENT_ID', 'AYpYkhzGuMPHwKf0jJ2R63izoWJBmoEptpTE2q8L7nK7shgdwQh7GHn-1Vb9fE1mF8mWAR8PZm5vALqE');
define('PAYPAL_SECRET_KEY', 'EBHORmiMRnKiReUyVoGx4-jbzNnSojf7jH9Z2KIZR3vZrUCD_gyom6MSfoUZ97DHoViryIK7Nf0dsioo');

// Función para verificar el pago con PayPal
function verifyPayPalPayment($orderId) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api-m.paypal.com/v2/checkout/orders/" . $orderId);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode(PAYPAL_CLIENT_ID . ':' . PAYPAL_SECRET_KEY)
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception('Error al verificar el pago con PayPal');
    }

    return json_decode($response, true);
}

try {
    // Obtener datos de la orden
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Datos de orden inválidos');
    }

    // Verificar el pago con PayPal antes de procesar la orden
    $paypalVerification = verifyPayPalPayment($input['paypal_order_id']);
    
    if ($paypalVerification['status'] !== 'COMPLETED') {
        throw new Exception('El pago no ha sido completado en PayPal');
    }

    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Resto del código existente...
    
    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // Insertar orden
        $stmt = $conn->prepare("
            INSERT INTO orders (
                paypal_order_id, 
                total_amount, 
                status,
                customer_email,
                customer_name,
                created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param(
            "sdsss",
            $input['paypal_order_id'],
            $input['total'],
            $input['status'],
            $input['customer']['email_address'],
            $input['customer']['name']['given_name'] . ' ' . $input['customer']['name']['surname']
        );

        $stmt->execute();
        $orderId = $conn->insert_id;

        // Insertar items de la orden
        $stmt = $conn->prepare("
            INSERT INTO order_items (
                order_id,
                product_id,
                quantity,
                price
            ) VALUES (?, ?, ?, ?)
        ");

        foreach ($input['cart_items'] as $item) {
            $stmt->bind_param(
                "iiid",
                $orderId,
                $item['id'],
                $item['quantity'],
                $item['price']
            );
            $stmt->execute();

            // Actualizar stock
            $conn->query("
                UPDATE products 
                SET stock = stock - {$item['quantity']} 
                WHERE id = {$item['id']}
            ");
        }

        // Actualizar Dolibarr si es necesario
        // Aquí puedes agregar la lógica para sincronizar con Dolibarr

        // Confirmar transacción
        $conn->commit();

        echo json_encode([
            'success' => true,
            'order_id' => $orderId,
            'message' => 'Orden procesada exitosamente'
        ]);

    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar la orden: ' . $e->getMessage()
    ]);
}
?>