// Disparada por un Database Webhook de Supabase en INSERT sobre arca.clients.
// Crea la cuenta de portal (auth.users + public.profiles) del cliente cuando
// una asesora lo registra en Arca, para que ya exista cuando le subamos un
// documento — evita mantener dos listas de clientes por separado. Además le
// envía un correo de bienvenida (vía Resend) con un link para que el propio
// cliente cree su contraseña la primera vez que entra al portal.
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

  const { data: linkData, error: linkErr } = await supabaseAdmin.auth.admin.generateLink({
    type: 'recovery',
    email: record.email,
    options: { redirectTo: 'https://skypetscol.com/portal' },
  })

  if (linkErr || !linkData) {
    return Response.json({ created: true, user_id: created.user.id, email_error: linkErr?.message })
  }

  const firstName = (record.full_name ?? '').trim().split(' ')[0] || 'Hola'
  const html = `
    <div style="font-family:Arial,sans-serif;max-width:520px;margin:0 auto;color:#1a1a1a;line-height:1.5">
      <p>Hola ${firstName},</p>
      <p>Bienvenido al portal de clientes de SkyPets. Aquí vas a poder ver y descargar todos los documentos de viaje de tu mascota apenas los subamos.</p>
      <p>Antes de entrar, crea tu contraseña de acceso con el siguiente botón:</p>
      <p style="margin-top:24px">
        <a href="${linkData.properties.action_link}" style="background:#FF7600;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:bold">Crear mi contraseña</a>
      </p>
      <p style="margin-top:24px">Equipo SkyPets</p>
    </div>
  `

  const text = `Hola ${firstName},

Bienvenido al portal de clientes de SkyPets. Aquí vas a poder ver y descargar todos los documentos de viaje de tu mascota apenas los subamos.

Antes de entrar, crea tu contraseña de acceso con este enlace:
${linkData.properties.action_link}

Equipo SkyPets`

  const res = await fetch('https://api.resend.com/emails', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${Deno.env.get('RESEND_API_KEY')}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      from: 'SkyPets <certificados@skypetscol.com>',
      to: record.email,
      subject: 'Bienvenido a tu portal SkyPets — crea tu contraseña',
      html,
      text,
    }),
  })

  if (!res.ok) {
    return Response.json({ created: true, user_id: created.user.id, email_error: await res.text() })
  }

  return Response.json({ created: true, user_id: created.user.id, welcome_email_sent: true })
})
