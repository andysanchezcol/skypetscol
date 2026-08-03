<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Catálogo · SkyPets</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Poppins:wght@400;500;600&display=swap">
<style>
:root {
  --orange: #FF7600; --teal: #008D83; --yellow: #FFBC00;
  --ink: #2B2418; --cream: #FFFAEC; --muted: #6B5540;
  --bg1: #FFFAEC; --bg2: #FFEFC2; --danger: #E5443A; --success: #71CC1E;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Poppins',sans-serif;background:#f4f0e8;color:var(--ink);min-height:100vh}

/* ── Header ── */
.adm-header{background:var(--ink);padding:0 32px;display:flex;align-items:center;justify-content:space-between;height:60px;position:sticky;top:0;z-index:100}
.adm-logo{font-family:'Fredoka',sans-serif;font-size:1.3rem;font-weight:700;color:var(--cream);display:flex;align-items:center;gap:10px}
.adm-logo span{background:var(--orange);color:#fff;font-size:0.7rem;padding:2px 10px;border-radius:100px;font-family:'Poppins',sans-serif;font-weight:600}
.adm-header-actions{display:flex;gap:10px}
.adm-header a{color:rgba(255,250,236,0.6);font-size:0.8rem;text-decoration:none;padding:6px 14px;border-radius:100px;border:1px solid rgba(255,250,236,0.15);transition:all 0.2s}
.adm-header a:hover{color:#fff;border-color:rgba(255,250,236,0.4)}

/* ── Layout ── */
.adm-body{max-width:1100px;margin:0 auto;padding:32px 24px}
.adm-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px}
.adm-title{font-family:'Fredoka',sans-serif;font-size:1.6rem;font-weight:700}
.btn{font-family:'Poppins',sans-serif;font-size:0.85rem;font-weight:600;padding:10px 22px;border-radius:100px;border:none;cursor:pointer;transition:transform 0.15s,box-shadow 0.15s;display:inline-flex;align-items:center;gap:7px}
.btn-primary{background:var(--orange);color:#fff;box-shadow:0 4px 16px rgba(255,118,0,0.35)}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 6px 22px rgba(255,118,0,0.45)}
.btn-teal{background:var(--teal);color:#fff;box-shadow:0 4px 14px rgba(0,141,131,0.3)}
.btn-teal:hover{transform:translateY(-1px)}
.btn-danger{background:rgba(229,68,58,0.1);color:var(--danger);border:1.5px solid rgba(229,68,58,0.2)}
.btn-danger:hover{background:var(--danger);color:#fff}
.btn-sm{padding:6px 14px;font-size:0.75rem}
.btn-ghost{background:rgba(43,36,24,0.06);color:var(--muted)}
.btn-ghost:hover{background:rgba(43,36,24,0.12)}

/* ── Tabla de productos ── */
.prod-table-wrap{background:#fff;border-radius:18px;box-shadow:0 4px 24px rgba(43,36,24,0.07);overflow:hidden}
.prod-table{width:100%;border-collapse:collapse}
.prod-table th{background:var(--bg2);font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;color:var(--muted);padding:12px 20px;text-align:left}
.prod-table td{padding:14px 20px;border-top:1px solid rgba(43,36,24,0.06);vertical-align:middle;font-size:0.85rem}
.prod-table tr:hover td{background:rgba(255,250,236,0.5)}
.prod-thumb{width:48px;height:48px;border-radius:10px;object-fit:contain;background:#1e1912;padding:4px}
.prod-thumb-empty{width:48px;height:48px;border-radius:10px;background:#e8e2d8;display:flex;align-items:center;justify-content:center;font-size:1.2rem}
.cat-pill{display:inline-block;font-size:0.7rem;font-weight:600;padding:3px 10px;border-radius:100px;background:rgba(0,141,131,0.1);color:var(--teal)}
.cat-pill.bolso{background:rgba(255,188,0,0.15);color:#a07800}
.cat-pill.accesorio{background:rgba(43,36,24,0.08);color:var(--muted)}
.empty-state{text-align:center;padding:60px 20px;color:var(--muted)}
.empty-state .icon{font-size:3rem;margin-bottom:16px}
.empty-state p{margin-bottom:20px}

/* ── Modal / Drawer ── */
.drawer-overlay{display:none;position:fixed;inset:0;z-index:200;background:rgba(43,36,24,0.45);backdrop-filter:blur(4px)}
.drawer-overlay.open{display:flex;align-items:flex-start;justify-content:flex-end}
.drawer{background:#fff;width:min(680px,100vw);height:100vh;overflow-y:auto;box-shadow:-8px 0 48px rgba(43,36,24,0.15);padding:32px;display:flex;flex-direction:column;gap:24px;animation:slideIn 0.28s cubic-bezier(0.34,1.2,0.64,1)}
@keyframes slideIn{from{transform:translateX(100%)}to{transform:translateX(0)}}
.drawer-header{display:flex;align-items:center;justify-content:space-between}
.drawer-title{font-family:'Fredoka',sans-serif;font-size:1.4rem;font-weight:700}
.drawer-close{background:rgba(43,36,24,0.08);border:none;width:34px;height:34px;border-radius:50%;cursor:pointer;font-size:1rem;color:var(--muted);display:flex;align-items:center;justify-content:center}
.drawer-close:hover{background:rgba(43,36,24,0.15)}

/* ── Form ── */
.form-group{display:flex;flex-direction:column;gap:6px}
.form-group label{font-size:0.78rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:0.05em}
.form-group input,.form-group textarea,.form-group select{font-family:'Poppins',sans-serif;font-size:0.88rem;padding:10px 14px;border:1.5px solid rgba(43,36,24,0.14);border-radius:10px;background:#fafaf8;color:var(--ink);outline:none;transition:border-color 0.2s}
.form-group input:focus,.form-group textarea:focus,.form-group select:focus{border-color:var(--orange)}
.form-group textarea{resize:vertical;min-height:80px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-section-title{font-size:0.78rem;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;color:var(--muted);padding-bottom:8px;border-bottom:1.5px solid rgba(43,36,24,0.08)}
.hint{font-size:0.72rem;color:var(--muted);margin-top:2px}

/* ── Features list ── */
.features-list{display:flex;flex-direction:column;gap:8px}
.feature-row{display:flex;gap:8px;align-items:center}
.feature-row input{flex:1}
.btn-icon{width:32px;height:32px;border-radius:8px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:0.9rem;flex-shrink:0;background:rgba(229,68,58,0.1);color:var(--danger);transition:background 0.2s}
.btn-icon:hover{background:var(--danger);color:#fff}
.btn-add-feature{background:rgba(0,141,131,0.08);color:var(--teal);border:1.5px dashed rgba(0,141,131,0.3);border-radius:10px;padding:8px;font-size:0.8rem;font-weight:500;cursor:pointer;width:100%;font-family:'Poppins',sans-serif;transition:all 0.2s}
.btn-add-feature:hover{background:rgba(0,141,131,0.15)}

/* ── Variantes ── */
.variants-type-toggle{display:flex;gap:8px;margin-bottom:12px}
.vtype-btn{flex:1;padding:8px;border-radius:10px;border:1.5px solid rgba(43,36,24,0.14);background:#fafaf8;cursor:pointer;font-family:'Poppins',sans-serif;font-size:0.82rem;font-weight:500;color:var(--muted);transition:all 0.2s;text-align:center}
.vtype-btn.active{background:var(--orange);color:#fff;border-color:transparent;box-shadow:0 3px 12px rgba(255,118,0,0.3)}
.variant-card{background:#fafaf8;border:1.5px solid rgba(43,36,24,0.1);border-radius:14px;padding:16px;display:flex;flex-direction:column;gap:12px;position:relative}
.variant-card-header{display:flex;align-items:center;gap:10px}
.variant-label-input{flex:1;font-family:'Poppins',sans-serif;font-size:0.88rem;padding:8px 12px;border:1.5px solid rgba(43,36,24,0.14);border-radius:8px;background:#fff;color:var(--ink);outline:none}
.variant-label-input:focus{border-color:var(--orange)}
.color-input{width:44px;height:36px;padding:2px;border:1.5px solid rgba(43,36,24,0.14);border-radius:8px;cursor:pointer;background:#fff}
.variant-remove{position:absolute;top:12px;right:12px;background:none;border:none;cursor:pointer;color:var(--muted);font-size:0.9rem;opacity:0.5;transition:opacity 0.2s}
.variant-remove:hover{opacity:1;color:var(--danger)}

/* ── Image upload ── */
.img-upload-zone{border:2px dashed rgba(43,36,24,0.18);border-radius:12px;padding:20px;text-align:center;cursor:pointer;transition:all 0.2s;background:#fafaf8;position:relative}
.img-upload-zone:hover,.img-upload-zone.drag{border-color:var(--orange);background:rgba(255,118,0,0.04)}
.img-upload-zone input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
.img-upload-zone .icon{font-size:1.6rem;margin-bottom:6px}
.img-upload-zone p{font-size:0.78rem;color:var(--muted)}
.img-upload-zone .uploading{font-size:0.8rem;color:var(--orange);margin-top:4px;display:none}
.img-preview-list{display:flex;flex-wrap:wrap;gap:10px;margin-top:12px}
.img-preview-item{position:relative;width:80px;height:80px}
.img-preview-item img{width:80px;height:80px;border-radius:10px;object-fit:contain;background:#1e1912;padding:4px}
.img-preview-item .img-remove{position:absolute;top:-6px;right:-6px;width:20px;height:20px;border-radius:50%;background:var(--danger);color:#fff;border:none;cursor:pointer;font-size:0.65rem;display:flex;align-items:center;justify-content:center}
.img-main-badge{position:absolute;bottom:2px;left:2px;background:var(--orange);color:#fff;font-size:0.55rem;font-weight:700;padding:1px 5px;border-radius:4px}

/* ── Drawer footer ── */
.drawer-footer{display:flex;gap:10px;justify-content:flex-end;padding-top:8px;border-top:1px solid rgba(43,36,24,0.08)}

/* ── Toast ── */
.toast{position:fixed;bottom:28px;left:50%;transform:translateX(-50%) translateY(20px);background:var(--ink);color:#fff;font-size:0.85rem;font-weight:500;padding:12px 24px;border-radius:100px;box-shadow:0 8px 28px rgba(43,36,24,0.25);opacity:0;transition:all 0.3s;pointer-events:none;z-index:999;white-space:nowrap}
.toast.show{opacity:1;transform:translateX(-50%) translateY(0)}
.toast.success::before{content:'✓  '}
.toast.error{background:var(--danger)}
.toast.error::before{content:'✕  '}

/* ── Drag handle ── */
.drag-handle{cursor:grab;color:var(--muted);font-size:1rem;padding:0 4px}
.drag-handle:active{cursor:grabbing}
</style>
</head>
<body>

<header class="adm-header">
  <div class="adm-logo">
    🐾 SkyPets <span>ADMIN</span>
  </div>
  <div class="adm-header-actions">
    <a href="/catalogo.html" target="_blank">Ver catálogo →</a>
  </div>
</header>

<div class="adm-body">
  <div class="adm-top">
    <div class="adm-title">Catálogo de Productos</div>
    <button class="btn btn-primary" onclick="openDrawer()">+ Nuevo producto</button>
  </div>

  <div class="prod-table-wrap">
    <table class="prod-table" id="prodTable">
      <thead>
        <tr>
          <th></th>
          <th>Foto</th>
          <th>Nombre</th>
          <th>Categoría</th>
          <th>Variantes</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody id="prodTbody">
        <tr><td colspan="6"><div class="empty-state"><div class="icon">📦</div><p>No hay productos aún.</p><button class="btn btn-primary" onclick="openDrawer()">+ Crear primer producto</button></div></td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- ══ DRAWER ══ -->
<div class="drawer-overlay" id="drawerOverlay" onclick="if(event.target===this)closeDrawer()">
  <div class="drawer" id="drawer">
    <div class="drawer-header">
      <div class="drawer-title" id="drawerTitle">Nuevo producto</div>
      <button class="drawer-close" onclick="closeDrawer()">✕</button>
    </div>

    <!-- Info básica -->
    <div class="form-section-title">Información básica</div>

    <div class="form-group">
      <label>Nombre del producto *</label>
      <input type="text" id="fName" placeholder="Ej: Transportadora Cabina Pro">
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Categoría *</label>
        <select id="fCat">
          <option value="transportadora">🏠 Transportadora</option>
          <option value="bolso">👜 Bolso de viaje</option>
          <option value="accesorio">✨ Accesorio</option>
        </select>
      </div>
      <div class="form-group">
        <label>Precio (texto)</label>
        <input type="text" id="fPrice" placeholder="Ej: Consultar precio">
      </div>
    </div>

    <div class="form-group">
      <label>Descripción *</label>
      <textarea id="fDesc" placeholder="Descripción del producto..."></textarea>
    </div>

    <!-- Características -->
    <div class="form-section-title">Características (lista de puntos)</div>
    <div class="features-list" id="featuresList"></div>
    <button class="btn-add-feature" onclick="addFeature()">+ Agregar característica</button>

    <!-- Variantes -->
    <div class="form-section-title">Variantes</div>
    <p class="hint" style="margin-top:-16px">Si el producto tiene tallas o colores diferentes, agrégalas aquí. Cada variante puede tener sus propias fotos.</p>

    <div class="variants-type-toggle">
      <button class="vtype-btn active" id="vtypeNone" onclick="setVariantType('none')">Sin variantes</button>
      <button class="vtype-btn" id="vtypeSize" onclick="setVariantType('size')">Por talla</button>
      <button class="vtype-btn" id="vtypeColor" onclick="setVariantType('color')">Por color</button>
    </div>

    <!-- Sin variantes: imágenes directas -->
    <div id="sectionNoVariants">
      <div class="form-section-title" style="margin-bottom:12px">Fotos del producto</div>
      <div class="img-upload-zone" id="dropZoneMain">
        <input type="file" accept="image/png,image/jpeg,image/webp" multiple onchange="handleImgUpload(event,'main')">
        <div class="icon">🖼️</div>
        <p>Arrastra fotos aquí o haz clic para seleccionar<br><small>PNG, JPG o WebP · máx. 10 MB por foto</small></p>
        <div class="uploading" id="uploadingMain">Subiendo...</div>
      </div>
      <div class="img-preview-list" id="previewMain"></div>
    </div>

    <!-- Con variantes -->
    <div id="sectionVariants" style="display:none">
      <div id="variantsList"></div>
      <button class="btn-add-feature" id="btnAddVariant" onclick="addVariant()">+ Agregar variante</button>
    </div>

    <div class="drawer-footer">
      <button class="btn btn-ghost" onclick="closeDrawer()">Cancelar</button>
      <button class="btn btn-primary" onclick="saveProduct()">Guardar producto</button>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
/* ══ Estado ══ */
let products    = [];
let editIdx     = -1;
let variantType = 'none';
let mainImages  = []; // [{path, uploading}]

/* ══ Carga inicial ══ */
fetch('/data/productos.json?t=' + Date.now())
  .then(r => r.json()).then(data => { products = data || []; renderTable(); })
  .catch(() => { products = []; renderTable(); });

/* ══ Tabla ══ */
function renderTable() {
  const tbody = document.getElementById('prodTbody');
  if (!products.length) {
    tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state"><div class="icon">📦</div><p>No hay productos aún.</p><button class="btn btn-primary" onclick="openDrawer()">+ Crear primer producto</button></div></td></tr>`;
    return;
  }
  tbody.innerHTML = products.map((p, i) => {
    const firstImg = p.variants?.length ? (p.variants[0].images[0] || '') : (p.images?.[0] || '');
    const thumb = firstImg
      ? `<img class="prod-thumb" src="${firstImg}" alt="${p.name}">`
      : `<div class="prod-thumb-empty">📦</div>`;
    const varCount = p.variants?.length || 0;
    const varInfo  = varCount ? `${varCount} variante${varCount>1?'s':''}` : '—';
    const catClass = p.category === 'bolso' ? 'bolso' : p.category === 'accesorio' ? 'accesorio' : '';
    return `<tr>
      <td class="drag-handle" title="Reordenar">⠿</td>
      <td>${thumb}</td>
      <td><strong>${p.name}</strong></td>
      <td><span class="cat-pill ${catClass}">${p.badge}</span></td>
      <td>${varInfo}</td>
      <td style="display:flex;gap:8px">
        <button class="btn btn-teal btn-sm" onclick="openDrawer(${i})">Editar</button>
        <button class="btn btn-danger btn-sm" onclick="deleteProduct(${i})">Eliminar</button>
      </td>
    </tr>`;
  }).join('');
}

/* ══ Drawer ══ */
function openDrawer(idx = -1) {
  editIdx = idx;
  resetDrawer();
  document.getElementById('drawerTitle').textContent = idx === -1 ? 'Nuevo producto' : 'Editar producto';

  if (idx !== -1) {
    const p = products[idx];
    document.getElementById('fName').value  = p.name  || '';
    document.getElementById('fCat').value   = p.category || 'transportadora';
    document.getElementById('fPrice').value = p.price || '';
    document.getElementById('fDesc').value  = p.desc  || '';
    (p.features || []).forEach(f => addFeature(f));

    if (p.variants && p.variants.length > 0) {
      const type = p.variants[0].color != null ? 'color' : 'size';
      setVariantType(type);
      p.variants.forEach(v => addVariant(v));
    } else {
      setVariantType('none');
      mainImages = (p.images || []).map(path => ({ path }));
      renderMainPreviews();
    }
  } else {
    addFeature();
  }

  document.getElementById('drawerOverlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeDrawer() {
  document.getElementById('drawerOverlay').classList.remove('open');
  document.body.style.overflow = '';
}

function resetDrawer() {
  ['fName','fPrice','fDesc'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('fCat').value = 'transportadora';
  document.getElementById('featuresList').innerHTML = '';
  document.getElementById('variantsList').innerHTML = '';
  document.getElementById('previewMain').innerHTML  = '';
  mainImages = [];
  setVariantType('none');
}

/* ══ Características ══ */
function addFeature(val = '') {
  const row = document.createElement('div');
  row.className = 'feature-row';
  row.innerHTML = `<input type="text" placeholder="Ej: Aprobada por aerolíneas colombianas" value="${val}">
    <button class="btn-icon" onclick="this.parentElement.remove()" title="Eliminar">✕</button>`;
  document.getElementById('featuresList').appendChild(row);
}

/* ══ Tipo de variante ══ */
function setVariantType(type) {
  variantType = type;
  ['none','size','color'].forEach(t => document.getElementById('vtype'+t.charAt(0).toUpperCase()+t.slice(1)).classList.toggle('active', t===type));
  document.getElementById('sectionNoVariants').style.display = type === 'none' ? '' : 'none';
  document.getElementById('sectionVariants').style.display   = type !== 'none' ? '' : 'none';
  if (type !== 'none' && !document.getElementById('variantsList').children.length) addVariant();
}

/* ══ Variantes ══ */
let variantImgs = {}; // variantId → [{path}]
let variantIdCounter = 0;

function addVariant(data = null) {
  const vid  = 'v' + (++variantIdCounter);
  const isColor = variantType === 'color';
  variantImgs[vid] = (data?.images || []).map(p => ({ path: p }));

  const card = document.createElement('div');
  card.className = 'variant-card';
  card.dataset.vid = vid;
  card.innerHTML = `
    <button class="variant-remove" onclick="removeVariant(this,'${vid}')" title="Eliminar variante">✕</button>
    <div class="variant-card-header">
      ${isColor ? `<input type="color" class="color-input" value="${data?.color || '#888888'}">` : ''}
      <input type="text" class="variant-label-input" placeholder="${isColor ? 'Nombre del color (ej: Negro)' : 'Talla (ej: S, M, L, XL)'}" value="${data?.label || ''}">
    </div>
    <div style="font-size:0.75rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:0.05em">Fotos de esta variante</div>
    <div class="img-upload-zone" id="drop_${vid}">
      <input type="file" accept="image/png,image/jpeg,image/webp" multiple onchange="handleImgUpload(event,'${vid}')">
      <div class="icon">🖼️</div>
      <p>Arrastra fotos o haz clic<br><small>PNG, JPG o WebP</small></p>
      <div class="uploading" id="uploading_${vid}">Subiendo...</div>
    </div>
    <div class="img-preview-list" id="preview_${vid}"></div>`;

  document.getElementById('variantsList').appendChild(card);
  renderVariantPreviews(vid);
}

function removeVariant(btn, vid) {
  btn.closest('.variant-card').remove();
  delete variantImgs[vid];
}

/* ══ Upload imágenes ══ */
async function handleImgUpload(event, target) {
  const files = [...event.target.files];
  if (!files.length) return;

  const slug = slugify(document.getElementById('fName').value || 'producto');
  const uploadingEl = document.getElementById('uploading_' + target) || document.getElementById('uploadingMain');
  if (uploadingEl) uploadingEl.style.display = 'block';

  for (const file of files) {
    const fd = new FormData();
    fd.append('image', file);
    fd.append('slug', slug);
    try {
      const res  = await fetch('/catalogo-admin/upload.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.path) {
        if (target === 'main') { mainImages.push({ path: data.path }); renderMainPreviews(); }
        else { variantImgs[target].push({ path: data.path }); renderVariantPreviews(target); }
      } else { showToast(data.error || 'Error al subir imagen', 'error'); }
    } catch { showToast('Error de conexión al subir imagen', 'error'); }
  }

  if (uploadingEl) uploadingEl.style.display = 'none';
  event.target.value = '';
}

function renderMainPreviews() {
  const el = document.getElementById('previewMain');
  el.innerHTML = mainImages.map((img, i) => `
    <div class="img-preview-item">
      <img src="${img.path}" alt="foto ${i+1}">
      ${i===0 ? '<span class="img-main-badge">Principal</span>' : ''}
      <button class="img-remove" onclick="removeImg('main',${i})">✕</button>
    </div>`).join('');
}

function renderVariantPreviews(vid) {
  const el = document.getElementById('preview_' + vid);
  if (!el) return;
  el.innerHTML = (variantImgs[vid] || []).map((img, i) => `
    <div class="img-preview-item">
      <img src="${img.path}" alt="foto ${i+1}">
      ${i===0 ? '<span class="img-main-badge">Principal</span>' : ''}
      <button class="img-remove" onclick="removeImg('${vid}',${i})">✕</button>
    </div>`).join('');
}

function removeImg(target, idx) {
  const arr = target === 'main' ? mainImages : variantImgs[target];
  const path = arr[idx].path;
  arr.splice(idx, 1);
  if (target === 'main') renderMainPreviews(); else renderVariantPreviews(target);
  /* borrar del servidor solo si no es placeholder */
  if (path.startsWith('/assets/')) {
    fetch('/catalogo-admin/delete-image.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ path }) });
  }
}

/* ══ Guardar producto ══ */
async function saveProduct() {
  const name  = document.getElementById('fName').value.trim();
  const cat   = document.getElementById('fCat').value;
  const price = document.getElementById('fPrice').value.trim() || 'Consultar precio';
  const desc  = document.getElementById('fDesc').value.trim();

  if (!name) { showToast('El nombre es obligatorio', 'error'); return; }
  if (!desc) { showToast('La descripción es obligatoria', 'error'); return; }

  const features = [...document.querySelectorAll('#featuresList .feature-row input')]
    .map(i => i.value.trim()).filter(Boolean);

  const BADGE_MAP = { transportadora:'Transportadora', bolso:'Bolso de viaje', accesorio:'Accesorio' };
  const CLASS_MAP = { transportadora:'', bolso:'bolso', accesorio:'accesorio' };

  let variants = [];
  let images   = [];

  if (variantType === 'none') {
    images = mainImages.map(i => i.path);
  } else {
    const cards = document.querySelectorAll('#variantsList .variant-card');
    variants = [...cards].map(card => {
      const vid   = card.dataset.vid;
      const label = card.querySelector('.variant-label-input').value.trim();
      const imgs  = (variantImgs[vid] || []).map(i => i.path);
      const obj   = { label, images: imgs };
      if (variantType === 'color') obj.color = card.querySelector('.color-input').value;
      return obj;
    }).filter(v => v.label);
    if (!variants.length) { showToast('Agrega al menos una variante con nombre', 'error'); return; }
  }

  const waText = 'Hola%20SkyPets%2C%20quiero%20información%20sobre%20' + encodeURIComponent(name);

  const product = { name, category: cat, badge: BADGE_MAP[cat], badgeClass: CLASS_MAP[cat], price, desc, features, variants, images, wa: waText };

  if (editIdx === -1) products.push(product);
  else products[editIdx] = product;

  const res  = await fetch('/catalogo-admin/save.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ products }) });
  const data = await res.json();

  if (data.ok) { renderTable(); closeDrawer(); showToast('Producto guardado', 'success'); }
  else showToast(data.error || 'Error al guardar', 'error');
}

/* ══ Eliminar ══ */
async function deleteProduct(idx) {
  if (!confirm(`¿Eliminar "${products[idx].name}"? Esta acción no se puede deshacer.`)) return;
  products.splice(idx, 1);
  const res  = await fetch('/catalogo-admin/save.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ products }) });
  const data = await res.json();
  if (data.ok) { renderTable(); showToast('Producto eliminado', 'success'); }
  else showToast(data.error || 'Error al eliminar', 'error');
}

/* ══ Helpers ══ */
function slugify(str) {
  return str.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g,'').replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
}
function showToast(msg, type = 'success') {
  const t = document.getElementById('toast');
  t.textContent = msg; t.className = 'toast ' + type;
  requestAnimationFrame(() => { t.classList.add('show'); setTimeout(() => t.classList.remove('show'), 3000); });
}
</script>
</body>
</html>
