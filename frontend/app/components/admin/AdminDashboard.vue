<template>
  <div class="p-6 md:p-10 max-w-[1400px] mx-auto">
    <!-- Page header -->
    <div
      class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-10"
    >
      <div>
        <h1
          class="font-headline text-3xl font-black tracking-tight text-on-surface"
        >
          Dashboard <span class="text-primary italic">en Temps Real</span>
        </h1>
        <p class="text-on-surface-variant text-sm mt-1 flex items-center gap-2">
          <span class="material-symbols-outlined text-sm">schedule</span>
          Actualitzat fa {{ secondsSinceRefresh }}s
        </p>
      </div>

      <!-- Event Filter & Controls -->
      <div class="flex items-center gap-4">
        <!-- Selector de Concierto -->
        <select 
          v-model="selectedEventId" 
          @change="refresh"
          class="bg-surface-container-high border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/50 transition-all font-headline font-bold uppercase tracking-wider min-w-[200px]"
        >
          <option v-for="event in store.events" :key="event.id" :value="event.id">
            {{ event.title }}
          </option>
        </select>

        <button
          @click="refresh"
          :class="[
            'flex items-center gap-2 px-4 py-2.5 rounded-xl border font-headline font-bold text-sm transition-all',
            statsLoading
              ? 'border-white/10 text-on-surface-variant cursor-wait'
              : 'border-primary/30 text-primary hover:bg-primary/10',
          ]"
        >
        <span
          class="material-symbols-outlined text-base"
          :class="{ 'animate-spin': statsLoading }"
          >refresh</span
        >
        Actualitzar
        </button>
      </div>
    </div>

    <!-- Stat cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <div
        v-for="card in statCards"
        :key="card.label"
        :class="[
          'glass-panel rounded-2xl p-6 border transition-all duration-300 hover:-translate-y-1',
          card.border,
          card.glow,
        ]"
      >
        <div class="flex items-start justify-between mb-4">
          <div
            :class="[
              'w-11 h-11 rounded-xl flex items-center justify-center',
              card.bg,
            ]"
          >
            <span :class="['material-symbols-outlined', card.color]">{{
              card.icon
            }}</span>
          </div>
          <span
            :class="[
              'text-xs font-bold uppercase tracking-wider px-2 py-1 rounded-full',
              card.bg,
              card.color,
            ]"
          >
            {{ card.percent }}%
          </span>
        </div>
        <div
          :class="[
            'text-4xl font-black font-headline tracking-tighter mb-1',
            card.color,
          ]"
        >
          {{ card.value.toLocaleString() }}
        </div>
        <div
          class="text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-3"
        >
          {{ card.label }}
        </div>
        <!-- Mini progress bar -->
        <div class="h-1 bg-white/5 rounded-full overflow-hidden">
          <div
            :class="[
              'h-full rounded-full transition-all duration-1000',
              card.bar,
            ]"
            :style="{ width: card.percent + '%' }"
          ></div>
        </div>
      </div>
    </div>

    <!-- Occupancy + Clients row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
      <!-- Occupancy bar -->
      <div
        class="lg:col-span-2 glass-panel rounded-2xl p-6 border border-white/10"
      >
        <div class="flex items-center justify-between mb-5">
          <h2
            class="font-headline font-bold text-on-surface flex items-center gap-2"
          >
            <span class="material-symbols-outlined text-base text-primary"
              >bar_chart</span
            >
            Aforo Global
          </h2>
          <span class="font-headline font-black text-2xl text-on-surface"
            >{{ store.occupancyPercent }}%</span
          >
        </div>

        <!-- Stacked bar -->
        <div class="h-8 bg-white/5 rounded-full overflow-hidden flex mb-3">
          <div
            class="h-full bg-primary transition-all duration-1000 flex items-center justify-center"
            :style="{ width: store.soldPercent + '%' }"
          >
            <span
              v-if="store.soldPercent > 8"
              class="text-[10px] font-black text-on-primary uppercase tracking-wider"
              >Venuts</span
            >
          </div>
          <div
            class="h-full bg-tertiary transition-all duration-1000 flex items-center justify-center"
            :style="{ width: store.reservedPercent + '%' }"
          >
            <span
              v-if="store.reservedPercent > 8"
              class="text-[10px] font-black text-black/70 uppercase tracking-wider"
              >Reservats</span
            >
          </div>
          <div
            class="h-full bg-secondary/30 flex-1 transition-all duration-1000"
          ></div>
        </div>

        <div class="flex items-center gap-6 text-xs text-on-surface-variant">
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full bg-primary"></div>
            <span>Venuts ({{ store.stats.sold }})</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full bg-tertiary"></div>
            <span>Reservats ({{ store.stats.reserved }})</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full bg-secondary/40"></div>
            <span>Disponibles ({{ store.stats.available }})</span>
          </div>
        </div>
      </div>

      <!-- Connected clients -->
      <div
        class="glass-panel rounded-2xl p-6 border border-white/10 flex flex-col items-center justify-center text-center"
      >
        <div class="relative mb-4">
          <div
            class="w-20 h-20 rounded-full bg-secondary/10 border-2 border-secondary/30 flex items-center justify-center"
          >
            <span class="material-symbols-outlined text-secondary text-4xl"
              >people</span
            >
          </div>
          <div
            class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-secondary animate-pulse"
          ></div>
        </div>
        <div
          class="text-5xl font-black font-headline text-secondary tabular-nums mb-2"
        >
          {{ connectedClients }}
        </div>
        <div
          class="text-xs font-bold uppercase tracking-widest text-on-surface-variant"
        >
          Usuaris connectats
        </div>
      </div>
    </div>

    <!-- Active reservations -->
    <div class="glass-panel rounded-2xl border border-white/10 overflow-hidden">
      <div
        class="px-6 py-4 border-b border-white/5 flex items-center justify-between"
      >
        <h2
          class="font-headline font-bold text-on-surface flex items-center gap-2"
        >
          <span class="material-symbols-outlined text-base text-tertiary"
            >pending_actions</span
          >
          Reserves Actives
          <span
            class="ml-2 px-2 py-0.5 rounded-full bg-tertiary/20 text-tertiary text-xs font-bold"
          >
            {{ store.activeReservations.length }}
          </span>
        </h2>
      </div>

      <div
        v-if="store.activeReservations.length === 0"
        class="py-16 text-center text-on-surface-variant"
      >
        <span class="material-symbols-outlined text-4xl opacity-30 block mb-2"
          >event_seat</span
        >
        <p class="text-sm">No hi ha reserves actives en aquest moment</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr
              class="border-b border-white/5 text-[11px] uppercase tracking-widest text-on-surface-variant"
            >
              <th class="text-left px-6 py-3 font-bold">Assiento</th>
              <th class="text-left px-6 py-3 font-bold hidden sm:table-cell">
                Sessió
              </th>
              <th class="text-left px-6 py-3 font-bold hidden md:table-cell">
                Reservat fa
              </th>
              <th class="text-right px-6 py-3 font-bold">Preu</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="seat in store.activeReservations"
              :key="seat.id"
              class="border-b border-white/5 hover:bg-white/5 transition-colors"
            >
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div
                    class="w-9 h-9 rounded-lg bg-tertiary/10 border border-tertiary/20 flex items-center justify-center"
                  >
                    <span class="font-headline font-black text-tertiary text-sm"
                      >{{ seat.row }}{{ seat.number }}</span
                    >
                  </div>
                  <div>
                    <div
                      class="font-headline font-bold text-sm text-on-surface"
                    >
                      Fila {{ seat.row }}, Seient {{ seat.number }}
                    </div>
                    <div class="text-xs text-on-surface-variant">
                      #{{ seat.id }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 hidden sm:table-cell">
                <span
                  class="font-mono text-xs text-on-surface-variant bg-white/5 px-2 py-1 rounded"
                >
                  {{
                    seat.session_id
                      ? seat.session_id.substring(0, 16) + "..."
                      : "—"
                  }}
                </span>
              </td>
              <td class="px-6 py-4 hidden md:table-cell">
                <span class="text-sm text-on-surface-variant">{{
                  timeAgo(seat.reserved_at)
                }}</span>
              </td>
              <td class="px-6 py-4 text-right">
                <span class="font-headline font-bold text-on-surface"
                  >€{{ Number(seat.price).toFixed(2) }}</span
                >
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useAdminStore } from "~/stores/adminStore";

const store = useAdminStore();
const { $socket } = useNuxtApp();

const connectedClients = ref(0);
const lastRefresh = ref(Date.now());
const statsLoading = ref(false);
let pollInterval = null;
let clockInterval = null;
const secondsSinceRefresh = ref(0);
const selectedEventId = ref(null);

const timeAgo = (dateStr) => {
  if (!dateStr) return "—";
  const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
  if (diff < 60) return `${diff}s`;
  if (diff < 3600) return `${Math.floor(diff / 60)}m ${diff % 60}s`;
  return `${Math.floor(diff / 3600)}h`;
};

const refresh = async () => {
  statsLoading.value = true;
  await store.fetchStats(selectedEventId.value);
  lastRefresh.value = Date.now();
  secondsSinceRefresh.value = 0;
  statsLoading.value = false;
};

const statCards = computed(() => [
  {
    label: "Disponibles",
    value: store.stats.available,
    icon: "event_seat",
    color: "text-secondary",
    bg: "bg-secondary/10",
    border: "border-secondary/20",
    glow: "shadow-[0_0_24px_rgba(131,251,165,0.08)]",
    bar: "bg-secondary",
    percent: store.stats.total
      ? Math.round((store.stats.available / store.stats.total) * 100)
      : 0,
  },
  {
    label: "Reservats",
    value: store.stats.reserved,
    icon: "pending",
    color: "text-tertiary",
    bg: "bg-tertiary/10",
    border: "border-tertiary/20",
    glow: "shadow-[0_0_24px_rgba(255,209,111,0.08)]",
    bar: "bg-tertiary",
    percent: store.stats.total
      ? Math.round((store.stats.reserved / store.stats.total) * 100)
      : 0,
  },
  {
    label: "Venuts",
    value: store.stats.sold,
    icon: "confirmation_number",
    color: "text-primary",
    bg: "bg-primary/10",
    border: "border-primary/20",
    glow: "shadow-[0_0_24px_rgba(149,170,255,0.08)]",
    bar: "bg-primary",
    percent: store.stats.total
      ? Math.round((store.stats.sold / store.stats.total) * 100)
      : 0,
  },
  {
    label: "Total Aforo",
    value: store.stats.total,
    icon: "stadium",
    color: "text-on-surface-variant",
    bg: "bg-white/5",
    border: "border-white/10",
    glow: "",
    bar: "bg-on-surface-variant/50",
    percent: 100,
  },
]);

onMounted(async () => {
  await store.fetchEvents(); // Cargar la lista de eventos para el desplegable
  
  // Seleccionar automáticamente el primer evento si existe
  if (store.events && store.events.length > 0) {
    selectedEventId.value = store.events[0].id;
  }
  
  await refresh();

  $socket.on("clients_count", (count) => {
    connectedClients.value = count;
  });

  $socket.on("seat_updated", () => {
    store.fetchStats();
  });

  // Auto-refresh every 30s
  pollInterval = setInterval(refresh, 30000);

  // Seconds since last refresh counter
  clockInterval = setInterval(() => {
    secondsSinceRefresh.value = Math.floor(
      (Date.now() - lastRefresh.value) / 1000,
    );
  }, 1000);
});

onUnmounted(() => {
  $socket.off("clients_count");
  $socket.off("seat_updated");
  if (pollInterval) clearInterval(pollInterval);
  if (clockInterval) clearInterval(clockInterval);
});
</script>
