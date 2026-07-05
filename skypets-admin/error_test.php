<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/google_api.php';
require_once __DIR__ . '/helpers.php';

$rows = getSheetRows();
echo "Filas: " . count($rows) . "<br>";

$db = getDB();
echo "DB OK<br>";
?>
