<?php
require_once __DIR__ . '/config.php';
// Clave y atajo del formulario de Konexa (archivo propio del servidor, fuera de git). Si no existe, el admin sigue leyendo solo la hoja de Google.
if (file_exists(__DIR__ . '/konexa_config.php')) require_once __DIR__ . '/konexa_config.php';

function base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Petición cURL a una API de Google con las defensas aprendidas en el hosting
 * compartido de HostGator: fuerza IPv4 (la ruta IPv6 del servidor cuelga hasta
 * agotar el timeout — "0 de 0 bytes recibidos"), acota los tiempos de espera y
 * reintenta una vez si la conexión no llega a establecerse.
 * Devuelve [cuerpo, códigoHTTP]. Lanza RuntimeException si la conexión sigue
 * fallando tras el reintento.
 */
function googleRequest(string $url, array $extraOpts = []): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, $extraOpts + [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 25,
    ]);
    $body = curl_exec($ch);
    if ($body === false) {
        $body = curl_exec($ch); // reintento único ante fallo de conexión
    }
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false) {
        throw new RuntimeException("Google API: fallo de conexión a $url — $err");
    }
    return [$body, $code];
}

function getAccessToken(): string {
    static $token = null;
    static $expires = 0;

    if ($token && time() < $expires - 60) return $token;

    $creds = json_decode(file_get_contents(CREDENTIALS_FILE), true);

    $now    = time();
    $header  = base64url_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $payload = base64url_encode(json_encode([
        'iss'   => $creds['client_email'],
        'scope' => 'https://www.googleapis.com/auth/spreadsheets.readonly https://www.googleapis.com/auth/drive.readonly',
        'aud'   => 'https://oauth2.googleapis.com/token',
        'exp'   => $now + 3600,
        'iat'   => $now,
    ]));

    $sig_input = "$header.$payload";
    openssl_sign($sig_input, $signature, $creds['private_key'], OPENSSL_ALGO_SHA256);
    $jwt = "$sig_input." . base64url_encode($signature);

    [$raw, $code] = googleRequest('https://oauth2.googleapis.com/token', [
        CURLOPT_POST       => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    ]);

    $resp = json_decode($raw, true);
    if (!isset($resp['access_token'])) {
        throw new RuntimeException('Google OAuth: respuesta sin access_token — ' . ($resp['error_description'] ?? $raw));
    }

    $token   = $resp['access_token'];
    $expires = $now + ($resp['expires_in'] ?? 3600);
    return $token;
}

// PAUSA (orden de Andrés, 2026-11-11): con true NO se consulta el formulario de Google. Se muestra la última copia
// guardada de la hoja (los certificados ya registrados siguen visibles) y no entra ninguna respuesta nueva.
// Para reanudar: poner false y publicar.
const FORMULARIO_GOOGLE_PAUSADO = false;

function getSheetRowsGoogle(): array {
    static $cache = null;
    static $cacheTime = 0;
    $cacheFile = __DIR__ . '/tmp/sheet_rows_cache.json';

    if (FORMULARIO_GOOGLE_PAUSADO) {
        $congelada = file_exists($cacheFile) ? json_decode((string)file_get_contents($cacheFile), true) : null;
        return is_array($congelada) ? $congelada : [];
    }

    if ($cache !== null && time() < $cacheTime + 60) {
        return $cache;
    }

    try {
        $token = getAccessToken();
        $range = urlencode("'Form Responses 1'!A:AI");
        $url   = 'https://sheets.googleapis.com/v4/spreadsheets/' . SHEET_ID . "/values/$range";

        [$raw, $code] = googleRequest($url, [
            CURLOPT_HTTPHEADER => ["Authorization: Bearer $token"],
        ]);
        $resp = json_decode($raw, true);
        if (!isset($resp['values'])) {
            throw new RuntimeException('Google Sheets: respuesta sin values — ' . ($resp['error']['message'] ?? $raw));
        }

        $cache     = $resp['values'];
        $cacheTime = time();
        @file_put_contents($cacheFile, json_encode($cache));
        return $cache;
    } catch (Throwable $e) {
        // Google no respondió: usar el último cache en disco en vez de tumbar la página
        if (file_exists($cacheFile)) {
            $stale = json_decode(file_get_contents($cacheFile), true);
            if (is_array($stale)) return $stale;
        }
        throw $e;
    }
}

/**
 * Filas de la hoja de Google (clave = fila - 2) MÁS las respuestas del formulario de Konexa (clave = 100000 + id, o sea fila 100002 + id).
 * Con claves distintas, los números de fila de unas y otras no se mezclan aunque la hoja crezca; el dashboard y los generadores buscan por clave.
 * Si Konexa no está configurado o falla la base, se devuelve solo lo de la hoja: el admin nunca se cae por esto.
 */
function getSheetRows(): array {
    $rows = getSheetRowsGoogle();
    if (defined('KONEXA_SECRET')) {
        try { $rows = $rows + konexaRows(); } catch (Throwable $e) { error_log('konexaRows: ' . $e->getMessage()); }
    }
    return $rows;
}

const KONEXA_FILA_BASE = 100000;
// Orden de las 35 columnas A:AI de la hoja del formulario de salud (las posiciones que usa el mapa COL de config.php).
const KONEXA_ENCABEZADOS = [
    'Timestamp', 'Correo', 'TIPO DE CERTIFICADO', 'FECHA DE VIAJE', 'CIUDAD DE ORIGEN', 'CIUDAD DE DESTINO', 'CIUDAD DE DESTINO',
    'TIENES ALGUNA ESCALA ? MENCIONALA', 'AEROLINEA', 'NOMBRE DEL TUTOR', 'TIPO DE DOCUMENTO DE IDENTIDAD', 'CORREO ELECTRONICO', 'CELULAR',
    'DIRECCION DE RESIDENCIA', 'DIRECCION DE DESTINO', 'CODIGO POSTAL', 'NOMBRE PELUDITO', 'FOTO DEL PELUDITO', 'FECHA DE NACIMIENTO', 'ESPECIE',
    'NUMERO MICROCHIP (15 DIGITOS)', 'RAZA', 'SEXO', 'PESO', 'EDAD DE TU PELUDITO', 'COLOR', 'FOTO CARNET DE VACUNAS', 'DESPARASITANTE INTERNO',
    'FOTO DESPARASITANTE INTERNO', 'NOMBRE DESPARASITANTE EXTERNO', 'FOTO DESPARASITENTE EXTERNO', 'RESULTADO DE SEROLOGIA',
    'NOMBRE DEL ASESOR QUE LLEVA TU CASO', 'CONFIRMA TU FECHA DE VIAJE', 'NUMERO DE DOCUMERO',
];
const KONEXA_TIPO_DOC = [
    'cédula de ciudadanía' => 'CEDULA', 'cédula de extranjería' => 'CEDULA DE EXTRANJERIA', 'pasaporte' => 'PASAPORTE',
    'tarjeta de identidad' => 'TARJETA DE IDENTIDAD', 'permiso por protección temporal (ppt)' => 'PPT', 'nit' => 'NIT',
];

function konexaRows(): array {
    $out = [];
    foreach (getDB()->query('SELECT id, payload FROM konexa_envios ORDER BY id') as $r) {
        $p = json_decode($r['payload'], true);
        if (is_array($p)) $out[KONEXA_FILA_BASE + (int)$r['id']] = konexaFila($p);
    }
    return $out;
}

/** Convierte lo que manda Konexa en una fila con el MISMO orden de columnas que la hoja, para que col()/COL sigan funcionando sin cambios. */
function konexaFila(array $p): array {
    $resp = is_array($p['respuestas'] ?? null) ? $p['respuestas'] : [];
    $urls = [];
    foreach (($p['archivos'] ?? []) as $a) {
        $urls[trim($a['columna'] ?? '')][] = $a['url'] ?? '';
    }
    $bogota = new DateTimeZone('America/Bogota');
    $cuando = new DateTime($p['completado_at'] ?? 'now', new DateTimeZone('UTC'));
    $cuando->setTimezone($bogota);
    $fechaViaje = trim($resp['FECHA DE VIAJE'] ?? '');
    $vistos = [];
    $fila = [];
    foreach (KONEXA_ENCABEZADOS as $i => $h) {
        $dup = isset($vistos[$h]); $vistos[$h] = true;
        $v = '';
        if ($h === 'Timestamp')                                $v = $cuando->format('n/j/Y G:i:s');
        elseif ($h === 'Correo')                               $v = $resp['CORREO ELECTRONICO'] ?? '';
        elseif ($dup)                                          $v = ''; // «CIUDAD DE DESTINO» aparece dos veces en la hoja
        elseif ($h === 'NOMBRE DEL ASESOR QUE LLEVA TU CASO')  $v = mb_strtoupper(explode(' ', trim($p['asesor'] ?? ''))[0], 'UTF-8');
        elseif ($h === 'CONFIRMA TU FECHA DE VIAJE')           $v = $fechaViaje !== '' && strtotime($fechaViaje) ? date('j-m-Y', strtotime($fechaViaje)) : $fechaViaje;
        elseif (isset($urls[$h]))                              $v = implode(', ', $urls[$h]); // fotos: una o varias direcciones de Konexa
        elseif ($h === 'TIPO DE DOCUMENTO DE IDENTIDAD')       { $t = trim($resp[$h] ?? ''); $v = KONEXA_TIPO_DOC[mb_strtolower($t, 'UTF-8')] ?? mb_strtoupper($t, 'UTF-8'); }
        elseif ($h === 'ESPECIE' || $h === 'SEXO')             $v = mb_strtoupper($resp[$h] ?? '', 'UTF-8');
        else                                                   $v = $resp[$h] ?? '';
        $fila[$i] = (string)$v;
    }
    return $fila;
}

/** Descarga una foto de Konexa con la clave del destino. Devuelve [cuerpo, códigoHTTP, tipo]. */
function konexaDescargar(string $uuid): array {
    $ch = curl_init('https://api.eclipseagency.com.co/api/integracion/archivo/' . rawurlencode($uuid));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 30, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . KONEXA_SECRET],
    ]);
    $body = curl_exec($ch);
    if ($body === false) $body = curl_exec($ch); // un reintento si la conexión no se establece
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $tipo = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    return [$body === false ? '' : $body, $code, $tipo];
}

function driveFileId(string $url): string {
    // Foto guardada por Konexa: se identifica como «konexa-<uuid>» y se descarga de Konexa, no de Drive.
    if (preg_match('#/api/integracion/archivo/([0-9a-f-]{36})#i', $url, $m)) return 'konexa-' . strtolower($m[1]);
    if (preg_match('/[?&]id=([^&]+)/', $url, $m)) return $m[1];
    if (preg_match('/\/d\/([^\/]+)/', $url, $m)) return $m[1];
    return '';
}

function downloadDriveImage(string $fileId): ?string {
    if (!$fileId) return null;

    if (str_starts_with($fileId, 'konexa-')) {
        $tmp = __DIR__ . '/tmp/' . $fileId . '.jpg';
        if (file_exists($tmp) && filemtime($tmp) > time() - 3600) return $tmp;
        try {
            [$data, $code] = konexaDescargar(substr($fileId, 7));
            if ($code === 200 && $data !== '') { file_put_contents($tmp, $data); return $tmp; }
        } catch (Throwable $e) { error_log('downloadDriveImage konexa: ' . $e->getMessage()); }
        return null;
    }

    $tmpFile = __DIR__ . '/tmp/' . $fileId . '.jpg';
    if (file_exists($tmpFile) && filemtime($tmpFile) > time() - 3600) {
        return $tmpFile;
    }

    // Contrato ?string: si Google falla, devolver null para que el documento se
    // genere sin foto en vez de romper toda la descarga con un error fatal.
    try {
        $token = getAccessToken();
        $url   = "https://www.googleapis.com/drive/v3/files/$fileId?alt=media";

        [$data, $code] = googleRequest($url, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER     => ["Authorization: Bearer $token"],
        ]);

        if ($code === 200 && $data !== '') {
            file_put_contents($tmpFile, $data);
            return $tmpFile;
        }
    } catch (Throwable $e) {
        error_log('downloadDriveImage: ' . $e->getMessage());
    }
    return null;
}
