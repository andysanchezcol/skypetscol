# SkyPets Colombia — Sitio Web

Sitio web estático de [SkyPets Colombia](https://skypetscol.com), empresa de asesoría de viaje aéreo para mascotas.

## Stack
- HTML5 / CSS3 / Vanilla JS — sin frameworks ni build tools
- Hosting: HostGator (cPanel), publicado con el webhook `deploy.php` en cada push a `main`
- Dominio: skypetscol.com (nameservers de HostGator)

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
└── CLAUDE.md                           ← Instrucciones para Claude Code
```

## Cómo publicar

Haz push a `main`: el webhook `deploy.php` actualiza HostGator solo.

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
