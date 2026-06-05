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

## Estilo visual — Liquidglass (desde index.html, estándar del proyecto)
- **NO usar** bordes sólidos `2px solid #2B2418` ni sombras neobrutalist `4px 4px 0 #2B2418`
- **Botones**: pill (`border-radius:100px`), sin border sólido, `backdrop-filter:blur(12px)`, sombra con color de acento
  ```css
  /* Botón principal naranja */
  background: rgba(255,118,0,0.9); box-shadow: 0 4px 20px rgba(255,118,0,0.38);
  backdrop-filter: blur(12px); transition: transform 0.2s, box-shadow 0.2s;
  /* Hover */
  transform: translateY(-2px); box-shadow: 0 8px 28px rgba(255,118,0,0.52);
  ```
- **Cards**: `background: rgba(255,255,255,0.75); backdrop-filter: blur(16px); border: 1.5px solid rgba(43,36,24,0.1); box-shadow: 0 8px 32px rgba(43,36,24,0.1);`
- **Toggles / selectors**: `border: 1.5px solid rgba(43,36,24,0.14); background: rgba(255,250,236,0.55); backdrop-filter: blur(8px)`; cuando selected → color sólido + `box-shadow: 0 4px 16px rgba(color,0.32)`
- **Sombras suaves** (no ink): siempre con alpha `rgba(43,36,24,0.08~0.14)` o con color de acento
- Bordes redondeados: sm=10px, md=16px, lg=24px, xl=36px

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

## Routing de modelos para subagentes

Cuando se usen subagentes (Agent tool), elegir el modelo según la tarea:

| Modelo | Cuándo usarlo | Ejemplos |
|--------|---------------|---------|
| `haiku` | Búsqueda, lectura, tareas rápidas y de bajo costo | Buscar un selector en HTML, grep de símbolo, leer un archivo, verificar que un enlace existe, listar archivos |
| `sonnet` | Escritura de código, edición de HTML/CSS/JS, tareas normales | Modificar una sección, agregar una card, actualizar estilos, escribir copy |
| `opus` | Revisión de arquitectura, decisiones críticas, análisis profundo | Revisar estructura del design system, evaluar refactors grandes, auditar accesibilidad o SEO global |

### Reglas de aplicación
- **Por defecto usar `sonnet`** — es el balance correcto para la mayoría de ediciones.
- **Forzar `haiku`** cuando el único objetivo es leer/buscar sin escribir nada.
- **Reservar `opus`** únicamente para preguntas del tipo "¿cómo debería estructurar X?" o revisiones completas de una feature antes de implementarla.
- **No usar `opus` para ediciones puntuales** aunque sean difíciles — ahí va `sonnet`.
- Si hay dudas entre haiku y sonnet, elegir haiku solo si la tarea es 100% lectura.

## Tareas frecuentes
- **Actualizar texto:** editar directamente el HTML en la sección correspondiente
- **Cambiar imagen de banner:** buscar `hero-slide-N` y actualizar el `src` o `background-image`
- **Agregar nuevo artículo de blog:** copiar plantilla de `blog/como-preparar-a-tu-perro...html`, actualizar contenido, agregar card en `blog/index.html`
- **Actualizar estadísticas (591, 384...):** buscar `data-target` en index.html y actualizar el número
- **Agregar nuevo servicio:** copiar un `.svc-card` en servicios.html y actualizar contenido
