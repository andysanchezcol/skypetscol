// Utilidades de seguridad compartidas por las Edge Functions del portal.

// Escapa texto antes de meterlo en el HTML de un correo (nombres de cliente y de mascota los escribe el cliente).
export function escapeHtml(s: unknown): string {
  return String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c] as string))
}

// Comparación de secretos en tiempo constante (no revela cuántos caracteres coinciden).
export function secretoValido(recibido: string | null, esperado: string): boolean {
  const a = new TextEncoder().encode(recibido ?? '')
  const b = new TextEncoder().encode(esperado)
  let diff = a.length ^ b.length
  for (let i = 0; i < Math.max(a.length, b.length); i++) diff |= (a[i] ?? 0) ^ (b[i] ?? 0)
  return diff === 0
}

export const UUID_RE = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i
export const EMAIL_RE = /^[^\s@<>"']{1,64}@[^\s@<>"']{1,255}\.[^\s@<>"']{2,63}$/

// Texto opcional con tope de largo: devuelve null si no es texto o se pasa del tope.
export function textoOpcional(v: unknown, max: number): string | null {
  return typeof v === 'string' && v.length <= max ? v : null
}

// Lee el cuerpo JSON sin lanzar error (cuerpo vacío o roto → null).
export async function leerJson(req: Request): Promise<Record<string, unknown> | null> {
  try {
    const j = await req.json()
    return j && typeof j === 'object' && !Array.isArray(j) ? j as Record<string, unknown> : null
  } catch (_e) {
    return null
  }
}
