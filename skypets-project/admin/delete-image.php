<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido.']); exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$path  = $input['path'] ?? '';

// Solo permitir rutas dentro de assets/images/productos/
if (!preg_match('#^/assets/images/productos/[a-zA-Z0-9\-_.]+$#', $path)) {
    http_response_code(400); echo json_encode(['error' => 'Ruta no válida.']); exit;
}

$fullPath = __DIR__ . '/..' . $path;
if (file_exists($fullPath)) unlink($fullPath);

echo json_encode(['ok' => true]);
