<?php
require_once __DIR__ . '/auth.php';
requireLogin();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

$TIPOS = [
    'vacuna' => ['titulo' => 'Vacunas',                  'icono' => '💉'],
    'desint' => ['titulo' => 'Desparasitante interno',   'icono' => '🐛'],
    'desext' => ['titulo' => 'Desparasitante externo',   'icono' => '🐛'],
];

$tipo = $_GET['tipo'] ?? $_POST['tipo'] ?? 'vacuna';
if (!isset($TIPOS[$tipo])) $tipo = 'vacuna';

if (empty($_SESSION['csrf_med'])) $_SESSION['csrf_med'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf_med'];

$error = '';
try {
    $db = getDB();
    asegurarTablaMedicamentos($db);
} catch (Throwable $e) {
    error_log('medicamentos.php: ' . $e->getMessage());
    $db = null;
    $error = 'No se pudo preparar la tabla de medicamentos en la base de datos. Avisa a soporte.';
}

function limpiarNombre(string $s): string {
    return mb_strtoupper(trim(preg_replace('/\s+/u', ' ', $s)), 'UTF-8');
}
function limpiarUsos(string $s): string {
    $partes = preg_split('/[,|]/u', $s) ?: [];
    $partes = array_filter(array_map('limpiarNombre', $partes), fn($x) => $x !== '');
    return implode('|', array_unique($partes));
}

if ($db && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
        $error = 'La sesión de este formulario venció. Recarga la página e inténtalo de nuevo.';
    } else {
        $accion = $_POST['accion'] ?? '';
        $id     = (int)($_POST['id'] ?? 0);
        $nombre = limpiarNombre($_POST['nombre'] ?? '');
        $usos   = $tipo === 'vacuna' ? limpiarUsos($_POST['usos'] ?? '') : null;
        $princ  = $tipo !== 'vacuna' ? limpiarNombre($_POST['principio'] ?? '') : null;
        $ica    = $tipo !== 'vacuna' ? trim($_POST['ica'] ?? '') : null;
        $msg    = '';

        try {
            if ($accion === 'eliminar' && $id > 0) {
                $db->prepare('DELETE FROM catalogo_medicamentos WHERE id = ? AND tipo = ?')->execute([$id, $tipo]);
                $msg = 'eliminado';
            } elseif ($accion === 'guardar' || $accion === 'agregar') {
                if ($nombre === '') throw new RuntimeException('El nombre es obligatorio.');
                if (mb_strlen($nombre) > 120) throw new RuntimeException('El nombre es demasiado largo.');
                if ($tipo === 'vacuna' && $usos === '') throw new RuntimeException('Indica al menos un uso (ej. RABIA, PENTAVALENTE).');
                if ($tipo !== 'vacuna' && $princ === '') throw new RuntimeException('El principio activo es obligatorio.');
                if ($tipo !== 'vacuna' && $ica === '') throw new RuntimeException('El registro ICA es obligatorio.');

                if ($accion === 'agregar') {
                    $db->prepare('INSERT INTO catalogo_medicamentos (tipo, nombre, usos, principio_activo, registro_ica) VALUES (?,?,?,?,?)')
                       ->execute([$tipo, $nombre, $usos, $princ, $ica]);
                    $msg = 'agregado';
                } else {
                    $db->prepare('UPDATE catalogo_medicamentos SET nombre = ?, usos = ?, principio_activo = ?, registro_ica = ? WHERE id = ? AND tipo = ?')
                       ->execute([$nombre, $usos, $princ, $ica, $id, $tipo]);
                    $msg = 'guardado';
                }
            }
        } catch (PDOException $e) {
            $error = ((string)$e->getCode() === '23000')
                ? "Ya existe «{$nombre}» en esta lista."
                : 'No se pudo guardar. Inténtalo de nuevo.';
            if ((string)$e->getCode() !== '23000') error_log('medicamentos.php: ' . $e->getMessage());
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }

        if ($error === '') {
            header('Location: medicamentos.php?tipo=' . urlencode($tipo) . '&ok=' . $msg);
            exit;
        }
    }
}

$items = [];
if ($db) {
    $st = $db->prepare('SELECT id, nombre, usos, principio_activo, registro_ica FROM catalogo_medicamentos WHERE tipo = ? ORDER BY nombre');
    $st->execute([$tipo]);
    $items = $st->fetchAll();
}

$okMsg = ['guardado' => 'Cambios guardados.', 'agregado' => 'Producto agregado.', 'eliminado' => 'Producto eliminado.'][$_GET['ok'] ?? ''] ?? '';
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Medicamentos — SkyPets</title>
<link rel="icon" type="image/png" href="/assets/images/logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<style>
.tabs-med { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
.tab-med { padding:8px 18px; border-radius:100px; text-decoration:none; font-size:13px; font-weight:500;
           background:rgba(43,36,24,0.08); color:var(--ink); }
.tab-med.active { background:var(--orange); color:#fff; box-shadow:0 4px 16px rgba(255,118,0,0.32); }
.alert-ok { background:#e6f6ef; color:#1f7a4d; border:1px solid #bfe6d2; border-radius:12px; padding:12px 18px; margin-bottom:16px; font-size:13px; }
.card-med { background:#fff; border-radius:16px; box-shadow:0 4px 20px rgba(43,36,24,0.08); padding:18px; margin-bottom:20px; }
.card-med h3 { font-size:15px; margin-bottom:6px; }
.card-med .hint { margin:0 0 12px; }
.med-grid { display:grid; gap:10px; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); align-items:end; }
.med-grid.vacuna { grid-template-columns:minmax(200px,1.2fr) minmax(220px,1.6fr) auto; }
.med-grid.despar { grid-template-columns:minmax(180px,1fr) minmax(220px,1.4fr) minmax(160px,1fr) auto; }
.med-row { padding:12px 16px; border-top:1px solid rgba(43,36,24,0.08); }
.med-row:first-child { border-top:none; }
.med-row label { font-size:11px; color:var(--muted); font-weight:500; display:block; margin-bottom:3px; }
.med-row input, .card-med input { width:100%; padding:8px 12px; border-radius:10px; border:1.5px solid rgba(43,36,24,0.14);
    background:rgba(255,250,236,0.6); font-family:'Poppins',sans-serif; font-size:13px; }
.med-row input:focus, .card-med input:focus { outline:none; border-color:var(--orange); }
.acciones { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.btn-sm { padding:8px 16px; border-radius:100px; border:none; font-family:'Poppins',sans-serif; font-size:12px; font-weight:600; cursor:pointer; }
.btn-guardar { background:var(--teal); color:#fff; }
.btn-borrar { background:none; border:1.5px solid #E5443A; color:#E5443A; }
.btn-borrar-ok { background:#E5443A; color:#fff; }
.btn-cancelar { background:rgba(43,36,24,0.08); color:var(--ink); }
.confirmar { display:none; align-items:center; gap:8px; flex-wrap:wrap; font-size:12px; color:#E5443A; font-weight:500; }
.vacio { padding:28px; text-align:center; color:var(--muted); }
@media (max-width:760px){ .med-grid.vacuna, .med-grid.despar { grid-template-columns:1fr; } }
</style>
</head>
<body>
<nav class="topbar">
    <img src="assets/images/logo.png" alt="SkyPets" height="36">
    <span class="topbar-title"><a href="dashboard.php">← Volver</a></span>
    <a href="logout.php" class="btn-logout">Salir</a>
</nav>

<div class="container">
    <h2 style="font-family:'Fredoka',sans-serif;margin-bottom:4px;">💊 Medicamentos del certificado</h2>
    <p class="hint" style="margin:0 0 16px;">
        Estas listas son las que aparecen para seleccionar en el formulario del certificado y se autocompletan al elegir un producto.
        Los certificados ya guardados no cambian.
    </p>

    <div class="tabs-med">
        <?php foreach ($TIPOS as $k => $t): ?>
            <a class="tab-med <?= $k === $tipo ? 'active' : '' ?>" href="medicamentos.php?tipo=<?= $k ?>"><?= $t['icono'] ?> <?= $h($t['titulo']) ?></a>
        <?php endforeach; ?>
    </div>

    <?php if ($error): ?><div class="alert alert-error"><?= $h($error) ?></div><?php endif; ?>
    <?php if ($okMsg): ?><div class="alert-ok"><?= $h($okMsg) ?></div><?php endif; ?>

    <?php if ($db): ?>
    <div class="card-med">
        <h3>Agregar a «<?= $h($TIPOS[$tipo]['titulo']) ?>»</h3>
        <?php if ($tipo === 'vacuna'): ?>
            <p class="hint">Los usos se escriben separados por coma, por ejemplo: <strong>RABIA, TRIPLE FELINA</strong>.</p>
        <?php endif; ?>
        <form method="POST" class="med-grid <?= $tipo === 'vacuna' ? 'vacuna' : 'despar' ?>">
            <input type="hidden" name="csrf" value="<?= $h($csrf) ?>">
            <input type="hidden" name="tipo" value="<?= $h($tipo) ?>">
            <input type="hidden" name="accion" value="agregar">
            <div><label style="font-size:11px;color:var(--muted)">Nombre</label>
                <input name="nombre" maxlength="120" required placeholder="<?= $tipo === 'vacuna' ? 'Ej: NOBIVAC DHPPI' : 'Ej: NEXGARD SPECTRA' ?>"></div>
            <?php if ($tipo === 'vacuna'): ?>
                <div><label style="font-size:11px;color:var(--muted)">Usos</label>
                    <input name="usos" maxlength="200" required placeholder="Ej: PENTAVALENTE, RABIA"></div>
            <?php else: ?>
                <div><label style="font-size:11px;color:var(--muted)">Principio activo</label>
                    <input name="principio" maxlength="250" required placeholder="Ej: MILBEMICIN A OXIMA"></div>
                <div><label style="font-size:11px;color:var(--muted)">Registro ICA</label>
                    <input name="ica" maxlength="80" required placeholder="Ej: Reg ICA No 9759-MV"></div>
            <?php endif; ?>
            <div><button type="submit" class="btn-primary" style="padding:9px 22px;font-size:13px;">Agregar</button></div>
        </form>
    </div>

    <div class="card-med" style="padding:0;overflow:hidden;">
        <div style="padding:14px 16px;border-bottom:1px solid rgba(43,36,24,0.08);font-weight:600;font-size:14px;">
            <?= $h($TIPOS[$tipo]['titulo']) ?> <span style="color:var(--muted);font-weight:400;">(<?= count($items) ?>)</span>
        </div>
        <?php if (!$items): ?>
            <div class="vacio">Esta lista está vacía. Agrega el primer producto arriba.</div>
        <?php endif; ?>
        <?php foreach ($items as $it): ?>
        <form method="POST" class="med-row">
            <input type="hidden" name="csrf" value="<?= $h($csrf) ?>">
            <input type="hidden" name="tipo" value="<?= $h($tipo) ?>">
            <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
            <div class="med-grid <?= $tipo === 'vacuna' ? 'vacuna' : 'despar' ?>">
                <div><label>Nombre</label><input name="nombre" maxlength="120" required value="<?= $h($it['nombre']) ?>"></div>
                <?php if ($tipo === 'vacuna'): ?>
                    <div><label>Usos (separados por coma)</label>
                        <input name="usos" maxlength="200" required value="<?= $h(str_replace('|', ', ', (string)$it['usos'])) ?>"></div>
                <?php else: ?>
                    <div><label>Principio activo</label><input name="principio" maxlength="250" required value="<?= $h($it['principio_activo']) ?>"></div>
                    <div><label>Registro ICA</label><input name="ica" maxlength="80" required value="<?= $h($it['registro_ica']) ?>"></div>
                <?php endif; ?>
                <div class="acciones">
                    <button type="submit" name="accion" value="guardar" class="btn-sm btn-guardar">Guardar</button>
                    <button type="button" class="btn-sm btn-borrar" onclick="pedirBorrar(this)">Eliminar</button>
                    <span class="confirmar">¿Eliminar «<?= $h($it['nombre']) ?>»?
                        <button type="submit" name="accion" value="eliminar" class="btn-sm btn-borrar-ok" formnovalidate>Sí, eliminar</button>
                        <button type="button" class="btn-sm btn-cancelar" onclick="cancelarBorrar(this)">No</button>
                    </span>
                </div>
            </div>
        </form>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function pedirBorrar(btn) {
    const acc = btn.closest('.acciones');
    acc.querySelectorAll('.btn-guardar, .btn-borrar').forEach(b => b.style.display = 'none');
    acc.querySelector('.confirmar').style.display = 'flex';
}
function cancelarBorrar(btn) {
    const acc = btn.closest('.acciones');
    acc.querySelector('.confirmar').style.display = 'none';
    acc.querySelectorAll('.btn-guardar, .btn-borrar').forEach(b => b.style.display = '');
}
</script>
<script>
setInterval(function() { fetch('keepalive.php').catch(function(){}); }, 10 * 60 * 1000);
</script>
</body>
</html>
