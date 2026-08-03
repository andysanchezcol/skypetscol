<?php
header('Content-Type: application/json');

$allowed = ['image/png','image/jpeg','image/webp'];
$maxSize = 10 * 1024 * 1024; // 10 MB

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No se recibió ningún archivo.']);
    exit;
}

$file = $_FILES['image'];

if (!in_array($file['type'], $allowed)) {
    http_response_code(400);
    echo json_encode(['error' => 'Solo se permiten PNG, JPG o WebP.']);
    exit;
}

if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['error' => 'El archivo supera los 10 MB.']);
    exit;
}

$ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
$slug     = preg_replace('/[^a-z0-9\-]/', '', strtolower($_POST['slug'] ?? 'producto'));
$filename = $slug . '-' . uniqid() . '.' . strtolower($ext);
$destDir  = __DIR__ . '/../assets/images/productos/';
$destPath = $destDir . $filename;

if (!is_dir($destDir)) mkdir($destDir, 0755, true);

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al guardar el archivo.']);
    exit;
}

echo json_encode(['path' => '/assets/images/productos/' . $filename]);
