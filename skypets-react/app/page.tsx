import { LiquidButton } from "@/components/ui/liquid-glass-button"
import { MetalButton } from "@/components/ui/liquid-glass-button"

export default function Page() {
  return (
    <div className="min-h-screen flex flex-col items-center justify-center gap-12 bg-[#FFFAEC]">

      <h1 className="text-3xl font-bold" style={{ color: "#FF7600", fontFamily: "sans-serif" }}>
        SkyPets — Botones
      </h1>

      {/* ── LiquidButton naranja (hero CTA principal) */}
      <div className="flex flex-col items-center gap-3">
        <span className="text-xs text-gray-400 uppercase tracking-widest">Hero CTA · btn-primary</span>
        <div className="relative h-[120px] w-[420px] rounded-2xl overflow-hidden"
          style={{ background: "linear-gradient(135deg, #FF7600 0%, #FFBC00 100%)" }}>
          <LiquidButton
            className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-[#2B2418] font-semibold"
            size="xl"
          >
            Agenda tu asesoría →
          </LiquidButton>
        </div>
      </div>

      {/* ── LiquidButton teal (navbar / secundario) */}
      <div className="flex flex-col items-center gap-3">
        <span className="text-xs text-gray-400 uppercase tracking-widest">Navbar CTA · nav-cta</span>
        <div className="relative h-[100px] w-[360px] rounded-2xl overflow-hidden"
          style={{ background: "linear-gradient(135deg, #008D83 0%, #00BFB3 100%)" }}>
          <LiquidButton
            className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-white font-semibold text-sm"
            size="lg"
          >
            Cotiza Ahora
          </LiquidButton>
        </div>
      </div>

      {/* ── MetalButton WhatsApp CTA (sección final) */}
      <div className="flex flex-col items-center gap-3">
        <span className="text-xs text-gray-400 uppercase tracking-widest">CTA Final · btn-cta-dark</span>
        <div className="flex gap-4">
          <MetalButton variant="default">
            ✉ Escribir por WhatsApp
          </MetalButton>
          <MetalButton variant="success">
            Ver todos los servicios
          </MetalButton>
        </div>
      </div>

      {/* ── LiquidButton dark (fondo oscuro footer CTA) */}
      <div className="flex flex-col items-center gap-3">
        <span className="text-xs text-gray-400 uppercase tracking-widest">CTA sobre fondo oscuro</span>
        <div className="relative h-[120px] w-[500px] rounded-2xl overflow-hidden flex items-center justify-center gap-6"
          style={{ background: "#2B2418" }}>
          <LiquidButton
            className="text-white font-semibold"
            size="xl"
          >
            ✉ Escribir por WhatsApp
          </LiquidButton>
          <LiquidButton
            className="text-white font-semibold"
            size="lg"
          >
            Ver servicios
          </LiquidButton>
        </div>
      </div>

    </div>
  )
}
