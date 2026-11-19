<?php
require_once __DIR__ . '/_comun.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responderJson(405, ['error' => 'Método no permitido.']);
exigirMismoOrigen();
// Solo JSON: un formulario de otro sitio no puede mandar este tipo de contenido sin permiso previo del navegador.
if (stripos((string)($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json') !== 0) responderJson(415, ['error' => 'Se esperaba JSON.']);

$raw = file_get_contents('php://input');
if ($raw === false || strlen($raw) > 2_000_000) responderJson(413, ['error' => 'Cuerpo demasiado grande.']);
$input = json_decode($raw, true);
if (!is_array($input) || !isset($input['products']) || !is_array($input['products'])) responderJson(400, ['error' => 'Datos inválidos.']);

$error = validarProductos($input['products']);
if ($error !== null) responderJson(422, ['error' => $error]);

$dataFile = __DIR__ . '/../data/productos.json';
$result   = file_put_contents($dataFile, json_encode($input['products'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);

if ($result === false) responderJson(500, ['error' => 'No se pudo guardar. Verifica permisos del archivo data/productos.json']);

responderJson(200, ['ok' => true]);
