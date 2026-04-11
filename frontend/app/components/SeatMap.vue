<script setup>
import { onMounted, onUnmounted, ref, computed } from "vue";
import { useSeatStore } from "~/stores/seatStore";

const seatStore = useSeatStore();
const authStore = useAuthStore();
const { $socket } = useNuxtApp();
const router = useRouter();

// Unified toast notification system
const toast = ref({ show: false, message: "", title: "", type: "info" });
let toastTimer = null;

const showToast = (title, message, type = "info", duration = 4000) => {
  if (toastTimer) clearTimeout(toastTimer);
  toast.value = { show: true, title, message, type };
  toastTimer = setTimeout(() => {
    toast.value.show = false;
  }, duration);
};

// Keep liveUpdate for backward compat (seat conflict events)
const liveUpdate = ref({ message: "", show: false });
const selectedSeats = ref(new Set());
const isProcessing = ref(false);

const totalAmount = computed(() => {
  return seatStore.seats
    .filter((s) => selectedSeats.value.has(s.id))
    .reduce((sum, s) => sum + (parseFloat(s.price) || 0), 0);
});

const checkoutSelection = computed(() => {
  return seatStore.seats.filter((s) => selectedSeats.value.has(s.id));
});

onMounted(async () => {
  if (!seatStore.sessionId) {
    seatStore.setSessionId(
      "session-" + Math.random().toString(36).substr(2, 9),
    );
  }

  await seatStore.fetchSeats();

  $socket.on("seat_updated", (data) => {
    // Si nos lo quitaron
    if (data.status === "reserved" && data.session_id !== seatStore.sessionId) {
      if (selectedSeats.value.has(data.seat_id)) {
        selectedSeats.value.delete(data.seat_id);
      }
      // Show toast
      liveUpdate.value = {
        message: `Alguien acaba de reservar un asiento.`,
        show: true,
      };
      setTimeout(() => {
        liveUpdate.value.show = false;
      }, 3000);
    }

    seatStore.updateSeat(data.seat_id, data.status);
  });
});

onUnmounted(() => {
  $socket.off("seat_updated");
});

const handleSeatClick = async (seatId) => {
  const seat = seatStore.seats.find((s) => s.id === seatId);
  if (!seat || seat.status !== "available") return;

  if (selectedSeats.value.has(seatId)) {
    selectedSeats.value.delete(seatId);
    return;
  }

  if (selectedSeats.value.size >= 5) {
    showToast(
      "Límite de entradas alcanzado",
      "Solo puedes seleccionar un máximo de 5 entradas por compra.",
      "warning",
    );
    return;
  }

  selectedSeats.value.add(seatId);
};

const processCheckout = async () => {
  if (selectedSeats.value.size === 0 || isProcessing.value) return;
  isProcessing.value = true;

  const config = useRuntimeConfig();

  try {
    for (const seatId of selectedSeats.value) {
      const seat = seatStore.seats.find((s) => s.id === seatId);
      // Skip if already reserved by this session
      if (
        seat &&
        seat.status === "reserved" &&
        seat.session_id === seatStore.sessionId
      )
        continue;

      await $fetch(`${config.public.apiUrl}/api/seats/reserve`, {
        method: "POST",
        body: { seat_id: seatId, session_id: seatStore.sessionId },
      });
    }

    // Navigate to checkout page with seat data
    await router.push({
      path: "/checkout",
      query: {
        seats: Array.from(selectedSeats.value).join(","),
        session: seatStore.sessionId,
        total: totalAmount.value.toString(),
      },
    });
  } catch (e) {
    const status = e.response?.status || e.status;
    const msg = e.response?._data?.error || e.message;
    if (status === 409 || status === 403) {
      showToast("Operación abortada", msg, "error");
    } else {
      console.error("Error Checkout General", e);
      showToast(
        "Error inesperado",
        "Hubo un error procesando tu solicitud.",
        "error",
      );
    }
  } finally {
    isProcessing.value = false;
  }
};

const getSeatStyle = (seat) => {
  const color = seat.zone_color || '#6366f1'; // fallback indigo

  if (selectedSeats.value.has(seat.id)) {
    return { backgroundColor: color, opacity: 1 };
  }
  switch (seat.status) {
    case 'available': return { backgroundColor: color, opacity: 1 };
    case 'reserved':  return { backgroundColor: color, opacity: 0.35 };
    case 'sold':      return { backgroundColor: color, opacity: 0.15 };
    default:          return { backgroundColor: color, opacity: 0.5 };
  }
};

const getSeatClass = (seat) => {
  const base = 'relative w-full aspect-square rounded-sm transition-all duration-200 flex items-center justify-center overflow-hidden';
  if (selectedSeats.value.has(seat.id)) {
    return `${base} scale-110 ring-2 ring-white shadow-[0_0_8px_rgba(255,255,255,0.6)] cursor-pointer`;
  }
  switch (seat.status) {
    case 'available': return `${base} hover:scale-125 hover:ring-2 hover:ring-white/50 cursor-pointer`;
    case 'reserved':  return `${base} cursor-not-allowed`;
    case 'sold':      return `${base} cursor-not-allowed`;
    default:          return `${base} bg-slate-500 opacity-30`;
  }
};

// Zonas únicas para la leyenda
const zoneColors = computed(() => {
  const map = new Map();
  for (const seat of seatStore.seats) {
    if (seat.zone_name && seat.zone_color && !map.has(seat.zone_name)) {
      map.set(seat.zone_name, seat.zone_color);
    }
  }
  return Array.from(map, ([name, color]) => ({ name, color }));
});
</script>

<template>
  <div
    class="bg-background text-on-background font-body min-h-screen selection:bg-primary/30"
  >
    <!-- TopNavBar -->
    <nav
      class="fixed top-0 w-full z-50 bg-slate-950/60 backdrop-blur-xl shadow-2xl shadow-black/40 flex justify-between items-center px-6 py-4"
    >
      <div
        class="text-2xl font-bold tracking-tighter bg-gradient-to-r from-fuchsia-500 via-purple-500 to-cyan-400 bg-clip-text text-transparent uppercase font-headline"
      >
        TICKETMONSTER
      </div>
      <div class="hidden md:flex gap-8 items-center">
        <NuxtLink
          to="/"
          class="font-headline tracking-tight text-fuchsia-400/80 hover:text-fuchsia-400 transition-colors"
          >Concerts</NuxtLink
        >
        <NuxtLink
          :to="authStore.isLoggedIn ? '/my-tickets' : '/login'"
          class="font-headline tracking-tight text-fuchsia-400/80 hover:text-fuchsia-400 transition-colors"
          >Les meves entrades</NuxtLink
        >
      </div>
      <div class="flex items-center gap-4">
        <NuxtLink
          v-if="!authStore.isLoggedIn"
          to="/login"
          class="flex items-center gap-1.5 text-sm font-headline font-bold text-fuchsia-400/80 hover:text-fuchsia-400 transition-colors"
        >
          <span class="material-symbols-outlined text-base"
            >account_circle</span
          >
          <span class="hidden sm:inline">Iniciar Sessió</span>
        </NuxtLink>
        <div
          v-else
          class="flex items-center gap-1.5 text-sm text-fuchsia-400/80"
        >
          <span class="material-symbols-outlined text-base"
            >account_circle</span
          >
          <span class="hidden sm:inline">{{ authStore.user?.name }}</span>
        </div>
      </div>
    </nav>

    <!-- SideNavBar (Map Focused Layout) -->
    <aside
      class="fixed left-0 top-0 h-screen w-20 border-r border-white/5 bg-slate-900 flex flex-col items-center py-20 gap-8 z-40 hidden md:flex"
    >
      <button
        class="flex flex-col items-center justify-center text-blue-400 bg-blue-500/10 border-r-2 border-blue-500 w-full py-4 transition-all duration-300"
      >
        <span class="material-symbols-outlined">map</span>
        <span class="font-label text-[10px] tracking-wide uppercase mt-1"
          >Map</span
        >
      </button>
      <button
        class="flex flex-col items-center justify-center text-slate-500 hover:text-slate-300 w-full py-4 transition-all duration-300"
      >
        <span class="material-symbols-outlined">payments</span>
        <span class="font-label text-[10px] tracking-wide uppercase mt-1"
          >Price</span
        >
      </button>
    </aside>

    <!-- Main Content Canvas -->
    <main class="pt-20 pb-24 md:pl-20 md:pr-[400px] min-h-screen">
      <header class="px-6 py-8">
        <div
          class="flex flex-col md:flex-row md:items-end justify-between gap-6"
        >
          <div>
            <span
              class="text-primary-fixed font-label text-xs tracking-[0.2em] uppercase mb-2 block"
              >World Tour / 2024</span
            >
            <h1
              class="text-5xl md:text-7xl font-headline font-bold tracking-tighter text-on-background leading-none"
            >
              THE VOID
            </h1>
            <p class="text-on-surface-variant font-body text-lg mt-2">
              Stadium Arena • Barcelona, ES
            </p>
          </div>
        </div>
      </header>

      <section class="px-6 mb-8">
        <div class="flex flex-wrap gap-x-6 gap-y-3 p-4 bg-surface-container-low rounded-xl">
          <!-- Zone legend -->
          <template v-if="zoneColors.length">
            <div v-for="z in zoneColors" :key="z.name" class="flex items-center gap-2">
              <div class="w-4 h-4 rounded-sm" :style="{ backgroundColor: z.color }"></div>
              <span class="font-label text-xs uppercase tracking-wider" :style="{ color: z.color }">{{ z.name }}</span>
            </div>
            <div class="w-px h-4 bg-white/10 self-center"></div>
          </template>
          <!-- Status indicators -->
          <div class="flex items-center gap-2">
            <div class="w-4 h-4 rounded-sm bg-primary ring-2 ring-white/50"></div>
            <span class="font-label text-xs uppercase tracking-wider text-on-surface-variant">Seleccionat</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="w-4 h-4 rounded-sm bg-tertiary opacity-60"></div>
            <span class="font-label text-xs uppercase tracking-wider text-on-surface-variant">Reservat</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="w-4 h-4 rounded-sm bg-error-container opacity-40"></div>
            <span class="font-label text-xs uppercase tracking-wider text-on-surface-variant">Venut</span>
          </div>
        </div>
      </section>

      <!-- Seat Map Container -->
      <section class="px-6 overflow-x-auto no-scrollbar">
        <div
          class="min-w-[800px] bg-surface-container-low p-12 rounded-3xl relative"
        >
          <!-- Stage -->
          <div
            class="w-full h-12 electric-gradient rounded-full mb-20 flex items-center justify-center neon-border"
          >
            <span
              class="font-headline text-on-primary font-bold tracking-[0.5em] text-sm uppercase"
              >STAGE</span
            >
          </div>

          <div
            v-if="seatStore.loading"
            class="text-center text-on-surface-variant py-20"
          >
            Loading Map Nodes...
          </div>
          <div
            v-else-if="seatStore.error"
            class="text-center text-error border border-error px-4 py-3 rounded"
          >
            {{ seatStore.error }}
          </div>

          <div
            v-else
            class="seat-grid"
            style="grid-template-columns: repeat(24, minmax(0, 1fr))"
          >
            <!-- Dynamic Grid -->
            <div
              v-for="seat in seatStore.seats"
              :key="seat.id"
              class="relative group"
            >
              <button
                :class="getSeatClass(seat)"
                :style="getSeatStyle(seat)"
                @click="handleSeatClick(seat.id)"
                :disabled="seat.status !== 'available' && !selectedSeats.has(seat.id)"
              >
                <!-- Icono de estado overlay -->
                <span v-if="selectedSeats.value?.has(seat.id)"
                  class="material-symbols-outlined text-white drop-shadow"
                  style="font-size:0.7rem">check</span>
                <span v-else-if="seat.status === 'sold'"
                  class="material-symbols-outlined text-white/80"
                  style="font-size:0.65rem">close</span>
                <span v-else-if="seat.status === 'reserved'"
                  class="material-symbols-outlined text-white/80"
                  style="font-size:0.65rem">lock</span>
                <!-- Fila + número (solo en disponible) -->
                <span v-else
                  class="text-[0.45rem] sm:text-[0.55rem] font-bold tracking-tighter text-black/60"
                  >{{ seat.row }}{{ seat.number }}</span
                >
              </button>
              <!-- Tooltip hover -->
              <div
                class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-2 z-50
                       opacity-0 group-hover:opacity-100 transition-opacity duration-150
                       bg-zinc-900/95 border border-white/10 backdrop-blur-md
                       rounded-xl px-3 py-2 w-36 shadow-xl text-center"
              >
                <p class="font-headline font-bold text-[10px] uppercase tracking-widest"
                   :style="seat.zone_color ? { color: seat.zone_color } : {}"
                   :class="!seat.zone_color ? 'text-primary' : ''"
                >{{ seat.zone_name ?? 'General' }}</p>
                <p class="text-white font-bold text-sm mt-0.5">€{{ parseFloat(seat.price).toFixed(2) }}</p>
                <p class="text-zinc-400 text-[10px] mt-0.5">Fila {{ seat.row }} · Seient {{ seat.number }}</p>
                <p class="text-[9px] mt-1 font-bold uppercase tracking-widest"
                   :class="{
                     'text-emerald-400': seat.status === 'available',
                     'text-amber-400': seat.status === 'reserved',
                     'text-red-400': seat.status === 'sold',
                   }"
                >{{ seat.status === 'available' ? 'Disponible' : seat.status === 'reserved' ? 'Reservat' : 'Venut' }}</p>
              </div>
            </div>
          </div>

          <!-- Floor Label -->
          <div class="mt-12 text-center">
            <span
              class="font-label text-xs text-outline tracking-[1em] uppercase"
              >Ground Floor Arena</span
            >
          </div>
        </div>
      </section>
    </main>

    <!-- Checkout Sidebar -->
    <aside
      class="hidden md:flex fixed right-0 top-0 h-screen w-[400px] bg-surface-container-low border-l border-white/5 flex-col p-8 z-50 transition-all duration-300"
    >
      <div class="flex justify-between items-center mb-10">
        <h2 class="font-headline text-2xl font-bold tracking-tight">
          Order Summary
        </h2>
        <button
          class="material-symbols-outlined text-outline hover:text-white"
          @click="selectedSeats.clear()"
        >
          close
        </button>
      </div>

      <div
        v-if="selectedSeats.size === 0"
        class="flex-grow flex items-center justify-center text-outline-variant font-label text-sm uppercase tracking-widest text-center px-4"
      >
        No tickets selected yet. Tap blue glowing dots on the map.
      </div>

      <div
        v-else
        class="flex-grow flex flex-col space-y-4 overflow-y-auto no-scrollbar pb-6"
      >
        <!-- Selected Ticket Cards dynamically rendered -->
        <div
          v-for="seat in checkoutSelection"
          :key="seat.id"
          class="bg-surface-container-highest p-6 rounded-2xl border border-white/5 shrink-0 animate-pulse"
        >
          <div class="flex justify-between items-start mb-4">
            <div>
              <span
                class="text-primary-fixed font-label text-[10px] uppercase tracking-widest"
                >{{ seat.zone_name ?? 'Zona' }}</span
              >
              <h3 class="text-xl font-bold font-headline mt-1">
                Fila {{ seat.row }}, Seient {{ seat.number }}
              </h3>
            </div>
            <span class="font-headline font-bold text-xl text-on-background"
              >€{{ parseFloat(seat.price).toFixed(2) }}</span
            >
          </div>
          <div class="flex items-center gap-2 text-on-surface-variant text-sm">
            <span class="material-symbols-outlined text-sm">bolt</span>
            <span>ID: {{ seat.id }}</span>
          </div>
        </div>
      </div>

      <div
        class="mt-auto pt-8 border-t border-white/5 bg-surface-container-low z-10"
      >
        <div class="flex justify-between items-center mb-6">
          <span
            class="text-on-surface-variant font-label text-sm uppercase tracking-widest"
            >Total Price</span
          >
          <span
            class="text-3xl font-headline font-bold text-on-background tracking-tighter"
            >€{{ totalAmount.toFixed(2) }}</span
          >
        </div>

        <button
          @click="processCheckout()"
          :disabled="selectedSeats.size === 0 || isProcessing"
          :class="[
            'w-full py-5 rounded-xl font-headline font-bold text-lg uppercase tracking-widest transition-all',
            selectedSeats.size > 0 && !isProcessing
              ? 'electric-gradient text-white shadow-[0_0_30px_rgba(191,126,255,0.3)] hover:scale-[1.02] active:scale-95'
              : 'bg-surface-container-highest text-outline cursor-not-allowed opacity-70',
          ]"
        >
          {{ isProcessing ? "Processing..." : "Proceed to Payment" }}
        </button>
      </div>
    </aside>

    <!-- Seat conflict toast (live update from socket) -->
    <Transition name="toast">
      <div
        v-if="liveUpdate.show"
        class="fixed bottom-10 left-1/2 -translate-x-1/2 z-[100] w-[340px] max-w-[90vw]"
      >
        <div
          class="glass-panel border-l-4 border-error p-4 rounded-xl shadow-2xl flex items-start gap-3"
        >
          <span class="material-symbols-outlined text-error mt-0.5"
            >sensors</span
          >
          <div class="flex-1">
            <h4 class="text-sm font-bold font-headline mb-0.5 text-on-surface">
              Actualización en vivo
            </h4>
            <p class="text-xs text-on-surface-variant font-body">
              {{ liveUpdate.message }}
            </p>
          </div>
          <button
            @click="liveUpdate.show = false"
            class="material-symbols-outlined text-outline text-base hover:text-white transition-colors"
          >
            close
          </button>
        </div>
      </div>
    </Transition>

    <!-- Unified toast (warning / error / info) -->
    <Transition name="toast">
      <div
        v-if="toast.show"
        class="fixed bottom-10 left-1/2 -translate-x-1/2 z-[110] w-[360px] max-w-[90vw]"
      >
        <div
          class="glass-panel p-5 rounded-2xl shadow-2xl flex items-start gap-4 border-l-4"
          :class="{
            'border-tertiary': toast.type === 'warning',
            'border-error': toast.type === 'error',
            'border-primary': toast.type === 'info',
          }"
        >
          <!-- Icon -->
          <div
            class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
            :class="{
              'bg-tertiary/20': toast.type === 'warning',
              'bg-error/20': toast.type === 'error',
              'bg-primary/20': toast.type === 'info',
            }"
          >
            <span
              class="material-symbols-outlined text-xl"
              :class="{
                'text-tertiary': toast.type === 'warning',
                'text-error': toast.type === 'error',
                'text-primary': toast.type === 'info',
              }"
            >
              {{
                toast.type === "warning"
                  ? "warning"
                  : toast.type === "error"
                    ? "error"
                    : "info"
              }}
            </span>
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <h4 class="text-sm font-bold font-headline mb-1 text-on-surface">
              {{ toast.title }}
            </h4>
            <p
              class="text-xs text-on-surface-variant font-body leading-relaxed"
            >
              {{ toast.message }}
            </p>
          </div>

          <!-- Close -->
          <button
            @click="toast.show = false"
            class="material-symbols-outlined text-outline hover:text-white transition-colors text-base flex-shrink-0 mt-0.5"
          >
            close
          </button>
        </div>

        <!-- Progress bar auto-dismiss -->
        <div
          class="h-0.5 rounded-full mx-5 mt-1 animate-shrink"
          :class="{
            'bg-tertiary/60': toast.type === 'warning',
            'bg-error/60': toast.type === 'error',
            'bg-primary/60': toast.type === 'info',
          }"
        ></div>
      </div>
    </Transition>

    <!-- Mobile BottomNav -->
    <nav
      class="fixed bottom-0 left-0 w-full flex justify-around items-center px-4 pb-6 pt-3 md:hidden bg-slate-950/80 backdrop-blur-2xl rounded-t-3xl z-50 border-t border-white/5 shadow-[0_-10px_40px_rgba(0,0,0,0.5)]"
    >
      <button
        class="flex flex-col items-center justify-center text-blue-400 bg-blue-500/10 rounded-xl px-4 py-2 scale-110 duration-300"
      >
        <span class="material-symbols-outlined">map</span>
        <span
          class="font-['Inter'] text-[10px] font-bold tracking-widest uppercase mt-1"
          >Map
          {{ selectedSeats.size > 0 ? `(${selectedSeats.size})` : "" }}</span
        >
      </button>
      <button
        class="flex flex-col items-center justify-center text-slate-500 hover:text-blue-300 transition-colors"
        @click="processCheckout()"
      >
        <span class="material-symbols-outlined">payments</span>
        <span
          class="font-['Inter'] text-[10px] font-bold tracking-widest uppercase mt-1"
          >Pay</span
        >
      </button>
    </nav>

    <!-- Map View Background Detail -->
    <div
      class="fixed inset-0 -z-10 opacity-20 pointer-events-none overflow-hidden blur-[80px]"
    >
      <div
        class="absolute top-1/4 left-1/4 w-[500px] h-[500px] bg-primary rounded-full mix-blend-screen opacity-30"
      ></div>
      <div
        class="absolute bottom-1/4 right-1/4 w-[600px] h-[600px] bg-tertiary rounded-full mix-blend-screen opacity-20"
      ></div>
    </div>
  </div>
</template>
