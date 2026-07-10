<?php
require_once __DIR__ . '/auth.php';
requireLogin();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/google_api.php';
require_once __DIR__ . '/helpers.php';

try {
    $rows = getSheetRows();
} catch (Throwable $e) {
    error_log('dashboard.php getSheetRows: ' . $e->getMessage());
    http_response_code(503);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>SkyPets — Certificados</title></head><body style="font-family:sans-serif;text-align:center;padding:60px;">'
       . '<h1>No se pudo cargar el listado</h1>'
       . '<p>Google Sheets no respondió a tiempo. Intenta recargar en unos segundos.</p>'
       . '<button onclick="location.reload()">Volver a cargar</button>'
       . '</body></html>';
    exit;
}
$db   = getDB();

// ── Acción: archivar vencidos ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'archivar_vencidos') {
    $stmt2 = $db->query('SELECT sheet_row, status FROM certificados');
    foreach ($stmt2->fetchAll() as $r) {
        if ($r['status'] !== 'generado') {
            $fecha = '';
            foreach ($rows as $i => $row) {
                if ($i + 2 === (int)$r['sheet_row']) {
                    $fecha = col($row, 'fecha_viaje');
                    break;
                }
            }
            if ($fecha && diasHastaViajeStatic($fecha) < 0) {
                $db->prepare("UPDATE certificados SET status='archivado' WHERE sheet_row=?")->execute([$r['sheet_row']]);
            }
        }
    }
    header('Location: dashboard.php?tab=pendientes');
    exit;
}

function diasHastaViajeStatic(string $fecha): int {
    if (!$fecha) return 9999;
    $fecha = trim($fecha);
    $formatos = ['d/m/Y','j/n/Y','d-m-Y','j-M-Y','d-M-Y','Y-m-d','j/m/Y','d/n/Y'];
    $d = null;
    foreach ($formatos as $fmt) {
        $d = DateTime::createFromFormat($fmt, $fecha);
        if ($d) break;
        $d = null;
    }
    if (!$d) { $ts = strtotime($fecha); if ($ts) $d = (new DateTime())->setTimestamp($ts); }
    if (!$d) return 9999;
    $hoy = new DateTime('today');
    $diff = (int)$hoy->diff($d)->days;
    return $d < $hoy ? -$diff : $diff;
}

// Cargar estados guardados
$estados = [];
$portalSubidos = [];
$stmt = $db->query('SELECT sheet_row, status, portal_uploaded_at FROM certificados');
foreach ($stmt->fetchAll() as $r) {
    $estados[$r['sheet_row']] = $r['status'];
    $portalSubidos[$r['sheet_row']] = $r['portal_uploaded_at'];
}

// Filtros
$filtroTipo   = $_GET['tipo']   ?? '';
$filtroAsesor = $_GET['asesor'] ?? '';
$filtroBuscar = strtolower(trim($_GET['q'] ?? ''));
$tabActiva    = $_GET['tab']    ?? 'pendientes';

$asesores = array_unique(array_filter(array_map(fn($r) => col($r, 'asesor'), $rows)));
sort($asesores);

// ── Calcular días hasta viaje (acepta varios formatos) ───────────
function diasHastaViaje(string $fecha): int {
    if (!$fecha) return 9999;
    $fecha = trim($fecha);
    $formatos = ['d/m/Y', 'j/n/Y', 'd-m-Y', 'j-M-Y', 'd-M-Y', 'Y-m-d', 'j/m/Y', 'd/n/Y'];
    $d = null;
    foreach ($formatos as $fmt) {
        $d = DateTime::createFromFormat($fmt, $fecha);
        if ($d && $d->format($fmt) !== false) break;
        $d = null;
    }
    // Último intento con strtotime
    if (!$d) {
        $ts = strtotime($fecha);
        if ($ts) $d = (new DateTime())->setTimestamp($ts);
    }
    if (!$d) return 9999;
    $hoy = new DateTime('today');
    $diff = (int)$hoy->diff($d)->days;
    return $d < $hoy ? -$diff : $diff;
}

function urgenciaBadge(int $dias, bool $esInt = false): string {
    if ($dias < 0) return '<span class="urg-badge urg-vencido">Venció</span>';
    $limRojo    = $esInt ? 10 : 3;
    $limAmarillo = $esInt ? 14 : 6;
    if ($dias <= $limRojo)    return '<span class="urg-badge urg-critico">¡' . $dias . 'd!</span>';
    if ($dias <= $limAmarillo) return '<span class="urg-badge urg-urgente">' . $dias . ' días</span>';
    return '<span class="urg-badge urg-ok">' . $dias . ' días</span>';
}

function urgenciaPrioridad(int $dias, bool $esInt = false): int {
    if ($dias < 0) return 3; // vencidos al final
    $limRojo     = $esInt ? 10 : 3;
    $limAmarillo = $esInt ? 14 : 6;
    if ($dias <= $limRojo)    return 0; // rojo primero
    if ($dias <= $limAmarillo) return 1; // amarillo segundo
    return 2; // verde último
}

// ── Construir y clasificar filas ─────────────────────────────────
$pendientes = [];
$generados  = [];

foreach ($rows as $i => $row) {
    $rowNum  = $i + 2;
    if ($rowNum === 2) continue; // fila de ejemplo/encabezado
    $tipo    = col($row, 'tipo_cert');
    $asesor  = col($row, 'asesor');
    $tutor   = col($row, 'nombre_tutor');
    $mascota = col($row, 'nombre_mascota');
    $origen  = col($row, 'ciudad_origen');
    $destino = col($row, 'ciudad_destino');
    $fecha   = col($row, 'fecha_viaje');
    $status  = $estados[$rowNum] ?? 'pendiente';

    if ($filtroTipo   && !str_contains(strtoupper($tipo), strtoupper($filtroTipo))) continue;
    if ($filtroAsesor && $asesor !== $filtroAsesor) continue;
    if ($filtroBuscar && !str_contains(strtolower($tutor . $mascota), $filtroBuscar)) continue;

    $dias   = diasHastaViaje($fecha);
    $esInt  = esInternacional($tipo);
    $prio   = urgenciaPrioridad($dias, $esInt);
    $portalUploadedAt = $portalSubidos[$rowNum] ?? null;
    $entry  = compact('rowNum','tipo','asesor','tutor','mascota','origen','destino','fecha','status','dias','esInt','prio','portalUploadedAt');

    if ($status === 'generado') {
        $generados[] = $entry;
    } else {
        $pendientes[] = $entry;
    }
}

// Ordenar pendientes: rojo → amarillo → verde → vencidos; dentro de cada grupo, menor días primero
usort($pendientes, function($a, $b) {
    if ($a['prio'] !== $b['prio']) return $a['prio'] <=> $b['prio'];
    $da = $a['dias'] < 0 ? 9999 + abs($a['dias']) : $a['dias'];
    $db = $b['dias'] < 0 ? 9999 + abs($b['dias']) : $b['dias'];
    return $da <=> $db;
});

// Ordenar generados: más reciente fecha de viaje primero
usort($generados, fn($a,$b) => $a['dias'] <=> $b['dias']);

$totalPend = count(array_filter($pendientes, fn($e) => $e['dias'] >= 0));
$totalGen  = count($generados);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard — SkyPets Certificados</title>
<link rel="icon" type="image/png" href="/assets/images/logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<style>
/* ── Tabs ─────────────────────────────────── */
.tabs {
    display: flex;
    gap: 6px;
    margin: 18px 0 0;
    border-bottom: 2px solid #FFE7A6;
}
.tab-btn {
    padding: 9px 22px;
    border-radius: 12px 12px 0 0;
    border: none;
    background: rgba(255,250,236,0.7);
    font-family: 'Poppins', sans-serif;
    font-size: 14px;
    font-weight: 500;
    color: #6B5540;
    cursor: pointer;
    transition: background 0.18s, color 0.18s;
    display: flex;
    align-items: center;
    gap: 7px;
}
.tab-btn.active {
    background: #FF7600;
    color: #fff;
    font-weight: 600;
}
.tab-btn .tab-count {
    background: rgba(255,255,255,0.28);
    border-radius: 20px;
    padding: 1px 8px;
    font-size: 12px;
    font-weight: 600;
}
.tab-btn.active .tab-count { background: rgba(255,255,255,0.3); }
.tab-panel { display: none; }
.tab-panel.active { display: block; }

/* ── Urgencia badges ─────────────────────── */
.urg-badge {
    display: inline-block;
    padding: 2px 9px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
}
.urg-critico  { background: #E5443A; color: #fff; animation: pulse 1.2s infinite; }
.urg-urgente  { background: #FFBC00; color: #2B2418; }
.urg-ok       { background: #e8f5d0; color: #4a7a10; }
.urg-vencido  { background: #ccc; color: #555; }

@keyframes pulse {
    0%,100% { opacity:1; } 50% { opacity:.65; }
}

/* Filas críticas (≤3 días) */
.row-critico td { background: #fff5f5 !important; }
.row-urgente td { background: #fffde8 !important; }

/* Botones de acción en tabla */
.action-btns { display: flex; flex-wrap: wrap; gap: 4px; align-items: center; }
.action-btns .btn-ver { margin: 0 !important; white-space: nowrap; font-size: 12px; padding: 5px 10px; }
.btn-doc { background: rgba(0,141,131,0.85) !important; }

/* Grupo de acciones (fila Generados): misma píldora de siempre, en dos filas */
.action-group { display: flex; flex-direction: column; align-items: flex-start; gap: 5px; }
.action-row { display: flex; flex-wrap: nowrap; gap: 5px; }
.action-row .btn-ver { margin: 0 !important; white-space: nowrap; font-size: 12px; padding: 5px 10px; }
.btn-portal {
  display: inline-block; white-space: nowrap;
  padding: 5px 10px; border-radius: 100px;
  background: rgba(0,141,131,0.85); color: #fff;
  text-decoration: none; font-size: 12px; font-weight: 500;
  box-shadow: 0 2px 8px rgba(0,141,131,0.28);
  transition: transform 0.15s, box-shadow 0.15s;
}
.btn-portal:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(0,141,131,0.42); }

/* Toolbar sobre tabla pendientes */
.pend-toolbar {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 0 4px;
}
.btn-toggle-venc {
    padding: 6px 14px; border-radius: 20px; border: 1.5px solid #ccc;
    background: #fff; font-family: 'Poppins', sans-serif; font-size: 12px;
    cursor: pointer; color: #6B5540; transition: all 0.18s;
}
.btn-toggle-venc.active { background: #6B5540; color: #fff; border-color: #6B5540; }
.btn-archivar {
    padding: 6px 14px; border-radius: 20px; border: 1.5px solid #E5443A;
    background: #fff; font-family: 'Poppins', sans-serif; font-size: 12px;
    cursor: pointer; color: #E5443A; transition: all 0.18s;
}
.btn-archivar:hover { background: #E5443A; color: #fff; }
.row-vencido-js { /* marcador para JS */ }

/* Scroll horizontal en tabla cuando la ventana es angosta */
.table-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.main-table {
    min-width: 820px;
}
</style>
</head>
<body>
<nav class="topbar">
    <img src="assets/images/logo.png" alt="SkyPets" height="36">
    <span class="topbar-title">Certificados</span>
    <a href="logout.php" class="btn-logout">Salir</a>
</nav>

<div class="container">
    <div class="filters">
        <form method="GET" class="filter-form">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tabActiva) ?>">
            <input type="text" name="q" placeholder="Buscar tutor o mascota…" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            <select name="tipo">
                <option value="">Todos los tipos</option>
                <option value="NACIONAL"      <?= $filtroTipo === 'NACIONAL'      ? 'selected' : '' ?>>Nacional</option>
                <option value="INTERNACIONAL" <?= $filtroTipo === 'INTERNACIONAL' ? 'selected' : '' ?>>Internacional</option>
            </select>
            <select name="asesor">
                <option value="">Todos los asesores</option>
                <?php foreach ($asesores as $a): ?>
                    <option <?= $filtroAsesor === $a ? 'selected' : '' ?>><?= htmlspecialchars($a) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-filter">Filtrar</button>
            <a href="dashboard.php" class="btn-clear">Limpiar</a>
        </form>
    </div>

    <div class="stats-row">
        <div class="stat-card"><span class="stat-num"><?= $totalPend + $totalGen ?></span><span>Total</span></div>
        <div class="stat-card stat-ok"><span class="stat-num"><?= $totalGen ?></span><span>Generados</span></div>
        <div class="stat-card stat-pending"><span class="stat-num"><?= $totalPend ?></span><span>Pendientes</span></div>
    </div>

    <!-- Pestañas -->
    <div class="tabs">
        <button class="tab-btn <?= $tabActiva === 'pendientes' ? 'active' : '' ?>"
                onclick="switchTab('pendientes', this)">
            Pendientes / En proceso
            <span class="tab-count"><?= $totalPend ?></span>
        </button>
        <button class="tab-btn <?= $tabActiva === 'generados' ? 'active' : '' ?>"
                onclick="switchTab('generados', this)">
            Generados
            <span class="tab-count"><?= $totalGen ?></span>
        </button>
    </div>

    <!-- Panel: Pendientes -->
    <div id="tab-pendientes" class="tab-panel <?= $tabActiva === 'pendientes' ? 'active' : '' ?>">
        <div class="pend-toolbar">
            <button class="btn-toggle-venc active" id="btnToggleVenc" onclick="toggleVencidos(this)">
                Mostrar vencidos
            </button>
            <form method="POST" style="display:inline;" onsubmit="return confirm('¿Archivar todos los vencidos? No aparecerán más en pendientes.')">
                <input type="hidden" name="action" value="archivar_vencidos">
                <button type="submit" class="btn-archivar">Archivar vencidos permanentemente</button>
            </form>
        </div>
        <div class="table-wrap">
            <table class="main-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Viaje</th>
                        <th>Urgencia</th>
                        <th>Mascota</th>
                        <th>Tutor</th>
                        <th>Tipo</th>
                        <th>Ruta</th>
                        <th>Asesor</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($pendientes as $e):
                    $esVencido = $e['dias'] < 0;
                    $rowClass  = match($e['prio']) {
                        0 => 'row-critico',
                        1 => 'row-urgente',
                        default => 'row-' . $e['status'],
                    };
                    if ($esVencido) $rowClass .= ' row-vencido-js';
                ?>
                    <tr class="<?= $rowClass ?>">
                        <td class="td-num"><?= $e['rowNum'] ?></td>
                        <td><?= htmlspecialchars($e['fecha']) ?></td>
                        <td><?= urgenciaBadge($e['dias'], $e['esInt']) ?></td>
                        <td class="td-bold"><?= htmlspecialchars($e['mascota']) ?></td>
                        <td><?= htmlspecialchars($e['tutor']) ?></td>
                        <td><span class="tipo-badge <?= $e['esInt'] ? 'tipo-int' : 'tipo-nac' ?>"><?= htmlspecialchars(tipoLabel($e['tipo'])) ?></span></td>
                        <td><?= htmlspecialchars($e['origen']) ?> → <?= htmlspecialchars($e['destino']) ?></td>
                        <td><?= htmlspecialchars($e['asesor']) ?></td>
                        <td><?= statusLabel($e['status']) ?></td>
                        <td><div class="action-btns"><a href="certificado.php?row=<?= $e['rowNum'] ?>" class="btn-ver">Ver / Generar</a></div></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($pendientes)): ?>
                    <tr><td colspan="10" style="text-align:center;padding:24px;color:#6B5540;">No hay certificados pendientes 🎉</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Panel: Generados -->
    <div id="tab-generados" class="tab-panel <?= $tabActiva === 'generados' ? 'active' : '' ?>">
        <div class="table-wrap">
            <table class="main-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Viaje</th>
                        <th>Días</th>
                        <th>Mascota</th>
                        <th>Tutor</th>
                        <th>Tipo</th>
                        <th>Ruta</th>
                        <th>Asesor</th>
                        <th>Portal</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($generados as $e): ?>
                    <tr class="row-generado">
                        <td class="td-num"><?= $e['rowNum'] ?></td>
                        <td><?= htmlspecialchars($e['fecha']) ?></td>
                        <td><?= urgenciaBadge($e['dias'], $e['esInt']) ?></td>
                        <td class="td-bold"><?= htmlspecialchars($e['mascota']) ?></td>
                        <td><?= htmlspecialchars($e['tutor']) ?></td>
                        <td><span class="tipo-badge <?= esInternacional($e['tipo']) ? 'tipo-int' : 'tipo-nac' ?>"><?= htmlspecialchars(tipoLabel($e['tipo'])) ?></span></td>
                        <td><?= htmlspecialchars($e['origen']) ?> → <?= htmlspecialchars($e['destino']) ?></td>
                        <td><?= htmlspecialchars($e['asesor']) ?></td>
                        <td>
                            <?php if ($e['portalUploadedAt']): ?>
                                <span class="urg-badge urg-ok" title="Subido el <?= htmlspecialchars(date('d/m/Y H:i', strtotime($e['portalUploadedAt']))) ?>">✅ Subido</span>
                            <?php else: ?>
                                <span class="urg-badge urg-vencido">Pendiente</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-group">
                                <div class="action-row">
                                    <a href="certificado.php?row=<?= $e['rowNum'] ?>" class="btn-ver">Ver</a>
                                    <a href="generar_pdf.php?row=<?= $e['rowNum'] ?>" class="btn-ver" target="_blank">PDF</a>
                                    <a href="generar_docx.php?row=<?= $e['rowNum'] ?>" class="btn-ver btn-doc" target="_blank">.doc</a>
                                </div>
                                <a href="subir_portal.php?row=<?= $e['rowNum'] ?>" class="btn-portal">Subir al portal</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($generados)): ?>
                    <tr><td colspan="10" style="text-align:center;padding:24px;color:#6B5540;">Aún no hay certificados generados.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function switchTab(name, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
    var hidden = document.querySelector('input[name="tab"]');
    if (hidden) hidden.value = name;
}

var _vencidosOcultos = true;
function toggleVencidos(btn) {
    _vencidosOcultos = !_vencidosOcultos;
    document.querySelectorAll('.row-vencido-js').forEach(function(tr) {
        tr.style.display = _vencidosOcultos ? 'none' : '';
    });
    btn.textContent = _vencidosOcultos ? 'Mostrar vencidos' : 'Ocultar vencidos';
    btn.classList.toggle('active', _vencidosOcultos);
}
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.row-vencido-js').forEach(function(tr) {
        tr.style.display = 'none';
    });
});
</script>
<script>
// Keepalive: renueva la sesión cada 10 minutos para evitar cierre por inactividad
setInterval(function() {
    fetch('keepalive.php').catch(function(){});
}, 10 * 60 * 1000);
</script>
</body>
</html>
