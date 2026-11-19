-- v10: un cliente ya no puede marcarse is_admin (ni cambiar su email) editando su propia fila.
-- Antes: la política "usuario actualiza su propio perfil" dejaba UPDATE en todas las columnas.
revoke update on public.profiles from anon, authenticated;
grant update (full_name, phone, city, country, avatar_url) on public.profiles to authenticated;
