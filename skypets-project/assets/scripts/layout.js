/* ── SkyPets shared layout — nav + footer ──────────────────────────
   Edita este archivo para que los cambios apliquen en TODAS las páginas.
   Las páginas que llaman este script NO deben tener <nav> ni <footer> propios.
─────────────────────────────────────────────────────────────────── */

(function () {
  /* ── NAV ── */
  const NAV_HTML = `
<nav class="nav" id="navbar">
  <a href="/" class="nav-logo">
    <img src="/assets/images/Logo_SkyPets.webp" alt="SkyPets Colombia" onerror="this.style.display='none'">
    <span class="nav-brand"><span class="sky">Sky</span><span class="pets">Pets</span></span>
  </a>
  <ul class="nav-links">
    <li><a href="/servicios">Servicios</a></li>
    <li><a href="/nosotros">Nosotros</a></li>
    <li><a href="/blog">Blog</a></li>
    <li><a href="/contacto">Contacto</a></li>
  </ul>
  <div class="nav-actions">
    <a href="/portal" class="nav-portal" id="navPortalBtn">🐾 Mi Portal</a>
    <a href="https://wa.me/573213556909?text=Hola%20SkyPets%2C%20quiero%20información%20para%20viajar%20con%20mi%20mascota" class="nav-cta">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" style="vertical-align:middle;flex-shrink:0"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
      Cotiza Ahora
    </a>
  </div>
  <button class="nav-hamburger" id="hamburger" aria-label="Menú" aria-expanded="false">
    <span></span><span></span><span></span>
  </button>
</nav>
<div class="nav-mobile" id="mobileMenu">
  <a href="/servicios">Servicios</a>
  <a href="/nosotros">Nosotros</a>
  <a href="/blog">Blog</a>
  <a href="/contacto">Contacto</a>
  <a href="https://wa.me/573213556909?text=Hola%20SkyPets%2C%20quiero%20información%20para%20viajar%20con%20mi%20mascota" class="mob-cta">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
    Cotiza Ahora
  </a>
</div>`;

  /* ── FOOTER ── */
  const FOOTER_HTML = `
<footer id="contacto">
  <div class="footer-grid">
    <div class="footer-brand">
      <div class="footer-logo-wrap">
        <img src="/assets/images/Logo_SkyPets.webp" alt="SkyPets" style="height:44px;width:auto;">
        <span class="footer-brand-name">
          <span class="sky">Sky</span><span class="pets">Pets</span>
        </span>
      </div>
      <p>Asesoría integral para el viaje de tu mascota. Con amor, experiencia y acompañamiento experto desde Bogotá, Colombia.</p>
      <div class="social-row">
        <a href="https://facebook.com/skypetscol" class="social-btn" aria-label="Facebook">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="white" xmlns="http://www.w3.org/2000/svg"><path d="M24 12.073C24 5.446 18.627 0 12 0S0 5.446 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.43c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.97H15.83c-1.491 0-1.956.93-1.956 1.886v2.247h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/></svg>
        </a>
        <a href="https://instagram.com/skypetcol" class="social-btn" aria-label="Instagram">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="2" y="2" width="20" height="20" rx="5.5" stroke="white" stroke-width="2"/><circle cx="12" cy="12" r="4.5" stroke="white" stroke-width="2"/><circle cx="17.5" cy="6.5" r="1.5" fill="white"/></svg>
        </a>
        <a href="https://tiktok.com/@skypetstravel" class="social-btn" aria-label="TikTok">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="white" xmlns="http://www.w3.org/2000/svg"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.27 6.27 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.69a8.18 8.18 0 004.79 1.52V6.78a4.85 4.85 0 01-1.02-.09z"/></svg>
        </a>
        <a href="https://wa.me/573213556909" class="social-btn social-btn--wa" aria-label="WhatsApp">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="#25D366" xmlns="http://www.w3.org/2000/svg"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        </a>
      </div>
    </div>
    <div class="footer-col">
      <h4>Sitio</h4>
      <ul>
        <li><a href="/">Inicio</a></li>
        <li><a href="/servicios">Servicios</a></li>
        <li><a href="/nosotros">Nosotros</a></li>
        <li><a href="/blog">Blog</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h4>Legal</h4>
      <ul>
        <li><a href="/terminos-y-condiciones">Términos y Condiciones</a></li>
        <li><a href="/politica-de-privacidad">Política de Privacidad</a></li>
        <li><a href="/politica-de-cookies">Política de Cookies</a></li>
        <li><a href="/nosotros">Acerca de Nosotros</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h4>Contacto</h4>
      <div class="footer-contact">
        <div class="footer-cr">📍 Bogotá D.C., Colombia</div>
        <div class="footer-cr">📞 +57 321 355 6909</div>
        <div class="footer-cr">📧 info@skypetscol.com</div>
        <div class="footer-cr">🕗 Lun–Dom 8am–10pm</div>
        <div class="footer-cr">📸 <a href="https://instagram.com/skypetcol" style="color:rgba(255,250,236,0.48);text-decoration:none;transition:color 0.2s" onmouseover="this.style.color='#FF7600'" onmouseout="this.style.color='rgba(255,250,236,0.48)'">@skypetcol</a></div>
        <div class="footer-cr">🎵 <a href="https://tiktok.com/@skypetstravel" style="color:rgba(255,250,236,0.48);text-decoration:none;transition:color 0.2s" onmouseover="this.style.color='#FF7600'" onmouseout="this.style.color='rgba(255,250,236,0.48)'">@skypetstravel</a></div>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <span>© 2026 SkyPets — Todos los derechos reservados</span>
    <span>Hecho con 🧡 en Bogotá</span>
  </div>
</footer>

<!-- WhatsApp flotante -->
<a href="https://wa.me/573213556909?text=Hola%20SkyPets%2C%20quiero%20información%20para%20viajar%20con%20mi%20mascota"
   class="wa-float" aria-label="Escríbenos por WhatsApp">
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28" fill="white"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
</a>`;

  /* ── Inyectar nav al inicio del body ── */
  document.body.insertAdjacentHTML('afterbegin', NAV_HTML);

  /* ── Inyectar footer al final del body ── */
  document.body.insertAdjacentHTML('beforeend', FOOTER_HTML);

  /* ── Marcar link activo según URL actual ── */
  const path = window.location.pathname.replace(/\/$/, '');
  document.querySelectorAll('.nav-links a, .mobile-menu a').forEach(function (a) {
    const href = a.getAttribute('href').replace(/\/$/, '');
    if (href && path.startsWith(href)) a.classList.add('active');
  });

  /* ── Hamburger menu ── */
  const hamburger  = document.getElementById('hamburger');
  const mobileMenu = document.getElementById('mobileMenu');
  if (hamburger && mobileMenu) {
    hamburger.addEventListener('click', function () {
      const open = mobileMenu.classList.toggle('open');
      hamburger.classList.toggle('open', open);
      hamburger.setAttribute('aria-expanded', String(open));
    });
    document.addEventListener('click', function (e) {
      if (!hamburger.contains(e.target) && !mobileMenu.contains(e.target)) {
        mobileMenu.classList.remove('open');
        hamburger.classList.remove('open');
        hamburger.setAttribute('aria-expanded', 'false');
      }
    });
    /* Cerrar al hacer clic en un link del menú */
    mobileMenu.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () {
        mobileMenu.classList.remove('open');
        hamburger.classList.remove('open');
        hamburger.setAttribute('aria-expanded', 'false');
      });
    });
  }

  /* ── Nav scroll effect ── */
  const navbar = document.getElementById('navbar');
  if (navbar) {
    window.addEventListener('scroll', function () {
      navbar.classList.toggle('scrolled', window.scrollY > 40);
    }, { passive: true });
  }
})();
