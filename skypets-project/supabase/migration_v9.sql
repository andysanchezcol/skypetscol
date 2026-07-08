-- ============================================================
-- SkyPets — Migration v9
-- Devuelve el email de la asesora responsable del viaje activo de un
-- cliente, para copiarla (CC) en el correo de "documento nuevo" que ya
-- envía notify-new-document — así queda con acceso al documento sin
-- tener que pedirlo aparte.
--
-- Solo service_role puede llamarla (la usa la Edge Function, nunca el
-- navegador) — expone el email de staff (arca.profiles → auth.users),
-- no debe quedar accesible ni a clientes ni a otras asesoras vía REST.
-- ============================================================

create or replace function public.get_trip_asesor_email(p_email text)
returns text
language plpgsql
security definer
set search_path = public, arca, auth
as $$
declare
  v_asesor_email text;
begin
  select u.email into v_asesor_email
  from arca.trips t
  join arca.clients c on c.id = t.client_id
  join arca.profiles p on p.id = t.asesor_id
  join auth.users u on u.id = p.id
  where lower(c.email) = lower(p_email)
    and t.archived = false
  order by t.flight_date asc
  limit 1;

  return v_asesor_email;
end;
$$;

revoke all on function public.get_trip_asesor_email(text) from public;
revoke all on function public.get_trip_asesor_email(text) from anon;
revoke all on function public.get_trip_asesor_email(text) from authenticated;
grant execute on function public.get_trip_asesor_email(text) to service_role;
