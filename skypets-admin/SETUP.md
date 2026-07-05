# SkyPets Admin — Guía de instalación

## Archivos que debes subir/configurar manualmente

### 1. Credenciales Google
- Renombra el JSON de la service account a `service-account.json`
- Súbelo a la carpeta `credentials/`

### 2. Assets de la veterinaria
Sube estas imágenes a `assets/images/`:
- `logo.png` — logo de SkyPets (copiar del sitio principal)
- `firma.png` — firma + sello de la Dra. Viviana (fondo transparente ideal)
- `cedula.jpg` — foto frontal de la cédula
- `tarjeta.jpg` — foto frontal de la tarjeta profesional

### 3. config.php — llenar estos campos
```php
define('DB_NAME', 'tu_base_de_datos');
define('DB_USER', 'tu_usuario_db');
define('DB_PASS', 'tu_contraseña_db');
```
Para la contraseña del dashboard, genera el hash así (en tu terminal o en PHP):
```php
echo password_hash('tu_clave_segura', PASSWORD_DEFAULT);
```
Y pégalo en `ADMIN_PASS`.

### 4. Instalar dependencias (vía SSH en HostGator)
```bash
cd /path/to/skypets-admin
composer install --no-dev --optimize-autoloader
```

### 5. Crear base de datos
- En cPanel de HostGator: crea una base de datos MySQL y un usuario
- Asigna todos los privilegios al usuario sobre esa base de datos
- Visita `https://admin.skypetscol.com/setup_db.php` una sola vez
- **Elimina `setup_db.php` del servidor inmediatamente después**

### 6. Subdominio en HostGator
- En cPanel → Subdominios → crear `admin.skypetscol.com`
- Apuntarlo a la carpeta donde subiste este proyecto

### 7. Carpeta tmp — permisos
```bash
chmod 755 tmp/
```

### 8. Compartir Drive con la service account
- En Google Drive, busca la carpeta donde se guardan los adjuntos del formulario
- Compartirla con: `skypets-certificados@skypets-certificados.iam.gserviceaccount.com`
- Permiso: Lector

## Flujo de uso
1. La veterinaria entra a `admin.skypetscol.com`
2. Ve la lista de solicitudes del Sheet en tiempo real
3. Hace clic en "Ver / Generar" en cualquier fila
4. Abre las fotos de referencia (carnet vacunas, desparasitantes)
5. Llena los datos médicos manualmente
6. Clic en "Guardar datos"
7. Clic en "Generar PDF" → se descarga el certificado listo
