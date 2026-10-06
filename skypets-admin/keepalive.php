<?php
// Renueva la sesión activa — llamado por JS cada 10 minutos
require_once __DIR__ . '/auth.php';
iniciarSesionAdmin();
// PHP no actualiza la fecha del archivo de sesión si $_SESSION no cambia
// (session.lazy_write, activo por defecto) — sin esta línea, este ping
// no sirve de nada: confirma la sesión pero no la renueva de verdad.
$_SESSION['last_keepalive'] = time();
header('Content-Type: application/json');
echo json_encode(['ok' => isset($_SESSION['admin_logged_in'])]);
