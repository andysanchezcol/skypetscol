<?php
require_once __DIR__ . '/config.php';

// Sesión del admin con duración de 8 h y carpeta propia. En el hosting compartido
// las sesiones por defecto viven en un /tmp común que el sistema limpia por su
// cuenta (~24 min) sin importar ini_set(), lo que cerraba la sesión de la doctora.
function iniciarSesionAdmin(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $dir = __DIR__ . '/tmp/sessions';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
        @file_put_contents($dir . '/.htaccess', "Require all denied\nDeny from all\n");
    }
    if (is_dir($dir) && is_writable($dir)) session_save_path($dir);
    ini_set('session.gc_maxlifetime', 28800);
    ini_set('session.cookie_lifetime', 28800);
    session_set_cookie_params([
        'lifetime' => 28800, 'path' => '/',
        'secure' => true, 'httponly' => true, 'samesite' => 'Lax',
    ]);
    session_start();
}

function requireLogin(): void {
    iniciarSesionAdmin();
    if (empty($_SESSION['admin_logged_in'])) {
        header('Location: index.php');
        exit;
    }
}

function doLogin(string $user, string $pass): bool {
    return $user === ADMIN_USER && password_verify($pass, ADMIN_PASS);
}
