<?php
require_once __DIR__ . '/config.php';

function requireLogin(): void {
    // Debe fijarse antes de session_start() en TODA página que abra sesión — si solo
    // se hace en index.php/keepalive.php, el resto de páginas (donde la doctora pasa
    // el tiempo real llenando el formulario) corre con el límite por defecto del
    // hosting (~24 min), y la sesión puede morir a media edición aunque el keepalive
    // esté activo.
    ini_set('session.gc_maxlifetime', 28800); // 8 horas
    ini_set('session.cookie_lifetime', 28800);
    session_start();
    if (empty($_SESSION['admin_logged_in'])) {
        header('Location: index.php');
        exit;
    }
}

function doLogin(string $user, string $pass): bool {
    return $user === ADMIN_USER && password_verify($pass, ADMIN_PASS);
}
