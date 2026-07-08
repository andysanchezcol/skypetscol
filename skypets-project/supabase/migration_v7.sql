-- ============================================================
-- SkyPets — Migration v7
-- Lista completa de viajes de Arca para el panel de staff (admin.html),
-- independiente de si el cliente del viaje ya tiene cuenta en el portal.
--
-- Antes, admin.html solo mostraba el checklist de un viaje si el email del
-- cliente en arca.clients coincidía con un email en public.profiles
-- (get_trip_checklist). Los asesores ya están creando viajes nuevos en
-- Arca que aún no tienen ese cruce resuelto (cliente sin cuenta de portal
-- todavía, o el viaje ni siquiera tiene client_id asignado) — por eso no
-- aparecían. get_all_trips() no depende de ese cruce: lee directo de
-- arca.trips.
--
-- Solo se otorga a `authenticated` porque el propio cliente final del
-- portal también tiene ese rol — la función verifica public.is_admin()
-- y lanza excepción si quien la llama no es staff, para no exponer datos
-- de otros clientes.
-- ============================================================

create or replace function public.get_all_trips()
returns table(
  trip_id uuid,
  client_name text,
  pet_name text,
  flight_date timestamptz,
  destination text,
  airline text,
  seller_name text,
  archived boolean,
  total_docs int,
  sent_docs int,
  pendientes text[]
)
language plpgsql
security definer
set search_path = public, arca
as $$
begin
  if not public.is_admin() then
    raise exception 'not authorized';
  end if;

  return query
    select
      t.id,
      t.client_name,
      t.pet_name,
      t.flight_date,
      t.destination,
      t.airline,
      t.seller_name,
      t.archived,
      count(td.id)::int as total_docs,
      count(td.id) filter (where td.status = 'enviado')::int as sent_docs,
      array_agg(td.name order by td.name) filter (where td.status = 'pendiente') as pendientes
    from arca.trips t
    left join arca.trip_documents td on td.trip_id = t.id
    group by t.id
    order by t.flight_date asc
    limit 500;
end;
$$;

revoke all on function public.get_all_trips() from public;
revoke all on function public.get_all_trips() from anon;
grant execute on function public.get_all_trips() to authenticated;
grant execute on function public.get_all_trips() to service_role;
