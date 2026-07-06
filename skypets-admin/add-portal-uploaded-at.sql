-- Registra cuándo se subió por última vez el documento de un certificado
-- al portal de clientes, para mostrar el check "Subido" en el dashboard
-- sin quitar la opción de volver a subir (ej. tras una corrección).
ALTER TABLE certificados
  ADD COLUMN IF NOT EXISTS portal_uploaded_at DATETIME NULL;
