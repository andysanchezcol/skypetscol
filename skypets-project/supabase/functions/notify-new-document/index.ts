// Disparada por un Database Webhook de Supabase en INSERT sobre public.documents.
// Envía al cliente el correo "documento nuevo disponible" vía la API de Resend
// (dominio skypetscol.com ya verificado). El contenido de "certificado_apoyo_emocional"
// es el texto real que SkyPets ya usaba manualmente por correo — el resto de tipos
// se redactó con el mismo tono pero SIN inventar datos de vigencia o requisitos que
// no están confirmados; revisar con Andrés antes de dar por bueno cada uno.
import { createClient } from 'https://esm.sh/@supabase/supabase-js@2'

interface DocumentRecord {
  id: string
  owner_id: string
  type: string
  name: string
}

interface DocInfo {
  label: string
  recommendations: string[]
}

const DOC_INFO: Record<string, DocInfo> = {
  certificado_apoyo_emocional: {
    label: 'Certificado de Apoyo Emocional (ESA)',
    recommendations: [
      '🌟 Contacta a la aerolínea con mínimo 72 horas de anticipación. Informa que viajarás con tu mascota de apoyo emocional para que sea añadida a la reserva. Puedes hacerlo a través de servicio al cliente, WhatsApp o los canales disponibles.',
      '🌟 El carnet proporcionado debe llevarse IMPRESO y plastificado. Todos los documentos deben ir impresos dentro de la carpeta de viaje de tu mascota.',
      '🌟 La certificación tiene una vigencia de 1 año.',
      '🌟 Recuerda renovarla con nosotros dentro de 12 meses para mantener la validez y continuidad del proceso.',
    ],
  },
  certificado_salud: {
    label: 'Certificado de Salud',
    recommendations: [
      '🌟 Preséntalo en el mostrador de la aerolínea el día del vuelo.',
      '🌟 Llévalo impreso dentro de la carpeta de viaje de tu mascota.',
    ],
  },
  certificado_asistencia_servicio: {
    label: 'Certificado de Asistencia y Servicio (SVAN)',
    recommendations: [
      '🌟 Contacta a la aerolínea con mínimo 72 horas de anticipación e informa que viajarás con tu mascota de servicio.',
      '🌟 Llévalo impreso dentro de la carpeta de viaje de tu mascota.',
    ],
  },
  cartilla_vacunacion: {
    label: 'Cartilla de Vacunación',
    recommendations: [
      '🌟 Lleva la cartilla física el día del vuelo, además de esta copia digital.',
    ],
  },
  permiso_importacion: {
    label: 'Permiso de Importación',
    recommendations: [
      '🌟 Preséntalo ante las autoridades del país de destino al llegar.',
    ],
  },
  permiso_exportacion: {
    label: 'Permiso de Exportación',
    recommendations: [
      '🌟 Preséntalo ante el ICA antes de salir de Colombia.',
    ],
  },
  pasaporte_mascota: {
    label: 'Pasaporte de Mascota',
    recommendations: [
      '🌟 Llévalo contigo durante todo el viaje.',
    ],
  },
  microchip_certificado: {
    label: 'Certificado de Microchip',
    recommendations: [
      '🌟 Consérvalo como respaldo de la identificación de tu mascota.',
    ],
  },
  prueba_titulacion_rabia: {
    label: 'Prueba de Titulación de Rabia',
    recommendations: [
      '🌟 Verifica si tu país de destino la requiere para el ingreso.',
    ],
  },
  tapete_aerolinea: {
    label: 'Tapete Aerolínea',
    recommendations: [
      '🌟 Úsalo dentro del guacal/transportadora durante el vuelo.',
    ],
  },
  otro: {
    label: 'Documento',
    recommendations: [],
  },
}

Deno.serve(async (req: Request) => {
  const payload = await req.json()
  const record = payload.record as DocumentRecord

  const supabaseAdmin = createClient(
    Deno.env.get('SUPABASE_URL')!,
    Deno.env.get('SUPABASE_SERVICE_ROLE_KEY')!,
  )

  const { data: profile } = await supabaseAdmin
    .from('profiles')
    .select('email, full_name')
    .eq('id', record.owner_id)
    .maybeSingle()

  if (!profile?.email) {
    return Response.json({ skipped: 'sin_email' })
  }

  const info = DOC_INFO[record.type] ?? DOC_INFO.otro
  const firstName = (profile.full_name ?? '').trim().split(' ')[0] || 'Hola'

  const recsHtml = info.recommendations.map(r => `<p style="margin:0 0 12px">${r}</p>`).join('')

  const html = `
    <div style="font-family:Arial,sans-serif;max-width:520px;margin:0 auto;color:#1a1a1a;line-height:1.5">
      <p>Hola ${firstName}! 💙🐾</p>
      <p>Te hacemos entrega de la documentación correspondiente para el viaje de tu peludito: <strong>${info.label}</strong>. Te pedimos revisarla con atención y validar que todos los datos estén correctos según la información suministrada.</p>
      <p>Gracias por confiar en nosotros para acompañarte en este momento tan especial. Muy pronto comenzará una nueva aventura juntos ✈️🐶🐱</p>
      ${recsHtml ? `<p><strong>Recomendaciones importantes:</strong></p>${recsHtml}` : ''}
      <p><strong>IMPORTANTE:</strong> Si necesitas realizar alguna solicitud, corrección o requieres información adicional, comunícate directamente con nosotros. Somos quienes acompañamos tu proceso y estaremos encantados de apoyarte en cada paso.</p>
      <p>✨ Te deseamos un viaje tranquilo, seguro y lleno de momentos inolvidables junto a tu peludito.</p>
      <p style="margin-top:24px">
        <a href="https://skypetscol.com/portal" style="background:#FF7600;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:bold">Ingresar al portal</a>
      </p>
      <p style="margin-top:24px">Con cariño,<br>Equipo SkyPets<br>Juntos en cada destino 🐶🐱✈️</p>
    </div>
  `

  const res = await fetch('https://api.resend.com/emails', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${Deno.env.get('RESEND_API_KEY')}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      from: 'Certificados SkyPets <certificados@skypetscol.com>',
      to: profile.email,
      subject: `${info.label} disponible en tu portal SkyPets`,
      html,
    }),
  })

  if (!res.ok) {
    return Response.json({ error: await res.text() }, { status: 500 })
  }

  return Response.json({ sent: true })
})
