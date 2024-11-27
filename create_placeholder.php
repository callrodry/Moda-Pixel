<?php
// Verificar que GD está habilitado
if (!extension_loaded('gd')) {
    die('La extensión GD de PHP no está habilitada');
}

// Configurar el directorio assets
$assetsDir = __DIR__ . '/assets/';

// Crear el directorio si no existe
if (!file_exists($assetsDir)) {
    mkdir($assetsDir, 0777, true);
}

// Crear una imagen de 300x300 píxeles
$width = 300;
$height = 300;
$image = imagecreatetruecolor($width, $height);

// Definir colores
$bgColor = imagecolorallocate($image, 240, 240, 240);  // Gris claro para el fondo
$textColor = imagecolorallocate($image, 100, 100, 100); // Gris oscuro para el texto

// Rellenar el fondo
imagefill($image, 0, 0, $bgColor);

// Agregar texto
$text = "No Image";
$font = 5; // Tamaño de fuente (1-5)
// Centrar el texto
$textWidth = imagefontwidth($font) * strlen($text);
$textHeight = imagefontheight($font);
$x = ($width - $textWidth) / 2;
$y = ($height - $textHeight) / 2;

imagestring($image, $font, $x, $y, $text, $textColor);

// Establecer el header para imagen JPEG
header('Content-Type: image/jpeg');

// Guardar la imagen
$placeholderPath = $assetsDir . 'placeholder.jpg';
imagejpeg($image, $placeholderPath, 90); // 90 es la calidad

// Liberar memoria
imagedestroy($image);

echo "Placeholder creado exitosamente en: " . $placeholderPath;