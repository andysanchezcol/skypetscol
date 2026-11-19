/* ── SkyPets — consentimiento de cookies (compartido) ─────────────────
   Lo carga layout.js en todas las páginas públicas.

   Qué hace:
   - Muestra el banner con «Aceptar» y «Rechazar» del mismo peso visual.
   - Guarda la elección en localStorage (clave sp_cookie_consent) con fecha y versión del texto.
   - Solo carga el chat de Konexa (app.konexa.com.co, que recibe la IP y abre una sesión
     con api.eclipseagency.com.co) si la persona lo acepta. Sin aceptación no sale ninguna
     petición a esos dominios. El botón de WhatsApp sigue disponible siempre.
   - Permite cambiar la elección en cualquier momento: enlace «Configurar cookies» del pie
     (window.spCookies.open()).

   No hay analítica ni publicidad en el sitio. Si algún día se agregan, deben ir detrás de
   su propia casilla en este archivo y cargarse solo con consentimiento.

   Terceros que SÍ cargan sin consentimiento (necesarios para un servicio que la persona pide):
   - cdn.jsdelivr.net: supabase-js (portal, cotizador y panel) y tipos de cambio (cotizador).
   - jkrcdsneilbnuwvqgjba.supabase.co: sesión del portal, solo al iniciar sesión.
   Están declarados en la política de cookies y de privacidad (los escribe el redactor legal).
─────────────────────────────────────────────────────────────────── */
(function () {
  'use strict';
  if (window.spCookies) return;

  var STORAGE_KEY = 'sp_cookie_consent';
  var TEXT_VERSION = '2026-11-19'; /* súbela cada vez que cambie el texto del banner */
  var KONEXA_SRC = 'https://app.konexa.com.co/widget.js';
  var KONEXA_CLAVE = 'wc__K4LK-tGkaI-JtxFUvg9qw';

  var CSS = '' +
    '#sp-ck-banner{position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:2000;width:min(760px,calc(100vw - 32px));background:#FFFAEC;border:2px solid rgba(43,36,24,.12);border-radius:24px;box-shadow:0 8px 40px rgba(43,36,24,.16),0 2px 8px rgba(43,36,24,.08);padding:24px 28px;display:flex;align-items:center;gap:20px;font-family:Poppins,sans-serif;}' +
    '#sp-ck-banner[hidden],#sp-ck-overlay[hidden]{display:none!important;}' +
    '.sp-ck-paw{width:52px;height:52px;border-radius:50%;flex-shrink:0;background:linear-gradient(135deg,#FF7600,#FF9A3C);display:flex;align-items:center;justify-content:center;font-size:1.5rem;}' +
    '.sp-ck-body{flex:1;min-width:0;}' +
    '.sp-ck-body p{font-size:.875rem;color:#2B2418;line-height:1.65;font-weight:400;margin:0;}' +
    '.sp-ck-link{display:inline-block;font-size:.8rem;font-weight:500;color:#006F67;text-decoration:underline;margin-top:6px;}' +
    '.sp-ck-actions{display:flex;flex-direction:column;gap:8px;flex-shrink:0;}' +
    /* Aceptar y Rechazar: exactamente el mismo estilo */
    '.sp-ck-btn{display:inline-flex;align-items:center;justify-content:center;font-family:Poppins,sans-serif;font-size:.875rem;font-weight:600;padding:11px 22px;border-radius:100px;border:2px solid #B85500;background:#B85500;color:#fff;cursor:pointer;white-space:nowrap;min-height:44px;}' +
    '.sp-ck-btn:hover{background:#9C4800;border-color:#9C4800;}' +
    '.sp-ck-cfg{display:inline-flex;align-items:center;justify-content:center;font-family:Poppins,sans-serif;font-size:.82rem;font-weight:500;padding:8px 18px;background:transparent;color:#2B2418;border:0;text-decoration:underline;cursor:pointer;min-height:44px;}' +
    '.sp-ck-btn:focus-visible,.sp-ck-cfg:focus-visible,.sp-ck-x:focus-visible,.sp-ck-link:focus-visible,.sp-ck-switch input:focus-visible+.sp-ck-track{outline:3px solid #008D83;outline-offset:2px;}' +
    '#sp-ck-overlay{position:fixed;inset:0;z-index:2100;background:rgba(43,36,24,.45);display:flex;align-items:center;justify-content:center;padding:16px;font-family:Poppins,sans-serif;}' +
    '#sp-ck-modal{background:#FFFAEC;border-radius:28px;border:2px solid rgba(43,36,24,.10);box-shadow:0 20px 60px rgba(43,36,24,.20);width:min(500px,100%);max-height:90vh;overflow-y:auto;}' +
    '.sp-ck-head{padding:28px 28px 0;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;}' +
    '.sp-ck-title{font-family:Fredoka,sans-serif;font-size:1.35rem;font-weight:700;color:#2B2418;margin:0;}' +
    '.sp-ck-x{width:44px;height:44px;border-radius:50%;flex-shrink:0;background:rgba(43,36,24,.07);border:none;cursor:pointer;font-size:1rem;color:#2B2418;}' +
    '.sp-ck-mbody{padding:16px 28px;}' +
    '.sp-ck-desc{font-size:.875rem;color:#6B5540;line-height:1.7;margin:0 0 16px;}' +
    '.sp-ck-row{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:16px 0;border-top:1px solid rgba(43,36,24,.07);}' +
    '.sp-ck-lbl{font-family:Fredoka,sans-serif;font-size:1rem;font-weight:600;color:#2B2418;margin-bottom:4px;}' +
    '.sp-ck-sub{font-size:.8rem;color:#6B5540;line-height:1.55;margin:0;}' +
    '.sp-ck-switch{position:relative;flex-shrink:0;margin-top:2px;}' +
    '.sp-ck-switch input{opacity:0;width:44px;height:24px;position:absolute;margin:0;cursor:pointer;}' +
    '.sp-ck-track{display:block;width:44px;height:24px;border-radius:100px;background:rgba(43,36,24,.30);position:relative;transition:background .25s;}' +
    '.sp-ck-track::after{content:"";position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.2);transition:transform .25s;}' +
    '.sp-ck-switch input:checked+.sp-ck-track{background:#B85500;}' +
    '.sp-ck-switch input:checked+.sp-ck-track::after{transform:translateX(20px);}' +
    '.sp-ck-switch input:disabled+.sp-ck-track{background:#006F67;opacity:.85;}' +
    '.sp-ck-mfoot{padding:0 28px 28px;display:flex;gap:10px;flex-wrap:wrap;}' +
    '.sp-ck-mfoot .sp-ck-btn{flex:1;}' +
    '@media(max-width:600px){#sp-ck-banner{flex-direction:column;align-items:flex-start;bottom:12px;padding:20px;}.sp-ck-actions{flex-direction:row;flex-wrap:wrap;width:100%;}.sp-ck-actions .sp-ck-btn{flex:1;padding:10px 14px;font-size:.82rem;}.sp-ck-head{padding:20px 20px 0;}.sp-ck-mbody{padding:16px 20px;}.sp-ck-mfoot{padding:0 20px 20px;}}';

  var HTML = '' +
    '<div id="sp-ck-banner" role="dialog" aria-label="Consentimiento de cookies" hidden>' +
      '<div class="sp-ck-paw" aria-hidden="true">🐾</div>' +
      '<div class="sp-ck-body">' +
        '<p>En <strong>SkyPets</strong> no usamos cookies de analítica ni de publicidad. Solo cargamos el <strong>chat de atención (Konexa)</strong>, un servicio de un tercero, si tú lo aceptas. Puedes rechazarlo y seguir usando el sitio; sigues pudiendo escribirnos por WhatsApp.</p>' +
        '<a href="/politica-de-cookies" class="sp-ck-link">Leer la Política de Cookies</a>' +
      '</div>' +
      '<div class="sp-ck-actions">' +
        '<button type="button" class="sp-ck-btn" id="spCkAccept">Aceptar</button>' +
        '<button type="button" class="sp-ck-btn" id="spCkReject">Rechazar</button>' +
        '<button type="button" class="sp-ck-cfg" id="spCkConfig">Configurar</button>' +
      '</div>' +
    '</div>' +
    '<div id="sp-ck-overlay" role="dialog" aria-modal="true" aria-labelledby="spCkTitle" hidden>' +
      '<div id="sp-ck-modal">' +
        '<div class="sp-ck-head">' +
          '<h2 class="sp-ck-title" id="spCkTitle">Preferencias de cookies</h2>' +
          '<button type="button" class="sp-ck-x" id="spCkClose" aria-label="Cerrar">✕</button>' +
        '</div>' +
        '<div class="sp-ck-mbody">' +
          '<p class="sp-ck-desc">Elige qué permites. Puedes cambiar tu elección cuando quieras desde «Configurar cookies» en el pie de página.</p>' +
          '<div class="sp-ck-row" style="border-top:none;padding-top:0">' +
            '<div><div class="sp-ck-lbl">Necesarias</div><p class="sp-ck-sub">Guardan tu elección de cookies y mantienen tu sesión en el portal de clientes. Sin ellas el sitio no funciona. Siempre activas.</p></div>' +
            '<label class="sp-ck-switch"><input type="checkbox" checked disabled aria-label="Necesarias, siempre activas"><span class="sp-ck-track"></span></label>' +
          '</div>' +
          '<div class="sp-ck-row">' +
            '<div><div class="sp-ck-lbl">Chat de atención (Konexa)</div><p class="sp-ck-sub">Muestra el chat en la esquina de la página. Al activarlo, tu navegador se conecta a app.konexa.com.co y api.eclipseagency.com.co (Eclipse Agency, proveedor de Konexa), que reciben tu dirección IP y lo que escribas en el chat. Si lo dejas apagado, no se carga.</p></div>' +
            '<label class="sp-ck-switch"><input type="checkbox" id="spCkChat" aria-label="Chat de atención (Konexa)"><span class="sp-ck-track"></span></label>' +
          '</div>' +
          '<div class="sp-ck-row"><div><div class="sp-ck-lbl">Analítica y publicidad</div><p class="sp-ck-sub">No las usamos.</p></div></div>' +
        '</div>' +
        '<div class="sp-ck-mfoot">' +
          '<button type="button" class="sp-ck-btn" id="spCkSave">Guardar mi elección</button>' +
          '<button type="button" class="sp-ck-btn" id="spCkRejectAll">Rechazar todo</button>' +
        '</div>' +
      '</div>' +
    '</div>';

  function getConsent() {
    try {
      var c = JSON.parse(localStorage.getItem(STORAGE_KEY));
      /* Elecciones guardadas con el banner anterior (sin versión) no valen: se vuelve a preguntar. */
      return c && c.version ? c : null;
    } catch (e) { return null; }
  }

  function saveConsent(chat) {
    var c = { necesarias: true, chat: !!chat, version: TEXT_VERSION, fecha: new Date().toISOString() };
    try { localStorage.setItem(STORAGE_KEY, JSON.stringify(c)); } catch (e) {}
    return c;
  }

  function cargarChat() {
    if (window.__konexaChat || document.getElementById('sp-konexa-script')) return;
    var s = document.createElement('script');
    s.id = 'sp-konexa-script';
    s.src = KONEXA_SRC;
    s.async = true;
    s.setAttribute('data-clave', KONEXA_CLAVE);
    document.body.appendChild(s);
    document.documentElement.classList.add('kx-chat');
  }

  function quitarChat() {
    var s = document.getElementById('sp-konexa-script');
    if (s) s.remove();
    document.querySelectorAll('iframe[src^="https://app.konexa.com.co"]').forEach(function (f) { f.remove(); });
    window.__konexaChat = false;
    document.documentElement.classList.remove('kx-chat');
  }

  function aplicar(c) {
    if (c && c.chat) cargarChat(); else quitarChat();
  }

  function init() {
    var wrap = document.createElement('div');
    wrap.id = 'sp-ck-root';
    wrap.innerHTML = HTML;
    var st = document.createElement('style');
    st.id = 'sp-ck-css';
    st.textContent = CSS;
    document.head.appendChild(st);
    document.body.appendChild(wrap);

    var banner = document.getElementById('sp-ck-banner');
    var overlay = document.getElementById('sp-ck-overlay');
    var chat = document.getElementById('spCkChat');
    var lastFocus = null;

    function cerrarModal() {
      overlay.hidden = true;
      if (lastFocus && lastFocus.focus) lastFocus.focus();
    }
    function abrirModal() {
      var c = getConsent();
      chat.checked = !!(c && c.chat);
      lastFocus = document.activeElement;
      banner.hidden = true;
      overlay.hidden = false;
      document.getElementById('spCkClose').focus();
    }
    function elegir(valor) {
      aplicar(saveConsent(valor));
      banner.hidden = true;
      overlay.hidden = true;
      if (lastFocus && lastFocus.focus) lastFocus.focus();
    }

    document.getElementById('spCkAccept').addEventListener('click', function () { elegir(true); });
    document.getElementById('spCkReject').addEventListener('click', function () { elegir(false); });
    document.getElementById('spCkRejectAll').addEventListener('click', function () { elegir(false); });
    document.getElementById('spCkSave').addEventListener('click', function () { elegir(chat.checked); });
    document.getElementById('spCkConfig').addEventListener('click', abrirModal);
    document.getElementById('spCkClose').addEventListener('click', function () {
      cerrarModal();
      if (!getConsent()) banner.hidden = false;
    });
    overlay.addEventListener('click', function (e) { if (e.target === overlay) document.getElementById('spCkClose').click(); });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !overlay.hidden) document.getElementById('spCkClose').click();
    });

    window.spCookies = { open: abrirModal, get: getConsent };

    var c = getConsent();
    aplicar(c);
    if (!c) banner.hidden = false;
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
