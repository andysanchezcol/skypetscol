-- ============================================================
-- SkyPets — Migration v6
-- Sincronización entre el checklist de viajes de Arca (arca.trip_documents)
-- y los documentos subidos al portal de clientes (public.documents).
--
-- Dos funciones RPC en `public` (siempre expuesto vía PostgREST, sin
-- depender de si el schema `arca` está expuesto):
--   - get_trip_checklist: lee el checklist completo (16 ítems) del viaje
--     activo más reciente de un cliente, por email. Usada por admin.html
--     (rol authenticated) para mostrar qué falta por entregar.
--   - mark_trip_document_sent: marca un ítem del checklist como "enviado"
--     cuando se sube el documento correspondiente al portal. Solo
--     ejecutable por service_role (la llama la Edge Function
--     notify-new-document, nunca el navegador).
-- ============================================================

create or replace function public.get_trip_checklist(p_email text)
returns table(
  trip_id uuid,
  pet_name text,
  flight_date timestamptz,
  destination text,
  doc_name text,
  doc_status text,
  doc_sent_at timestamptz
)
language plpgsql
security definer
set search_path = public, arca
as $$
begin
  return query
    select t.id, t.pet_name, t.flight_date, t.destination,
           td.name, td.status, td.sent_at
    from arca.trips t
    join arca.clients c on c.id = t.client_id
    join arca.trip_documents td on td.trip_id = t.id
    where lower(c.email) = lower(p_email)
      and t.archived = false
    order by t.flight_date asc, td.created_at asc
    limit 50;
end;
$$;

revoke all on function public.get_trip_checklist(text) from public;
revoke all on function public.get_trip_checklist(text) from anon;
grant execute on function public.get_trip_checklist(text) to authenticated;
grant execute on function public.get_trip_checklist(text) to service_role;

-- ------------------------------------------------------------

create or replace function public.mark_trip_document_sent(p_email text, p_doc_type text)
returns boolean
language plpgsql
security definer
set search_path = public, arca
as $$
declare
  v_doc_name text;
  v_trip_id uuid;
  v_row_count int;
begin
  v_doc_name := case p_doc_type
    when 'certificado_apoyo_emocional'     then 'Certificado Apoyo Emocional'
    when 'certificado_asistencia_servicio' then 'Certificado de Servicio'
    when 'certificado_salud_nacional'      then 'Certificado de Salud Nacional'
    when 'certificado_salud_internacional' then 'Certificado de Salud Internacional'
    when 'anexo_europa'                    then 'Anexo Europa'
    when 'anexo_latinoamerica'             then 'Anexo Latinoamerica'
    when 'certificado_cdc'                 then 'CDC'
    when 'microchip_certificado'           then 'Microchip'
    when 'prueba_titulacion_rabia'         then 'Serología'
    else null
  end;

  if v_doc_name is null then
    return false;
  end if;

  select t.id into v_trip_id
  from arca.trips t
  join arca.clients c on c.id = t.client_id
  where lower(c.email) = lower(p_email)
    and t.archived = false
  order by t.flight_date asc
  limit 1;

  if v_trip_id is null then
    return false;
  end if;

  update arca.trip_documents
  set status = 'enviado', sent_at = now()
  where trip_id = v_trip_id
    and name = v_doc_name
    and status = 'pendiente';

  get diagnostics v_row_count = row_count;
  return v_row_count > 0;
end;
$$;

revoke all on function public.mark_trip_document_sent(text, text) from public;
revoke all on function public.mark_trip_document_sent(text, text) from anon;
revoke all on function public.mark_trip_document_sent(text, text) from authenticated;
grant execute on function public.mark_trip_document_sent(text, text) to service_role;
