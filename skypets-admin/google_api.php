<?php
require_once __DIR__ . '/config.php';

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

function getSheetRows(): array {
    static $cache = null;
    static $cacheTime = 0;
    if ($cache !== null && time() < $cacheTime + 60) {
        return $cache;
    }

    $cacheFile = __DIR__ . '/tmp/sheet_rows_cache.json';
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

function driveFileId(string $url): string {
    if (preg_match('/[?&]id=([^&]+)/', $url, $m)) return $m[1];
    if (preg_match('/\/d\/([^\/]+)/', $url, $m)) return $m[1];
    return '';
}

function downloadDriveImage(string $fileId): ?string {
    if (!$fileId) return null;

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
