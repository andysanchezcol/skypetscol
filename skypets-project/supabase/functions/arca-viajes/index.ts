// Viajes de Arca para el panel admin del sitio (y para el cliente, solo los suyos).
// Reemplaza las funciones SQL get_all_trips y get_trip_checklist, que leían el esquema de Arca cuando Arca y el portal
// compartían proyecto de Supabase. Ahora se pide a la API de integración de Arca con el secreto de esta empresa.
//  Body {}: todos los viajes (solo admin del sitio).  Body { email }: checklist de ese cliente (admin o el propio cliente).
import { createClient } from 'https://esm.sh/@supabase/supabase-js@2'

const CORS = {
  'Access-Control-Allow-Origin': '*',
  'Access-Control-Allow-Headers': 'authorization, x-client-info, apikey, content-type',
  'Access-Control-Allow-Methods': 'POST, OPTIONS',
}
const responder = (cuerpo: unknown, status = 200) => Response.json(cuerpo, { status, headers: CORS })

Deno.serve(async (req: Request) => {
  if (req.method === 'OPTIONS') return new Response('ok', { headers: CORS })

  const token = req.headers.get('Authorization')?.replace(/^Bearer\s+/i, '')
  const sb = createClient(Deno.env.get('SUPABASE_URL')!, Deno.env.get('SUPABASE_SERVICE_ROLE_KEY')!)
  const { data: { user } } = await sb.auth.getUser(token ?? '')
  if (!user) return responder({ error: 'No autenticado' }, 401)

  const { data: perfil } = await sb.from('profiles').select('is_admin').eq('id', user.id).maybeSingle()
  const esAdmin = perfil?.is_admin === true
  const { email } = await req.json().catch(() => ({})) as { email?: string }

  let ruta: string
  if (email) {
    if (!esAdmin && email.trim().toLowerCase() !== (user.email ?? '').toLowerCase()) return responder({ error: 'No autorizado' }, 403)
    ruta = `/api/integracion/viajes?email=${encodeURIComponent(email.trim())}`
  } else {
    if (!esAdmin) return responder({ error: 'No autorizado' }, 403)
    ruta = '/api/integracion/viajes'
  }

  try {
    const res = await fetch(`${Deno.env.get('ARCA_URL')}${ruta}`, { headers: { Authorization: `Bearer ${Deno.env.get('ARCA_INTEGRACION_SECRETO')}` } })
    const cuerpo = await res.json().catch(() => null)
    if (!res.ok) return responder({ error: cuerpo?.error ?? 'Arca no respondió' }, 502)
    return responder(cuerpo)
  } catch (_e) {
    return responder({ error: 'No se pudo conectar con Arca' }, 502)
  }
})
