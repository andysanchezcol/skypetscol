<?php
require_once __DIR__ . '/config.php';

function requireLogin(): void {
    session_start();
    if (empty($_SESSION['admin_logged_in'])) {
        header('Location: index.php');
        exit;
    }
}

function doLogin(string $user, string $pass): bool {
    return $user === ADMIN_USER && password_verify($pass, ADMIN_PASS);
}
