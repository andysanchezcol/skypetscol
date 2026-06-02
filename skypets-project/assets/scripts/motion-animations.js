/* SkyPets — Motion animations (motion.dev CDN)
 * Complementa main.js sin reemplazarlo.
 * Respeta prefers-reduced-motion automáticamente.
 */
import { animate, inView, scroll, spring } from "https://cdn.jsdelivr.net/npm/motion@latest/+esm";

const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

/* ─── 1. REVEAL SUAVE EN SCROLL ────────────────────────────────────────────
 * Reemplaza el sistema CSS .reveal → .visible con animaciones más fluidas.
 * Fade + slide-up con stagger para grids.
 */
if (!reduceMotion) {
  /* Cards en grid (equipo, servicios, blog) — stagger 60ms */
  inView(".equipo-grid .team-card, .svc-grid .svc-card, .blog-grid .blog-card", (info) => {
    const siblings = info.target.parentElement.querySelectorAll(
      ".team-card, .svc-card, .blog-card"
    );
    const index = Array.from(siblings).indexOf(info.target);

    animate(
      info.target,
      { opacity: [0, 1], y: [32, 0], scale: [0.97, 1] },
      {
        duration: 0.45,
        delay: index * 0.06,
        easing: [0.25, 0.46, 0.45, 0.94], /* ease-out-quart */
      }
    );
  }, { amount: 0.15 });

  /* Elementos genéricos .reveal que no son cards */
  inView(".reveal:not(.team-card):not(.svc-card):not(.blog-card)", (info) => {
    animate(
      info.target,
      { opacity: [0, 1], y: [24, 0] },
      { duration: 0.5, easing: "ease-out" }
    );
  }, { amount: 0.1 });

  /* Títulos de sección */
  inView(".section-title, .section-sub", (info) => {
    animate(
      info.target,
      { opacity: [0, 1], y: [20, 0] },
      { duration: 0.55, easing: "ease-out" }
    );
  }, { amount: 0.5 });
}

/* ─── 2. HOVER EN CARDS — spring suave ─────────────────────────────────────
 * Aplica escala spring al hacer hover en cards interactivas.
 */
if (!reduceMotion) {
  document.querySelectorAll(
    ".team-card, .svc-card, .blog-card, .valor-card, .process-step"
  ).forEach((card) => {
    card.addEventListener("mouseenter", () => {
      animate(card, { scale: 1.025, y: -4 }, {
        easing: spring({ stiffness: 400, damping: 28 }),
        duration: 0.3,
      });
    });
    card.addEventListener("mouseleave", () => {
      animate(card, { scale: 1, y: 0 }, {
        easing: spring({ stiffness: 300, damping: 30 }),
        duration: 0.3,
      });
    });
  });
}

/* ─── 3. BOTONES — feedback de press ───────────────────────────────────────
 * Escala sutil al hacer clic en CTAs.
 */
if (!reduceMotion) {
  document.querySelectorAll(".btn, a[href*='wa.me']").forEach((btn) => {
    btn.addEventListener("mousedown", () => {
      animate(btn, { scale: 0.95 }, { duration: 0.1, easing: "ease-out" });
    });
    btn.addEventListener("mouseup", () => {
      animate(btn, { scale: 1 }, {
        easing: spring({ stiffness: 500, damping: 25 }),
        duration: 0.2,
      });
    });
    btn.addEventListener("mouseleave", () => {
      animate(btn, { scale: 1 }, { duration: 0.15 });
    });
  });
}

/* ─── 4. HERO STICKER STATS — entrada con spring ────────────────────────────
 * Los stat-stickers del hero entran con rebote suave.
 */
if (!reduceMotion) {
  inView(".stat-sticker", (info) => {
    const stickers = document.querySelectorAll(".stat-sticker");
    const index = Array.from(stickers).indexOf(info.target);
    animate(
      info.target,
      { opacity: [0, 1], scale: [0.7, 1], rotate: [index % 2 === 0 ? -6 : 6, 0] },
      {
        duration: 0.6,
        delay: index * 0.1,
        easing: spring({ stiffness: 350, damping: 22 }),
      }
    );
  }, { amount: 0.5 });
}

/* ─── 5. PARALLAX SUAVE EN HERO ─────────────────────────────────────────────
 * El contenido del hero se desplaza levemente al hacer scroll.
 */
if (!reduceMotion) {
  const heroContent = document.querySelector(".hero-content, .hero-text");
  if (heroContent) {
    scroll(
      animate(heroContent, { y: [0, 60], opacity: [1, 0.6] }),
      { target: heroContent.closest("section, .hero"), offset: ["start start", "end start"] }
    );
  }
}

/* ─── 6. NAV — entrada al cargar ────────────────────────────────────────────
 */
if (!reduceMotion) {
  const nav = document.getElementById("nav");
  if (nav) {
    animate(nav, { opacity: [0, 1], y: [-16, 0] }, { duration: 0.4, easing: "ease-out" });
  }
}
