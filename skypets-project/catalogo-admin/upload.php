<?php
require_once __DIR__ . '/_comun.php';
header('Content-Type: application/json');

$maxSize = 10 * 1024 * 1024; // 10 MB

exigirMismoOrigen();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No se recibió ningún archivo.']);
    exit;
}

$file = $_FILES['image'];

// El tipo se lee del contenido del archivo, no de lo que declara el navegador.
$extPorTipo = [IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_WEBP => 'webp'];
$info = @getimagesize($file['tmp_name']);
if ($file['error'] !== UPLOAD_ERR_OK || !$info || !isset($extPorTipo[$info[2]])) {
    http_response_code(400);
    echo json_encode(['error' => 'Solo se permiten PNG, JPG o WebP.']);
    exit;
}

if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['error' => 'El archivo supera los 10 MB.']);
    exit;
}

$ext      = $extPorTipo[$info[2]];
$slug     = preg_replace('/[^a-z0-9\-]/', '', strtolower($_POST['slug'] ?? 'producto'));
$slug     = substr($slug, 0, 60) ?: 'producto';
$filename = $slug . '-' . bin2hex(random_bytes(8)) . '.' . $ext;   // sufijo aleatorio: no se puede adivinar el nombre
$destDir  = __DIR__ . '/../assets/images/productos/';
$destPath = $destDir . $filename;

if (!is_dir($destDir)) mkdir($destDir, 0755, true);

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al guardar el archivo.']);
    exit;
}

echo json_encode(['path' => '/assets/images/productos/' . $filename]);
