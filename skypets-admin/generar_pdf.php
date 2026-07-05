<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/auth.php';
requireLogin();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/google_api.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/vendor/autoload.php';

$rowNum = (int)($_GET['row'] ?? 0);
if ($rowNum < 2) { header('Location: dashboard.php'); exit; }

$rows = getSheetRows();
$row  = $rows[$rowNum - 2] ?? null;
if (!$row) { header('Location: dashboard.php'); exit; }

$db = getDB();
$stmt = $db->prepare('SELECT * FROM certificados WHERE sheet_row = ?');
$stmt->execute([$rowNum]);
$med = $stmt->fetch();
if (!$med) { header("Location: certificado.php?row=$rowNum"); exit; }

// ─── Datos del formulario ─────────────────────────────────────────
$tipo         = col($row, 'tipo_cert');
$esInt        = esInternacional($tipo);
$tipoEuropa   = str_contains(strtoupper($tipo), 'EUROPA');

$ciudadOrigen = col($row, 'ciudad_origen');
$ciudadDest   = col($row, 'ciudad_destino');
$ciudadDest2  = col($row, 'ciudad_destino2');
$destFinal    = $ciudadDest2 ?: $ciudadDest;
// Mapa ciudad → país para derivar país de destino
function ciudadAPais(string $ciudad): string {
    $ciudad = strtolower(trim(preg_replace('/[^a-záéíóúüñ\s]/ui', '', $ciudad)));
    $mapa = [
        // México
        'ciudad de mexico'=>'México','ciudad de méxico'=>'México','cdmx'=>'México',
        'cancun'=>'México','cancún'=>'México','guadalajara'=>'México','monterrey'=>'México',
        'tijuana'=>'México','puebla'=>'México','merida'=>'México','mérida'=>'México',
        'queretaro'=>'México','querétaro'=>'México','leon'=>'México','léon'=>'México',
        'san luis potosi'=>'México','aguascalientes'=>'México','hermosillo'=>'México',
        'chihuahua'=>'México','veracruz'=>'México','acapulco'=>'México','mazatlan'=>'México',
        'los cabos'=>'México','playa del carmen'=>'México','tulum'=>'México',
        // Panamá
        'ciudad de panama'=>'Panamá','ciudad de panamá'=>'Panamá','panama city'=>'Panamá',
        'panama'=>'Panamá','panamá'=>'Panamá','tocumen'=>'Panamá',
        // Argentina
        'buenos aires'=>'Argentina','cordoba'=>'Argentina','córdoba'=>'Argentina',
        'rosario'=>'Argentina','mendoza'=>'Argentina','salta'=>'Argentina',
        'bariloche'=>'Argentina','mar del plata'=>'Argentina','tucuman'=>'Argentina',
        'tucumán'=>'Argentina',
        // Chile
        'santiago'=>'Chile','valparaiso'=>'Chile','valparaíso'=>'Chile',
        'concepcion'=>'Chile','concepción'=>'Chile','antofagasta'=>'Chile',
        'vina del mar'=>'Chile','viña del mar'=>'Chile','temuco'=>'Chile',
        // Perú
        'lima'=>'Perú','cusco'=>'Perú','arequipa'=>'Perú','trujillo'=>'Perú',
        'iquitos'=>'Perú','piura'=>'Perú','chiclayo'=>'Perú','machu picchu'=>'Perú',
        // Ecuador
        'quito'=>'Ecuador','guayaquil'=>'Ecuador','cuenca'=>'Ecuador',
        'manta'=>'Ecuador','loja'=>'Ecuador','ambato'=>'Ecuador',
        // Bolivia
        'la paz'=>'Bolivia','santa cruz'=>'Bolivia','cochabamba'=>'Bolivia',
        'sucre'=>'Bolivia','oruro'=>'Bolivia','potosi'=>'Bolivia','potosí'=>'Bolivia',
        // Venezuela
        'caracas'=>'Venezuela','maracaibo'=>'Venezuela','valencia'=>'Venezuela',
        'barquisimeto'=>'Venezuela','maracay'=>'Venezuela',
        // Uruguay
        'montevideo'=>'Uruguay','punta del este'=>'Uruguay','colonia'=>'Uruguay',
        // Paraguay
        'asuncion'=>'Paraguay','asunción'=>'Paraguay','ciudad del este'=>'Paraguay',
        // Cuba
        'la habana'=>'Cuba','habana'=>'Cuba','havana'=>'Cuba','santiago de cuba'=>'Cuba',
        'varadero'=>'Cuba','holguin'=>'Cuba','holguín'=>'Cuba',
        // República Dominicana
        'santo domingo'=>'República Dominicana','punta cana'=>'República Dominicana',
        'santiago de los caballeros'=>'República Dominicana','la romana'=>'República Dominicana',
        // Costa Rica
        'san jose'=>'Costa Rica','san josé'=>'Costa Rica','liberia'=>'Costa Rica',
        'limon'=>'Costa Rica','limón'=>'Costa Rica',
        // Guatemala
        'ciudad de guatemala'=>'Guatemala','quetzaltenango'=>'Guatemala','antigua'=>'Guatemala',
        // Honduras
        'tegucigalpa'=>'Honduras','san pedro sula'=>'Honduras','roatan'=>'Honduras',
        'roatán'=>'Honduras',
        // El Salvador
        'san salvador'=>'El Salvador','santa ana'=>'El Salvador','san miguel'=>'El Salvador',
        // Nicaragua
        'managua'=>'Nicaragua','granada'=>'Nicaragua','leon'=>'Nicaragua',
        // Belice
        'belize city'=>'Belice','belmopan'=>'Belice',
        // Haití
        'puerto principe'=>'Haití','puerto príncipe'=>'Haití','port au prince'=>'Haití',
        // Jamaica
        'kingston'=>'Jamaica','montego bay'=>'Jamaica','ocho rios'=>'Jamaica',
        // Trinidad y Tobago
        'port of spain'=>'Trinidad y Tobago','puerto españa'=>'Trinidad y Tobago',
        // España (por si acaso viene en ciudad_destino)
        'madrid'=>'España','barcelona'=>'España','sevilla'=>'España','valencia'=>'España',
        'bilbao'=>'España','malaga'=>'España','málaga'=>'España',
        // USA (en caso de aparecer)
        'miami'=>'Estados Unidos','new york'=>'Estados Unidos','nueva york'=>'Estados Unidos',
        'los angeles'=>'Estados Unidos','los ángeles'=>'Estados Unidos',
        'houston'=>'Estados Unidos','chicago'=>'Estados Unidos','orlando'=>'Estados Unidos',
    ];
    // Búsqueda exacta primero
    if (isset($mapa[$ciudad])) return $mapa[$ciudad];
    // Búsqueda parcial (ciudad puede tener texto adicional)
    foreach ($mapa as $key => $pais) {
        if (str_contains($ciudad, $key)) return $pais;
    }
    return '';
}

if (!$esInt) {
    $paisDest = 'Colombia';
} elseif ($tipoEuropa) {
    $paisDest = 'España';
} else {
    // Derivar país desde ciudad de destino
    $ciudadParaPais = $ciudadDest2 ?: $ciudadDest;
    $paisDest = ciudadAPais($ciudadParaPais);
}

$nombreTutor  = mb_convert_case(mb_strtolower(col($row, 'nombre_tutor'), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
$tipoDoc      = col($row, 'tipo_doc');
$numDoc       = col($row, 'num_documento');
$celular      = col($row, 'celular');
$correo       = col($row, 'correo');
$dirRes       = col($row, 'dir_residencia');
$dirDest      = col($row, 'dir_destino');
$codPostal    = col($row, 'codigo_postal');

$mascota      = col($row, 'nombre_mascota');
$microchip    = col($row, 'microchip');
$especie      = col($row, 'especie');
$esp          = especieCheck($especie);
$raza         = (!empty($med['raza_corregida'])) ? $med['raza_corregida'] : col($row, 'raza');
$edad         = col($row, 'edad');
$sexo         = col($row, 'sexo');
$peso         = col($row, 'peso');
$color        = col($row, 'color');
$escala       = col($row, 'escala');
$aerolinea    = col($row, 'aerolinea');
$fechaViaje   = strtoupper(col($row, 'fecha_viaje'));

$fechaEmision = $med['fecha_emision'];
$fechaValido  = date('d \d\e F \d\e Y', strtotime($fechaEmision . ' + 10 days'));
$fechaEmisionLabel = formatFecha($fechaEmision);

// ─── Descargar foto mascota ──────────────────────────────────────
$fotoMascotaId   = driveFileId(col($row, 'foto_mascota'));
$fotoMascotaPath = $fotoMascotaId ? downloadDriveImage($fotoMascotaId) : null;

// ─── Construir vacunas ───────────────────────────────────────────
$vacunas = [];
for ($i = 1; $i <= 5; $i++) {
    $med_name = trim($med["vacuna{$i}_medicamento"] ?? '');
    if (!$med_name) continue;
    $usos = array_filter(array_map('trim', explode('|', $med["vacuna{$i}_uso"] ?? '')));
    if (empty($usos)) $usos = [''];
    foreach ($usos as $uso) {
        $vacunas[] = [
            'aplicacion'  => formatFechaCorta($med["vacuna{$i}_aplicacion"]),
            'proxima'     => formatFechaCorta($med["vacuna{$i}_proxima"]),
            'medicamento' => $med_name,
            'lote'        => $med["vacuna{$i}_lote"],
            'uso'         => $uso,
        ];
    }
}

// ─── Helpers de imagen para HTML ────────────────────────────────
// Redimensiona a max $maxPx en el lado mayor y convierte a JPEG base64 (calidad $q)
// para reducir el tamaño del PDF final. Devuelve data URI o cadena vacía.
function compressImg(?string $path, int $maxPx = 800, int $q = 72): string {
    if (!$path || !file_exists($path)) return '';
    $bytes = file_get_contents($path, false, null, 0, 12);
    if ($bytes === false) return 'file://' . $path;

    $im = null;
    if (str_starts_with($bytes, "\xFF\xD8\xFF"))  $im = @imagecreatefromjpeg($path);
    elseif (str_starts_with($bytes, "\x89PNG"))   $im = @imagecreatefrompng($path);
    elseif (str_starts_with($bytes, 'RIFF'))      $im = @imagecreatefromwebp($path);
    elseif (str_starts_with($bytes, 'GIF'))       $im = @imagecreatefromgif($path);

    if (!$im) return 'file://' . $path; // fallback sin compresión

    $w = imagesx($im); $h = imagesy($im);
    $scale = ($w > $maxPx || $h > $maxPx) ? min($maxPx / $w, $maxPx / $h) : 1.0;
    $nw = max(1, (int)($w * $scale));
    $nh = max(1, (int)($h * $scale));

    $out = imagecreatetruecolor($nw, $nh);
    // Fondo blanco para transparencias PNG
    imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
    imagecopyresampled($out, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);
    imagedestroy($im);

    ob_start();
    imagejpeg($out, null, $q);
    imagedestroy($out);
    $data = ob_get_clean();

    return 'data:image/jpeg;base64,' . base64_encode($data);
}

$logoSrc    = compressImg(LOGO_PATH,    600, 75);
$firmaSrc   = compressImg(FIRMA_PATH,   600, 75);
$cedulaSrc  = compressImg(CEDULA_PATH,  900, 70);
$tarjetaSrc = compressImg(TARJETA_PATH, 900, 70);
$petSrc     = compressImg($fotoMascotaPath, 500, 72);

// ─── Textos bilingüe ─────────────────────────────────────────────
$tituloDoc = $esInt
    ? 'Certificado de Salud Internacional | International Health Certificate'
    : 'Certificado de Salud Nacional';

$declaracion = $esInt
    ? 'Yo, Dra. Martha Viviana Mora Macias, médica veterinaria con matrícula profesional No. 52816, certifico que he examinado al animal descrito anteriormente y declaro lo siguiente, I, Dr. Martha Viviana Mora Macias, veterinarian with professional license No. 52816, certify that I have examined the animal described above and declare the following:'
    : 'Yo, Dra. Martha Viviana Mora Macias, médica veterinaria con matrícula profesional No. 52816, certifico que he examinado al animal descrito anteriormente y declaro lo siguiente';

$saludText = $esInt
    ? 'El paciente examinado se encuentra en buen estado de salud y su condición le permite viajar sin restricción al no presentar ningún tipo de enfermedad infectocontagiosa zoonóticas. Tampoco, está infestado con gusano barrenador del ganado bovino Cochliomyia hominivorax).<br>According to the examination the pet is free of any transmissible, contagious or infectious disease, the pet does not appear to be clinically ill from parasitic infestation at the time of physical examination. Neither is infested with cattle screwworm Cochliomyia hominivorax).'
    : 'El paciente examinado se encuentra en buen estado de salud y su condición le permite viajar sin restricción al no presentar ningún tipo de enfermedad infectocontagiosa zoonóticas. Tampoco, está infestado con gusano barrenador del ganado bovino Cochliomyia hominivorax).';

$trasladoText = $esInt
    ? 'El suscrito Médico Veterinario hace constar que el paciente, al examen clínico no evidenció ningún signo de enfermedad infecto-contagiosa y/o parasitaria, ni heridas frescas y/o recientes; y puede trasladarse por vía aérea, marítima y/o terrestre. El paciente presenta vacunación y desparasitación vigente según los protocolos establecidos para su edad.<br><br>The undersigned Veterinary Doctor certifies that the patient, upon clinical examination, showed no signs of infectious-contagious diseases and/or parasitic diseases, nor any fresh and/or recent wounds; and can be transported by air, sea, and/or land. The patient has current vaccinations and deworming according to the established protocols for their age.'
    : 'El suscrito Médico Veterinario hace constar que el paciente, al examen clínico no evidenció ningún signo de enfermedad infecto-contagiosa y/o parasitaria, ni heridas frescas y/o recientes; y puede trasladarse por vía aérea, marítima y/o terrestre. El paciente presenta vacunación y desparasitación vigente según los protocolos establecidos para su edad.';

$validezText = $esInt
    ? "Este certificado es válido por <strong>10 días</strong> a partir de la fecha de emisión <strong>$fechaEmisionLabel</strong>; This certificate is valid for <strong>10 days</strong> from the date of issue. <strong>$fechaEmisionLabel</strong>"
    : "Este certificado es válido por <strong>10 días</strong> a partir de la fecha de <strong>$fechaEmisionLabel</strong>";

$vacTitle = $esInt
    ? 'DATOS DE VACUNACIÓN E INMUNIZACIÓN | VACCINATION AND IMMUNIZATION DATA'
    : 'DATOS DE VACUNACIÓN E INMUNIZACIÓN';

$desIntTitle = 'DATOS DESPARASITACIÓN INTERNA | INTERNAL DEWORMING DATA';
$desExtTitle = 'DATOS DESPARASITACIÓN EXTERNA | EXTERNAL DEWORMING DATA';

// Campo documento tutor según tipo
$docLabel = strtoupper($tipoDoc);

// ─── HTML del certificado ────────────────────────────────────────
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: Arial, sans-serif; font-size: 9.5pt; color: #2B2418; }

  .logo-wrap { text-align:center; margin-top:-14px; margin-bottom:8px; }
  .logo-wrap img { height:90px; }

  .titulo-doc {
    text-align:center; font-size:13pt; font-weight:bold;
    border:1.5px solid #FF7600; padding:10px 16px; margin:10px 0;
  }

  table { width:100%; border-collapse:collapse; margin:6px 0; }
  td, th { border:1px solid #ccc; padding:4px 7px; vertical-align:middle; }
  th { background:#FF7600; color:#fff; font-weight:bold; text-align:center; font-size:9.5pt; }
  .th-section { background:#FF7600; color:#fff; font-weight:bold; text-align:center; font-size:9.5pt; }
  .label-cell { font-weight:bold; width:42%; background:#fff9f0; }

  .ruta-table td { text-align:center; }
  .fecha-row td { padding:5px 7px; }

  .two-col { width:100%; border-collapse:collapse; margin:8px 0; }
  .two-col > tbody > tr > td { border:none; padding:0; vertical-align:top; }
  .pet-cell { width:140px; padding-right:10px; }
  .pet-img { width:130px; height:auto; border:1px solid #ddd; }

  .parrafo { margin:8px 0; font-size:9pt; line-height:1.5; text-align:justify; }

  .firma-block { margin-top:24px; }
  .firma-img { height:140px; }
  .firma-name { font-weight:bold; margin-top:4px; }
  .firma-sub  { font-size:8.5pt; }

  .tagline { text-align:center; font-style:italic; font-size:8.5pt; margin-top:18px; color:#6B5540; }
  .tagline strong { color:#FF7600; }
  .tagline a { color:#008D83; }

  .page-break { page-break-after:always; }

  .credencial-page { text-align:center; padding-top:30px; }
  .credencial-page img { max-width:400px; max-height:260px; margin:16px auto; display:block; border:1px solid #ddd; }
</style>
</head>
<body>

<!-- ════════════════════════════════════════════════
     PÁGINA 1 — DATOS PRINCIPALES
════════════════════════════════════════════════ -->

<div class="logo-wrap">
  <?php if ($logoSrc): ?><img src="<?= $logoSrc ?>"><?php endif; ?>
</div>

<div class="titulo-doc"><?= $tituloDoc ?></div>

<!-- Ruta -->
<table class="ruta-table">
  <tr>
    <th>PAÍS DE ORIGEN<?= $esInt ? ' | ORIGIN COUNTRY' : '' ?></th>
    <th>CIUDAD DE ORIGEN<?= $esInt ? ' | ORIGIN CITY' : '' ?></th>
    <th>PAÍS DE DESTINO<?= $esInt ? ' | DESTINATION COUNTRY' : '' ?></th>
    <th>CIUDAD DE DESTINO<?= $esInt ? ' | DESTINATION CITY' : '' ?></th>
  </tr>
  <tr>
    <td>COLOMBIA</td>
    <td><?= htmlspecialchars(strtoupper($ciudadOrigen)) ?></td>
    <td><?= htmlspecialchars(strtoupper($paisDest)) ?></td>
    <td><?= htmlspecialchars(strtoupper($destFinal)) ?></td>
  </tr>
</table>

<table>
  <tr>
    <td class="label-cell" style="width:30%;">FECHA DE VIAJE<?= $esInt ? ' / TRAVEL DATE' : '' ?></td>
    <td><strong><?= htmlspecialchars($fechaViaje) ?></strong></td>
  </tr>
</table>

<p class="parrafo"><?= $declaracion ?></p>

<!-- Foto + datos -->
<table class="two-col">
<tr>
  <td class="pet-cell">
    <?php if ($petSrc): ?>
      <img src="<?= $petSrc ?>" class="pet-img">
    <?php endif; ?>
  </td>
  <td>
    <!-- Tutor -->
    <table style="margin-bottom:6px;">
      <tr><th colspan="2">DATOS DEL TUTOR // TUTOR'S DATA</th></tr>
      <tr><td class="label-cell">NOMBRE / NAME</td><td><?= htmlspecialchars(strtoupper($nombreTutor)) ?></td></tr>
      <tr><td class="label-cell"><?= $docLabel ?></td><td><?= htmlspecialchars(strtoupper($numDoc)) ?></td></tr>
      <tr><td class="label-cell">CELULAR / CELL NUMBER</td><td><?= htmlspecialchars(strtoupper($celular)) ?></td></tr>
      <tr><td class="label-cell">DIRECCION / ADDRESS</td><td><?= htmlspecialchars(strtoupper($dirRes)) ?></td></tr>
      <?php if ($esInt && $correo): ?>
      <tr><td class="label-cell">CORREO / EMAIL</td><td><?= htmlspecialchars(strtoupper($correo)) ?></td></tr>
      <?php endif; ?>
    </table>
    <!-- Paciente -->
    <table>
      <tr><th colspan="2">DATOS DEL PACIENTE // PATIENT DATA</th></tr>
      <tr><td class="label-cell">NOMBRE/ NAME</td><td><?= htmlspecialchars(strtoupper($mascota)) ?></td></tr>
      <tr>
        <td class="label-cell">ID CHIP</td>
        <td><?php
          $chip    = trim($microchip ?? '');
          $soloNum = preg_replace('/\D/', '', $chip);
          $noAplica = !$chip
              || preg_match('/no\s*(aplica|tiene|posee|cuenta|chip)|sin\s*chip|n\/a/i', $chip)
              || $chip === '-';
          if ($noAplica) {
              echo '';
          } elseif (strlen($soloNum) === 15) {
              echo implode(' ', str_split($soloNum, 1));
          } elseif (strlen($soloNum) > 0) {
              echo '<span style="color:#E5443A;font-weight:bold;">[!] NUMERO INCOMPLETO (' . strlen($soloNum) . ' digitos)</span>';
          } else {
              echo htmlspecialchars($chip);
          }
        ?></td>
      </tr>
      <tr>
        <td class="label-cell">ESPECIE</td>
        <td><?= htmlspecialchars(strtoupper($especie)) ?></td>
      </tr>
      <tr><td class="label-cell">RAZA / BREED</td><td><?= htmlspecialchars(strtoupper($raza)) ?></td></tr>
      <tr>
        <td class="label-cell">EDAD / AGE</td>
        <td><?= htmlspecialchars(strtoupper($edad)) ?></td>
      </tr>
      <tr>
        <td class="label-cell">SEXO / SEX</td>
        <td><?= htmlspecialchars(strtoupper($sexo)) ?></td>
      </tr>
      <tr><td class="label-cell">PESO /WEIGHT</td><td><?= htmlspecialchars(strtoupper($peso)) ?></td></tr>
      <tr><td class="label-cell">COLOR/ COLOR</td><td><?= htmlspecialchars(strtoupper($color)) ?></td></tr>
    </table>
  </td>
</tr>
</table>

<p class="parrafo"><?= $saludText ?></p>

<!-- Vacunas -->
<div style="page-break-inside:avoid;">
<table>
  <tr><th colspan="5"><?= $vacTitle ?></th></tr>
  <tr>
    <th>APLICACIÓN<?= $esInt ? ' / APPLICATION' : '' ?></th>
    <th>PRÓXIMA<?= $esInt ? ' / NEXT' : '' ?></th>
    <th>MEDICAMENTO<?= $esInt ? ' / MEDICINE' : '' ?></th>
    <th>LOTE / LOT</th>
    <th>USO TERAPÉUTICO</th>
  </tr>
  <?php foreach ($vacunas as $vac): ?>
  <tr>
    <td><?= htmlspecialchars(strtoupper($vac['aplicacion'])) ?></td>
    <td><?= htmlspecialchars(strtoupper($vac['proxima'])) ?></td>
    <td><?= htmlspecialchars(strtoupper($vac['medicamento'])) ?></td>
    <td><?= htmlspecialchars(strtoupper($vac['lote'])) ?></td>
    <td><?= htmlspecialchars(strtoupper($vac['uso'])) ?></td>
  </tr>
  <?php endforeach; ?>
</table>
</div>

<p class="parrafo"><?= $trasladoText ?></p>

<!-- Desparasitación interna -->
<table>
  <tr><th colspan="5"><?= $desIntTitle ?></th></tr>
  <tr>
    <th>FECHA DE APLICACIÓN //<br>APPLICATION DATE</th>
    <th>PRODUCTO //<br>PRODUCT</th>
    <th>PRINCIPIO ACTIVO //<br>ACTIVE PRINCIPLE</th>
    <th>LOTE / LOT</th>
    <th>REGISTRO ICA //<br>ICA REGISTRATION</th>
  </tr>
  <?php if ($med['desint_producto']): ?>
  <tr>
    <td><?= htmlspecialchars(strtoupper(formatFechaCorta($med['desint_fecha']))) ?></td>
    <td><?= htmlspecialchars(strtoupper($med['desint_producto'])) ?></td>
    <td><?= htmlspecialchars(strtoupper($med['desint_principio_activo'])) ?></td>
    <td><?= htmlspecialchars(strtoupper($med['desint_lote'])) ?></td>
    <td><?= htmlspecialchars(strtoupper($med['desint_registro_ica'])) ?></td>
  </tr>
  <?php endif; ?>
</table>

<!-- Desparasitación externa -->
<table>
  <tr><th colspan="5"><?= $desExtTitle ?></th></tr>
  <?php if ($med['desext_producto']): ?>
  <tr>
    <td><?= htmlspecialchars(strtoupper(formatFechaCorta($med['desext_fecha']))) ?></td>
    <td><?= htmlspecialchars(strtoupper($med['desext_producto'])) ?></td>
    <td><?= htmlspecialchars(strtoupper($med['desext_principio_activo'])) ?></td>
    <td><?= htmlspecialchars(strtoupper($med['desext_lote'])) ?></td>
    <td><?= htmlspecialchars(strtoupper($med['desext_registro_ica'])) ?></td>
  </tr>
  <?php endif; ?>
</table>

<p class="parrafo" style="margin-top:14px;"><?= $validezText ?></p>

<div class="firma-block">
  <?php if ($firmaSrc): ?>
    <img src="<?= $firmaSrc ?>" class="firma-img"><br>
  <?php endif; ?>
  <div class="firma-name"><?= VET_NOMBRE ?></div>
  <div class="firma-sub">TP <?= VET_TP ?> – <?= VET_TITULO ?></div>
  <div class="firma-sub">C.C. <?= VET_CC ?></div>
  <div class="firma-sub"><?= VET_EMAIL ?></div>
  <div class="firma-sub">Tel: <?= VET_TEL ?></div>
</div>

<div class="page-break"></div>

<!-- ════════════════════════════════════════════════
     PÁGINA 3 — CREDENCIALES VETERINARIA
════════════════════════════════════════════════ -->

<div class="logo-wrap">
  <?php if ($logoSrc): ?><img src="<?= $logoSrc ?>"><?php endif; ?>
</div>

<div class="credencial-page">
  <?php if ($tarjetaSrc): ?>
    <img src="<?= $tarjetaSrc ?>">
  <?php endif; ?>
  <?php if ($cedulaSrc): ?>
    <img src="<?= $cedulaSrc ?>">
  <?php endif; ?>
</div>

</body>
</html>
<?php
$html = ob_get_clean();

// ─── Generar PDF con mPDF ────────────────────────────────────────
$mpdf = new \Mpdf\Mpdf([
    'mode'          => 'utf-8',
    'format'        => 'Letter',
    'margin_top'    => 14,
    'margin_bottom' => 24,
    'margin_left'   => 16,
    'margin_right'  => 16,
    'tempDir'       => __DIR__ . '/tmp',
    'default_font'  => 'arial',
    'allow_charset_conversion' => true,
    'dpi'           => 96,
    'img_dpi'       => 96,
]);

$footerHtml = '
<div style="text-align:center;font-family:Arial,sans-serif;border-top:1px solid #FFBC00;padding-top:5px;">
  <div style="font-size:8.5pt;font-style:italic;font-weight:bold;color:#2B2418;">No solo llevamos mascotas, llevamos historias, recuerdos y corazones viajeros,</div>
  <div style="font-size:8.5pt;font-weight:bold;color:#FF7600;">SkyPets, Juntos en Cada Destino</div>
  <div style="font-size:8pt;color:#008D83;">www.skypetscol.com</div>
</div>';

$mpdf->SetHTMLFooter($footerHtml);
$mpdf->SetTitle("Certificado - $mascota");
$mpdf->WriteHTML($html);

// Marcar como generado
$db->prepare("UPDATE certificados SET status='generado' WHERE sheet_row = ?")
   ->execute([$rowNum]);

$filename = 'SKYPETS_' . strtoupper(preg_replace('/\s+/', '_', $mascota)) . '_' . date('Ymd') . '.pdf';
$mpdf->Output($filename, 'D');
