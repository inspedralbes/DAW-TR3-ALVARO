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

    <!-- TopNavBar (identical to index.vue) -->
    <header
      class="fixed top-0 w-full z-50 bg-zinc-950/80 backdrop-blur-xl border-b border-white/5 font-headline tracking-tight"
    >
      <div
        class="flex justify-between items-center px-6 h-20 w-full max-w-[1920px] mx-auto"
      >
        <div
          class="text-2xl font-bold tracking-tighter bg-gradient-to-r from-fuchsia-500 via-purple-500 to-cyan-400 bg-clip-text text-transparent uppercase font-headline"
        >
          TICKETMONSTER
        </div>
        <nav class="hidden md:flex items-center space-x-8">
          <NuxtLink
            to="/"
            class="text-zinc-400 hover:text-white transition-colors"
            >Concerts</NuxtLink
          >
          <a
            class="text-zinc-400 hover:text-white transition-colors"
            href="/#venues"
            >Venues</a
          >
          <NuxtLink
            to="/my-tickets"
            class="text-fuchsia-400 border-b-2 border-fuchsia-500 pb-1"
            >My Tickets</NuxtLink
          >
        </nav>
        <div class="flex items-center space-x-4">
          <button
            class="material-symbols-outlined p-2 text-zinc-400 hover:bg-white/5 transition-all duration-300 rounded-full"
          >
            search
          </button>
          <button
            class="material-symbols-outlined p-2 text-fuchsia-500 hover:bg-white/5 transition-all duration-300 rounded-full"
          >
            account_circle
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
      <div class="mb-10">
        <h1
          class="font-headline text-4xl font-bold tracking-tight text-on-surface"
        >
          My <span class="text-primary italic">Tickets</span>
        </h1>
        <div
          class="h-1 w-20 bg-gradient-to-r from-primary to-tertiary mt-2"
        ></div>
        <p class="text-on-surface-variant mt-3">
          Enter your email to retrieve the tickets linked to your purchase.
        </p>
      </div>

      <!-- Email lookup form -->
      <form
        @submit.prevent="fetchTickets"
        class="glass-panel rounded-2xl p-6 border border-white/10 mb-8"
      >
        <div class="flex flex-col sm:flex-row gap-3">
          <div class="flex-1 relative group">
            <span
              class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline group-focus-within:text-primary transition-colors"
              >mail</span
            >
            <input
              v-model="email"
              type="email"
              required
              placeholder="your@email.com"
              class="w-full bg-surface-container-lowest border border-outline-variant/30 rounded-xl pl-12 pr-4 py-3 text-on-surface placeholder:text-outline focus:outline-none focus:border-primary/60 focus:ring-1 focus:ring-primary/40 transition-all"
            />
          </div>
          <button
            type="submit"
            :disabled="loading"
            class="px-8 py-3 rounded-xl signature-pulse text-white font-headline font-bold uppercase tracking-widest hover:scale-105 active:scale-95 transition-all disabled:opacity-60 disabled:cursor-not-allowed disabled:scale-100 whitespace-nowrap"
          >
            {{ loading ? "Searching..." : "Find My Tickets" }}
          </button>
        </div>
      </form>

      <!-- Error state -->
      <div
        v-if="error"
        class="glass-panel rounded-2xl p-6 border border-error/30 bg-error/5 flex items-center gap-4 mb-8"
      >
        <span class="material-symbols-outlined text-error text-3xl">error</span>
        <div>
          <p class="font-headline font-bold text-on-surface">
            No tickets found
          </p>
          <p class="text-on-surface-variant text-sm mt-1">{{ error }}</p>
        </div>
      </div>

      <!-- Results -->
      <transition name="fade">
        <div v-if="result">
          <!-- User + Event info card -->
          <div
            class="glass-panel rounded-2xl border border-white/10 overflow-hidden mb-6"
          >
            <!-- Event banner -->
            <div
              class="h-32 relative overflow-hidden flex items-center justify-center"
            >
              <img
                src="https://lh3.googleusercontent.com/aida-public/AB6AXuA8hpNkwm1PcuUuIgJxfpzKypnIBSXUVTFU6ZBB8gnG6TIMVYIt2coX5feoiKgjmLSIdLV49OuzZCxWClFC5wLKUZ3PSM4RfjPbgcCI-YQD76qjQDq6qYkciBnW4jNJyyeFcM07yzxK7-VT_EV1PwWFzVzf79XTKnQa16oFwSB2VvqwBrxUQxLG0vMIa8Gf5oAqs3_OJqbM0fspJW7iTjsZ13krYjX0KF_aBQcUYx-6KJvmTx4ecTzRDRw6XN_fo2vccUwdNJlTvDs"
                alt="Concert"
                class="w-full h-full object-cover opacity-40 scale-105"
              />
              <div
                class="absolute inset-0 bg-gradient-to-r from-background/90 via-background/40 to-background/90"
              ></div>
              <div class="absolute inset-0 flex items-center px-8 gap-6">
                <div
                  class="w-14 h-14 rounded-full signature-pulse shadow-[0_0_20px_rgba(255,171,243,0.4)] flex items-center justify-center flex-shrink-0"
                >
                  <span class="material-symbols-outlined text-white text-2xl"
                    >confirmation_number</span
                  >
                </div>
                <div>
                  <p
                    class="text-xs text-primary font-bold uppercase tracking-widest mb-1"
                  >
                    Confirmed Purchase
                  </p>
                  <h2
                    class="font-headline text-2xl font-bold text-on-surface leading-tight"
                  >
                    {{ result.event.name }}
                  </h2>
                  <p
                    class="text-on-surface-variant text-sm mt-0.5 flex items-center gap-2"
                  >
                    <span class="material-symbols-outlined text-xs"
                      >stadium</span
                    >
                    {{ result.event.venue }}
                    <span class="text-outline">•</span>
                    <span class="material-symbols-outlined text-xs">event</span>
                    {{ result.event.date }}
                  </p>
                </div>
              </div>
            </div>

            <!-- User info row -->
            <div
              class="px-8 py-4 border-t border-white/5 flex flex-col sm:flex-row sm:items-center justify-between gap-3"
            >
              <div class="flex items-center gap-3">
                <div
                  class="w-9 h-9 rounded-full bg-secondary-container/30 border border-secondary/20 flex items-center justify-center"
                >
                  <span class="material-symbols-outlined text-secondary text-sm"
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
                    Tickets
                  </p>
                  <p class="font-headline font-bold text-on-surface">
                    {{ result.tickets.length }}
                  </p>
                </div>
                <div class="text-right">
                  <p
                    class="text-xs text-on-surface-variant uppercase tracking-wider"
                  >
                    Total Paid
                  </p>
                  <p class="font-headline font-bold text-primary text-lg">
                    €{{ result.total.toFixed(2) }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Tickets grid -->
          <div
            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8"
          >
            <div
              v-for="ticket in result.tickets"
              :key="ticket.id"
              class="group glass-panel rounded-2xl border border-white/10 hover:border-primary/30 transition-all duration-300 overflow-hidden"
            >
              <!-- Ticket top strip -->
              <div class="h-1.5 w-full signature-pulse"></div>

              <div class="p-5">
                <!-- Seat label big -->
                <div class="flex items-start justify-between mb-4">
                  <div
                    class="w-14 h-14 rounded-xl bg-primary/10 border border-primary/20 flex flex-col items-center justify-center"
                  >
                    <span
                      class="text-primary font-headline font-black text-lg leading-none"
                    >
                      {{ ticket.row }}{{ ticket.number }}
                    </span>
                    <span
                      class="text-primary/60 text-[9px] uppercase tracking-wider mt-0.5"
                      >Seat</span
                    >
                  </div>
                  <div class="text-right">
                    <span
                      class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-tertiary/10 border border-tertiary/20 text-tertiary text-[10px] font-bold uppercase tracking-wider"
                    >
                      <span class="material-symbols-outlined text-xs"
                        >check_circle</span
                      >
                      Confirmed
                    </span>
                  </div>
                </div>

                <!-- Ticket details -->
                <div class="space-y-2">
                  <div class="flex items-center gap-2 text-sm">
                    <span
                      class="material-symbols-outlined text-xs text-on-surface-variant"
                      >location_on</span
                    >
                    <span class="text-on-surface-variant"
                      >Row {{ ticket.row }}, Seat {{ ticket.number }}</span
                    >
                  </div>
                  <div class="flex items-center gap-2 text-sm">
                    <span
                      class="material-symbols-outlined text-xs text-on-surface-variant"
                      >confirmation_number</span
                    >
                    <span class="text-on-surface-variant font-mono text-xs">
                      #{{ String(ticket.id).padStart(6, "0") }}
                    </span>
                  </div>
                  <div
                    v-if="ticket.purchased_at"
                    class="flex items-center gap-2 text-sm"
                  >
                    <span
                      class="material-symbols-outlined text-xs text-on-surface-variant"
                      >schedule</span
                    >
                    <span class="text-on-surface-variant text-xs">
                      {{ formatDate(ticket.purchased_at) }}
                    </span>
                  </div>
                </div>

                <!-- Price + dashed separator -->
                <div
                  class="mt-4 pt-4 border-t border-dashed border-white/10 flex items-center justify-between"
                >
                  <span
                    class="text-xs text-on-surface-variant uppercase tracking-wider"
                    >Price paid</span
                  >
                  <span class="font-headline font-bold text-on-surface">
                    €{{ ticket.price.toFixed(2) }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Download hint -->
          <div
            class="flex items-center justify-center gap-3 text-on-surface-variant text-sm"
          >
            <span class="material-symbols-outlined text-base">info</span>
            <span
              >Your e-tickets have been sent to
              <strong class="text-on-surface">{{ result.user.email }}</strong
              >. Present them at the venue entrance.</span
            >
          </div>
        </div>
      </transition>

      <!-- Empty / initial state -->
      <div
        v-if="!result && !error && !loading"
        class="flex flex-col items-center py-16 text-center text-on-surface-variant gap-4"
      >
        <span class="material-symbols-outlined text-6xl opacity-30"
          >confirmation_number</span
        >
        <p class="text-lg font-medium">
          Enter your email above to view your tickets.
        </p>
      </div>
      </div>
    </main>
  </div>
</template>

<script setup>
useHead({
  title: "My Tickets | TICKETMONSTER",
  htmlAttrs: { class: "dark", lang: "en" },
  link: [
    {
      rel: "preconnect",
      href: "https://fonts.googleapis.com",
    },
    {
      rel: "stylesheet",
      href: "https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap",
    },
    {
      rel: "stylesheet",
      href: "https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap",
    },
  ],
});

const config = useRuntimeConfig();
const email = ref("");
const loading = ref(false);
const error = ref(null);
const result = ref(null);

const fetchTickets = async () => {
  loading.value = true;
  error.value = null;
  result.value = null;

  try {
    const data = await $fetch(
      `${config.public.apiUrl}/api/tickets`,
      { query: { email: email.value } },
    );
    if (data.success) {
      result.value = data;
    } else {
      error.value = "No tickets found for this email.";
    }
  } catch (e) {
    const msg = e.response?._data?.error || e.message;
    error.value = msg || "Something went wrong. Please try again.";
  } finally {
    loading.value = false;
  }
};

const formatDate = (iso) => {
  return new Date(iso).toLocaleString("en-GB", {
    day: "2-digit",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
};
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
