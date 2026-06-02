import { LiquidButton } from "@/components/ui/liquid-glass-button"
import { MetalButton } from "@/components/ui/liquid-glass-button"

export default function Page() {
  return (
    <div className="min-h-screen flex flex-col items-center justify-center gap-10 bg-gradient-to-br from-orange-50 to-amber-100">
      <h1 className="text-3xl font-bold text-orange-600">SkyPets Botones</h1>
      <div className="relative h-[200px] w-[400px]">
        <LiquidButton className="absolute top-1/2 left-1/2 z-10 -translate-x-1/2 -translate-y-1/2">
          Cotiza Ahora
        </LiquidButton>
      </div>
      <MetalButton variant="gold">SkyPets Premium</MetalButton>
      <MetalButton variant="primary">Agenda tu asesoría</MetalButton>
    </div>
  )
}
