-- ============================================================
-- SkyPets — Migration v2
-- Solo ejecutar si ya tienes el schema v1 corrido
-- Agrega: is_admin, nuevos tipos de documento, función is_admin()
-- y corrige políticas RLS de documents
-- ============================================================

-- 1. Agregar columna is_admin a profiles (si no existe)
alter table public.profiles
  add column if not exists is_admin boolean not null default false;

-- 2. Agregar columna file_path a documents (si no existe)
alter table public.documents
  add column if not exists file_path text;

-- 3. Agregar columna uploaded_by a documents (si no existe)
alter table public.documents
  add column if not exists uploaded_by uuid references public.profiles(id);

-- 4. Agregar nuevos valores al enum document_type (si no existen)
do $$ begin
  alter type public.document_type add value if not exists 'certificado_apoyo_emocional';
exception when duplicate_object then null;
end $$;

do $$ begin
  alter type public.document_type add value if not exists 'certificado_asistencia_servicio';
exception when duplicate_object then null;
end $$;

-- 5. Función helper is_admin()
create or replace function public.is_admin()
returns boolean language sql security definer as $$
  select coalesce(
    (select is_admin from public.profiles where id = auth.uid()),
    false
  );
$$;

-- 6. Eliminar políticas antiguas de documents que daban write al cliente
drop policy if exists "documents: usuario sube sus propios documentos"    on public.documents;
drop policy if exists "documents: usuario actualiza sus propios documentos" on public.documents;
drop policy if exists "documents: usuario elimina sus propios documentos"  on public.documents;

-- 7. Política SELECT para cliente (solo sus docs)
drop policy if exists "documents: cliente ve sus documentos" on public.documents;
create policy "documents: cliente ve sus documentos"
  on public.documents for select
  using (auth.uid() = owner_id);

-- 8. Política total para admin
drop policy if exists "documents: admin acceso total" on public.documents;
create policy "documents: admin acceso total"
  on public.documents for all
  using (public.is_admin())
  with check (public.is_admin());

-- 9. Política admin ve todos los perfiles
drop policy if exists "profiles: admin ve todos los perfiles" on public.profiles;
create policy "profiles: admin ve todos los perfiles"
  on public.profiles for select
  using (public.is_admin());

-- 10. Política admin ve todas las mascotas
drop policy if exists "pets: admin ve todas las mascotas" on public.pets;
create policy "pets: admin ve todas las mascotas"
  on public.pets for select
  using (public.is_admin());

-- Listo
select 'Migration v2 aplicada correctamente ✓' as resultado;
