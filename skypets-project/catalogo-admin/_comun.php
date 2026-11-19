<?php
// Funciones compartidas por save.php, upload.php y delete-image.php.
// Estas páginas se protegen con usuario y clave del navegador (Basic Auth, ver .htaccess). Como el navegador reenvía esas credenciales
// solas, otro sitio podría hacer que el navegador de quien administra dispare un POST: por eso se exige que la petición venga de este mismo sitio.

function responderJson(int $codigo, array $datos): void {
    http_response_code($codigo);
    header('Content-Type: application/json');
    echo json_encode($datos);
    exit;
}

function exigirMismoOrigen(): void {
    $host = strtolower(preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? '')));
    $origen = (string)($_SERVER['HTTP_ORIGIN'] ?? '');
    $referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
    $sfs = (string)($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '');
    if ($origen !== '' && $origen !== 'null') $ok = strtolower((string)parse_url($origen, PHP_URL_HOST)) === $host;
    elseif ($sfs !== '') $ok = in_array($sfs, ['same-origin', 'none'], true);
    elseif ($referer !== '') $ok = strtolower((string)parse_url($referer, PHP_URL_HOST)) === $host;
    else $ok = false;
    if (!$ok) responderJson(403, ['error' => 'Origen no permitido.']);
}

// Valida la lista de productos del catálogo (tipo, largo y forma de cada campo). Devuelve el mensaje de error o null si está bien.
function validarProductos($productos): ?string {
    $texto = function ($v, int $max, bool $obligatorio = true): bool {
        if (!is_string($v)) return false;
        if ($obligatorio && trim($v) === '') return false;
        return mb_strlen($v, 'UTF-8') <= $max && !preg_match('/[<>\x00-\x08\x0B\x0C\x0E-\x1F]/', $v);
    };
    $imagen = fn($v) => is_string($v) && preg_match('#^/assets/images/productos/[A-Za-z0-9._-]{1,150}$#', $v) === 1;
    if (!is_array($productos) || !array_is_list($productos) || count($productos) > 200) return 'Lista de productos inválida.';
    foreach ($productos as $i => $p) {
        $n = $i + 1;
        if (!is_array($p)) return "Producto $n inválido.";
        $extra = array_diff(array_keys($p), ['name', 'category', 'badge', 'badgeClass', 'price', 'desc', 'features', 'variants', 'images', 'wa']);
        if ($extra) return "Producto $n: campo no permitido.";
        if (!$texto($p['name'] ?? null, 120)) return "Producto $n: nombre inválido.";
        if (!$texto($p['desc'] ?? null, 3000)) return "Producto $n: descripción inválida.";
        if (!$texto($p['price'] ?? null, 60)) return "Producto $n: precio inválido.";
        if (!$texto($p['badge'] ?? null, 60)) return "Producto $n: etiqueta inválida.";
        if (!is_string($p['category'] ?? null) || !preg_match('/^[a-z0-9-]{1,30}$/', $p['category'])) return "Producto $n: categoría inválida.";
        if (!is_string($p['badgeClass'] ?? '') || !preg_match('/^[a-z0-9-]{0,30}$/', (string)($p['badgeClass'] ?? ''))) return "Producto $n: clase inválida.";
        if (!is_string($p['wa'] ?? null) || !preg_match('/^[\p{L}\p{N}%._~-]{1,500}$/u', $p['wa'])) return "Producto $n: texto de WhatsApp inválido.";
        $features = $p['features'] ?? [];
        if (!is_array($features) || !array_is_list($features) || count($features) > 30) return "Producto $n: características inválidas.";
        foreach ($features as $f) if (!$texto($f, 600)) return "Producto $n: característica inválida.";
        $images = $p['images'] ?? [];
        if (!is_array($images) || !array_is_list($images) || count($images) > 20) return "Producto $n: imágenes inválidas.";
        foreach ($images as $im) if (!$imagen($im)) return "Producto $n: ruta de imagen inválida.";
        $variants = $p['variants'] ?? [];
        if (!is_array($variants) || !array_is_list($variants) || count($variants) > 20) return "Producto $n: variantes inválidas.";
        foreach ($variants as $v) {
            if (!is_array($v) || array_diff(array_keys($v), ['label', 'images', 'color'])) return "Producto $n: variante inválida.";
            if (!$texto($v['label'] ?? null, 60)) return "Producto $n: nombre de variante inválido.";
            if (isset($v['color']) && !(is_string($v['color']) && preg_match('/^#[0-9a-fA-F]{6}$/', $v['color']))) return "Producto $n: color inválido.";
            $vi = $v['images'] ?? [];
            if (!is_array($vi) || !array_is_list($vi) || count($vi) > 20) return "Producto $n: imágenes de variante inválidas.";
            foreach ($vi as $im) if (!$imagen($im)) return "Producto $n: ruta de imagen de variante inválida.";
        }
    }
    return null;
}
