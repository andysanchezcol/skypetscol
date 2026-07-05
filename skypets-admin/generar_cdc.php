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

// ─── Datos ───────────────────────────────────────────────────────
$nombreTutor = strtoupper(col($row, 'nombre_tutor'));
$celular     = col($row, 'celular');
$correo      = col($row, 'correo');
$dirRes      = strtoupper(col($row, 'dir_residencia'));
$microchip   = col($row, 'microchip');
$mascota     = strtoupper(col($row, 'nombre_mascota'));
$raza        = strtoupper((!empty($med['raza_corregida'])) ? $med['raza_corregida'] : col($row, 'raza'));
$edad        = col($row, 'edad');
$sexo        = strtoupper(col($row, 'sexo'));
$color       = strtoupper(col($row, 'color'));

function mmddyyyy(string $f): string {
    if (!$f) return '';
    $ts = strtotime($f);
    return $ts ? date('m/d/Y', $ts) : $f;
}

// ─── Vacunas RABIA ────────────────────────────────────────────────
$vacunasRabia = [];
for ($i = 1; $i <= 5; $i++) {
    $medName = trim($med["vacuna{$i}_medicamento"] ?? '');
    if (!$medName) continue;
    $usos = array_filter(array_map('trim', explode('|', $med["vacuna{$i}_uso"] ?? '')));
    foreach ($usos as $uso) {
        if (stripos($uso, 'RABIA') !== false) {
            $vacunasRabia[] = [
                'producto'   => strtoupper($medName),
                'lote'       => strtoupper($med["vacuna{$i}_lote"] ?? ''),
                'aplicacion' => mmddyyyy($med["vacuna{$i}_aplicacion"] ?? ''),
                'proxima'    => mmddyyyy($med["vacuna{$i}_proxima"] ?? ''),
            ];
            break;
        }
    }
}
while (count($vacunasRabia) < 3) {
    $vacunasRabia[] = ['producto'=>'','lote'=>'','aplicacion'=>'','proxima'=>''];
}

// ─── Constantes veterinaria ───────────────────────────────────────
$VET_NOMBRE   = 'Dra. Martha Viviana Mora Macias';
$VET_LICENCIA = '52816';
$VET_DIR      = 'Bogota D.C., Cundinamarca';
$VET_CIUDAD   = 'Bogota';
$VET_REGION   = 'Cundinamarca';
$VET_PAIS     = 'Colombia';
$VET_TEL      = '+57 321 355 6909';
$VET_EMAIL    = 'info@skypetscol.com';
$HOY          = date('m/d/Y');

$cdcLogoSrc = __DIR__ . '/assets/images/cdc_logo.png';

// ─── HTML ─────────────────────────────────────────────────────────
ob_start();

// Helper para fila de campo con subrayado
function fl(string $label, string $val, string $width = '100%'): string {
    $v = htmlspecialchars($val);
    return "<span class='lbl'>{$label}</span> <span class='fld' style='width:{$width};'>{$v}</span>";
}

?><!DOCTYPE html>
<html>
<head><meta charset="UTF-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, Helvetica, sans-serif; font-size: 8pt; color:#000; }

/* Header */
.hdr-table { width:100%; border-collapse:collapse; margin-bottom:3px; }
.hdr-logo { width:68px; vertical-align:middle; }
.hdr-logo img { width:65px; height:65px; display:block; }
.hdr-title { vertical-align:middle; text-align:center; padding:0 4px; }
.hdr-title h1 { font-size:12.5pt; font-weight:bold; line-height:1.2; }
.hdr-title h2 { font-size:10pt; font-weight:bold; line-height:1.3; }
.hdr-title .sub { font-size:7.8pt; line-height:1.4; }
.hdr-title .typed { font-size:8pt; font-weight:bold; font-style:italic; margin-top:1px; }
.omb { font-size:7.5pt; text-align:right; line-height:1.5; margin-bottom:4px; }

/* Secciones */
.sec { background:#000; color:#fff; font-weight:bold; font-size:7.8pt;
       padding:2.5px 5px; margin-top:5px; margin-bottom:3px; }

/* Campos */
.row { margin:3px 0; font-size:7.8pt; line-height:1.6; }
.lbl { font-weight:bold; }
.fld { display:inline-block; border-bottom:1px solid #000;
       vertical-align:bottom; min-width:60px; font-size:8pt; padding:0 2px; }

/* Tablas */
table.t { width:100%; border-collapse:collapse; font-size:7.3pt; }
table.t th {
    border:1px solid #000; padding:2px 3px; text-align:center;
    font-weight:bold; vertical-align:middle; background:#f2f2f2; line-height:1.3;
}
table.t td {
    border:1px solid #000; padding:2px 3px; vertical-align:middle;
    height:13px; font-size:7.8pt;
}
.fn { font-size:6.8pt; font-style:italic; margin-top:2px; line-height:1.4; }
.pub { font-size:6.5pt; margin-top:8px; border-top:1px solid #ccc;
       padding-top:3px; line-height:1.4; }
.pgfoot { font-size:7pt; margin-top:6px; }

/* Página 2 */
.p2-header { border:1px solid #000; padding:3px 6px; margin-bottom:6px;
             font-size:8pt; }
.p2-sig-title { font-size:10pt; font-weight:bold; font-style:italic;
                margin:6px 0 3px 0; }
.p2-certif { font-size:9pt; font-style:italic; margin:0 0 5px 0; }
ol.stmt { font-size:7.8pt; margin-left:16px; line-height:1.5; }
ol.stmt li { margin-bottom:2px; }
.seal-box { width:100%; border-collapse:collapse; margin-top:8px; }
.seal-cell { width:38%; border:1px solid #000; height:75px;
             text-align:center; vertical-align:middle; font-size:7.5pt; color:#777; }
.g-box { border:1px solid #000; padding:4px 6px; margin-bottom:5px; font-size:8pt; }
</style>
</head>
<body>

<!-- ═══════════════════════ PÁGINA 1 ═══════════════════════════ -->
<table class="hdr-table">
<tr>
  <td class="hdr-logo"><img src="<?= htmlspecialchars($cdcLogoSrc) ?>" alt="CDC" width="65" height="65"></td>
  <td class="hdr-title">
    <h1>Certification of Foreign Rabies Vaccination and Microchip</h1>
    <h2>(for Live Dog Importations into the United States)</h2>
    <p class="sub">This form must be completed by the examining veterinarian not more than 30 days before travel.<br>
    Endorsement by an official government veterinarian is required for the form to be valid.</p>
    <p class="typed">THIS FORM MUST BE TYPED.</p>
  </td>
</tr>
</table>
<div class="omb">OMB Approval Number: 0920-1383<br>Form Expires: 5/31/2027</div>

<!-- SECTION A -->
<div class="sec">SECTION A: NAME, ADDRESS, PHONE NUMBER, AND EMAIL OF OWNER (CONSIGNOR)</div>
<div class="row"><?= fl('Name:', $nombreTutor, '62%') ?></div>
<div class="row"><?= fl('Organization <em>(if applicable)</em>:', '', '73%') ?></div>
<div class="row">
  <?= fl('Address:', $dirRes, '45%') ?>
  &nbsp;&nbsp;<?= fl('City:', 'BOGOTA', '35%') ?>
</div>
<div class="row">
  <?= fl('Region/State:', 'CUNDINAMARCA', '38%') ?>
  &nbsp;&nbsp;<?= fl('Zip Code <em>(if in U.S.)</em>:', '', '20%') ?>
</div>
<div class="row">
  <?= fl('Phone Number <em>(including country area code)</em>:', $celular, '25%') ?>
  &nbsp;&nbsp;<?= fl('Email address:', $correo, '38%') ?>
</div>

<!-- SECTION B -->
<div class="sec">SECTION B: NAME, ADDRESS, PHONE NUMBER, AND EMAIL OF RECIPIENT AT U.S. DESTINATION (CONSIGNEE)</div>
<div class="row">☐ &nbsp;<em>Select if information is the same as section A</em></div>
<div class="row"><?= fl('Name:', '', '62%') ?></div>
<div class="row"><?= fl('Organization <em>(if applicable)</em>:', '', '73%') ?></div>
<div class="row"><?= fl('U.S. Address <em>(cannot be PO Box)</em>:', '', '73%') ?></div>
<div class="row">
  <?= fl('City:', '', '24%') ?>
  &nbsp;&nbsp;<?= fl('Region/State:', '', '30%') ?>
  &nbsp;&nbsp;<?= fl('Zip Code <em>(if in U.S.)</em>:', '', '14%') ?>
</div>
<div class="row">
  <?= fl('Phone Number <em>(including country and/or area code)</em>:', '', '25%') ?>
  &nbsp;&nbsp;<?= fl('Email address:', '', '35%') ?>
</div>

<!-- SECTION C -->
<div class="sec">SECTION C: ANIMAL IDENTIFICATION</div>
<table class="t">
<tr>
  <th style="width:16%">ANIMAL NAME</th>
  <th style="width:18%">ISO-COMPLIANT<br>MICROCHIP NUMBER</th>
  <th style="width:14%">ISO-COMPLIANT<br>MICROCHIP<br>IMPLANT DATE*<br><em>(MM/DD/YYYY)</em></th>
  <th style="width:16%">BREED</th>
  <th style="width:8%">SEX</th>
  <th style="width:14%">DATE OF<br>BIRTH OR AGE<br><em>(MM/DD/YYYY)</em></th>
  <th style="width:14%">COLOR/<br>MARKINGS</th>
</tr>
<tr>
  <td><?= htmlspecialchars($mascota) ?></td>
  <td style="text-align:center"><?= htmlspecialchars($microchip) ?></td>
  <td style="text-align:center">&nbsp;</td>
  <td><?= htmlspecialchars($raza) ?></td>
  <td style="text-align:center"><?= htmlspecialchars($sexo) ?></td>
  <td style="text-align:center"><?= htmlspecialchars($edad) ?></td>
  <td><?= htmlspecialchars($color) ?></td>
</tr>
</table>
<p class="fn">*If implant date unknown, input earliest date when ISO-compliant microchip is documented on dog's medical/vaccination records.</p>

<!-- SECTION D -->
<div class="sec">SECTION D: RABIES VACCINE INFORMATION (INCLUDE 3 MOST RECENT RABIES VACCINES, IF APPLICABLE)</div>
<table class="t">
<tr>
  <th style="width:22%">PRODUCT NAME</th>
  <th style="width:20%">MANUFACTURER</th>
  <th style="width:14%">LOT NUMBER</th>
  <th style="width:14%">PRODUCT<br>EXPIRATION DATE<br><em>(MM/DD/YYYY)</em></th>
  <th style="width:15%">DATE OF<br>VACCINATION<br><em>(MM/DD/YYYY)</em></th>
  <th style="width:15%">DATE NEXT<br>VACCINATION IS DUE<br><em>(MM/DD/YYYY)</em></th>
</tr>
<?php foreach ($vacunasRabia as $vr): ?>
<tr>
  <td><?= htmlspecialchars($vr['producto']) ?></td>
  <td>&nbsp;</td>
  <td style="text-align:center"><?= htmlspecialchars($vr['lote']) ?></td>
  <td style="text-align:center">&nbsp;</td>
  <td style="text-align:center"><?= htmlspecialchars($vr['aplicacion']) ?></td>
  <td style="text-align:center"><?= htmlspecialchars($vr['proxima']) ?></td>
</tr>
<?php endforeach; ?>
</table>

<!-- SECTION E -->
<div class="sec">SECTION E: RABIES SEROLOGY INFORMATION (IF AVAILABLE)**</div>
<table class="t">
<tr>
  <th style="width:28%">LABORATORY NAME</th>
  <th style="width:28%">LOCATION OF LABORATORY (COUNTRY)</th>
  <th style="width:15%">DATE SAMPLE<br>WAS COLLECTED<br><em>(MM/DD/YYYY)</em></th>
  <th style="width:15%">DATE<br>SAMPLE WAS<br>TESTED<br><em>(MM/DD/YYYY)</em></th>
  <th style="width:14%">RESULT<br><em>(IU/ML)</em></th>
</tr>
<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
</table>
<p class="fn">**Rabies serology results should be submitted with this form for certification by the official government veterinarian. The official government veterinarian must certify the serology results are from a CDC-approved laboratory.</p>
<p class="fn" style="margin-top:3px;">☐ &nbsp;<em>Select if no serology results are included with this form</em></p>
<p class="fn" style="margin-top:2px;">Dogs entering the United States without a valid rabies serology result or with results less than 0.5 IU/mL are subject to a 28-day quarantine at a CDC-registered animal care facility at the importer's expense. Importers of dogs from DMRVV-free or low-risk countries may, in lieu of serology results, present veterinary records for veterinary services completed in the dog rabies-free or low-risk country at least six months prior to traveling to the United States.</p>

<div class="pub">Public reporting burden of this collection of information is estimated to average 15 minutes per response, including the time for reviewing instructions, searching existing data sources, gathering and maintaining the data needed, and completing and reviewing the collection of information. An agency may not conduct or sponsor, and a person is not required to respond to a collection of information unless it displays a currently valid OMB Control Number. Send comments regarding this burden estimate or any other aspect of this collection of information, including suggestions for reducing this burden to CDC/ATSDR Reports Clearance Officer, 1600 Clifton Road NE, MS D-74, Atlanta, Georgia 30333; ATTN: PRA 0920-1383</div>
<div class="pgfoot">CS350493-A &nbsp; 12/02/2024 <span style="float:right">Page 1 of 2</span></div>

<!-- ═══════════════════════ PÁGINA 2 ═══════════════════════════ -->
<pagebreak/>

<div class="p2-header">
  <strong>ANIMAL NAME:</strong> <?= htmlspecialchars($mascota) ?>
  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
  <strong>ISO-COMPLIANT MICROCHIP NUMBER:</strong> <?= htmlspecialchars($microchip) ?>
</div>

<!-- SECTION F -->
<div class="sec">SECTION F: EXAMINING VETERINARIAN CERTIFICATION STATEMENT</div>
<ol class="stmt">
  <li>I am authorized to practice veterinary medicine in the country of export.</li>
  <li>I have verified the presence of an ISO-compliant microchip in the animal and the microchip number listed on this form is true and correct.</li>
  <li>I have examined the animal presented to me and based on that examination I reasonably believe the animal to be over six months of age.</li>
  <li>I have examined the animal presented to me and find that the age, breed, sex, and description of the animal listed on this form is true and correct, and matches the information documented on the animal's rabies vaccination certificate.</li>
  <li>I reasonably believe, based on my examination of the animal presented to me, that it appears at this time to be healthy and free of infectious or contagious diseases, and to the best of my knowledge and belief, has not been exposed to any infectious or contagious diseases in the past 30 days that would endanger the health of humans or other animals.</li>
  <li>I reasonably believe, based on either having personally administered or supervised the administration of the vaccine, or based on my review of the relevant documentation, that (select one):<br>
    &nbsp;&nbsp;○ &nbsp;The initial rabies vaccine was administered on or after 12 weeks (84 days) of age; or<br>
    &nbsp;&nbsp;○ &nbsp;The rabies vaccine was administered on or after 60 weeks (15 months) of age and the owner had proof of at least one previous rabies vaccination
  </li>
  <li>I have truthfully recorded the animal's complete rabies vaccination history for the past 3 years on this form.</li>
  <li>To the best of my knowledge and belief, the animal listed on this form is not from an area under quarantine for rabies and has not been exposed to rabies in the past 30 days.</li>
  <li>I hereby certify to the best of my knowledge and belief that that the dog's veterinary medical information (Sections C-E) submitted herein is complete and accurate.</li>
</ol>

<div class="p2-sig-title">SIGNATURE OF EXAMINING VETERINARIAN THAT INSPECTED THE DOG:</div>
<div class="p2-certif">I certify that all information provided on this form is true and accurate.</div>

<div class="row"><?= fl('Printed Name and Title:', $VET_NOMBRE, '72%') ?></div>
<div class="row"><?= fl('Address of Veterinarian:', $VET_DIR, '72%') ?></div>
<div class="row">
  <?= fl('City:', $VET_CIUDAD, '22%') ?>
  &nbsp;&nbsp;<?= fl('Region/State:', $VET_REGION, '22%') ?>
  &nbsp;&nbsp;<?= fl('Country:', $VET_PAIS, '18%') ?>
</div>
<div class="row">
  <?= fl('Telephone <em>(including country code)</em>:', $VET_TEL, '22%') ?>
  &nbsp;&nbsp;<?= fl('Email address:', $VET_EMAIL, '35%') ?>
</div>
<div class="row"><?= fl('License Number of Examining Veterinarian:', $VET_LICENCIA, '22%') ?></div>
<div class="row">
  <?= fl('Date <em>(MM/DD/YYYY)</em>:', $HOY, '18%') ?>
  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
  <?= fl("Veterinarian's Signature:", '', '38%') ?>
</div>
<p class="fn" style="margin-top:3px;">The examining veterinarian must be authorized by the competent authority to practice veterinary medicine in the exporting country or be an official government veterinarian.<br>This certificate is valid for travel into the United States for 30 days from the date of examination.</p>

<!-- SECTION G -->
<div class="sec">SECTION G: ENDORSEMENT BY OFFICIAL GOVERNMENT VETERINARIAN IN EXPORTING COUNTRY</div>
<ol class="stmt">
  <li>I certify that the veterinarian listed above holds a valid license to practice veterinary medicine in the country of export.</li>
  <li>I certify I have reviewed all health records, microchip information, vaccination documents, and serology documents (if available) accompanying the animal and they are true and correct to the best of my knowledge and belief.</li>
  <li>Serology documents, if submitted, are from a CDC-approved laboratory.</li>
  <li>I hereby certify to the best of my knowledge and belief that that the dog's veterinary medical information (Sections C-E) submitted herein is complete and accurate.</li>
</ol>

<div class="p2-certif" style="margin-top:5px;">I certify that all information provided on this form is true and accurate.</div>

<div class="g-box">
  <strong>ANIMAL NAME:</strong> <?= htmlspecialchars($mascota) ?>
  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
  <strong>ISO-COMPLIANT MICROCHIP NUMBER:</strong> <?= htmlspecialchars($microchip) ?>
</div>

<div class="row"><?= fl('Printed Name and Title:', '', '72%') ?></div>
<div class="row"><?= fl('Address of Official Government Veterinarian:', '', '60%') ?></div>
<div class="row">
  <?= fl('City:', '', '22%') ?>
  &nbsp;&nbsp;<?= fl('Region/State:', '', '22%') ?>
  &nbsp;&nbsp;<?= fl('Country:', '', '18%') ?>
</div>
<div class="row"><?= fl('Email address:', '', '40%') ?></div>
<div class="row">
  <?= fl('Date <em>(MM/DD/YYYY)</em>:', '', '18%') ?>
  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
  <?= fl("Official Government Veterinarian's Signature:", '', '38%') ?>
</div>

<table class="seal-box">
<tr>
  <td style="width:62%; vertical-align:bottom; font-size:7.8pt; font-style:italic; padding-bottom:4px;">
    Upload electronic government seal or affix wet seal here (<em>required</em>):
  </td>
  <td class="seal-cell">UPLOAD SEAL</td>
</tr>
</table>

<div class="pgfoot" style="margin-top:10px;"><span style="float:right">Page 2 of 2</span></div>

</body>
</html>
<?php
$html = ob_get_clean();

$mpdf = new \Mpdf\Mpdf([
    'margin_top'    => 10,
    'margin_bottom' => 8,
    'margin_left'   => 11,
    'margin_right'  => 11,
    'format'        => 'Letter',
]);
$mpdf->WriteHTML($html);

$filename = 'CDC_' . preg_replace('/[^A-Z0-9_]/', '_', $mascota) . '_' . date('Ymd') . '.pdf';
$mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
