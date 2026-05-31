# SkyPets Colombia — Sitio Web

Sitio web estático de [SkyPets Colombia](https://skypetscol.com), empresa de asesoría de viaje aéreo para mascotas.

## Stack
- HTML5 / CSS3 / Vanilla JS — sin frameworks ni build tools
- Hosting: [Netlify](https://netlify.com) (plan gratuito)
- Dominio: skypetscol.com (apuntado a Netlify)

## Estructura
```
/
├── index.html                          ← Home
├── servicios.html                      ← Servicios
├── nosotros.html                       ← Nosotros / Equipo
├── contacto.html                       ← Contacto + FAQ
├── terminos-y-condiciones.html         ← Legal
├── politica-de-privacidad.html         ← Legal
├── blog/
│   ├── index.html                      ← Índice del blog
│   ├── como-preparar-a-tu-perro...html ← Artículo 1
│   └── como-preparar-a-tu-mascota...html ← Artículo 2
├── assets/
│   ├── images/                         ← Logo, hero slides, fotos blog
│   ├── styles/
│   │   └── tokens.css                  ← Design system tokens
│   └── scripts/
│       └── main.js                     ← JS compartido
├── netlify.toml                        ← Config de deploy
└── CLAUDE.md                           ← Instrucciones para Claude Code
```

## Cómo publicar en Netlify

1. Sube este proyecto a un repositorio de GitHub
2. Ve a [netlify.com](https://netlify.com) → "Add new site" → "Import from Git"
3. Selecciona el repositorio
4. En "Publish directory" escribe: `.`
5. Haz clic en "Deploy site"
6. En "Domain settings", conecta `skypetscol.com` cambiando los nameservers a Netlify

## Cómo hacer cambios con Claude Code

```bash
# Instalar Claude Code (solo la primera vez)
npm install -g @anthropic-ai/claude-code

# Abrir el proyecto
cd skypets-project
claude

# Ejemplos de comandos en lenguaje natural:
# "Actualiza el número de viajeros internacionales a 650 en la home"
# "Agrega un nuevo artículo de blog sobre requisitos para viajar a España"
# "Cambia la imagen del slide 1 del banner principal"
# "Agrega el servicio de Pet Taxi a la página de servicios"
```

## Design System

Ver `CLAUDE.md` para tokens completos y reglas de diseño.
Colores principales:
- Naranja: `#FF7600`
- Teal: `#008D83`
- Amarillo: `#FFBC00`
- Fondo crema: `#FFFAEC`

## Contacto
- WhatsApp: +57 321 355 6909
- Email: info@skypetscol.com
- Instagram: [@skypetcol](https://instagram.com/skypetcol)
