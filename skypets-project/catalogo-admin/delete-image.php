<?php
require_once __DIR__ . '/_comun.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responderJson(405, ['error' => 'Método no permitido.']);
exigirMismoOrigen();
if (stripos((string)($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json') !== 0) responderJson(415, ['error' => 'Se esperaba JSON.']);

$input = json_decode((string)file_get_contents('php://input'), true);
$path  = is_array($input) ? (string)($input['path'] ?? '') : '';

// Solo archivos de imagen dentro de assets/images/productos/ (sin «..» ni subcarpetas)
if (!preg_match('#^/assets/images/productos/([A-Za-z0-9][A-Za-z0-9._-]{0,150}\.(png|jpe?g|webp))$#i', $path, $m)) {
    responderJson(400, ['error' => 'Ruta no válida.']);
}

$fullPath = __DIR__ . '/../assets/images/productos/' . $m[1];
if (is_file($fullPath)) unlink($fullPath);

responderJson(200, ['ok' => true]);
