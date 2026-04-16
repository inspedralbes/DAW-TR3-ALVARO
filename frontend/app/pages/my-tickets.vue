<template>
  <div
    class="min-h-screen bg-background text-on-background font-body selection:bg-primary/30"
  >
    <!-- Background blobs -->
    <div
      class="fixed inset-0 -z-10 pointer-events-none overflow-hidden blur-[100px] opacity-20"
    >
      <div
        class="absolute top-1/3 left-1/4 w-[500px] h-[500px] bg-primary rounded-full mix-blend-screen opacity-25"
      ></div>
      <div
        class="absolute bottom-1/4 right-1/4 w-[400px] h-[400px] bg-secondary rounded-full mix-blend-screen opacity-15"
      ></div>
    </div>

    <!-- TopNavBar -->
    <header
      class="fixed top-0 w-full z-50 bg-zinc-950/80 backdrop-blur-xl border-b border-white/5 font-headline tracking-tight"
    >
      <div
        class="flex justify-between items-center px-6 h-20 w-full max-w-[1920px] mx-auto"
      >
        <NuxtLink
          to="/"
          class="text-2xl font-bold tracking-tighter bg-gradient-to-r from-fuchsia-500 via-purple-500 to-cyan-400 bg-clip-text text-transparent uppercase font-headline"
        >
          TICKETMONSTER
        </NuxtLink>
        <nav class="hidden md:flex items-center space-x-8">
          <NuxtLink
            to="/"
            class="text-zinc-400 hover:text-white transition-colors"
            >Concerts</NuxtLink
          >
          <NuxtLink
            to="/my-tickets"
            class="text-fuchsia-400 border-b-2 border-fuchsia-500 pb-1"
            >Les meves entrades</NuxtLink
          >
        </nav>
        <div class="flex items-center space-x-3">
          <!-- Admin link -->
          <NuxtLink
            v-if="authStore.isAdmin"
            to="/admin"
            class="flex items-center gap-2 px-4 py-2 rounded-xl border border-fuchsia-500/30 text-fuchsia-400 hover:bg-fuchsia-500/10 transition-all text-sm font-bold"
          >
            <span class="material-symbols-outlined text-base"
              >admin_panel_settings</span
            >
            <span class="hidden sm:inline">Admin</span>
          </NuxtLink>

          <!-- User chip -->
          <div
            v-else
            class="flex items-center gap-2 px-4 py-2 rounded-xl border border-white/10 text-zinc-400 text-sm"
          >
            <span class="material-symbols-outlined text-base text-fuchsia-500"
              >account_circle</span
            >
            <span class="hidden sm:inline">{{ authStore.user?.name }}</span>
          </div>
          <button
            @click="handleLogout"
            class="flex items-center gap-1.5 px-3 py-2 rounded-xl border border-white/10 text-zinc-500 hover:text-red-400 hover:border-red-500/30 hover:bg-red-500/5 transition-all text-sm font-bold"
          >
            <span class="material-symbols-outlined text-base">logout</span>
            <span class="hidden sm:inline">Sortir</span>
          </button>
        </div>
      </div>
      <div
        class="bg-gradient-to-r from-fuchsia-500/20 via-purple-500/20 to-cyan-400/20 h-[1px]"
      ></div>
    </header>

    <main class="pt-20 pb-16 min-h-screen">
      <div class="container mx-auto px-6 py-12 max-w-5xl">
        <!-- Page title -->
        <div class="mb-10 flex items-start justify-between">
          <div>
            <h1
              class="font-headline text-4xl font-bold tracking-tight text-on-surface"
            >
              Les meves <span class="text-primary italic">Entrades</span>
            </h1>
            <div
              class="h-1 w-20 bg-gradient-to-r from-primary to-tertiary mt-2"
            ></div>
            <p class="text-on-surface-variant mt-3 text-sm">
              Hola,
              <strong class="text-on-surface">{{
                authStore.user?.name
              }}</strong>
              — aquí tens totes les teves entrades confirmes.
            </p>
          </div>
          <button
            @click="loadTickets"
            :disabled="loading"
            class="flex items-center gap-2 px-4 py-2 rounded-xl border border-white/10 text-on-surface-variant hover:border-primary/30 hover:text-primary transition-all text-sm"
          >
            <span
              class="material-symbols-outlined text-base"
              :class="{ 'animate-spin': loading }"
              >refresh</span
            >
            Actualitzar
          </button>
        </div>

        <!-- Loading -->
        <div
          v-if="loading"
          class="flex flex-col items-center py-24 gap-4 text-on-surface-variant"
        >
          <span
            class="material-symbols-outlined text-5xl animate-spin opacity-30"
            >progress_activity</span
          >
          <p class="text-sm">Carregant les teves entrades...</p>
        </div>

        <!-- Error -->
        <div
          v-else-if="error"
          class="glass-panel rounded-2xl p-6 border border-error/30 bg-error/5 flex items-center gap-4 mb-8"
        >
          <span class="material-symbols-outlined text-error text-3xl"
            >error</span
          >
          <div>
            <p class="font-headline font-bold text-on-surface">
              Error carregant les entrades
            </p>
            <p class="text-on-surface-variant text-sm mt-1">{{ error }}</p>
          </div>
          <button
            @click="loadTickets"
            class="ml-auto text-primary text-sm font-bold hover:underline"
          >
            Reintentar
          </button>
        </div>

        <!-- Results -->
        <transition name="fade">
          <div v-if="result && !loading">
            <!-- Summary card -->
            <div
              class="glass-panel rounded-2xl border border-white/10 overflow-hidden mb-6"
            >
              <div
                class="px-8 py-5 border-b border-white/5 flex flex-col sm:flex-row sm:items-center justify-between gap-3"
              >
                <div class="flex items-center gap-3">
                  <div
                    class="w-10 h-10 rounded-full bg-secondary-container/30 border border-secondary/20 flex items-center justify-center"
                  >
                    <span
                      class="material-symbols-outlined text-secondary text-sm"
                      >person</span
                    >
                  </div>
                  <div>
                    <p class="font-headline font-bold text-on-surface text-sm">
                      {{ result.user.name }}
                    </p>
                    <p class="text-on-surface-variant text-xs">
                      {{ result.user.email }}
                    </p>
                  </div>
                </div>
                <div class="flex items-center gap-6">
                  <div class="text-right">
                    <p
                      class="text-xs text-on-surface-variant uppercase tracking-wider"
                    >
                      Entrades
                    </p>
                    <p class="font-headline font-bold text-on-surface">
                      {{ result.tickets.length }}
                    </p>
                  </div>
                  <div class="text-right">
                    <p
                      class="text-xs text-on-surface-variant uppercase tracking-wider"
                    >
                      Total pagat
                    </p>
                    <p class="font-headline font-bold text-primary text-lg">
                      €{{ result.total.toFixed(2) }}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Empty tickets -->
            <div
              v-if="result.tickets.length === 0"
              class="flex flex-col items-center py-20 text-center text-on-surface-variant gap-4"
            >
              <span class="material-symbols-outlined text-6xl opacity-20"
                >confirmation_number</span
              >
              <p class="text-lg font-medium">
                Encara no tens cap entrada comprada.
              </p>
              <NuxtLink
                to="/"
                class="px-6 py-3 rounded-xl border border-primary/30 text-primary font-bold hover:bg-primary/10 transition-colors"
              >
                Explorar esdeveniments
              </NuxtLink>
            </div>

            <!-- Tickets grid — grouped by event -->
            <template
              v-for="(group, eventTitle) in ticketsByEvent"
              :key="eventTitle"
            >
              <!-- Event header -->
              <div class="flex items-center gap-3 mb-4 mt-8 first:mt-0">
                <div
                  class="w-8 h-8 rounded-lg signature-pulse flex items-center justify-center flex-shrink-0"
                >
                  <span class="material-symbols-outlined text-white text-sm"
                    >event</span
                  >
                </div>
                <div>
                  <h2 class="font-headline font-bold text-on-surface">
                    {{ eventTitle }}
                  </h2>
                  <p
                    v-if="group[0]?.event?.venue"
                    class="text-xs text-on-surface-variant"
                  >
                    {{ group[0].event.venue }} ·
                    {{
                      group[0].event.date ? formatDate(group[0].event.date) : ""
                    }}
                  </p>
                </div>
                <span
                  class="ml-auto text-xs font-bold text-on-surface-variant bg-white/5 px-2 py-1 rounded-full"
                  >{{ group.length }} entrada{{
                    group.length !== 1 ? "s" : ""
                  }}</span
                >
              </div>

              <div
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6"
              >
                <div
                  v-for="ticket in group"
                  :key="ticket.id"
                  class="group glass-panel rounded-2xl border border-white/10 hover:border-primary/30 transition-all duration-300 overflow-hidden"
                >
                  <div class="h-1.5 w-full signature-pulse"></div>
                  <div class="p-5">
                    <div class="flex items-start justify-between mb-4">
                      <div
                        class="w-14 h-14 rounded-xl bg-primary/10 border border-primary/20 flex flex-col items-center justify-center"
                      >
                        <span
                          class="text-primary font-headline font-black text-lg leading-none"
                          >{{ ticket.row }}{{ ticket.number }}</span
                        >
                        <span
                          class="text-primary/60 text-[9px] uppercase tracking-wider mt-0.5"
                          >Seient</span
                        >
                      </div>
                      <span
                        class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-tertiary/10 border border-tertiary/20 text-tertiary text-[10px] font-bold uppercase tracking-wider"
                      >
                        <span class="material-symbols-outlined text-xs"
                          >check_circle</span
                        >
                        Confirmat
                      </span>
                    </div>
                    <div class="space-y-2">
                      <div class="flex items-center gap-2 text-sm">
                        <span
                          class="material-symbols-outlined text-xs text-on-surface-variant"
                          >location_on</span
                        >
                        <span class="text-on-surface-variant"
                          >Fila {{ ticket.row }}, Seient
                          {{ ticket.number }}</span
                        >
                      </div>
                      <div class="flex items-center gap-2 text-sm">
                        <span
                          class="material-symbols-outlined text-xs text-on-surface-variant"
                          >confirmation_number</span
                        >
                        <span class="text-on-surface-variant font-mono text-xs"
                          >#{{ String(ticket.id).padStart(6, "0") }}</span
                        >
                      </div>
                      <div
                        v-if="ticket.purchased_at"
                        class="flex items-center gap-2 text-sm"
                      >
                        <span
                          class="material-symbols-outlined text-xs text-on-surface-variant"
                          >schedule</span
                        >
                        <span class="text-on-surface-variant text-xs">{{
                          formatDate(ticket.purchased_at)
                        }}</span>
                      </div>
                    </div>
                    <div
                      class="mt-4 pt-4 border-t border-dashed border-white/10 flex items-center justify-between"
                    >
                      <span
                        class="text-xs text-on-surface-variant uppercase tracking-wider"
                        >Preu pagat</span
                      >
                      <span class="font-headline font-bold text-on-surface"
                        >€{{ ticket.price.toFixed(2) }}</span
                      >
                    </div>
                  </div>
                </div>
              </div>
            </template>
          </div>
        </transition>
      </div>
    </main>
  </div>
</template>

<script setup>
definePageMeta({ middleware: "user" });

useHead({
  title: "Les meves Entrades | TICKETMONSTER",
  htmlAttrs: { class: "dark", lang: "ca" },
});

const config = useRuntimeConfig();
const authStore = useAuthStore();
const router = useRouter();

const loading = ref(false);
const error = ref(null);
const result = ref(null);

// Group tickets by event title
const ticketsByEvent = computed(() => {
  if (!result.value?.tickets) return {};
  return result.value.tickets.reduce((groups, ticket) => {
    const eventName = ticket.event?.title || "Sense esdeveniment";
    if (!groups[eventName]) groups[eventName] = [];
    groups[eventName].push(ticket);
    return groups;
  }, {});
});

const loadTickets = async () => {
  loading.value = true;
  error.value = null;
  try {
    const data = await $fetch(`${config.public.apiUrl}/api/auth/my-tickets`, {
      headers: authStore.authHeaders(),
    });
    result.value = data;
  } catch (e) {
    error.value =
      e.response?._data?.message || e.message || "Error desconegut.";
  } finally {
    loading.value = false;
  }
};

const handleLogout = async () => {
  await authStore.logout();
  router.push("/login");
};

const formatDate = (iso) => {
  if (!iso) return "";
  return new Date(iso).toLocaleString("ca-ES", {
    day: "2-digit",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
};

// Auto-load on mount
onMounted(loadTickets);
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition:
    opacity 0.4s ease,
    transform 0.4s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(12px);
}
</style>
