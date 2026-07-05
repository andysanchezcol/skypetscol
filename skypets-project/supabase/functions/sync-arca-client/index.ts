// Disparada por un Database Webhook de Supabase en INSERT sobre arca.clients.
// Crea la cuenta de portal (auth.users + public.profiles) del cliente en silencio
// (sin enviar ningún correo) cuando una asesora lo registra en Arca, para que ya
// exista cuando le subamos un documento — evita mantener dos listas de clientes
// por separado. El cliente inicia sesión más adelante con el Magic Link ya
// existente del portal; el aviso de bienvenida se envía junto con el primer
// documento, no en este paso.
import { createClient } from 'https://esm.sh/@supabase/supabase-js@2'

interface ArcaClientRecord {
  id: string
  full_name: string
  email: string | null
  phone: string | null
  city: string | null
  pet_name: string | null
}

Deno.serve(async (req: Request) => {
  const payload = await req.json()
  const record = payload.record as ArcaClientRecord

  if (!record.email) {
    return Response.json({ skipped: 'sin_email' })
  }

  const supabaseAdmin = createClient(
    Deno.env.get('SUPABASE_URL')!,
    Deno.env.get('SUPABASE_SERVICE_ROLE_KEY')!,
  )

  const { data: existing } = await supabaseAdmin
    .from('profiles')
    .select('id')
    .ilike('email', record.email)
    .maybeSingle()

  if (existing) {
    return Response.json({ skipped: 'cuenta_existente', user_id: existing.id })
  }

  const { data: created, error: createErr } = await supabaseAdmin.auth.admin.createUser({
    email: record.email,
    email_confirm: true, // no envía correo de confirmación — cuenta lista en silencio
    user_metadata: { full_name: record.full_name },
  })

  if (createErr || !created.user) {
    return Response.json({ error: createErr?.message ?? 'create_failed' }, { status: 500 })
  }

  await supabaseAdmin
    .from('profiles')
    .update({ phone: record.phone, city: record.city })
    .eq('id', created.user.id)

  if (record.pet_name) {
    await supabaseAdmin.from('pets').insert({
      owner_id: created.user.id,
      name: record.pet_name,
      species: 'otro', // se ajusta después cuando la asesora complete la ficha real de la mascota
    })
  }

  return Response.json({ created: true, user_id: created.user.id })
})
