<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ratelimit.php';

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
    ini_set('session.use_strict_mode', '1');   // no acepta ids de sesión que el servidor no emitió
    ini_set('session.use_only_cookies', '1');
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

// Límite de intentos de inicio de sesión: 5 fallos por IP cada 15 minutos y 60 fallos en total (ataque repartido entre IP).
const LOGIN_MAX_IP = 5;
const LOGIN_MAX_TOTAL = 60;
const LOGIN_VENTANA = 900;

// Segundos de bloqueo vigentes (0 = puede intentar).
function loginBloqueo(): int {
    return max(
        rlBloqueado('login-ip-' . rlIp(), LOGIN_MAX_IP, LOGIN_VENTANA),
        rlBloqueado('login-total', LOGIN_MAX_TOTAL, LOGIN_VENTANA)
    );
}

function loginFallo(): void {
    rlRegistrarFallo('login-ip-' . rlIp(), LOGIN_VENTANA);
    rlRegistrarFallo('login-total', LOGIN_VENTANA);
    usleep(400000); // frena la adivinanza en línea
}

// Al entrar bien: id de sesión nuevo (evita fijación de sesión) y se borra el conteo de fallos de esa IP.
function loginExito(): void {
    session_regenerate_id(true);
    $_SESSION['admin_logged_in'] = true;
    rlLimpiar('login-ip-' . rlIp());
}
