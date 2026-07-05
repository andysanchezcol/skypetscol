-- ============================================================
-- SkyPets — Migration v3
-- Agrega tipos de documento usados por skypets-admin (certificados
-- veterinarios): CDC, Anexo Europa, Anexo Latinoamérica.
-- ============================================================

do $$ begin
  alter type public.document_type add value if not exists 'certificado_cdc';
exception when duplicate_object then null;
end $$;

do $$ begin
  alter type public.document_type add value if not exists 'anexo_europa';
exception when duplicate_object then null;
end $$;

do $$ begin
  alter type public.document_type add value if not exists 'anexo_latinoamerica';
exception when duplicate_object then null;
end $$;
