<?php
// Renueva la sesión activa — llamado por JS cada 10 minutos
ini_set('session.gc_maxlifetime', 28800); // 8 horas
session_start();
header('Content-Type: application/json');
echo json_encode(['ok' => isset($_SESSION['admin_logged_in'])]);
