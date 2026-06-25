<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['products']) || !is_array($input['products'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos inválidos.']);
    exit;
}

$dataFile = __DIR__ . '/../data/productos.json';
$result   = file_put_contents($dataFile, json_encode($input['products'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

if ($result === false) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo guardar. Verifica permisos del archivo data/productos.json']);
    exit;
}

echo json_encode(['ok' => true]);
