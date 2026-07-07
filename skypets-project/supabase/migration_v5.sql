-- ============================================================
-- SkyPets — Migration v5
-- Divide certificado_salud en dos tipos (Nacional/Internacional)
-- para poder mapear 1 a 1 contra el checklist de viajes de Arca
-- (arca.trip_documents: "Certificado de Salud Nacional" /
-- "Certificado de Salud Internacional"). El valor viejo
-- 'certificado_salud' se conserva por compatibilidad con
-- documentos ya subidos — solo deja de ofrecerse en subidas nuevas.
-- ============================================================

do $$ begin
  alter type public.document_type add value if not exists 'certificado_salud_nacional';
exception when duplicate_object then null;
end $$;

do $$ begin
  alter type public.document_type add value if not exists 'certificado_salud_internacional';
exception when duplicate_object then null;
end $$;
