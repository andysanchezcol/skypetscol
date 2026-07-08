-- ============================================================
-- SkyPets — Migration v8
-- Agrega email/teléfono del cliente a get_all_trips(), para poder ofrecer
-- "Subir documento" directo desde la pestaña Viajes de admin.html —
-- necesita el email para encontrar (o crear) la cuenta de portal
-- correspondiente en public.profiles.
-- ============================================================

drop function if exists public.get_all_trips();

create or replace function public.get_all_trips()
returns table(
  trip_id uuid,
  client_id uuid,
  client_name text,
  client_email text,
  client_phone text,
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
      t.client_id,
      t.client_name,
      c.email,
      c.phone,
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
    left join arca.clients c on c.id = t.client_id
    left join arca.trip_documents td on td.trip_id = t.id
    group by t.id, c.email, c.phone
    order by t.flight_date asc
    limit 500;
end;
$$;

revoke all on function public.get_all_trips() from public;
revoke all on function public.get_all_trips() from anon;
grant execute on function public.get_all_trips() to authenticated;
grant execute on function public.get_all_trips() to service_role;
