<?php
// Sirve imágenes de producto redimensionadas (WebP) con caché en disco.
// Uso: /img.php?w=600&src=/assets/images/productos/archivo.png

$base    = realpath(__DIR__ . '/assets/images/productos');
$widths  = [96, 400, 640, 900];
$w       = (int)($_GET['w'] ?? 640);
$src     = (string)($_GET['src'] ?? '');
$name    = basename($src);

if (!in_array($w, $widths, true)) $w = 640;

function fallback($src) {
    header('Location: ' . $src, true, 302);
    exit;
}

if (!$base || !preg_match('/^[A-Za-z0-9._-]+\.(png|jpe?g|webp)$/i', $name)) {
    http_response_code(400);
    exit;
}

$orig = $base . '/' . $name;
if (!is_file($orig)) { http_response_code(404); exit; }

$cacheDir  = $base . '/cache';
$cacheFile = $cacheDir . '/' . $w . '-' . pathinfo($name, PATHINFO_FILENAME) . '.webp';
$origUrl   = '/assets/images/productos/' . $name;

if (!is_file($cacheFile) || filemtime($cacheFile) < filemtime($orig)) {
    if (!function_exists('imagewebp') || !function_exists('imagecreatefrompng')) fallback($origUrl);
    if (!is_dir($cacheDir) && !@mkdir($cacheDir, 0755, true)) fallback($origUrl);

    @ini_set('memory_limit', '512M');
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $im  = $ext === 'png' ? @imagecreatefrompng($orig)
         : ($ext === 'webp' ? @imagecreatefromwebp($orig) : @imagecreatefromjpeg($orig));
    if (!$im) fallback($origUrl);

    $ow = imagesx($im); $oh = imagesy($im);
    if ($ow > $w) {
        $nh  = (int)round($oh * $w / $ow);
        $out = imagecreatetruecolor($w, $nh);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));
        imagecopyresampled($out, $im, 0, 0, 0, 0, $w, $nh, $ow, $oh);
        imagedestroy($im);
        $im = $out;
    } else {
        imagealphablending($im, false);
        imagesavealpha($im, true);
    }
    $tmp = $cacheFile . '.' . getmypid() . '.tmp';
    $ok  = imagewebp($im, $tmp, 80);
    imagedestroy($im);
    if (!$ok) { @unlink($tmp); fallback($origUrl); }
    rename($tmp, $cacheFile);
}

header('Content-Type: image/webp');
header('Cache-Control: public, max-age=31536000, immutable');
header('Content-Length: ' . filesize($cacheFile));
readfile($cacheFile);
