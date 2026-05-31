# SkyPets Colombia — Instrucciones para Claude Code

## Contexto del proyecto
Sitio web estático de SkyPets Colombia (skypetscol.com), empresa de asesoría de viaje aéreo para mascotas. HTML/CSS/JS puro, sin frameworks ni dependencias externas de build.

## Design System — SIEMPRE respetar estos tokens

```css
--orange:  #FF7600   /* Primary — CTA, accents */
--teal:    #008D83   /* Secondary — secciones calm, headers */
--yellow:  #FFBC00   /* Accent — badges, highlights */
--success: #71CC1E
--danger:  #E5443A
--bg1:     #FFFAEC   /* Page surface */
--bg2:     #FFEFC2   /* Warm cream panel */
--bg3:     #FFE7A6   /* Highlight panel */
--ink:     #2B2418   /* Texto, bordes, sombras */
--cream:   #FFFAEC   /* Texto sobre fondos oscuros */
--muted:   #6B5540   /* Texto secundario */
```

## Regla crítica de legibilidad
- `background:var(--ink)` SOLO en: footer, filter bars, botones .btn-ink
- NUNCA usar --ink como fondo en cards, headers de widgets, badges de contenido
- Siempre verificar que haya contraste suficiente antes de aplicar cualquier color de fondo

## Tipografía
- **Fredoka** (700/600) → títulos, headings H1-H4, números grandes
- **Poppins** (300/400/500/600) → body, UI, labels, botones
- Cargar desde Google Fonts: `family=Fredoka:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600`

## Estilo visual (Design System sticker)
- Bordes: `2px solid #2B2418` en todos los elementos interactivos
- Sombra: `4px 4px 0 #2B2418` (cards normales), `6px 6px 0 #2B2418` (hover)
- Hover: `transform: translate(-2px, -2px)` + sombra mayor
- Bordes redondeados: sm=10px, md=16px, lg=24px, xl=36px
- Botones: siempre pill (border-radius:100px) + border + shadow

## Estructura de archivos
```
/
├── index.html          ← Home
├── servicios.html
├── nosotros.html
├── contacto.html
├── terminos-y-condiciones.html
├── politica-de-privacidad.html
├── blog/
│   ├── index.html
│   ├── como-preparar-a-tu-perro-para-volar-sin-estres.html
│   └── como-preparar-a-tu-mascota-antes-de-viajar.html
├── assets/
│   ├── images/
│   │   ├── logo-skypets.webp
│   │   ├── hero-slide-1.webp
│   │   ├── hero-slide-2.webp
│   │   └── hero-slide-3.webp
│   ├── styles/
│   │   └── tokens.css      ← Variables CSS globales
│   └── scripts/
│       └── main.js         ← Nav scroll, reveals, counters
└── CLAUDE.md
```

## WhatsApp
- Número: +57 321 355 6909
- URL base: `https://wa.me/573213556909`
- Siempre incluir `?text=` con mensaje pre-llenado en español

## Contacto y marca
- Email: info@skypetscol.com
- Instagram: @skypetcol
- TikTok: @skypetstravel
- Facebook: /skypetscol
- Bogotá D.C., Colombia · Lun-Dom 8am-10pm

## Páginas existentes (no crear de nuevo)
Todos los archivos HTML están en /pages/ — al modificar una página, editar el archivo correspondiente. No crear archivos nuevos sin confirmar.

## Tareas frecuentes
- **Actualizar texto:** editar directamente el HTML en la sección correspondiente
- **Cambiar imagen de banner:** buscar `hero-slide-N` y actualizar el `src` o `background-image`
- **Agregar nuevo artículo de blog:** copiar plantilla de `blog/como-preparar-a-tu-perro...html`, actualizar contenido, agregar card en `blog/index.html`
- **Actualizar estadísticas (591, 384...):** buscar `data-target` en index.html y actualizar el número
- **Agregar nuevo servicio:** copiar un `.svc-card` en servicios.html y actualizar contenido
