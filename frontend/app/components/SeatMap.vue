<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed } from 'vue'
import { useSeatStore } from '~/stores/seatStore'

interface LiveUpdate {
  message: string;
  show: boolean;
}

const seatStore = useSeatStore()
const { $socket } = useNuxtApp() as unknown as { $socket: import('socket.io-client').Socket }

const liveUpdate = ref<LiveUpdate>({ message: '', show: false })
const selectedSeats = ref<Set<number>>(new Set())

// Precios harcodeado a 145€ base as per HTML, 
// o podemos usar 50 € de bd, asumiremos dinámico
const checkoutPrice = 50.00;

const totalAmount = computed(() => {
  return selectedSeats.value.size * checkoutPrice;
})

const checkoutSelection = computed(() => {
  return seatStore.seats.filter(s => selectedSeats.value.has(s.id))
})

onMounted(async () => {
  if (!seatStore.sessionId) {
    seatStore.setSessionId('session-' + Math.random().toString(36).substr(2, 9))
  }

  await seatStore.fetchSeats()

  $socket.on('seat_updated', (data: { seat_id: number, status: 'available' | 'reserved' | 'sold', session_id: string | null }) => {
    // Si nos lo quitaron
    if (data.status === 'reserved' && data.session_id !== seatStore.sessionId) {
       if (selectedSeats.value.has(data.seat_id)) {
          selectedSeats.value.delete(data.seat_id);
       }
       // Show toast
       liveUpdate.value = { message: `Alguien acaba de reservar un asiento.`, show: true }
       setTimeout(() => { liveUpdate.value.show = false }, 3000)
    }

    seatStore.updateSeat(data.seat_id, data.status)
  })
})

onUnmounted(() => {
  $socket.off('seat_updated')
})

const handleSeatClick = async (seatId: number) => {
  const seat = seatStore.seats.find(s => s.id === seatId)
  if (!seat || seat.status !== 'available') return;

  if (selectedSeats.value.has(seatId)) {
    selectedSeats.value.delete(seatId);
    return;
  }

  if (selectedSeats.value.size >= 5) {
    alert("Puedes reservar un máximo de 5 entradas.");
    return;
  }

  selectedSeats.value.add(seatId);
}

const processCheckout = async () => {
    if (selectedSeats.value.size === 0) return;

    // Primer paso: Reservar todo el carrito localmente en Backend
    const config = useRuntimeConfig()
    try {
        // En un caso real iteraríamos todos para reserve, asumimos llamada individual o iterativa
        for (const seatId of selectedSeats.value) {
            await $fetch<{ success: boolean }>(`${config.public.apiUrl}/api/seats/reserve`, {
                method: 'POST',
                body: { seat_id: seatId, session_id: seatStore.sessionId }
            })
        }
        
        // Finalizar y simular Checkout Masivo
        const confirmPurchase = confirm(`Se va a proceder al pago de €${totalAmount.value}. ¿Aceptar?`);
        if (!confirmPurchase) return;

        const email = prompt("Email de compra:", "pago@cliente.com") || "pago@cliente.com"
        const name = prompt("Nombre:", "Usuario Ticket") || "Usuario Ticket"

        const response = await $fetch<{ success: boolean }>(`${config.public.apiUrl}/api/seats/checkout`, {
            method: 'POST',
            body: {
                seat_ids: Array.from(selectedSeats.value),
                session_id: seatStore.sessionId,
                name: name,
                email: email
            }
        });

        if (response.success) {
            alert('¡Compra finalizada exitosamente!')
            selectedSeats.value.clear();
        }

    } catch (e: unknown) {
        const err = e as { status?: number, message?: string }
        if (err.status === 409 || err.status === 403) {
            alert('Error en checkout: ' + (err.message || 'Asientos conflictivos.'));
        } else {
            console.error('Error Checkout General', e);
        }
    }
}

const getSeatColor = (id: number, status: string) => {
  if (selectedSeats.value.has(id)) {
     return 'bg-primary scale-110';
  }
  switch (status) {
    case 'available': return 'bg-secondary seat-glow-available'
    case 'reserved': return 'bg-tertiary'
    case 'sold': return 'bg-error-container opacity-40'
    default: return 'bg-slate-500'
  }
}
</script>

<template>
<div class="bg-background text-on-background font-body min-h-screen selection:bg-primary/30">
        <!-- TopNavBar -->
        <nav class="fixed top-0 w-full z-50 bg-slate-950/60 backdrop-blur-xl shadow-2xl shadow-black/40 flex justify-between items-center px-6 py-4">
            <div class="text-2xl font-bold tracking-tighter text-blue-500 uppercase font-headline">ELECTRIC STAGE</div>
            <div class="hidden md:flex gap-8 items-center">
                <a href="#" class="font-headline tracking-tight text-slate-400 hover:text-slate-100 transition-colors">Concerts</a>
                <a href="#" class="font-headline tracking-tight text-blue-400 border-b-2 border-blue-500 pb-1">Venues</a>
                <a href="#" class="font-headline tracking-tight text-slate-400 hover:text-slate-100 transition-colors">My Tickets</a>
            </div>
            <div class="flex items-center gap-4">
                <button class="material-symbols-outlined text-on-background hover:bg-white/5 transition-all p-2 rounded-full">account_circle</button>
            </div>
        </nav>

        <!-- SideNavBar (Map Focused Layout) -->
        <aside class="fixed left-0 top-0 h-screen w-20 border-r border-white/5 bg-slate-900 flex flex-col items-center py-20 gap-8 z-40 hidden md:flex">
            <div class="flex flex-col items-center gap-2 mb-4">
                <div class="w-10 h-10 rounded-full overflow-hidden bg-surface-container-highest">
                    <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuB_58zi2356YZzL437Jvz7PK2aSTEULQDG22sHZJkG6MBm-aBsiLzlFFHwAwV9dIx0uhsifryWsABp91PDg2UEt1DgV0MlI8mygicVBSZtX00mR_Qh1nvGtMbZFQui2MxcN101MnLYqG8LkzCGuJWdxnJ95FkXstYECU3E2_km2Riu8gS-uYBPz1fDphQ4doQ3WvSW76M0wWmMD3WPDqHiEg5xQCxbZYAXFcwu47OoY-etRRhWj3k9Qu73s8kcYOVb0uaVmdTMjnJs" class="w-full h-full object-cover">
                </div>
            </div>
            
            <button class="flex flex-col items-center justify-center text-blue-400 bg-blue-500/10 border-r-2 border-blue-500 w-full py-4 transition-all duration-300">
                <span class="material-symbols-outlined">map</span>
                <span class="font-label text-[10px] tracking-wide uppercase mt-1">Map</span>
            </button>
            <button class="flex flex-col items-center justify-center text-slate-500 hover:text-slate-300 w-full py-4 transition-all duration-300">
                <span class="material-symbols-outlined">payments</span>
                <span class="font-label text-[10px] tracking-wide uppercase mt-1">Price</span>
            </button>
        </aside>

        <!-- Main Content Canvas -->
        <main class="pt-20 pb-24 md:pl-20 md:pr-[400px] min-h-screen">
            <header class="px-6 py-8">
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <div>
                        <span class="text-primary-fixed font-label text-xs tracking-[0.2em] uppercase mb-2 block">World Tour / 2024</span>
                        <h1 class="text-5xl md:text-7xl font-headline font-bold tracking-tighter text-on-background leading-none">THE VOID</h1>
                        <p class="text-on-surface-variant font-body text-lg mt-2">Stadium Arena • Barcelona, ES</p>
                    </div>

                    <!-- Countdown Timer -->
                    <div v-show="selectedSeats.size > 0" class="glass-panel rounded-xl px-6 py-4 flex flex-col items-center border border-white/10 shadow-2xl transition-all duration-500">
                        <span class="font-label text-[10px] tracking-widest text-primary-fixed uppercase mb-1">Time to purchase</span>
                        <span class="text-4xl font-headline font-bold text-on-background tracking-tight">~ 00:15</span>
                    </div>
                </div>
            </header>

            <section class="px-6 mb-8">
                <div class="flex flex-wrap gap-6 p-4 bg-surface-container-low rounded-xl">
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded-sm bg-secondary seat-glow-available"></div>
                        <span class="font-label text-xs uppercase tracking-wider text-on-surface-variant">Available</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded-sm bg-tertiary"></div>
                        <span class="font-label text-xs uppercase tracking-wider text-on-surface-variant">Reserved</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded-sm bg-primary"></div>
                        <span class="font-label text-xs uppercase tracking-wider text-on-surface-variant">Selected</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded-sm bg-error-container opacity-40"></div>
                        <span class="font-label text-xs uppercase tracking-wider text-on-surface-variant">Sold</span>
                    </div>
                </div>
            </section>

            <!-- Seat Map Container -->
            <section class="px-6 overflow-x-auto no-scrollbar">
                <div class="min-w-[800px] bg-surface-container-low p-12 rounded-3xl relative">
                    <!-- Stage -->
                    <div class="w-full h-12 bg-gradient-to-r from-primary-dim to-primary rounded-full mb-20 flex items-center justify-center shadow-[0_0_40px_rgba(55,102,255,0.4)]">
                        <span class="font-headline text-on-primary-fixed font-bold tracking-[0.5em] text-sm uppercase">STAGE</span>
                    </div>

                    <div v-if="seatStore.loading" class="text-center text-on-surface-variant py-20">Loading Map Nodes...</div>
                    <div v-else-if="seatStore.error" class="text-center text-error border border-error px-4 py-3 rounded">{{ seatStore.error }}</div>
                    
                    <div v-else class="seat-grid" style="grid-template-columns: repeat(10, minmax(0, 1fr));">
                        <!-- Dynamic Grid (Columns configured down to 10 dynamically in style to match BBDD) -->
                        <button 
                             v-for="seat in seatStore.seats" :key="seat.id"
                             :class="[
                                'w-full aspect-square rounded-sm hover:scale-125 transition-transform duration-200', 
                                getSeatColor(seat.id, seat.status)
                             ]"
                             @click="handleSeatClick(seat.id)"
                             :title="`Group - Row ${seat.row} - Seat ${seat.number}`"
                        ></button>
                    </div>

                    <!-- Floor Label -->
                    <div class="mt-12 text-center">
                        <span class="font-label text-xs text-outline tracking-[1em] uppercase">Ground Floor Arena</span>
                    </div>
                </div>
            </section>
        </main>

        <!-- Checkout Sidebar -->
        <aside class="hidden md:flex fixed right-0 top-0 h-screen w-[400px] bg-surface-container-low border-l border-white/5 flex-col p-8 z-50 transition-all duration-300">
            <div class="flex justify-between items-center mb-10">
                <h2 class="font-headline text-2xl font-bold tracking-tight">Order Summary</h2>
                <button class="material-symbols-outlined text-outline hover:text-white" @click="selectedSeats.clear()">close</button>
            </div>
            
            <div v-if="selectedSeats.size === 0" class="flex-grow flex items-center justify-center text-outline-variant font-label text-sm uppercase tracking-widest text-center px-4">
                No tickets selected yet. Tap blue glowing dots on the map.
            </div>

            <div v-else class="flex-grow flex flex-col space-y-4 overflow-y-auto no-scrollbar pb-6">
                <!-- Selected Ticket Cards dynamically rendered -->
                <div v-for="seat in checkoutSelection" :key="seat.id" class="bg-surface-container-highest p-6 rounded-2xl border border-white/5 shrink-0 animate-pulse">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <span class="text-primary-fixed font-label text-[10px] uppercase tracking-widest">Section {{ seat.row }}</span>
                            <h3 class="text-xl font-bold font-headline mt-1">Row {{ seat.row }}, Seat {{ seat.number }}</h3>
                        </div>
                        <span class="font-headline font-bold text-xl text-on-background">€{{ checkoutPrice.toFixed(2) }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-on-surface-variant text-sm">
                        <span class="material-symbols-outlined text-sm">bolt</span>
                        <span>Electronic ID: {{ seat.id }}</span>
                    </div>
                </div>
            </div>

            <div class="mt-auto pt-8 border-t border-white/5 bg-surface-container-low z-10">
                <div class="flex justify-between items-center mb-6">
                    <span class="text-on-surface-variant font-label text-sm uppercase tracking-widest">Total Price</span>
                    <span class="text-3xl font-headline font-bold text-on-background tracking-tighter">€{{ totalAmount.toFixed(2) }}</span>
                </div>
                
                <button 
                    @click="processCheckout()"
                    :disabled="selectedSeats.size === 0"
                    :class="['w-full py-5 rounded-xl font-headline font-bold text-lg uppercase tracking-widest transition-all', selectedSeats.size > 0 ? 'bg-gradient-to-br from-primary-dim to-primary text-on-primary-fixed shadow-xl shadow-primary/20 hover:scale-[1.02] active:scale-95' : 'bg-surface-container-highest text-outline cursor-not-allowed']">
                    Proceed to Payment
                </button>
                <p class="text-center text-[10px] text-outline mt-4 font-body">Transaction fees via Laravel API processed securely.</p>
            </div>
        </aside>

        <!-- Toast Notification -->
        <div v-if="liveUpdate.show" class="fixed bottom-24 right-6 md:bottom-10 md:right-[420px] z-[100] max-w-sm animate-bounce">
            <div class="glass-panel border-l-4 border-error p-5 rounded-r-xl shadow-2xl flex items-start gap-4">
                <span class="material-symbols-outlined text-error">error</span>
                <div>
                    <h4 class="text-sm font-bold font-headline mb-1">Live Update</h4>
                    <p class="text-xs text-on-surface-variant font-body">{{ liveUpdate.message }}</p>
                </div>
                <button @click="liveUpdate.show = false" class="material-symbols-outlined text-outline text-sm">close</button>
            </div>
        </div>

        <!-- Mobile BottomNav -->
        <nav class="fixed bottom-0 left-0 w-full flex justify-around items-center px-4 pb-6 pt-3 md:hidden bg-slate-950/80 backdrop-blur-2xl rounded-t-3xl z-50 border-t border-white/5 shadow-[0_-10px_40px_rgba(0,0,0,0.5)]">
            <button class="flex flex-col items-center justify-center text-blue-400 bg-blue-500/10 rounded-xl px-4 py-2 scale-110 duration-300">
                <span class="material-symbols-outlined">map</span>
                <span class="font-['Inter'] text-[10px] font-bold tracking-widest uppercase mt-1">Map {{selectedSeats.size > 0 ? `(${selectedSeats.size})` : ''}}</span>
            </button>
            <button class="flex flex-col items-center justify-center text-slate-500 hover:text-blue-300 transition-colors" @click="processCheckout()">
                <span class="material-symbols-outlined">payments</span>
                <span class="font-['Inter'] text-[10px] font-bold tracking-widest uppercase mt-1">Pay</span>
            </button>
        </nav>

        <!-- Map View Background Detail -->
        <div class="fixed inset-0 -z-10 opacity-5 pointer-events-none overflow-hidden">
            <img class="w-full h-full object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCpXSV3inu8M2its61dHB8Jgl8UKdABHsf8j3JHorO56WRiBp4NgLDZXlh99KXyTe2dk5EfrCCEiQLh5_7ehQz5-dOZrIA3M1e1MEGbEiFAjegCMJghpZ26m73cndvwWCq_lDTpUiM3QKzmizCYfiBgIK_pnT_bh-62uMROIJ_MWDl6zpbsd90hUp6qURbNCrf5P9x09bcl2vfsCD7NCcu1Xifl-2cjsHAppvBszf7weLqfp5IoE42tqmVDJnOXaC2_1D9YVHl8jk0">
        </div>
</div>
</template>
