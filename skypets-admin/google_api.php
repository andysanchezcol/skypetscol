<?php
require_once __DIR__ . '/config.php';

function base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
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

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $resp = json_decode(curl_exec($ch), true);
    curl_close($ch);

    $token   = $resp['access_token'];
    $expires = $now + ($resp['expires_in'] ?? 3600);
    return $token;
}

function getSheetRows(): array {
    $token = getAccessToken();
    $range = urlencode("'Form Responses 1'!A:AI");
    $url   = 'https://sheets.googleapis.com/v4/spreadsheets/' . SHEET_ID . "/values/$range";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer $token"],
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $resp = json_decode(curl_exec($ch), true);
    curl_close($ch);

    return $resp['values'] ?? [];
}

function driveFileId(string $url): string {
    if (preg_match('/[?&]id=([^&]+)/', $url, $m)) return $m[1];
    if (preg_match('/\/d\/([^\/]+)/', $url, $m)) return $m[1];
    return '';
}

function downloadDriveImage(string $fileId): ?string {
    if (!$fileId) return null;

    $token = getAccessToken();
    $url   = "https://www.googleapis.com/drive/v3/files/$fileId?alt=media";

    $tmpFile = __DIR__ . '/tmp/' . $fileId . '.jpg';

    if (file_exists($tmpFile) && filemtime($tmpFile) > time() - 3600) {
        return $tmpFile;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer $token"],
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $data = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 200 && $data) {
        file_put_contents($tmpFile, $data);
        return $tmpFile;
    }
    return null;
}
