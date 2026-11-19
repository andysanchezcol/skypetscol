-- v11 (2026-11-19): mínimo privilegio en el portal. SIN APLICAR: hay que probarla primero con ROLLBACK (ver el final).
-- Proyecto Supabase «Skypets Portal» (jkrcdsneilbnuwvqgjba).
--
-- Por qué:
--  1) is_admin() es SECURITY DEFINER y no fijaba search_path (el asesor de seguridad de Supabase lo marca).
--  2) Un cliente podía crear/editar sus propios «trips» (con su estado) y sus «pets»; el portal no lo usa (los viajes y mascotas los crea
--     Arca o el equipo con la clave de servicio, que no pasa por estas reglas). Estado, dueño y precio no deben venir de un cliente.
--  3) El rol anon (visitante sin sesión) no necesita ningún permiso sobre estas tablas: el sitio solo usa anon para iniciar sesión.
--     RLS ya devuelve 0 filas, pero se quita el permiso de base para tener dos candados en vez de uno.
-- No toca «documents» a nivel de permisos: el panel admin inserta y borra documentos con la sesión de un admin (lo cubren sus políticas).

-- 1) is_admin() con search_path fijo
create or replace function public.is_admin()
returns boolean language sql security definer set search_path = public as $$
  select coalesce(
    (select is_admin from public.profiles where id = auth.uid()),
    false
  );
$$;

-- 2) trips y pets: el cliente solo lee lo suyo
drop policy if exists "trips: usuario crea viajes"        on public.trips;
drop policy if exists "trips: usuario actualiza viajes"   on public.trips;
drop policy if exists "pets: usuario gestiona sus mascotas"  on public.pets;
drop policy if exists "pets: usuario actualiza sus mascotas" on public.pets;
drop policy if exists "pets: usuario elimina sus mascotas"   on public.pets;
revoke insert, update, delete on public.trips from anon, authenticated;
revoke insert, update, delete on public.pets  from anon, authenticated;

-- 3) anon sin permisos sobre las tablas del portal
revoke all on public.profiles  from anon;
revoke all on public.pets      from anon;
revoke all on public.trips     from anon;
revoke all on public.documents from anon;

-- Cómo probar sin aplicar (Supabase → SQL Editor, o psql): envolver todo en una transacción y deshacer.
--   begin;
--     -- (pegar aquí las sentencias de arriba)
--     set local role anon;           select count(*) from public.documents;   -- debe fallar: permission denied
--     reset role;
--   rollback;
-- Después de aplicarla de verdad, con un cliente de prueba: el portal debe seguir mostrando sus documentos (select sobre documents/profiles).
