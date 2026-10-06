<?php
require_once __DIR__ . '/auth.php';
iniciarSesionAdmin();
session_destroy();
header('Location: index.php');
exit;
