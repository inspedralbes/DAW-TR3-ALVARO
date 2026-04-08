<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'
import { useSeatStore } from '~/app/stores/seatStore'

const seatStore = useSeatStore()
const { $socket } = useNuxtApp()

onMounted(async () => {
  // Generar un session_id si no existe
  if (!seatStore.sessionId) {
    seatStore.setSessionId('session-' + Math.random().toString(36).substr(2, 9))
  }

  await seatStore.fetchSeats()

  // Escuchar actualizaciones de WebSocket
  $socket.on('seat_updated', (data: { seat_id: number, status: 'available' | 'reserved' | 'sold' }) => {
    console.log('Update received via WS:', data)
    seatStore.updateSeat(data.seat_id, data.status)
  })
})

onUnmounted(() => {
  $socket.off('seat_updated')
})

const reserveSeat = async (seatId: number) => {
  const config = useRuntimeConfig()
  try {
    const response = await $fetch<{ success: boolean }>(`${config.public.apiUrl}/api/seats/reserve`, {
      method: 'POST',
      body: {
        seat_id: seatId,
        session_id: seatStore.sessionId
      }
    })
    
    if (response.success) {
      console.log('Reserva confirmada')
    }
  } catch (err: any) {
    if (err.status === 409) {
      alert('¡Demasiado tarde! El asiento ya no está disponible.')
    } else {
      alert('Error al reservar: ' + err.message)
    }
  }
}

const getSeatColor = (status: string) => {
  switch (status) {
    case 'available': return 'bg-green-500 hover:bg-green-600'
    case 'reserved': return 'bg-gray-400 cursor-not-allowed'
    case 'sold': return 'bg-red-500 cursor-not-allowed'
    default: return 'bg-blue-200'
  }
}
</script>

<template>
  <div class="p-8 max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold mb-6 text-center">Reserva de Asientos en Tiempo Real</h1>
    
    <div v-if="seatStore.loading" class="text-center">Cargando asientos...</div>
    <div v-else-if="seatStore.error" class="text-red-500 text-center">{{ seatStore.error }}</div>
    
    <div v-else class="grid grid-cols-10 gap-2 mb-8 bg-gray-100 p-6 rounded-lg shadow-inner">
      <div 
        v-for="seat in seatStore.seats" 
        :key="seat.id"
        :class="['w-full aspect-square flex items-center justify-center rounded transition-colors text-white font-bold cursor-pointer', getSeatColor(seat.status)]"
        @click="seat.status === 'available' ? reserveSeat(seat.id) : null"
      >
        {{ seat.row }}{{ seat.number }}
      </div>
    </div>

    <div class="flex justify-center gap-6 text-sm">
      <div class="flex items-center gap-2"><div class="w-4 h-4 bg-green-500 rounded"></div> Disponible</div>
      <div class="flex items-center gap-2"><div class="w-4 h-4 bg-gray-400 rounded"></div> Reservado</div>
      <div class="flex items-center gap-2"><div class="w-4 h-4 bg-red-500 rounded"></div> Vendido</div>
    </div>

    <div class="mt-8 p-4 bg-blue-50 rounded text-blue-800 text-xs">
      <p>Tu Sesión: <span class="font-mono">{{ seatStore.sessionId }}</span></p>
      <p class="mt-1 opacity-70">Abre esta página en otra pestaña para ver las actualizaciones en tiempo real.</p>
    </div>
  </div>
</template>

<style scoped>
/* Tailwind-like classes implemented in CSS if Tailwind is not present */
.grid { display: grid; }
.grid-cols-10 { grid-template-columns: repeat(10, minmax(0, 1fr)); }
.gap-2 { gap: 0.5rem; }
.p-8 { padding: 2rem; }
.max-w-4xl { max-width: 56rem; }
.mx-auto { margin-left: auto; margin-right: auto; }
.mb-6 { margin-bottom: 1.5rem; }
.text-3xl { font-size: 1.875rem; line-height: 2.25rem; }
.font-bold { font-weight: 700; }
.text-center { text-align: center; }
.w-full { width: 100%; }
.aspect-square { aspect-ratio: 1 / 1; }
.flex { display: flex; }
.items-center { align-items: center; }
.justify-center { justify-content: center; }
.rounded { border-radius: 0.25rem; }
.transition-colors { transition-property: background-color; transition-duration: 150ms; }
.text-white { color: #fff; }
.cursor-pointer { cursor: pointer; }
.cursor-not-allowed { cursor: not-allowed; }
.bg-green-500 { background-color: #22c55e; }
.hover\:bg-green-600:hover { background-color: #16a34a; }
.bg-gray-400 { background-color: #9ca3af; }
.bg-red-500 { background-color: #ef4444; }
.bg-gray-100 { background-color: #f3f4f6; }
.p-6 { padding: 1.5rem; }
.rounded-lg { border-radius: 0.5rem; }
.shadow-inner { box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06); }
.gap-6 { gap: 1.5rem; }
.text-sm { font-size: 0.875rem; }
.w-4 { width: 1rem; }
.h-4 { height: 1rem; }
.mt-8 { margin-top: 2rem; }
.p-4 { padding: 1rem; }
.bg-blue-50 { background-color: #eff6ff; }
.text-blue-800 { color: #1e40af; }
.text-xs { font-size: 0.75rem; }
.mt-1 { margin-top: 0.25rem; }
.opacity-70 { opacity: 0.7; }
.font-mono { font-family: monospace; }
</style>
