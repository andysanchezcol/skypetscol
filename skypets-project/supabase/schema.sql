-- ============================================================
-- SkyPets Colombia — Supabase Database Schema
-- Ejecutar en: Supabase Dashboard → SQL Editor
-- ============================================================

-- ============================================================
-- EXTENSIONES
-- ============================================================
create extension if not exists "uuid-ossp";


-- ============================================================
-- TABLA: profiles
-- Extiende auth.users con datos del perfil del cliente
-- ============================================================
create table public.profiles (
  id          uuid primary key references auth.users(id) on delete cascade,
  full_name   text,
  phone       text,
  email       text,
  avatar_url  text,
  city        text,
  country     text default 'Colombia',
  created_at  timestamptz not null default now(),
  updated_at  timestamptz not null default now()
);

comment on table public.profiles is 'Perfil público de cada cliente SkyPets.';


-- ============================================================
-- TABLA: pets
-- Mascotas registradas por cada usuario
-- ============================================================
create table public.pets (
  id               uuid primary key default uuid_generate_v4(),
  owner_id         uuid not null references public.profiles(id) on delete cascade,

  name             text not null,
  species          text not null check (species in ('perro', 'gato', 'ave', 'otro')),
  breed            text,
  sex              text check (sex in ('macho', 'hembra')),
  birth_date       date,
  weight_kg        numeric(5, 2),
  color            text,

  microchip        text unique,
  passport_number  text unique,
  photo_url        text,
  notes            text,

  created_at       timestamptz not null default now(),
  updated_at       timestamptz not null default now()
);

comment on table public.pets is 'Mascotas asociadas a cada perfil de cliente.';


-- ============================================================
-- TABLA: trips
-- Viajes de asesoría solicitados por los clientes
-- ============================================================
create type public.trip_status as enum (
  'borrador',
  'en_proceso',
  'documentacion_lista',
  'completado',
  'cancelado'
);

create table public.trips (
  id                  uuid primary key default uuid_generate_v4(),
  owner_id            uuid not null references public.profiles(id) on delete cascade,
  pet_id              uuid not null references public.pets(id) on delete restrict,

  origin_city         text not null,
  origin_country      text not null,
  destination_city    text not null,
  destination_country text not null,

  departure_date      date,
  return_date         date,
  airline             text,
  flight_number       text,
  cabin_or_cargo      text check (cabin_or_cargo in ('cabina', 'bodega', 'cargo')),

  status              public.trip_status not null default 'borrador',
  advisor_notes       text,
  client_notes        text,

  created_at          timestamptz not null default now(),
  updated_at          timestamptz not null default now(),

  constraint departure_before_return check (
    return_date is null or departure_date is null or return_date >= departure_date
  )
);

comment on table public.trips is 'Asesorías de viaje aéreo para mascotas.';


-- ============================================================
-- TABLA: documents
-- Documentos veterinarios y de viaje asociados a mascota o viaje
-- ============================================================
create type public.document_type as enum (
  'certificado_salud',
  'cartilla_vacunacion',
  'permiso_importacion',
  'permiso_exportacion',
  'pasaporte_mascota',
  'microchip_certificado',
  'prueba_titulacion_rabia',
  'tapete_aerolinea',
  'otro'
);

create type public.document_status as enum (
  'pendiente',
  'vigente',
  'vencido',
  'rechazado'
);

create table public.documents (
  id          uuid primary key default uuid_generate_v4(),
  owner_id    uuid not null references public.profiles(id) on delete cascade,
  pet_id      uuid references public.pets(id) on delete set null,
  trip_id     uuid references public.trips(id) on delete set null,

  type        public.document_type not null,
  name        text not null,
  file_url    text,
  issued_date date,
  expiry_date date,
  status      public.document_status not null default 'pendiente',
  notes       text,

  created_at  timestamptz not null default now(),
  updated_at  timestamptz not null default now(),

  constraint expiry_after_issue check (
    expiry_date is null or issued_date is null or expiry_date >= issued_date
  )
);

comment on table public.documents is 'Documentos veterinarios y de viaje por mascota o asesoría.';


-- ============================================================
-- ÍNDICES
-- ============================================================

-- pets
create index idx_pets_owner_id   on public.pets(owner_id);
create index idx_pets_microchip  on public.pets(microchip) where microchip is not null;

-- trips
create index idx_trips_owner_id        on public.trips(owner_id);
create index idx_trips_pet_id          on public.trips(pet_id);
create index idx_trips_status          on public.trips(status);
create index idx_trips_departure_date  on public.trips(departure_date);

-- documents
create index idx_documents_owner_id    on public.documents(owner_id);
create index idx_documents_pet_id      on public.documents(pet_id)  where pet_id  is not null;
create index idx_documents_trip_id     on public.documents(trip_id) where trip_id is not null;
create index idx_documents_type        on public.documents(type);
create index idx_documents_status      on public.documents(status);
create index idx_documents_expiry_date on public.documents(expiry_date) where expiry_date is not null;


-- ============================================================
-- FUNCIÓN: actualizar updated_at automáticamente
-- ============================================================
create or replace function public.handle_updated_at()
returns trigger language plpgsql as $$
begin
  new.updated_at = now();
  return new;
end;
$$;

-- Triggers updated_at
create trigger trg_profiles_updated_at
  before update on public.profiles
  for each row execute function public.handle_updated_at();

create trigger trg_pets_updated_at
  before update on public.pets
  for each row execute function public.handle_updated_at();

create trigger trg_trips_updated_at
  before update on public.trips
  for each row execute function public.handle_updated_at();

create trigger trg_documents_updated_at
  before update on public.documents
  for each row execute function public.handle_updated_at();


-- ============================================================
-- FUNCIÓN: crear perfil automáticamente al registrarse
-- ============================================================
create or replace function public.handle_new_user()
returns trigger language plpgsql security definer set search_path = public as $$
begin
  insert into public.profiles (id, email, full_name)
  values (
    new.id,
    new.email,
    coalesce(new.raw_user_meta_data->>'full_name', '')
  );
  return new;
end;
$$;

create trigger trg_on_auth_user_created
  after insert on auth.users
  for each row execute function public.handle_new_user();


-- ============================================================
-- ROW LEVEL SECURITY (RLS)
-- ============================================================

alter table public.profiles  enable row level security;
alter table public.pets       enable row level security;
alter table public.trips      enable row level security;
alter table public.documents  enable row level security;


-- ---- PROFILES ----

create policy "profiles: usuario ve su propio perfil"
  on public.profiles for select
  using (auth.uid() = id);

create policy "profiles: usuario actualiza su propio perfil"
  on public.profiles for update
  using (auth.uid() = id)
  with check (auth.uid() = id);

-- El insert lo realiza el trigger handle_new_user (security definer),
-- así que no se necesita política INSERT para el usuario normal.


-- ---- PETS ----

create policy "pets: usuario ve sus propias mascotas"
  on public.pets for select
  using (auth.uid() = owner_id);

create policy "pets: usuario inserta sus propias mascotas"
  on public.pets for insert
  with check (auth.uid() = owner_id);

create policy "pets: usuario actualiza sus propias mascotas"
  on public.pets for update
  using (auth.uid() = owner_id)
  with check (auth.uid() = owner_id);

create policy "pets: usuario elimina sus propias mascotas"
  on public.pets for delete
  using (auth.uid() = owner_id);


-- ---- TRIPS ----

create policy "trips: usuario ve sus propios viajes"
  on public.trips for select
  using (auth.uid() = owner_id);

create policy "trips: usuario crea sus propios viajes"
  on public.trips for insert
  with check (auth.uid() = owner_id);

create policy "trips: usuario actualiza sus propios viajes"
  on public.trips for update
  using (auth.uid() = owner_id)
  with check (auth.uid() = owner_id);

create policy "trips: usuario cancela sus propios viajes"
  on public.trips for delete
  using (auth.uid() = owner_id);


-- ---- DOCUMENTS ----

create policy "documents: usuario ve sus propios documentos"
  on public.documents for select
  using (auth.uid() = owner_id);

create policy "documents: usuario sube sus propios documentos"
  on public.documents for insert
  with check (auth.uid() = owner_id);

create policy "documents: usuario actualiza sus propios documentos"
  on public.documents for update
  using (auth.uid() = owner_id)
  with check (auth.uid() = owner_id);

create policy "documents: usuario elimina sus propios documentos"
  on public.documents for delete
  using (auth.uid() = owner_id);


-- ============================================================
-- STORAGE: bucket para documentos y fotos de mascotas
-- (ejecutar desde Dashboard → Storage o con service_role key)
-- ============================================================

-- insert into storage.buckets (id, name, public)
-- values ('pet-documents', 'pet-documents', false);

-- insert into storage.buckets (id, name, public)
-- values ('pet-photos', 'pet-photos', true);

-- Política de storage para pet-documents (privado):
-- create policy "doc storage: solo el dueño"
--   on storage.objects for all
--   using (bucket_id = 'pet-documents' and auth.uid()::text = (storage.foldername(name))[1]);

-- Política de storage para pet-photos (público read, dueño write):
-- create policy "photo storage: lectura pública"
--   on storage.objects for select
--   using (bucket_id = 'pet-photos');
-- create policy "photo storage: solo el dueño escribe"
--   on storage.objects for insert
--   with check (bucket_id = 'pet-photos' and auth.uid()::text = (storage.foldername(name))[1]);
