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

$db = getDB();
$stmt = $db->prepare('SELECT * FROM certificados WHERE sheet_row = ?');
$stmt->execute([$rowNum]);
$med = $stmt->fetch();
if (!$med) { header("Location: certificado.php?row=$rowNum"); exit; }

// Helper multibyte uppercase (strtoupper no maneja Ñ, tildes, etc.)
function uc(string $s): string { return mb_strtoupper($s, 'UTF-8'); }

// Fecha en formato MM/DD/YYYY para Estados Unidos
function formatFechaUSA(?string $fecha): string {
    if (!$fecha) return '';
    $ts = strtotime($fecha);
    return $ts ? date('m/d/Y', $ts) : $fecha;
}

// ─── Datos del formulario ─────────────────────────────────────────
$tipo         = col($row, 'tipo_cert');
$esInt        = esInternacional($tipo);
$tipoEuropa   = str_contains(strtoupper($tipo), 'EUROPA');

$ciudadOrigen = col($row, 'ciudad_origen');
$ciudadDest   = col($row, 'ciudad_destino');
$ciudadDest2  = col($row, 'ciudad_destino2');
$destFinal    = $ciudadDest2 ?: $ciudadDest;

function ciudadAPais(string $ciudad): string {
    $ciudad = strtolower(trim(preg_replace('/[^a-záéíóúüñ\s]/ui', '', $ciudad)));
    $mapa = [
        'ciudad de mexico'=>'México','ciudad de méxico'=>'México','cdmx'=>'México',
        'cancun'=>'México','cancún'=>'México','guadalajara'=>'México','monterrey'=>'México',
        'tijuana'=>'México','puebla'=>'México','merida'=>'México','mérida'=>'México',
        'queretaro'=>'México','querétaro'=>'México','leon'=>'México','veracruz'=>'México',
        'acapulco'=>'México','mazatlan'=>'México','los cabos'=>'México',
        'playa del carmen'=>'México','tulum'=>'México','chihuahua'=>'México',
        'ciudad de panama'=>'Panamá','ciudad de panamá'=>'Panamá','panama city'=>'Panamá',
        'panama'=>'Panamá','panamá'=>'Panamá','tocumen'=>'Panamá',
        'buenos aires'=>'Argentina','cordoba'=>'Argentina','córdoba'=>'Argentina',
        'rosario'=>'Argentina','mendoza'=>'Argentina','salta'=>'Argentina',
        'bariloche'=>'Argentina','mar del plata'=>'Argentina','tucuman'=>'Argentina',
        'santiago'=>'Chile','valparaiso'=>'Chile','valparaíso'=>'Chile',
        'concepcion'=>'Chile','concepción'=>'Chile','antofagasta'=>'Chile',
        'vina del mar'=>'Chile','viña del mar'=>'Chile','temuco'=>'Chile',
        'lima'=>'Perú','cusco'=>'Perú','arequipa'=>'Perú','trujillo'=>'Perú',
        'iquitos'=>'Perú','piura'=>'Perú','chiclayo'=>'Perú',
        'quito'=>'Ecuador','guayaquil'=>'Ecuador','cuenca'=>'Ecuador',
        'manta'=>'Ecuador','loja'=>'Ecuador','ambato'=>'Ecuador',
        'la paz'=>'Bolivia','santa cruz'=>'Bolivia','cochabamba'=>'Bolivia',
        'sucre'=>'Bolivia','oruro'=>'Bolivia','potosi'=>'Bolivia','potosí'=>'Bolivia',
        'caracas'=>'Venezuela','maracaibo'=>'Venezuela','valencia'=>'Venezuela',
        'montevideo'=>'Uruguay','punta del este'=>'Uruguay',
        'asuncion'=>'Paraguay','asunción'=>'Paraguay','ciudad del este'=>'Paraguay',
        'la habana'=>'Cuba','habana'=>'Cuba','havana'=>'Cuba','varadero'=>'Cuba',
        'santo domingo'=>'República Dominicana','punta cana'=>'República Dominicana',
        'san jose'=>'Costa Rica','san josé'=>'Costa Rica',
        'ciudad de guatemala'=>'Guatemala','antigua'=>'Guatemala',
        'tegucigalpa'=>'Honduras','san pedro sula'=>'Honduras',
        'san salvador'=>'El Salvador','managua'=>'Nicaragua',
        'kingston'=>'Jamaica','montego bay'=>'Jamaica',
        'port of spain'=>'Trinidad y Tobago',
        'madrid'=>'España','barcelona'=>'España','sevilla'=>'España',
        'bilbao'=>'España','malaga'=>'España','málaga'=>'España',
        'miami'=>'Estados Unidos','new york'=>'Estados Unidos','nueva york'=>'Estados Unidos',
        'los angeles'=>'Estados Unidos','houston'=>'Estados Unidos',
        'chicago'=>'Estados Unidos','orlando'=>'Estados Unidos',
        'toronto'=>'Canadá','montreal'=>'Canadá','montréal'=>'Canadá',
        'vancouver'=>'Canadá','calgary'=>'Canadá','ottawa'=>'Canadá',
        'edmonton'=>'Canadá','quebec'=>'Canadá','québec'=>'Canadá',
        'winnipeg'=>'Canadá','hamilton'=>'Canadá','victoria'=>'Canadá',
        'recife'=>'Brasil','sao paulo'=>'Brasil','são paulo'=>'Brasil',
        'rio de janeiro'=>'Brasil','brasilia'=>'Brasil','brasília'=>'Brasil',
        'fortaleza'=>'Brasil','salvador'=>'Brasil','belo horizonte'=>'Brasil',
    ];
    if (isset($mapa[$ciudad])) return $mapa[$ciudad];
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
    $ciudadParaPais = $ciudadDest2 ?: $ciudadDest;
    $paisDest = ciudadAPais($ciudadParaPais);
}

$esUSA    = ($paisDest === 'Estados Unidos');
$esCanada = ($paisDest === 'Canadá');
$esChile  = ($paisDest === 'Chile');

$nombreTutor  = mb_convert_case(mb_strtolower(col($row, 'nombre_tutor'), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
$tipoDoc      = col($row, 'tipo_doc');
$numDoc       = col($row, 'num_documento');
$celular      = col($row, 'celular');
$correo       = col($row, 'correo');
$dirRes       = col($row, 'dir_residencia');
$mascota      = col($row, 'nombre_mascota');
$microchip    = col($row, 'microchip');
$especie      = col($row, 'especie');
$raza         = (!empty($med['raza_corregida'])) ? $med['raza_corregida'] : col($row, 'raza');
$edad         = col($row, 'edad');
$sexo         = col($row, 'sexo');
$peso         = col($row, 'peso');
$color        = col($row, 'color');
$fechaViajeRaw = col($row, 'fecha_viaje');
$fechaViaje    = $esUSA
    ? formatFechaUSA($fechaViajeRaw)
    : strtoupper($fechaViajeRaw);
$aerolinea    = col($row, 'aerolinea');

$fechaEmision = $med['fecha_emision'];
$docLabel     = strtoupper($tipoDoc);

// Microchip display
$chip     = trim($microchip ?? '');
$soloNum  = preg_replace('/\D/', '', $chip);
$noAplica = !$chip || preg_match('/no\s*(aplica|tiene|posee|cuenta|chip)|sin\s*chip|n\/a/i', $chip) || $chip === '-';
if ($noAplica) {
    $chipDisplay = 'N/A';
} elseif (strlen($soloNum) === 15) {
    $chipDisplay = implode(' ', str_split($soloNum, 1));
} else {
    $chipDisplay = $chip;
}

// Vacunas
$vacunas = [];
for ($i = 1; $i <= 5; $i++) {
    $med_name = trim($med["vacuna{$i}_medicamento"] ?? '');
    if (!$med_name) continue;
    $usos = array_filter(array_map('trim', explode('|', $med["vacuna{$i}_uso"] ?? '')));
    if (empty($usos)) $usos = [''];
    foreach ($usos as $uso) {
        $vacunas[] = [
            'aplicacion'  => $esUSA
                ? formatFechaUSA($med["vacuna{$i}_aplicacion"])
                : formatFechaCorta($med["vacuna{$i}_aplicacion"]),
            'proxima'     => $esUSA
                ? formatFechaUSA($med["vacuna{$i}_proxima"])
                : formatFechaCorta($med["vacuna{$i}_proxima"]),
            'medicamento' => strtoupper($med_name),
            'lote'        => strtoupper($med["vacuna{$i}_lote"] ?? ''),
            'uso'         => strtoupper($uso),
        ];
    }
}

// Para USA y Canadá: la 3ª hoja reutiliza VAC1 y debe mostrar SOLO la vacuna de rabia.
// Reordenamos el array poniendo rabia primero para que VAC1 = rabia en ambas páginas.
if ($esUSA || $esCanada) {
    $idxRabia = null;
    foreach ($vacunas as $idx => $v) {
        $enMed = stripos($v['medicamento'], 'RABIA') !== false || stripos($v['medicamento'], 'RABIES') !== false
                 || stripos($v['medicamento'], 'RABI') !== false;
        $enUso = stripos($v['uso'], 'RABIA') !== false || stripos($v['uso'], 'RABIES') !== false;
        if ($enMed || $enUso) {
            $idxRabia = $idx;
            break;
        }
    }
    if ($idxRabia !== null && $idxRabia !== 0) {
        $rabiaEntry = array_splice($vacunas, $idxRabia, 1);
        array_unshift($vacunas, $rabiaEntry[0]);
    }
}

// Foto mascota
$fotoMascotaId   = driveFileId(col($row, 'foto_mascota'));
$fotoMascotaPath = $fotoMascotaId ? downloadDriveImage($fotoMascotaId) : null;

// ─── Mes y año de emisión ─────────────────────────────────────────
$mesesNombres = [
    1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
    7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'
];
$tsEmision   = $fechaEmision ? strtotime($fechaEmision) : time();
$mesesIngles = [
    1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
    7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'
];
$diaEmision      = (int)date('j', $tsEmision);
$mesEmision      = $mesesNombres[(int)date('n', $tsEmision)];
$mesEmisionEn    = $mesesIngles[(int)date('n', $tsEmision)];
$anioEmision     = date('Y', $tsEmision);

// ─── Tabla de reemplazos ──────────────────────────────────────────
$replacements = [
    'XXFECHAXX'         => $fechaViaje,
    'XXCIUDADORIGENXX'  => uc($ciudadOrigen),
    'XXPAISDESTXX'      => uc($paisDest),
    'XXCIUDADDESTXX'    => uc($destFinal),
    'XXNOMBRETUTORXX'   => uc($nombreTutor),
    'XXNUMDOCXX'        => uc($numDoc),
    'XXCELULARXX'       => $celular,
    'XXDIRECCIONXX'     => uc($dirRes),
    'XXNOMBREMASCOTAXX' => uc($mascota),
    'XXCHIPXX'          => $chipDisplay,
    'XXESPECIEXX'       => uc($especie),
    'XXRAZAXX'          => uc($raza),
    'XXEDADXX'          => uc($edad),
    'XXSEXOXX'          => uc($sexo),
    'XXPESOXX'          => uc($peso),
    'XXCOLORXX'         => uc($color),
    'XXCORREOXX'        => strtoupper($correo),
    'XXDIAEMISIONXX'    => $diaEmision,
    'XXMESEMISIONXX'    => $mesEmision,
    'XXMESEMISIONENXX'  => $mesEmisionEn,
    'XXANIOEMISIONXX'   => $anioEmision,
];

// Vacunas (hasta 5 filas en el template — slots vacíos se reemplazan con '' para que queden en blanco)
for ($i = 1; $i <= 5; $i++) {
    $v = $vacunas[$i - 1] ?? [];
    $replacements["XXVAC{$i}APLIXX"] = $v['aplicacion']  ?? '';
    $replacements["XXVAC{$i}PROXXX"] = $v['proxima']     ?? '';
    $replacements["XXVAC{$i}MEDXX"]  = $v['medicamento'] ?? '';
    $replacements["XXVAC{$i}LOTEXX"] = $v['lote']        ?? '';
    $replacements["XXVAC{$i}USOXX"]  = $v['uso']         ?? '';
}

// Desparasitación interna
$replacements['XXDESI1FECHAXX'] = $med['desint_fecha']
    ? ($esUSA ? formatFechaUSA($med['desint_fecha']) : uc(formatFechaCorta($med['desint_fecha']))) : '';
$replacements['XXDESI1PRODXX']  = uc($med['desint_producto'] ?? '');
$replacements['XXDESI1PRINXX']  = uc($med['desint_principio_activo'] ?? '');
$replacements['XXDESI1LOTEXX']  = uc($med['desint_lote'] ?? '');
$replacements['XXDESI1REGXX']   = uc($med['desint_registro_ica'] ?? '');

// Desparasitación externa
$replacements['XXDESE1FECHAXX'] = $med['desext_fecha']
    ? ($esUSA ? formatFechaUSA($med['desext_fecha']) : uc(formatFechaCorta($med['desext_fecha']))) : '';
$replacements['XXDESE1PRODXX']  = uc($med['desext_producto'] ?? '');
$replacements['XXDESE1PRINXX']  = uc($med['desext_principio_activo'] ?? '');
$replacements['XXDESE1LOTEXX']  = uc($med['desext_lote'] ?? '');
$replacements['XXDESE1REGXX']   = uc($med['desext_registro_ica'] ?? '');

// ─── Función principal: reemplaza marcadores en el XML del docx ───
// Word a veces parte el texto de un marcador en varios <w:r> dentro
// del mismo <w:p>. Por eso concatenamos el texto de todos los runs
// del párrafo, buscamos el marcador, y si existe reconstruimos el
// párrafo con un solo run que contiene el texto reemplazado.
// Regex estricta para <w:t>: solo matchea <w:t> o <w:t atributos>
// NO matchea <w:tab>, <w:tbl>, etc.
// Patrón: <w:t seguido de > o espacio, nunca letra/dígito
define('WT_REGEX', '/<w:t((?:\s[^>]*)?)>([^<]*)<\/w:t>/');

function replacePlaceholders(string $xml, array $replacements): string
{
    return preg_replace_callback(
        '/<w:p\b[^>]*>.*?<\/w:p>/s',
        function ($m) use ($replacements) {
            $para = $m[0];

            // Concatenar texto de todos los <w:t> reales del párrafo
            preg_match_all(WT_REGEX, $para, $tm);
            $allText = implode('', $tm[2]);

            // Si no hay ningún marcador, devolver sin cambios
            $found = false;
            foreach ($replacements as $ph => $_) {
                if (str_contains($allText, $ph)) { $found = true; break; }
            }
            if (!$found) return $para;

            // Aplicar reemplazos
            foreach ($replacements as $ph => $val) {
                $allText = str_replace($ph, $val, $allText);
            }

            // Poner todo el texto reemplazado en el primer <w:t>, vaciar los demás
            $firstDone = false;
            $result = preg_replace_callback(
                WT_REGEX,
                function ($wt) use (&$allText, &$firstDone) {
                    if (!$firstDone) {
                        $firstDone = true;
                        $attrs = $wt[1];
                        if (!str_contains($attrs, 'xml:space')) {
                            $attrs .= ' xml:space="preserve"';
                        }
                        return '<w:t' . $attrs . '>'
                            . htmlspecialchars($allText, ENT_XML1, 'UTF-8')
                            . '</w:t>';
                    }
                    return '<w:t' . $wt[1] . '></w:t>';
                },
                $para
            );

            // Limpiar formato de runs: quitar fondo oscuro, color claro, forzar Arial 11 negro
            $result = preg_replace_callback(
                '/<w:rPr>.*?<\/w:rPr>/s',
                function ($rpr) {
                    $r = $rpr[0];
                    // Shading — soporta tanto self-closing como con cierre
                    $r = preg_replace('/<w:shd\b[^>]*\/?>(?:<\/w:shd>)?/', '<w:shd w:val="clear" w:color="auto" w:fill="auto"/>', $r);
                    // Color de texto → negro
                    $r = preg_replace('/<w:color\b[^>]*\/?>(?:<\/w:color>)?/', '<w:color w:val="000000"/>', $r);
                    if (!str_contains($r, '<w:color')) {
                        $r = str_replace('<w:rPr>', '<w:rPr><w:color w:val="000000"/>', $r);
                    }
                    // Fuente → Arial
                    $r = preg_replace('/<w:rFonts\b[^>]*\/?>/', '<w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/>', $r);
                    if (!str_contains($r, '<w:rFonts')) {
                        $r = str_replace('<w:rPr>', '<w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/>', $r);
                    }
                    // Quitar highlight
                    $r = preg_replace('/<w:highlight\b[^>]*\/>/', '', $r);
                    return $r;
                },
                $result
            );

            return $result;
        },
        $xml
    );
}

// ─── Función: convierte cualquier imagen a JPEG cuadrado (center-crop) ───
function imageToJpeg(string $path): ?string
{
    $bytes = file_get_contents($path, false, null, 0, 12);
    if ($bytes === false) return null;

    $im = null;
    if (str_starts_with($bytes, "\xFF\xD8\xFF"))      $im = @imagecreatefromjpeg($path);
    elseif (str_starts_with($bytes, "\x89PNG"))       $im = @imagecreatefrompng($path);
    elseif (str_starts_with($bytes, 'RIFF'))          $im = @imagecreatefromwebp($path);
    elseif (str_starts_with($bytes, 'GIF'))           $im = @imagecreatefromgif($path);

    if (!$im) return null;

    $w = imagesx($im);
    $h = imagesy($im);

    // Fit con padding blanco: la foto completa centrada en un canvas cuadrado
    $out = 400;
    $sq = imagecreatetruecolor($out, $out);
    imagefill($sq, 0, 0, imagecolorallocate($sq, 255, 255, 255));

    // Escalar manteniendo proporción
    $scale = min($out / $w, $out / $h);
    $dstW  = (int)($w * $scale);
    $dstH  = (int)($h * $scale);
    $dstX  = (int)(($out - $dstW) / 2);
    $dstY  = (int)(($out - $dstH) / 2);
    imagecopyresampled($sq, $im, $dstX, $dstY, 0, 0, $dstW, $dstH, $w, $h);
    imagedestroy($im);

    ob_start();
    imagejpeg($sq, null, 72);
    imagedestroy($sq);
    return ob_get_clean();
}

// ─── Seleccionar template según tipo ─────────────────────────────
if ($esUSA) {
    $templateFile = __DIR__ . '/templates/SKYPETS_INTERNACIONAL_ESTADOS UNIDOS.docx';
} elseif ($esCanada) {
    $templateFile = __DIR__ . '/templates/SKYPETS_INTERNACIONAL_CANADA.docx';
} elseif ($esChile) {
    $templateFile = __DIR__ . '/templates/SKYPETS_INTERNACIONAL_CHILE.docx';
} elseif ($esInt) {
    $templateFile = __DIR__ . '/templates/SKYPETS_INTERNACIONAL.docx';
} else {
    $templateFile = __DIR__ . '/templates/SKYPETS_NACIONAL.docx';
}

if (!file_exists($templateFile)) {
    die('Template no encontrado: ' . basename($templateFile));
}

// ─── Copiar template a archivo temporal ──────────────────────────
$tmpFile = tempnam(sys_get_temp_dir(), 'sky_') . '.docx';
copy($templateFile, $tmpFile);

$zip = new ZipArchive();
if ($zip->open($tmpFile) !== true) {
    die('Error abriendo el template docx');
}

// Procesar document.xml
$docXml = $zip->getFromName('word/document.xml');

// Proteger <w:txbxContent> antes de replacePlaceholders:
// los text boxes flotantes contienen sus propios <w:p> anidados que rompen
// la regex de párrafos y corrompen la estructura del documento padre.
$textBoxes = [];
$docXml = preg_replace_callback(
    '/<w:txbxContent>.*?<\/w:txbxContent>/s',
    function ($m) use (&$textBoxes) {
        $token = "\x00TXBX" . count($textBoxes) . "\x00";
        $textBoxes[$token] = $m[0];
        return $token;
    },
    $docXml
);
$docXml = replacePlaceholders($docXml, $replacements);
foreach ($textBoxes as $token => $content) {
    $docXml = str_replace($token, $content, $docXml);
}

// Limpiar shading oscuro a nivel de celda (<w:tcPr>) en todo el documento
// (los placeholders en celdas de tabla llevan shading tanto en run como en celda)
$docXml = preg_replace_callback(
    '/<w:tcPr>.*?<\/w:tcPr>/s',
    function ($tc) {
        return preg_replace(
            '/<w:shd\b[^>]*\/?>(?:<\/w:shd>)?/',
            '<w:shd w:val="clear" w:color="auto" w:fill="auto"/>',
            $tc[0]
        );
    },
    $docXml
);

$zip->addFromString('word/document.xml', $docXml);

// Reemplazar foto de mascota (rId7 → word/media/image1.jpg)
// rId7 es el placeholder dentro del text box de la sección "Datos del Paciente"
if ($fotoMascotaPath) {
    $jpegData = imageToJpeg($fotoMascotaPath);
    if ($jpegData) {
        $zip->addFromString('word/media/image1.jpg', $jpegData);
    }
    @unlink($fotoMascotaPath);
}

$zip->close();

// ─── Marcar como generado ─────────────────────────────────────────
$db->prepare("UPDATE certificados SET status='generado' WHERE sheet_row = ?")
   ->execute([$rowNum]);

// ─── Enviar archivo al navegador ──────────────────────────────────
$mascotaFile = strtoupper(preg_replace('/\s+/', '_', $mascota));
$filename    = 'SKYPETS_' . $mascotaFile . '_' . date('Ymd') . '.docx';

header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($tmpFile));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');

readfile($tmpFile);
unlink($tmpFile);
