<?php
// config.php

// Definir las constantes de configuración
define('PIPEDRIVE_CONFIG', [
    'api_key' => '125ed656837ef1cde6ea47d0f134e109a8b2ae6b', // Tu API key de Pipedrive
    'api_url' => 'https://api.pipedrive.com/v1',
    'company_domain' => 'tu-dominio', // Reemplaza con tu dominio de Pipedrive
    'pipeline_id' => 1, // ID de tu pipeline por defecto
    'stage_id' => 1,   // ID de la etapa inicial de tu pipeline
]);

// Configuración de la base de datos (si la necesitas)
define('DB_CONFIG', [
    'host'     => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'moda_pixel'
]);

// Configuración de la aplicación
define('APP_CONFIG', [
    'debug_mode' => true,  // Establecer en false en producción
    'timezone'   => 'America/Mexico_City',
    'charset'    => 'UTF-8'
]);

// Configuración de correo (si planeas enviar notificaciones)
define('MAIL_CONFIG', [
    'smtp_host'     => 'smtp.tu-proveedor.com',
    'smtp_port'     => 587,
    'smtp_username' => 'tu@email.com',
    'smtp_password' => 'tu_contraseña',
    'from_email'    => 'noreply@tu-dominio.com',
    'from_name'     => 'Moda Pixel'
]);

// Función para obtener configuraciones de forma segura
function getConfig($section, $key = null) {
    $config = constant($section . '_CONFIG');
    
    if ($key === null) {
        return $config;
    }
    
    return isset($config[$key]) ? $config[$key] : null;
}

// Configurar zona horaria por defecto
date_default_timezone_set('America/Mexico_City');

// Configurar reporte de errores según el modo de debug
if (APP_CONFIG['debug_mode']) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}