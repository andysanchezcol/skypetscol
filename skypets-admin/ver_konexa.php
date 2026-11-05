<?php
// Muestra en el navegador una foto de un formulario de Konexa (los certificados con enlaces «Ver foto»). Pide la imagen a Konexa con la clave
// del destino; solo entra quien tenga sesión en el admin.
ini_set('display_errors', 0);
require_once __DIR__ . '/auth.php';
requireLogin();
require_once __DIR__ . '/google_api.php';

$id = $_GET['id'] ?? '';
if (!preg_match('/^[0-9a-f-]{36}$/i', $id)) { http_response_code(404); exit('No encontrado'); }
[$datos, $codigo, $tipo] = konexaDescargar($id);
if ($codigo !== 200 || $datos === '') { http_response_code(502); exit('No se pudo cargar la imagen.'); }
header('Content-Type: ' . ($tipo ?: 'application/octet-stream'));
header('Cache-Control: private, max-age=600');
echo $datos;
