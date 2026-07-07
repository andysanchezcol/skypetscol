<?php
require_once __DIR__ . '/auth.php';
requireLogin();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/google_api.php';
require_once __DIR__ . '/helpers.php';

$rowNum = (int)($_GET['row'] ?? $_POST['row'] ?? 0);
if ($rowNum < 2) { header('Location: dashboard.php'); exit; }

$rows = getSheetRows();
$row  = $rows[$rowNum - 2] ?? null;
if (!$row) { header('Location: dashboard.php'); exit; }

$mascota = col($row, 'nombre_mascota');
$tutor   = col($row, 'nombre_tutor');
$correo  = trim(col($row, 'correo'));
$celular = trim(col($row, 'celular'));

function waLink(string $phone, string $text): ?string {
    $digits = preg_replace('/\D/', '', $phone);
    if (!$digits) return null;
    if (strlen($digits) === 10) $digits = '57' . $digits; // celular colombiano sin indicativo
    return 'https://wa.me/' . $digits . '?text=' . rawurlencode($text);
}

$prevStmt = getDB()->prepare('SELECT portal_uploaded_at FROM certificados WHERE sheet_row = ?');
$prevStmt->execute([$rowNum]);
$portalUploadedAt = $prevStmt->fetchColumn() ?: null;

$tiposDoc = [
    'certificado_salud'       => 'Certificado de Salud',
    'certificado_cdc'         => 'CDC (Estados Unidos)',
    'anexo_europa'            => 'Anexo Europa',
    'anexo_latinoamerica'     => 'Anexo Latinoamérica',
];

function uuidv4(): string {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function supabaseRequest(string $method, string $path, ?array $jsonBody = null, ?array $rawBody = null): array {
    $ch = curl_init(SUPABASE_URL . $path);
    $headers = [
        'apikey: ' . SUPABASE_SERVICE_ROLE_KEY,
        'Authorization: Bearer ' . SUPABASE_SERVICE_ROLE_KEY,
    ];
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    if ($jsonBody !== null) {
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Prefer: return=representation';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($jsonBody));
    } elseif ($rawBody !== null) {
        $headers[] = 'Content-Type: ' . $rawBody['content_type'];
        curl_setopt($ch, CURLOPT_POSTFIELDS, $rawBody['data']);
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['status' => $status, 'body' => $response];
}

$msg = '';
$msgTipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'] ?? '';

    if (!$correo) {
        $msg = 'El registro de este cliente no tiene correo. No se puede subir el documento.';
        $msgTipo = 'error';
    } elseif (!isset($tiposDoc[$tipo])) {
        $msg = 'Selecciona un tipo de documento válido.';
        $msgTipo = 'error';
    } elseif (empty($_FILES['documento']['tmp_name'][0]) || $_FILES['documento']['error'][0] !== UPLOAD_ERR_OK) {
        $msg = 'Selecciona al menos un archivo para subir.';
        $msgTipo = 'error';
    } else {
        $find = supabaseRequest('GET', '/rest/v1/profiles?select=id&email=eq.' . rawurlencode($correo));
        $profiles = json_decode($find['body'], true) ?: [];

        if ($find['status'] !== 200 || empty($profiles)) {
            $msg = 'No se puede subir: este cliente no existe en el portal (' . htmlspecialchars($correo) . '). Debe estar registrado en Arca primero.';
            $msgTipo = 'error';
        } else {
            $ownerId = $profiles[0]['id'];
            $names    = $_FILES['documento']['name'];
            $tmpNames = $_FILES['documento']['tmp_name'];
            $count    = count(array_filter($tmpNames));
            $docId    = uuidv4();

            if ($count === 1) {
                $ext      = strtolower(pathinfo($names[0], PATHINFO_EXTENSION));
                $filePath = "$ownerId/$docId.$ext";
                $fileData = file_get_contents($tmpNames[0]);
                $contentType = $_FILES['documento']['type'][0] ?: 'application/octet-stream';
            } else {
                // Varios archivos del mismo documento (ej. carnet + adiestramiento + certificado médico):
                // se agrupan en un solo ZIP para que el cliente vea un único documento en su portal.
                $zipPath = tempnam(sys_get_temp_dir(), 'skypets_zip_');
                $zip = new ZipArchive();
                $zip->open($zipPath, ZipArchive::OVERWRITE);
                foreach ($tmpNames as $i => $tmp) {
                    if (!$tmp) continue;
                    $zip->addFile($tmp, $names[$i]);
                }
                $zip->close();
                $filePath = "$ownerId/$docId.zip";
                $fileData = file_get_contents($zipPath);
                $contentType = 'application/zip';
                unlink($zipPath);
            }

            $upload = supabaseRequest(
                'POST',
                '/storage/v1/object/skypets-docs/' . $filePath,
                null,
                ['content_type' => $contentType, 'data' => $fileData]
            );

            if ($upload['status'] >= 300) {
                $msg = 'Error al subir el archivo al portal: ' . $upload['body'];
                $msgTipo = 'error';
            } else {
                $nombreDoc = $tiposDoc[$tipo] . ' - ' . $mascota;
                $insert = supabaseRequest('POST', '/rest/v1/documents', [
                    'id'         => $docId,
                    'owner_id'   => $ownerId,
                    'type'       => $tipo,
                    'name'       => $nombreDoc,
                    'file_path'  => $filePath,
                    'status'     => 'vigente',
                ]);

                if ($insert['status'] >= 300) {
                    supabaseRequest('DELETE', '/storage/v1/object/skypets-docs/' . $filePath);
                    $msg = 'Error al registrar el documento: ' . $insert['body'];
                    $msgTipo = 'error';
                } else {
                    getDB()->prepare("UPDATE certificados SET portal_uploaded_at = NOW() WHERE sheet_row = ?")
                        ->execute([$rowNum]);
                    $msg = '✅ Documento subido al portal de ' . htmlspecialchars($tutor) . '. Se le notificará por correo automáticamente.';
                    $msgTipo = 'ok';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Subir al portal — <?= htmlspecialchars($mascota) ?></title>
<link rel="icon" type="image/png" href="/assets/images/logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<nav class="topbar">
    <img src="assets/images/logo.png" alt="SkyPets" height="36">
    <span class="topbar-title"><a href="certificado.php?row=<?= $rowNum ?>">← Volver</a></span>
    <a href="logout.php" class="btn-logout">Salir</a>
</nav>

<div class="container" style="max-width:560px;">
    <h2>Subir documento al portal</h2>
    <p><strong><?= htmlspecialchars($mascota) ?></strong> — Tutor: <?= htmlspecialchars($tutor) ?></p>
    <p>✉️ <?= $correo ? htmlspecialchars($correo) : '<em>sin correo registrado</em>' ?></p>

    <?php if ($portalUploadedAt): ?>
        <div class="alert alert-ok">✅ Ya se subió un documento al portal el <?= date('d/m/Y H:i', strtotime($portalUploadedAt)) ?>. Puedes subir otro si necesitas reemplazarlo (por ejemplo, tras una corrección).</div>
    <?php endif; ?>

    <?php if ($msg): ?>
        <div class="alert alert-<?= $msgTipo ?>"><?= $msg ?></div>
    <?php endif; ?>

    <?php if ($msgTipo === 'ok'):
        $tipoSubido = $tiposDoc[$_POST['tipo']] ?? 'documento';
        $waMsg = "Hola $tutor, tu $tipoSubido de $mascota ya está disponible en tu portal de SkyPets 🐾. A veces el correo llega a spam, revisa ahí también. Puedes verlo aquí: https://skypetscol.com/portal";
        $wa = waLink($celular, $waMsg);
    ?>
        <?php if ($wa): ?>
            <a href="<?= $wa ?>" target="_blank" class="btn-generate" style="background:#25D366;display:inline-block;margin-top:10px;">📲 Enviar aviso por WhatsApp</a>
        <?php else: ?>
            <p style="font-size:0.82rem;color:#6B5540;margin-top:10px;">Este cliente no tiene celular registrado en el Sheet — no se puede enviar por WhatsApp.</p>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($msgTipo !== 'ok'): ?>
    <form method="POST" enctype="multipart/form-data" class="form-medico">
        <input type="hidden" name="row" value="<?= $rowNum ?>">
        <div class="form-group">
            <label>Tipo de documento</label>
            <select name="tipo" required>
                <option value="">Selecciona…</option>
                <?php foreach ($tiposDoc as $val => $label): ?>
                    <option value="<?= $val ?>"><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Archivo(s) (PDF o DOCX final, ya corregido)</label>
            <input type="file" name="documento[]" accept=".pdf,.docx,.jpg,.jpeg,.png" multiple required>
            <p style="font-size:0.78rem;color:#6B5540;margin-top:6px;">Si son varios archivos del mismo documento (ej. carnet + adiestramiento + certificado médico), selecciónalos todos juntos: se agrupan en un solo ZIP en el portal del cliente.</p>
        </div>
        <button type="submit" class="btn-generate">Subir al portal</button>
    </form>
    <?php endif; ?>
</div>
</body>
</html>
