<?php
// Límite de intentos fallidos con archivos (el hosting compartido no tiene Redis ni base dedicada a esto).
// Cada clave (por ejemplo «login-ip-<hash>») guarda las marcas de tiempo de sus fallos dentro de la ventana.
// Si el disco no es escribible, no se bloquea a nadie (falla abierto) y se deja una línea en el log.

function rlArchivo(string $clave): ?string {
    $dir = __DIR__ . '/tmp/ratelimit';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
        @file_put_contents($dir . '/.htaccess', "Require all denied\nDeny from all\n");
    }
    if (!is_dir($dir) || !is_writable($dir)) { error_log('ratelimit: tmp/ratelimit no es escribible'); return null; }
    return $dir . '/' . hash('sha256', $clave) . '.json';
}

// Ejecuta $fn(array $marcas): array con el archivo bloqueado; devuelve lo que $fn devuelva en la posición 1.
function rlTransaccion(string $clave, int $ventana, callable $fn) {
    $f = rlArchivo($clave);
    if (!$f) return null;
    $h = @fopen($f, 'c+');
    if (!$h) return null;
    flock($h, LOCK_EX);
    $marcas = json_decode((string)stream_get_contents($h), true);
    $limite = time() - $ventana;
    $marcas = array_values(array_filter(is_array($marcas) ? $marcas : [], fn($t) => is_int($t) && $t > $limite));
    [$marcas, $resultado] = $fn($marcas);
    ftruncate($h, 0); rewind($h); fwrite($h, json_encode($marcas));
    flock($h, LOCK_UN); fclose($h);
    return $resultado;
}

// Segundos que faltan para poder reintentar (0 = no está bloqueado).
function rlBloqueado(string $clave, int $max, int $ventana): int {
    return (int)(rlTransaccion($clave, $ventana, function (array $m) use ($max, $ventana) {
        if (count($m) < $max) return [$m, 0];
        sort($m);
        return [$m, max(1, $m[count($m) - $max] + $ventana - time())];
    }) ?? 0);
}

function rlRegistrarFallo(string $clave, int $ventana): void {
    rlTransaccion($clave, $ventana, function (array $m) { $m[] = time(); return [$m, null]; });
}

function rlLimpiar(string $clave): void {
    $f = rlArchivo($clave);
    if ($f && is_file($f)) @unlink($f);
}

function rlIp(): string {
    return $_SERVER['REMOTE_ADDR'] ?? 'desconocida';
}

function rlResponder429(int $segundos): void {
    http_response_code(429);
    header('Retry-After: ' . $segundos);
}
