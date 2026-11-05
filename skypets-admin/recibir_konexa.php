<?php
// Recibe el webhook «formulario.completado» de Konexa (destino del formulario CERTIFICADOS DE SALUD) y guarda la respuesta para que el
// dashboard la muestre junto a las de la hoja de Google. Se autentica con la firma HMAC-SHA256 del cuerpo (cabecera X-Konexa-Firma) y la clave
// compartida; Konexa reintenta si no recibe un 2xx, y envio_id evita duplicados.
require_once __DIR__ . '/konexa_config.php';
require_once __DIR__ . '/db.php';
header('Content-Type: application/json; charset=utf-8');

function responder(int $codigo, array $datos): void { http_response_code($codigo); echo json_encode($datos); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responder(405, ['error' => 'metodo no permitido']);
$raw = file_get_contents('php://input');
if ($raw === false || strlen($raw) > 1_000_000) responder(413, ['error' => 'cuerpo invalido']);
$firma = $_SERVER['HTTP_X_KONEXA_FIRMA'] ?? '';
if (!hash_equals(hash_hmac('sha256', $raw, KONEXA_SECRET), $firma)) responder(401, ['error' => 'firma invalida']);

$p = json_decode($raw, true);
if (!is_array($p) || ($p['evento'] ?? '') !== 'formulario.completado' || !preg_match('/^[0-9a-f-]{36}$/i', (string)($p['envio_id'] ?? ''))) {
    responder(400, ['error' => 'payload invalido']);
}
// Solo el formulario de certificados de salud va al dashboard; el resto se acepta y se ignora.
if (($p['formulario_codigo'] ?? '') !== KONEXA_FORMULARIO_SALUD) responder(200, ['ok' => true, 'ignorado' => true]);

try {
    $st = getDB()->prepare('INSERT IGNORE INTO konexa_envios (envio_id, payload) VALUES (?, ?)');
    $st->execute([$p['envio_id'], $raw]);
    responder(200, ['ok' => true, 'duplicado' => $st->rowCount() === 0]);
} catch (Throwable $e) {
    error_log('recibir_konexa: ' . $e->getMessage());
    responder(500, ['error' => 'no se pudo guardar']);
}
