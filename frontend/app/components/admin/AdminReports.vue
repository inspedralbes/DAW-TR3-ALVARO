<template>
  <div class="p-6 md:p-10 max-w-[1400px] mx-auto">
    <!-- Page header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-10">
      <div>
        <h1 class="font-headline text-3xl font-black tracking-tight text-on-surface">
          Informes <span class="text-primary italic">i Analítica</span>
        </h1>
        <p class="text-on-surface-variant text-sm mt-1 flex items-center gap-2">
          <span class="material-symbols-outlined text-sm">monitoring</span>
          Resum econòmic i rendiment de vendes
        </p>
      </div>

      <!-- Event Filter -->
      <div class="flex items-center gap-4">
        <select 
          v-model="selectedEventId" 
          @change="fetchData"
          class="bg-surface-container-high border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/50 transition-all font-headline font-bold uppercase tracking-wider min-w-[200px]"
        >
          <option :value="null">Global (Tots els Concerts)</option>
          <option v-for="event in store.events" :key="event.id" :value="event.id">
            {{ event.title }}
          </option>
        </select>
        
        <button
          @click="fetchData"
          :class="[
            'flex items-center gap-2 px-4 py-2.5 rounded-xl border font-headline font-bold text-sm transition-all',
            loading
              ? 'border-white/10 text-on-surface-variant cursor-wait'
              : 'border-primary/30 text-primary hover:bg-primary/10',
          ]"
        >
          <span
            class="material-symbols-outlined text-base"
            :class="{ 'animate-spin': loading }"
          >refresh</span>
          Actualitzar
        </button>
      </div>
    </div>

    <!-- Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <!-- Revenue -->
      <div class="glass-panel rounded-2xl p-6 border border-white/5 relative overflow-hidden">
        <div class="absolute top-0 right-0 p-4 opacity-10">
          <span class="material-symbols-outlined text-8xl text-primary -rotate-12">payments</span>
        </div>
        <h3 class="text-on-surface-variant text-sm font-bold uppercase tracking-wider mb-2">Recaptació Total</h3>
        <div class="text-5xl font-black font-headline text-primary mb-2">
          €{{ data?.totalRevenue?.toLocaleString('ca-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) || '0.00' }}
        </div>
      </div>

      <!-- Avg per Ticket -->
      <div class="glass-panel rounded-2xl p-6 border border-white/5 relative overflow-hidden">
        <div class="absolute top-0 right-0 p-4 opacity-10">
          <span class="material-symbols-outlined text-8xl text-secondary -rotate-12">local_activity</span>
        </div>
        <h3 class="text-on-surface-variant text-sm font-bold uppercase tracking-wider mb-2">Preu Mig / Entrada</h3>
        <div class="text-5xl font-black font-headline text-secondary mb-2">
          €{{ data?.averagePerTicket?.toLocaleString('ca-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) || '0.00' }}
        </div>
        <p class="text-xs text-on-surface-variant mt-2 font-bold">{{ data?.soldCount || 0 }} entrades venudes</p>
      </div>

      <!-- Occupancy -->
      <div class="glass-panel rounded-2xl p-6 border border-white/5 relative overflow-hidden">
        <div class="absolute top-0 right-0 p-4 opacity-10">
          <span class="material-symbols-outlined text-8xl text-tertiary -rotate-12">stadium</span>
        </div>
        <h3 class="text-on-surface-variant text-sm font-bold uppercase tracking-wider mb-2">Ocupació (Aforament)</h3>
        <div class="flex items-end gap-3 text-tertiary mb-2">
          <div class="text-5xl font-black font-headline">{{ data?.occupancyPercentage || 0 }}%</div>
        </div>
        
        <!-- Progress bar -->
        <div class="h-2 w-full bg-white/10 rounded-full mt-4 overflow-hidden relative">
            <div 
              class="absolute top-0 left-0 bottom-0 bg-tertiary transition-all duration-1000 ease-out"
              :style="{ width: `${data?.occupancyPercentage || 0}%` }"
            ></div>
        </div>
      </div>
    </div>

    <!-- Chart Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
      <!-- Line Chart -->
      <div class="glass-panel rounded-2xl p-6 border border-white/10 flex flex-col h-[400px]">
        <h3 class="font-headline font-bold text-on-surface text-lg mb-6 flex items-center gap-2">
          <span class="material-symbols-outlined text-primary">timeline</span>
          Evolució de Vendes (€) Diàries
        </h3>
        
        <div class="w-full flex-1 min-h-0 relative">
          <Line 
            v-if="chartData && !loading" 
            :data="chartData" 
            :options="chartOptions" 
            class="absolute inset-0 w-full h-full"
          />
          <div v-else-if="loading" class="absolute inset-0 flex flex-col items-center justify-center text-on-surface-variant">
            <span class="material-symbols-outlined text-4xl animate-spin text-primary opacity-50 mb-2">sync</span>
            <span class="text-xs font-bold uppercase tracking-wider opacity-50">Carregant dades...</span>
          </div>
          <div v-else class="absolute inset-0 flex flex-col items-center justify-center text-on-surface-variant">
            <span class="material-symbols-outlined text-4xl opacity-50 mb-2">trending_down</span>
            <span class="text-xs font-bold uppercase tracking-wider opacity-50">Sense dades suficients</span>
          </div>
        </div>
      </div>

      <!-- Tickets evolution chart -->
      <div class="glass-panel rounded-2xl p-6 border border-white/10 flex flex-col h-[400px]">
        <h3 class="font-headline font-bold text-on-surface text-lg mb-6 flex items-center gap-2">
          <span class="material-symbols-outlined text-secondary">bar_chart</span>
          Volum d'Entrades Venudes Diàries
        </h3>
        
        <div class="w-full flex-1 min-h-0 relative">
          <Line 
            v-if="chartDataTickets && !loading" 
            :data="chartDataTickets" 
            :options="chartOptionsTickets" 
            class="absolute inset-0 w-full h-full"
          />
          <div v-else-if="loading" class="absolute inset-0 flex flex-col items-center justify-center text-on-surface-variant">
            <span class="material-symbols-outlined text-4xl animate-spin text-secondary opacity-50 mb-2">sync</span>
            <span class="text-xs font-bold uppercase tracking-wider opacity-50">Carregant dades...</span>
          </div>
          <div v-else class="absolute inset-0 flex flex-col items-center justify-center text-on-surface-variant">
            <span class="material-symbols-outlined text-4xl opacity-50 mb-2">trending_down</span>
            <span class="text-xs font-bold uppercase tracking-wider opacity-50">Sense dades suficients</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useAdminStore } from '~/stores/adminStore'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler,
  BarElement
} from 'chart.js'
import { Line } from 'vue-chartjs'

// Register ChartJS modules
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  Filler
)

const store = useAdminStore()
const selectedEventId = ref(null)
const loading = ref(true)
const data = ref(null)

const fetchData = async () => {
  loading.value = true
  data.value = await store.fetchReports(selectedEventId.value)
  loading.value = false
}

onMounted(async () => {
  if (store.events.length === 0) {
    await store.fetchEvents()
  }
  
  if (store.events && store.events.length > 0) {
    selectedEventId.value = store.events[0].id
  }
  
  await fetchData()
})

const getSharedLabels = (evo) => {
  return evo.map(item => {
    if(!item.date) return 'Sense Data';
    const split = item.date.split('-');
    if(split.length === 3) return `${split[2]}/${split[1]}`;
    return item.date;
  });
}

// Chart.js Configuration 1: Money
const chartData = computed(() => {
  if (!data.value || !data.value.salesEvolution || data.value.salesEvolution.length === 0) return null;
  const evo = data.value.salesEvolution;
  const values = evo.map(item => item.revenue)
  return {
    labels: getSharedLabels(evo),
    datasets: [{
      label: 'Recaptació Diària (€)',
      data: values,
      borderColor: '#00ffff', // primary color roughly
      backgroundColor: 'rgba(0, 255, 255, 0.05)',
      borderWidth: 2,
      pointBackgroundColor: '#18181b',
      pointBorderColor: '#00ffff',
      pointBorderWidth: 2,
      pointRadius: 4,
      tension: 0.4,
      fill: true,
    }]
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: 'rgba(9, 9, 11, 0.9)',
      titleColor: '#fff',
      bodyColor: '#00ffff',
      borderColor: 'rgba(0,255,255,0.2)',
      borderWidth: 1,
      padding: 12,
      displayColors: false,
      callbacks: { label: function(c) { return `€${c.parsed.y.toLocaleString('ca-ES')}`; } }
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      border: { display: false },
      grid: { color: 'rgba(255, 255, 255, 0.05)' },
      ticks: { color: '#a1a1aa', callback: function(v) { return '€' + v; } }
    },
    x: {
      border: { display: false },
      grid: { display: false },
      ticks: { color: '#a1a1aa' }
    }
  }
}

// Chart.js Configuration 2: Tickets count
const chartDataTickets = computed(() => {
  if (!data.value || !data.value.salesEvolution || data.value.salesEvolution.length === 0) return null;
  const evo = data.value.salesEvolution;
  const values = evo.map(item => item.count)
  return {
    labels: getSharedLabels(evo),
    datasets: [{
      label: 'Entrades Venudes',
      data: values,
      borderColor: '#ffabf3', // secondary color
      backgroundColor: 'rgba(255, 171, 243, 0.05)',
      borderWidth: 2,
      pointBackgroundColor: '#18181b',
      pointBorderColor: '#ffabf3',
      pointBorderWidth: 2,
      pointRadius: 4,
      tension: 0.4,
      fill: true,
    }]
  }
})

const chartOptionsTickets = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: 'rgba(9, 9, 11, 0.9)',
      titleColor: '#fff',
      bodyColor: '#ffabf3',
      borderColor: 'rgba(255,171,243,0.2)',
      borderWidth: 1,
      padding: 12,
      displayColors: false,
      callbacks: { label: function(c) { return `${c.parsed.y} entrades`; } }
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      border: { display: false },
      grid: { color: 'rgba(255, 255, 255, 0.05)' },
      ticks: { color: '#a1a1aa', stepSize: 1 }
    },
    x: {
      border: { display: false },
      grid: { display: false },
      ticks: { color: '#a1a1aa' }
    }
  }
}
</script>
