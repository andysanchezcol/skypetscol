<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/auth.php';
requireLogin();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/google_api.php';
require_once __DIR__ . '/helpers.php';

$rowNum = (int)($_GET['row'] ?? 0);
if ($rowNum < 2) { header('Location: dashboard.php'); exit; }

$rows = getSheetRows();
$row  = $rows[$rowNum - 2] ?? null;
if (!$row) { header('Location: dashboard.php'); exit; }

$db   = getDB();

// Cargar datos guardados de vacunas
$cert = $db->prepare('SELECT * FROM certificados WHERE sheet_row = ?');
$cert->execute([$rowNum]);
$saved = $cert->fetch() ?: [];

// Datos del sheet
$tipo    = col($row, 'tipo_cert');
$mascota = col($row, 'nombre_mascota');
$tutor   = col($row, 'nombre_tutor');
$especie = col($row, 'especie');
$esp     = especieCheck($especie);

// IDs de fotos en Drive
$fotoMascotaId  = driveFileId(col($row, 'foto_mascota'));
$fotoVacunasId  = driveFileId(col($row, 'foto_vacunas'));
$fotoDesIntId   = driveFileId(col($row, 'foto_desp_int'));
$fotoDesExtId   = driveFileId(col($row, 'foto_desp_ext'));
$serologiaId    = driveFileId(col($row, 'serologia'));

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    $fields = ['raza_corregida','fecha_emision',
               'vacuna1_medicamento','vacuna1_lote','vacuna1_aplicacion','vacuna1_proxima','vacuna1_uso',
               'vacuna2_medicamento','vacuna2_lote','vacuna2_aplicacion','vacuna2_proxima','vacuna2_uso',
               'vacuna3_medicamento','vacuna3_lote','vacuna3_aplicacion','vacuna3_proxima','vacuna3_uso',
               'vacuna4_medicamento','vacuna4_lote','vacuna4_aplicacion','vacuna4_proxima','vacuna4_uso',
               'vacuna5_medicamento','vacuna5_lote','vacuna5_aplicacion','vacuna5_proxima','vacuna5_uso',
               'desint_fecha','desint_producto','desint_principio_activo','desint_lote','desint_registro_ica',
               'desext_fecha','desext_producto','desext_principio_activo','desext_lote','desext_registro_ica'];

    $camposFecha = ['fecha_emision',
                    'vacuna1_aplicacion','vacuna1_proxima',
                    'vacuna2_aplicacion','vacuna2_proxima',
                    'vacuna3_aplicacion','vacuna3_proxima',
                    'vacuna4_aplicacion','vacuna4_proxima',
                    'vacuna5_aplicacion','vacuna5_proxima',
                    'desint_fecha','desext_fecha'];
    $data = [];
    foreach ($fields as $f) {
        $val = trim($_POST[$f] ?? '');
        $data[$f] = (in_array($f, $camposFecha) && $val === '') ? null : $val;
    }

    if ($saved) {
        $set = implode(', ', array_map(fn($f) => "$f = :$f", $fields));
        $s = $db->prepare("UPDATE certificados SET $set, status='en_proceso' WHERE sheet_row = :sheet_row");
    } else {
        $cols = implode(', ', $fields);
        $phs  = implode(', ', array_map(fn($f) => ":$f", $fields));
        $s = $db->prepare("INSERT INTO certificados (sheet_row, status, $cols) VALUES (:sheet_row, 'en_proceso', $phs)");
    }
    $data['sheet_row'] = $rowNum;
    $s->execute($data);

    $cert->execute([$rowNum]);
    $saved = $cert->fetch() ?: [];
    $msg   = 'Datos guardados correctamente.';
}

function v(array $saved, string $key): string {
    return htmlspecialchars($saved[$key] ?? '');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($mascota) ?> — SkyPets</title>
<link rel="icon" type="image/png" href="/assets/images/logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<style>
.btn-generate-docx { background: #FF7600; box-shadow: 0 4px 20px rgba(255,118,0,0.38); }
.btn-generate-docx:hover { background: #e06800; box-shadow: 0 8px 28px rgba(255,118,0,0.52); }
</style>
</head>
<body>
<nav class="topbar">
    <img src="assets/images/logo.png" alt="SkyPets" height="36">
    <span class="topbar-title"><a href="dashboard.php">← Volver</a></span>
    <a href="logout.php" class="btn-logout">Salir</a>
</nav>

<div class="container">

<?php if ($msg): ?>
<div class="alert alert-ok"><?= $msg ?></div>
<?php endif; ?>

<!-- ─── INFO RÁPIDA ──────────────────────────────────────────── -->
<div class="info-grid">
    <div class="info-block">
        <h3>Mascota</h3>
        <p><strong><?= htmlspecialchars($mascota) ?></strong></p>
        <p><?= htmlspecialchars(col($row,'especie')) ?> · <?= htmlspecialchars(col($row,'raza')) ?></p>
        <p><?= htmlspecialchars(col($row,'sexo')) ?> · <?= htmlspecialchars(col($row,'peso')) ?> · <?= htmlspecialchars(col($row,'edad')) ?></p>
        <p>Color: <?= htmlspecialchars(col($row,'color')) ?></p>
        <p>Chip: <?= htmlspecialchars(col($row,'microchip')) ?></p>
        <?php if ($fotoMascotaId): ?>
            <a href="<?= driveViewUrl($fotoMascotaId) ?>" target="_blank" class="btn-foto">📷 Ver foto mascota</a>
        <?php endif; ?>
    </div>
    <div class="info-block">
        <h3>Tutor</h3>
        <p><strong><?= htmlspecialchars($tutor) ?></strong></p>
        <p><?= htmlspecialchars(col($row,'tipo_doc')) ?>: <?= htmlspecialchars(col($row,'num_documento')) ?></p>
        <p>📱 <?= htmlspecialchars(col($row,'celular')) ?></p>
        <p>✉️ <?= htmlspecialchars(col($row,'correo')) ?></p>
        <p><?= htmlspecialchars(col($row,'dir_residencia')) ?></p>
    </div>
    <div class="info-block">
        <h3>Viaje</h3>
        <p><span class="tipo-badge <?= esInternacional($tipo) ? 'tipo-int' : 'tipo-nac' ?>"><?= htmlspecialchars(tipoLabel($tipo)) ?></span></p>
        <p><strong><?= htmlspecialchars(col($row,'ciudad_origen')) ?> → <?= htmlspecialchars(col($row,'ciudad_destino')) ?></strong></p>
        <p>Fecha: <?= htmlspecialchars(col($row,'fecha_viaje')) ?></p>
        <p>Aerolínea: <?= htmlspecialchars(col($row,'aerolinea')) ?></p>
        <p>Escala: <?= htmlspecialchars(col($row,'escala')) ?></p>
        <p>Asesor: <?= htmlspecialchars(col($row,'asesor')) ?></p>
    </div>
</div>

<!-- ─── FOTOS DE REFERENCIA ─────────────────────────────────── -->
<div class="fotos-ref">
    <h3>Fotos de referencia (para llenar datos abajo)</h3>
    <div class="fotos-grid">
        <?php $fotos = [
            ['id' => $fotoVacunasId, 'label' => '💉 Carnet de vacunas'],
            ['id' => $fotoDesIntId,  'label' => '🐛 Desparasitante interno'],
            ['id' => $fotoDesExtId,  'label' => '🐛 Desparasitante externo'],
            ['id' => $serologiaId,   'label' => '🧪 Serología'],
        ];
        foreach ($fotos as $f):
            if (!$f['id']) continue;
        ?>
            <a href="<?= driveViewUrl($f['id']) ?>" target="_blank" class="btn-foto-ref"><?= $f['label'] ?></a>
        <?php endforeach; ?>
    </div>
    <p class="hint">Abre cada foto, revísala y llena los datos médicos abajo.</p>
</div>

<!-- ─── FORMULARIO MÉDICO ────────────────────────────────────── -->
<div id="borradorAviso" style="display:none;background:#fff3cd;border:1px solid #ffe08a;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:0.9rem;">
    Encontramos cambios sin guardar de una sesión anterior en este formulario.
    <button type="button" onclick="restaurarBorrador()" class="btn-generate" style="margin-left:8px;padding:4px 12px;">Recuperar</button>
    <button type="button" onclick="descartarBorrador()" style="margin-left:4px;padding:4px 12px;border:1px solid #ccc;border-radius:6px;background:none;cursor:pointer;">Descartar</button>
</div>

<form method="POST" class="form-medico" id="formMedico">

    <div class="form-section">
        <h3>Raza (corrección)</h3>
        <div class="form-row">
            <div class="form-group" style="max-width:360px;">
                <label>Raza del formulario: <strong><?= htmlspecialchars(col($row,'raza')) ?></strong></label>
                <input type="text" name="raza_corregida" list="razas-lista"
                       placeholder="Corrígela aquí si está mal escrita"
                       value="<?= v($saved,'raza_corregida') ?>"
                       autocomplete="off">
                <datalist id="razas-lista">
                  <?php
                  $razas = [
                    // Caninas comunes
                    'Labrador Retriever','Golden Retriever','Pastor Alemán','Bulldog Francés','Bulldog Inglés',
                    'Beagle','Poodle','Poodle Toy','Poodle Miniatura','Poodle Estándar',
                    'Yorkshire Terrier','Chihuahua','Shih Tzu','Dachshund','Dachshund Miniatura',
                    'Boxer','Rottweiler','Doberman','Schnauzer','Schnauzer Miniatura','Schnauzer Gigante',
                    'Maltés','Bichón Frisé','Cocker Spaniel','Cocker Americano','Cocker Inglés',
                    'Husky Siberiano','Samoyedo','Akita Inu','Shiba Inu','Chow Chow',
                    'Border Collie','Australian Shepherd','Collie','Shetland Sheepdog',
                    'Great Dane','San Bernardo','Terranova','Mastín','Mastín Napolitano',
                    'Pitbull','American Pitbull Terrier','American Staffordshire Terrier',
                    'Bull Terrier','Jack Russell Terrier','West Highland White Terrier',
                    'Pomerania','Spitz Alemán','Bichón Habanero','Cavalier King Charles Spaniel',
                    'Basset Hound','Bloodhound','Dálmata','Weimaraner','Vizsla',
                    'Setter Irlandés','Pointer','Braco Alemán','Springer Spaniel',
                    'Whippet','Greyhound','Galgo','Borzoi',
                    'Pekinés','Lhasa Apso','Tibetan Spaniel','Shar Pei',
                    'Alaskan Malamute','Samoyed','Spitz Japonés',
                    'Cane Corso','Dogo Argentino','Fila Brasileño','Tosa',
                    'Pug','Boston Terrier','Carlino',
                    'Fox Terrier','Airedale Terrier','Scottish Terrier',
                    'Minipin','Pinscher Miniatura','Doberman Pinscher',
                    'Mestizo','Criollo',
                    // Felinas comunes
                    'Persa','Siamés','Maine Coon','Ragdoll','Bengal','Abisinio',
                    'Sphynx','British Shorthair','Scottish Fold','Russian Blue',
                    'Noruego del Bosque','Birman','Burmés','Tonkinés',
                    'Devon Rex','Cornish Rex','Angora Turco','Van Turco',
                    'Exótico de pelo corto','American Shorthair','Domestic Shorthair',
                    'Mestizo','Criollo',
                  ];
                  foreach ($razas as $r) echo "<option value=\"" . htmlspecialchars($r) . "\">";
                  ?>
                </datalist>
                <small style="color:#6B5540;">Si la raza está bien escrita, deja este campo vacío.</small>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h3>Fecha de emisión del certificado</h3>
        <div class="form-row">
            <div class="form-group">
                <label>Fecha de emisión (el certificado es válido 10 días desde esta fecha)</label>
                <input type="date" name="fecha_emision" value="<?= v($saved,'fecha_emision') ?>" required>
            </div>
        </div>
    </div>

    <?php
    $vacunas_lista = [
        'BRONCHICINE'               => ['TOS DE PERRERAS'],
        'CANIGEN MHA2PPI/L'         => ['PENTAVALENTE'],
        'CANINE DAPP 3YR LEPTO 1YR' => ['RABIA', 'LEPTOSPIRA'],
        'CANINE VAC-R'              => ['RABIA'],
        'DAPP'                      => ['PENTAVALENTE'],
        'DENSOR 1'                  => ['RABIA'],
        'FELIGEN C.R.P.'            => ['TRIPLE FELINA'],
        'FELIGEN CRP/R'             => ['RABIA', 'TRIPLE FELINA'],
        'INMUNOVAX 5'               => ['PENTAVALENTE'],
        'INMUNOVAX RABIA'           => ['RABIA'],
        'NOBIVAC DHPPI'             => ['PENTAVALENTE'],
        'NOBIVAC INTRA TAC'         => ['TOS DE PERRERAS'],
        'NOBIVAC KC'                => ['TOS DE PERRERAS'],
        'NOBIVAC RL'                => ['LEPTOSPIRA', 'RABIA'],
        'NOVICAC RABIA'             => ['RABIA'],
        'PROCYON DA2PPVL'           => ['PENTAVALENTE'],
        'PROCYON DOG PV'            => ['PARVO'],
        'PROVIDEAN 10CV-4L'         => ['PENTAVALENTE', 'CORONAVIRUS', 'LEPTOSPIRA'],
        'PROVIDEAN 10CV/4L'         => ['PENTAVALENTE', 'CORONAVIRUS', 'LEPTOSPIRA'],
        'PROVIDEAN 6 CV'            => ['PENTAVALENTE'],
        'PROVIDEAN 9 (L4)'          => ['PENTAVALENTE', 'LEPTOSPIRA'],
        'PUREVAX DHPPI'             => ['PENTAVALENTE'],
        'PUREVAX RCP'               => ['TRIPLE FELINA'],
        'RABISIN'                   => ['RABIA'],
        'RABCAN'                    => ['RABIA'],
        'RABIGEN MONO'              => ['TRIPLE FELINA'],
        'RABIES TYPER HILLED'       => ['RABIA'],
        'RABVACL'                   => ['RABIA'],
        'RECOMBITEK C3'             => ['PENTAVALENTE'],
        'RECOMBITEK C6/CV'          => ['PENTAVALENTE'],
        'RECOMBITEK LEPTO'          => ['LEPTOSPIRA'],
        'RECOMBITEK ORAL'           => ['TOS DE PERRERAS'],
        'RONVAC'                    => ['RABIA'],
        'VANGUARD L4 / CV'          => ['PENTAVALENTE', 'LEPTOSPIRA', 'CORONAVIRUS'],
        'VANGUARD PLUS 5'           => ['PENTAVALENTE'],
        'VANGUARD PLUS 5 L4'        => ['PENTAVALENTE'],
        'VENCOMAX 8'                => ['PENTAVALENTE'],
        'VIBIX C5'                  => ['PENTAVALENTE'],
        'VIBIX C6R'                 => ['PENTAVALENTE', 'LEPTOSPIRA', 'RABIA'],
        'VIBIX F3'                  => ['TRIPLE FELINA'],
        'VIBIX F3+R'                => ['RABIA', 'TRIPLE FELINA'],
        'VIBIX PUPPY'               => ['PARVO'],
    ];

    // Datos guardados para restaurar en JS
    $saved_vacs_js = [];
    for ($vi = 1; $vi <= 5; $vi++) {
        $saved_vacs_js[$vi] = [
            'med'   => $saved["vacuna{$vi}_medicamento"] ?? '',
            'uso'   => $saved["vacuna{$vi}_uso"] ?? '',
            'lote'  => $saved["vacuna{$vi}_lote"] ?? '',
            'aplic' => $saved["vacuna{$vi}_aplicacion"] ?? '',
            'prox'  => $saved["vacuna{$vi}_proxima"] ?? '',
        ];
    }
    $max_vac_visible = 1;
    for ($vi = 5; $vi >= 1; $vi--) {
        if (!empty($saved_vacs_js[$vi]['med'])) { $max_vac_visible = $vi; break; }
    }
    ?>
    <div class="form-section">
        <h3>💉 Vacunas</h3>

        <?php for ($vi = 1; $vi <= 5; $vi++): ?>
        <div class="vacuna-block" id="vb-<?= $vi ?>" <?= $vi > $max_vac_visible ? 'style="display:none"' : '' ?>>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                <h4 style="margin:0;">Vacuna <?= $vi ?></h4>
                <?php if ($vi > 1): ?>
                <button type="button" onclick="removeVacuna(<?= $vi ?>)"
                    style="background:none;border:1px solid #E5443A;color:#E5443A;border-radius:8px;padding:3px 10px;cursor:pointer;font-size:12px;">
                    ✕ Quitar
                </button>
                <?php endif; ?>
            </div>
            <div class="form-row">
                <div class="form-group" style="flex:2">
                    <label>Seleccionar vacuna</label>
                    <select id="vsel-<?= $vi ?>" onchange="fillVacuna(<?= $vi ?>, this.value)">
                        <option value="">— Selecciona un producto —</option>
                        <?php foreach ($vacunas_lista as $vnom => $vusos): ?>
                        <option value="<?= htmlspecialchars($vnom) ?>">
                            <?= htmlspecialchars($vnom) ?> — <?= htmlspecialchars(implode(', ', $vusos)) ?>
                        </option>
                        <?php endforeach; ?>
                        <option value="__otro__">Otro (ingresar manualmente)</option>
                    </select>
                </div>
                <div class="form-group" id="vusos-<?= $vi ?>" style="flex:2;align-self:flex-end;min-height:42px;display:flex;align-items:center;flex-wrap:wrap;gap:6px;">
                    <!-- usos terapéuticos auto-llenados aquí -->
                </div>
            </div>
            <div id="votro-<?= $vi ?>" class="form-row" style="display:none">
                <div class="form-group">
                    <label>Nombre del medicamento</label>
                    <input type="text" id="votro-med-<?= $vi ?>" placeholder="Ej: NOBIVAC DHPPI"
                           oninput="document.getElementById('vhid-med-<?= $vi ?>').value=this.value">
                </div>
                <div class="form-group">
                    <label>Uso terapéutico</label>
                    <input type="text" id="votro-uso-<?= $vi ?>" placeholder="Ej: PENTAVALENTE"
                           oninput="document.getElementById('vhid-uso-<?= $vi ?>').value=this.value">
                </div>
            </div>
            <input type="hidden" name="vacuna<?= $vi ?>_medicamento" id="vhid-med-<?= $vi ?>">
            <input type="hidden" name="vacuna<?= $vi ?>_uso"         id="vhid-uso-<?= $vi ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>Lote</label>
                    <input type="text" name="vacuna<?= $vi ?>_lote" id="vlote-<?= $vi ?>" placeholder="Ej: A842B01">
                </div>
                <div class="form-group">
                    <label>Fecha de aplicación</label>
                    <input type="date" name="vacuna<?= $vi ?>_aplicacion" id="vaplic-<?= $vi ?>"
                           oninput="autoProxima(<?= $vi ?>, this.value)">
                </div>
                <div class="form-group">
                    <label>Próxima dosis</label>
                    <input type="date" name="vacuna<?= $vi ?>_proxima" id="vprox-<?= $vi ?>">
                </div>
            </div>
        </div>
        <?php endfor; ?>

        <div style="margin-top:12px;">
            <button type="button" id="add-vac-btn" onclick="addVacuna()"
                style="background:rgba(0,141,131,0.1);border:1.5px solid #008D83;color:#008D83;border-radius:100px;padding:8px 20px;cursor:pointer;font-family:Poppins,sans-serif;font-size:14px;">
                + Agregar otra vacuna
            </button>
        </div>
    </div>

    <div class="form-section">
        <h3>🐛 Desparasitante Interno</h3>
        <div class="form-row">
            <div class="form-group">
                <label>Fecha de aplicación</label>
                <input type="date" name="desint_fecha" value="<?= v($saved,'desint_fecha') ?>">
            </div>
            <div class="form-group">
                <label>Seleccionar producto</label>
                <?php
                $desintProductos = [
                    'ADVOCATE'       => ['principio' => 'MOXIDECTINA 1%',                                              'ica' => 'REG ICA NO 7336 MV'],
                    'CANISAN D'      => ['principio' => 'FEBANTEL PAMOATO DE PIRANTEL PRAZICUENTEL',                   'ica' => 'Reg ICA No 5610 MV'],
                    'CANNEX'         => ['principio' => 'FENBENDAZOL TOLTRAZURIL PRAZIQUANTEL',                        'ica' => 'Reg ICA No 6803-MV'],
                    'CREDELIO PLUS'  => ['principio' => 'MIBEMICINA OXIMA',                                            'ica' => 'Reg ICA No 9871 MV'],
                    'DOGG & CAT'     => ['principio' => 'PAMOATO DE PIRANTEL Y PRAZIQUANTEL',                          'ica' => 'Reg ICA No 8722-MV'],
                    'DRONTAL'        => ['principio' => 'FEBANTEL, PAMOATO DE PIRANTEL PRAZICUANTEL',                  'ica' => 'Reg ICA No 6838 MV'],
                    'ENDOGARD'       => ['principio' => 'FEBANTEL PAMOATO DE PIRANTEL PRAZIQUANTEL IVERMECTINA',       'ica' => 'Reg ICA No 6510 MV'],
                    'GALGOCAL'       => ['principio' => 'FEBANTEL PAMOATO DE PIRANTEL PRAZICUENTEL',                   'ica' => 'Reg ICA No 5610 MV'],
                    'GALGOCAL 600'   => ['principio' => 'ALBENDAZOL PRAZICUANTEL',                                     'ica' => 'Reg ICA No 4853 DB'],
                    'NEXGARD COMBO'  => ['principio' => 'EPRINOMECTINA PRAZICUANTEL',                                  'ica' => 'Reg ICA No 11095 MV'],
                    'NEXGARD SPECTRA'=> ['principio' => 'MILBEMICIN A OXIMA',                                          'ica' => 'Reg ICA No 9759-MV'],
                    'PARACANIS'      => ['principio' => 'EMBONATO PIRANTEL PRAZICUENTEL',                              'ica' => 'Reg ICA No 4265-DB MV'],
                    'RONDEL PUPPY'   => ['principio' => 'FEBANTEL PAMOATO DE PIRANTEL PRAZICUENTEL',                   'ica' => 'Reg ICA No 5610 MV'],
                    'SIMPARICA TRIO' => ['principio' => 'MOXIDECTINA SAROLANER',                                       'ica' => 'Reg ICA No 10890 MV'],
                    'TOTAL F LC'     => ['principio' => 'FENBENDAZOL PIRANTEL PRAZICUANTEL',                           'ica' => 'Reg ICA No 8365 MV'],
                ];
                $savedDesintProd = v($saved,'desint_producto');
                $isDesintOtro = $savedDesintProd !== '' && !array_key_exists($savedDesintProd, $desintProductos);
                $desintSelectVal = $isDesintOtro ? '__otro__' : $savedDesintProd;
                ?>
                <select id="desint_selector" onchange="fillDespar('int', this.value)">
                    <option value="">— Selecciona un producto —</option>
                    <?php foreach ($desintProductos as $nombre => $datos): ?>
                    <option value="<?= htmlspecialchars($nombre) ?>" <?= $desintSelectVal === $nombre ? 'selected' : '' ?>><?= htmlspecialchars($nombre) ?></option>
                    <?php endforeach; ?>
                    <option value="__otro__" <?= $isDesintOtro ? 'selected' : '' ?>>Otro (ingresar manualmente)</option>
                </select>
            </div>
        </div>
        <div id="desint_otro_wrap" class="form-row" style="<?= $isDesintOtro ? '' : 'display:none' ?>">
            <div class="form-group">
                <label>Nombre del producto</label>
                <input type="text" id="desint_otro_nombre" placeholder="Nombre del producto" value="<?= $isDesintOtro ? $savedDesintProd : '' ?>" oninput="document.getElementById('desint_producto_hidden').value=this.value">
            </div>
        </div>
        <input type="hidden" name="desint_producto" id="desint_producto_hidden" value="<?= $savedDesintProd ?>">
        <div class="form-row">
            <div class="form-group">
                <label>Principio activo</label>
                <input type="text" name="desint_principio_activo" id="desint_principio_activo"
                       placeholder="Ej: MILBEMICIN A OXIMA"
                       value="<?= v($saved,'desint_principio_activo') ?>"
                       <?= (!$isDesintOtro && $desintSelectVal !== '') ? 'readonly style="background:#f5f5f5;color:#888;"' : '' ?>>
            </div>
            <div class="form-group">
                <label>Lote</label>
                <input type="text" name="desint_lote" placeholder="Ej: AAA1151/25" value="<?= v($saved,'desint_lote') ?>">
            </div>
            <div class="form-group">
                <label>Registro ICA</label>
                <input type="text" name="desint_registro_ica" id="desint_registro_ica"
                       placeholder="Ej: 9759-MV"
                       value="<?= v($saved,'desint_registro_ica') ?>"
                       <?= (!$isDesintOtro && $desintSelectVal !== '') ? 'readonly style="background:#f5f5f5;color:#888;"' : '' ?>>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h3>🐛 Desparasitante Externo</h3>
        <div class="form-row">
            <div class="form-group">
                <label>Fecha de aplicación</label>
                <input type="date" name="desext_fecha" value="<?= v($saved,'desext_fecha') ?>">
            </div>
            <div class="form-group">
                <label>Seleccionar producto</label>
                <?php
                $desextProductos = [
                    'ADVANTIX'       => ['principio' => 'IMIDACLOPRID Y PERMETRINA',  'ica' => 'Reg ICA No 10888 MV'],
                    'ADVOCATE'       => ['principio' => 'IMIDACLOPRIDA 10%',           'ica' => 'REG ICA NO 7336 MV'],
                    'BRAVECTO'       => ['principio' => 'FLURALANER',                  'ica' => 'Reg ICA 9759 MV'],
                    'CREDELIO'       => ['principio' => 'LOTILANER',                   'ica' => 'Reg ICA No 9561 MV'],
                    'CREDELIO PLUS'  => ['principio' => 'LOTILANER',                   'ica' => 'Reg ICA No 9871 MV'],
                    'FIPROTECTION'   => ['principio' => 'FIPRONIL',                    'ica' => 'REG ICA NO 8794 MV'],
                    'NEXGARD'        => ['principio' => 'AFOXOLANER',                  'ica' => 'Reg ICA No 10888 MV'],
                    'NEXGARD COMBO'  => ['principio' => 'ESAFOXOLANE',                 'ica' => 'Reg ICA No 11095 MV'],
                    'NEXGARD SPECTRA'=> ['principio' => 'MILBEMICIN A OXIMA',          'ica' => 'Reg ICA No 9759-MV'],
                    'PULFENNDOS'     => ['principio' => 'FIPRONIL',                    'ica' => 'Reg ICA NO 10504 MV'],
                    'REVOLUTION 6%'  => ['principio' => 'SELAMECTINA',                 'ica' => 'Reg ICA No 10887 MV'],
                    'SIMPARICA TRIO' => ['principio' => 'MOXIDECTINA SAROLANER',       'ica' => 'Reg ICA No 10890 MV'],
                ];
                $savedDesextProd = v($saved,'desext_producto');
                $isDesextOtro = $savedDesextProd !== '' && !array_key_exists($savedDesextProd, $desextProductos);
                $desextSelectVal = $isDesextOtro ? '__otro__' : $savedDesextProd;
                ?>
                <select id="desext_selector" onchange="fillDespar('ext', this.value)">
                    <option value="">— Selecciona un producto —</option>
                    <?php foreach ($desextProductos as $nombre => $datos): ?>
                    <option value="<?= htmlspecialchars($nombre) ?>" <?= $desextSelectVal === $nombre ? 'selected' : '' ?>><?= htmlspecialchars($nombre) ?></option>
                    <?php endforeach; ?>
                    <option value="__otro__" <?= $isDesextOtro ? 'selected' : '' ?>>Otro (ingresar manualmente)</option>
                </select>
            </div>
        </div>
        <div id="desext_otro_wrap" class="form-row" style="<?= $isDesextOtro ? '' : 'display:none' ?>">
            <div class="form-group">
                <label>Nombre del producto</label>
                <input type="text" id="desext_otro_nombre" placeholder="Nombre del producto" value="<?= $isDesextOtro ? $savedDesextProd : '' ?>" oninput="document.getElementById('desext_producto_hidden').value=this.value">
            </div>
        </div>
        <input type="hidden" name="desext_producto" id="desext_producto_hidden" value="<?= $savedDesextProd ?>">
        <div class="form-row">
            <div class="form-group">
                <label>Principio activo</label>
                <input type="text" name="desext_principio_activo" id="desext_principio_activo"
                       placeholder="Ej: AFOXOLANER"
                       value="<?= v($saved,'desext_principio_activo') ?>"
                       <?= (!$isDesextOtro && $desextSelectVal !== '') ? 'readonly style="background:#f5f5f5;color:#888;"' : '' ?>>
            </div>
            <div class="form-group">
                <label>Lote</label>
                <input type="text" name="desext_lote" placeholder="Ej: AAA1151/25" value="<?= v($saved,'desext_lote') ?>">
            </div>
            <div class="form-group">
                <label>Registro ICA</label>
                <input type="text" name="desext_registro_ica" id="desext_registro_ica"
                       placeholder="Ej: 9759-MV"
                       value="<?= v($saved,'desext_registro_ica') ?>"
                       <?= (!$isDesextOtro && $desextSelectVal !== '') ? 'readonly style="background:#f5f5f5;color:#888;"' : '' ?>>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" name="guardar" class="btn-primary">Guardar datos</button>
        <?php if ($saved): ?>
            <a href="generar_pdf.php?row=<?= $rowNum ?>" target="_blank" class="btn-generate">Generar PDF ↓</a>
            <a href="generar_docx.php?row=<?= $rowNum ?>" target="_blank" class="btn-generate btn-generate-docx">Generar DOCX ↓</a>
            <a href="subir_portal.php?row=<?= $rowNum ?>" class="btn-generate btn-doc">Subir al portal<?= v($saved,'portal_uploaded_at') ? ' ✅' : '' ?></a>
        <?php else: ?>
            <span class="hint">Guarda los datos primero para poder generar el PDF.</span>
        <?php endif; ?>
    </div>

</form>
</div>

<script>
// ── Vacunas ──────────────────────────────────────────────────────────────────
const VACUNAS_DB = <?= json_encode(array_map(fn($usos) => $usos, $vacunas_lista), JSON_UNESCAPED_UNICODE) ?>;
const SAVED_VACUNAS = <?= json_encode($saved_vacs_js, JSON_UNESCAPED_UNICODE) ?>;
let vacVisible = <?= $max_vac_visible ?>;
const VAC_MAX = 5;

const USO_COLORS = {
    'PENTAVALENTE':  '#FF7600',
    'RABIA':         '#E5443A',
    'LEPTOSPIRA':    '#008D83',
    'TOS DE PERRERAS': '#6B5540',
    'TRIPLE FELINA': '#9C27B0',
    'CORONAVIRUS':   '#2196F3',
    'PARVO':         '#FF5722',
};

function usosBadges(usos) {
    return usos.map(u => {
        const c = USO_COLORS[u] || '#6B5540';
        return `<span style="background:${c};color:#fff;border-radius:100px;padding:3px 10px;font-size:11px;font-family:Poppins,sans-serif;font-weight:600;white-space:nowrap;">${u}</span>`;
    }).join('');
}

function fillVacuna(idx, val) {
    const hidMed  = document.getElementById('vhid-med-' + idx);
    const hidUso  = document.getElementById('vhid-uso-' + idx);
    const usosWrap = document.getElementById('vusos-' + idx);
    const otroWrap = document.getElementById('votro-' + idx);

    if (val === '__otro__') {
        hidMed.value = '';
        hidUso.value = '';
        usosWrap.innerHTML = '';
        otroWrap.style.display = '';
        document.getElementById('votro-med-' + idx).value = '';
        document.getElementById('votro-uso-' + idx).value = '';
    } else if (val === '') {
        hidMed.value = '';
        hidUso.value = '';
        usosWrap.innerHTML = '';
        otroWrap.style.display = 'none';
    } else {
        const usos = VACUNAS_DB[val] || [];
        hidMed.value = val;
        hidUso.value = usos.join('|');
        usosWrap.innerHTML = usosBadges(usos);
        otroWrap.style.display = 'none';
    }
}

function addVacuna() {
    if (vacVisible >= VAC_MAX) return;
    vacVisible++;
    document.getElementById('vb-' + vacVisible).style.display = '';
    if (vacVisible >= VAC_MAX) {
        document.getElementById('add-vac-btn').style.display = 'none';
    }
}

function removeVacuna(idx) {
    const sel = document.getElementById('vsel-' + idx);
    sel.value = '';
    fillVacuna(idx, '');
    document.getElementById('vlote-' + idx).value = '';
    document.getElementById('vaplic-' + idx).value = '';
    document.getElementById('vprox-' + idx).value = '';
    document.getElementById('vb-' + idx).style.display = 'none';
    if (vacVisible === idx) vacVisible = Math.max(1, idx - 1);
    document.getElementById('add-vac-btn').style.display = '';
}

window.addEventListener('DOMContentLoaded', () => {
    // Restaurar vacunas guardadas
    for (let i = 1; i <= VAC_MAX; i++) {
        const sv = SAVED_VACUNAS[i];
        if (!sv || !sv.med) continue;
        const sel = document.getElementById('vsel-' + i);
        if (VACUNAS_DB[sv.med] !== undefined) {
            sel.value = sv.med;
            fillVacuna(i, sv.med);
        } else {
            sel.value = '__otro__';
            fillVacuna(i, '__otro__');
            document.getElementById('votro-med-' + i).value = sv.med;
            document.getElementById('votro-uso-' + i).value = sv.uso;
            document.getElementById('vhid-med-' + i).value = sv.med;
            document.getElementById('vhid-uso-' + i).value = sv.uso;
        }
        document.getElementById('vlote-' + i).value   = sv.lote  || '';
        document.getElementById('vaplic-' + i).value  = sv.aplic || '';
        document.getElementById('vprox-' + i).value   = sv.prox  || '';
    }
    if (vacVisible >= VAC_MAX) {
        document.getElementById('add-vac-btn').style.display = 'none';
    }
});

// ── Desparasitantes ───────────────────────────────────────────────────────────
const DESPAR = {
    int: {
        'ADVOCATE':        {principio:'MOXIDECTINA 1%',                                              ica:'REG ICA NO 7336 MV'},
        'CANISAN D':       {principio:'FEBANTEL PAMOATO DE PIRANTEL PRAZICUENTEL',                   ica:'Reg ICA No 5610 MV'},
        'CANNEX':          {principio:'FENBENDAZOL TOLTRAZURIL PRAZIQUANTEL',                        ica:'Reg ICA No 6803-MV'},
        'CREDELIO PLUS':   {principio:'MIBEMICINA OXIMA',                                            ica:'Reg ICA No 9871 MV'},
        'DOGG & CAT':      {principio:'PAMOATO DE PIRANTEL Y PRAZIQUANTEL',                          ica:'Reg ICA No 8722-MV'},
        'DRONTAL':         {principio:'FEBANTEL, PAMOATO DE PIRANTEL PRAZICUANTEL',                  ica:'Reg ICA No 6838 MV'},
        'ENDOGARD':        {principio:'FEBANTEL PAMOATO DE PIRANTEL PRAZIQUANTEL IVERMECTINA',       ica:'Reg ICA No 6510 MV'},
        'GALGOCAL':        {principio:'FEBANTEL PAMOATO DE PIRANTEL PRAZICUENTEL',                   ica:'Reg ICA No 5610 MV'},
        'GALGOCAL 600':    {principio:'ALBENDAZOL PRAZICUANTEL',                                     ica:'Reg ICA No 4853 DB'},
        'NEXGARD COMBO':   {principio:'EPRINOMECTINA PRAZICUANTEL',                                  ica:'Reg ICA No 11095 MV'},
        'NEXGARD SPECTRA': {principio:'MILBEMICIN A OXIMA',                                          ica:'Reg ICA No 9759-MV'},
        'PARACANIS':       {principio:'EMBONATO PIRANTEL PRAZICUENTEL',                              ica:'Reg ICA No 4265-DB MV'},
        'RONDEL PUPPY':    {principio:'FEBANTEL PAMOATO DE PIRANTEL PRAZICUENTEL',                   ica:'Reg ICA No 5610 MV'},
        'SIMPARICA TRIO':  {principio:'MOXIDECTINA SAROLANER',                                       ica:'Reg ICA No 10890 MV'},
        'TOTAL F LC':      {principio:'FENBENDAZOL PIRANTEL PRAZICUANTEL',                           ica:'Reg ICA No 8365 MV'},
    },
    ext: {
        'ADVANTIX':        {principio:'IMIDACLOPRID Y PERMETRINA',  ica:'Reg ICA No 10888 MV'},
        'ADVOCATE':        {principio:'IMIDACLOPRIDA 10%',           ica:'REG ICA NO 7336 MV'},
        'BRAVECTO':        {principio:'FLURALANER',                  ica:'Reg ICA 9759 MV'},
        'CREDELIO':        {principio:'LOTILANER',                   ica:'Reg ICA No 9561 MV'},
        'CREDELIO PLUS':   {principio:'LOTILANER',                   ica:'Reg ICA No 9871 MV'},
        'FIPROTECTION':    {principio:'FIPRONIL',                    ica:'REG ICA NO 8794 MV'},
        'NEXGARD':         {principio:'AFOXOLANER',                  ica:'Reg ICA No 10888 MV'},
        'NEXGARD COMBO':   {principio:'ESAFOXOLANE',                 ica:'Reg ICA No 11095 MV'},
        'NEXGARD SPECTRA': {principio:'MILBEMICIN A OXIMA',          ica:'Reg ICA No 9759-MV'},
        'PULFENNDOS':      {principio:'FIPRONIL',                    ica:'Reg ICA NO 10504 MV'},
        'REVOLUTION 6%':   {principio:'SELAMECTINA',                 ica:'Reg ICA No 10887 MV'},
        'SIMPARICA TRIO':  {principio:'MOXIDECTINA SAROLANER',       ica:'Reg ICA No 10890 MV'},
    }
};

// ── Auto Próxima dosis: +1 año desde fecha de aplicación ────────
function autoProxima(idx, fechaAplic) {
    const proxInput = document.getElementById('vprox-' + idx);
    if (!fechaAplic) return;
    if (proxInput.value) return;
    const d = new Date(fechaAplic); // siempre YYYY-MM-DD desde input[type=date]
    if (isNaN(d) || d.getFullYear() < 2000) return; // ignorar mientras se escribe el año
    d.setFullYear(d.getFullYear() + 1);
    proxInput.value = d.toISOString().slice(0, 10);
}

function fillDespar(tipo, val) {
    const pField  = document.getElementById('des' + tipo + '_principio_activo');
    const icaField = document.getElementById('des' + tipo + '_registro_ica');
    const otraWrap = document.getElementById('des' + tipo + '_otro_wrap');
    const hiddenProd = document.getElementById('des' + tipo + '_producto_hidden');

    if (val === '__otro__') {
        pField.removeAttribute('readonly');
        pField.style.background = '';
        pField.style.color = '';
        pField.value = '';
        icaField.removeAttribute('readonly');
        icaField.style.background = '';
        icaField.style.color = '';
        icaField.value = '';
        otraWrap.style.display = '';
        hiddenProd.value = document.getElementById('des' + tipo + '_otro_nombre').value;
    } else if (val === '') {
        pField.removeAttribute('readonly');
        pField.style.background = '';
        pField.style.color = '';
        pField.value = '';
        icaField.removeAttribute('readonly');
        icaField.style.background = '';
        icaField.style.color = '';
        icaField.value = '';
        otraWrap.style.display = 'none';
        hiddenProd.value = '';
    } else {
        const data = DESPAR[tipo][val] || {};
        pField.value = data.principio || '';
        pField.setAttribute('readonly', true);
        pField.style.background = '#f5f5f5';
        pField.style.color = '#888';
        icaField.value = data.ica || '';
        icaField.setAttribute('readonly', true);
        icaField.style.background = '#f5f5f5';
        icaField.style.color = '#888';
        otraWrap.style.display = 'none';
        hiddenProd.value = val;
    }
}
</script>
<script>
setInterval(function() {
    fetch('keepalive.php').catch(function(){});
}, 10 * 60 * 1000);
</script>
<script>
// Autoguardado en el navegador: si la sesión muere a media edición (o se
// cierra la pestaña por error), el trabajo escrito no se pierde — se
// recupera al volver a abrir este mismo certificado, sin depender de que
// el servidor haya alcanzado a guardarlo.
(function() {
    var form = document.getElementById('formMedico');
    if (!form) return;
    var draftKey = 'borrador_certificado_<?= $rowNum ?>';

    function leerCampos() {
        var data = {};
        Array.prototype.forEach.call(form.elements, function(el) {
            if (!el.name) return;
            if (el.type === 'submit' || el.type === 'button') return;
            data[el.name] = el.value;
        });
        return data;
    }

    function guardarBorrador() {
        try { localStorage.setItem(draftKey, JSON.stringify({ t: Date.now(), data: leerCampos() })); }
        catch (e) {}
    }

    var t;
    form.addEventListener('input', function() {
        clearTimeout(t);
        t = setTimeout(guardarBorrador, 800);
    });

    form.addEventListener('submit', function() {
        try { localStorage.removeItem(draftKey); } catch (e) {}
    });

    window.restaurarBorrador = function() {
        var raw = localStorage.getItem(draftKey);
        if (!raw) return;
        var draft = JSON.parse(raw).data;
        Object.keys(draft).forEach(function(name) {
            var el = form.elements[name];
            if (el && !el.readOnly) el.value = draft[name];
        });
        document.getElementById('borradorAviso').style.display = 'none';
    };

    window.descartarBorrador = function() {
        try { localStorage.removeItem(draftKey); } catch (e) {}
        document.getElementById('borradorAviso').style.display = 'none';
    };

    document.addEventListener('DOMContentLoaded', function() {
        var raw = localStorage.getItem(draftKey);
        if (!raw) return;
        try {
            var draft = JSON.parse(raw);
            var actual = leerCampos();
            var cambioReal = Object.keys(draft.data).some(function(k) { return draft.data[k] !== actual[k]; });
            if (cambioReal) document.getElementById('borradorAviso').style.display = 'block';
            else localStorage.removeItem(draftKey);
        } catch (e) {}
    });
})();
</script>

</body>
</html>
