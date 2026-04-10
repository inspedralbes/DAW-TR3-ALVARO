<template>
  <div class="min-h-screen bg-background text-on-background font-body">

    <!-- Admin Header -->
    <header class="fixed top-0 left-0 right-0 z-50 h-16 bg-zinc-950/95 backdrop-blur-xl border-b border-white/5 flex items-center px-6 gap-4">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg signature-pulse flex items-center justify-center">
          <span class="material-symbols-outlined text-white text-base">admin_panel_settings</span>
        </div>
        <div>
          <span class="text-xs font-bold tracking-[0.25em] uppercase text-on-surface-variant">Panel</span>
          <div class="text-sm font-black tracking-tighter bg-gradient-to-r from-fuchsia-400 to-cyan-400 bg-clip-text text-transparent uppercase font-headline leading-none">
            TICKETMONSTER ADMIN
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <nav class="flex items-center gap-1 ml-8 bg-white/5 rounded-xl p-1">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-headline font-bold transition-all duration-200',
            activeTab === tab.id
              ? 'bg-primary/20 text-primary'
              : 'text-on-surface-variant hover:text-on-surface hover:bg-white/5',
          ]"
        >
          <span class="material-symbols-outlined text-base">{{ tab.icon }}</span>
          <span class="hidden sm:inline">{{ tab.label }}</span>
        </button>
      </nav>

      <div class="flex-1"></div>

      <!-- Live indicator -->
      <div class="hidden md:flex items-center gap-2 text-xs text-on-surface-variant">
        <span class="w-2 h-2 rounded-full bg-secondary animate-pulse"></span>
        <span>Temps real actiu</span>
      </div>

      <!-- Back to site -->
      <NuxtLink
        to="/"
        class="flex items-center gap-2 text-xs text-on-surface-variant hover:text-on-surface transition-colors px-3 py-2 rounded-lg hover:bg-white/5"
      >
        <span class="material-symbols-outlined text-base">arrow_back</span>
        <span class="hidden sm:inline">Tornar al lloc</span>
      </NuxtLink>
    </header>

    <!-- Page content -->
    <main class="pt-16 min-h-screen">
      <Transition name="tab-fade" mode="out-in">
        <AdminDashboard v-if="activeTab === 'dashboard'" />
        <AdminEventManager v-else-if="activeTab === 'events'" />
      </Transition>
    </main>
  </div>
</template>

<script setup>
import AdminDashboard from '~/components/admin/AdminDashboard.vue'
import AdminEventManager from '~/components/admin/AdminEventManager.vue'

useHead({
  title: 'Panel Admin | TICKETMONSTER',
  htmlAttrs: { class: 'dark', lang: 'ca' },
})

const activeTab = ref('dashboard')

const tabs = [
  { id: 'dashboard', label: 'Dashboard', icon: 'dashboard' },
  { id: 'events', label: "Esdeveniments", icon: 'event_note' },
]
</script>

<style scoped>
.tab-fade-enter-active,
.tab-fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.tab-fade-enter-from {
  opacity: 0;
  transform: translateY(8px);
}
.tab-fade-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
