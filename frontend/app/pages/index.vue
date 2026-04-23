<template>
  <div
    class="bg-background text-on-background font-body selection:bg-primary selection:text-on-primary"
  >
    <!-- TopNavBar -->
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
            class="text-fuchsia-400 border-b-2 border-fuchsia-500 pb-1"
            >Concerts</NuxtLink
          >
          <NuxtLink
            :to="authStore.isLoggedIn ? '/my-tickets' : '/login'"
            class="text-zinc-400 hover:text-white transition-colors"
            >Les meves entrades</NuxtLink
          >
        </nav>
        <div class="flex items-center space-x-3">
          <!-- Profile / Auth button -->
          <NuxtLink
            v-if="!authStore.isLoggedIn"
            to="/login"
            class="flex items-center gap-2 px-4 py-2 rounded-xl border border-white/10 text-zinc-400 hover:text-white hover:border-fuchsia-500/40 hover:bg-fuchsia-500/5 transition-all text-sm font-bold"
          >
            <span class="material-symbols-outlined text-base text-fuchsia-500"
              >account_circle</span
            >
            <span class="hidden sm:inline">Iniciar Sessió</span>
          </NuxtLink>

          <template v-else>
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

            <!-- Logout button (always visible when logged in) -->
            <button
              @click="handleLogout"
              title="Tancar sessió"
              class="flex items-center gap-1.5 px-3 py-2 rounded-xl border border-white/10 text-zinc-500 hover:text-red-400 hover:border-red-500/30 hover:bg-red-500/5 transition-all text-sm font-bold"
            >
              <span class="material-symbols-outlined text-base">logout</span>
              <span class="hidden sm:inline">Sortir</span>
            </button>
          </template>
        </div>
      </div>
      <div
        class="bg-gradient-to-r from-fuchsia-500/20 via-purple-500/20 to-cyan-400/20 h-[1px]"
      ></div>
    </header>

    <main class="min-h-screen pb-24 md:pb-0">
      <!-- Hero Section -->
      <section
        class="relative h-[90vh] md:h-[921px] flex items-center overflow-hidden px-6 md:px-12"
      >
        <div class="absolute inset-0 z-0">
          <div
            class="absolute inset-0 bg-gradient-to-t from-background via-background/20 to-transparent z-10"
          ></div>
          <div
            class="absolute inset-0 bg-gradient-to-r from-background via-transparent to-transparent z-10"
          ></div>
          <img
            class="w-full h-full object-cover opacity-60 scale-105"
            alt="Concert stage with blue lasers cutting through atmospheric smoke"
            src="https://lh3.googleusercontent.com/aida-public/AB6AXuA8hpNkwm1PcuUuIgJxfpzKypnIBSXUVTFU6ZBB8gnG6TIMVYIt2coX5feoiKgjmLSIdLV49OuzZCxWClFC5wLKUZ3PSM4RfjPbgcCI-YQD76qjQDq6qYkciBnW4jNJyyeFcM07yzxK7-VT_EV1PwWFzVzf79XTKnQa16oFwSB2VvqwBrxUQxLG0vMIa8Gf5oAqs3_OJqbM0fspJW7iTjsZ13krYjX0KF_aBQcUYx-6KJvmTx4ecTzRDRw6XN_fo2vccUwdNJlTvDs"
          />
        </div>
        <div class="relative z-10 container mx-auto px-6 mt-20">
          <div class="max-w-4xl">
            <div
              class="inline-block mb-6 px-4 py-1 rounded-full border border-primary/30 bg-primary/10 backdrop-blur-md"
            >
              <span
                class="font-headline text-primary text-xs font-bold tracking-[0.2em] uppercase"
                >Esdeveniment Destacat</span
              >
            </div>
            <h1
              class="font-headline text-6xl md:text-9xl font-black italic tracking-tighter leading-none mb-8"
            >
              <span
                class="text-transparent bg-clip-text bg-gradient-to-r from-tertiary to-cyan-600 text-glow-tertiary"
                >{{
                  featuredEvent
                    ? featuredEvent.title
                    : "TICKETMONSTER EXCLUSIVE"
                }}</span
              >
            </h1>
            <div class="flex flex-wrap gap-4 items-center">
              <NuxtLink
                :to="featuredEvent ? `/event/${featuredEvent.id}` : '/'"
                class="px-8 py-4 signature-pulse text-on-secondary-fixed font-black text-lg uppercase tracking-tighter rounded-xl shadow-[0_0_20px_rgba(255,171,243,0.4)] hover:scale-105 transition-transform active:scale-95 flex items-center gap-3"
              >
                Comprar Entrades
                <span class="material-symbols-outlined">arrow_forward</span>
              </NuxtLink>
              <button
                @click="scrollToEvents"
                class="px-8 py-4 bg-surface-container-highest text-primary font-bold rounded-xl text-lg border border-outline-variant/20 backdrop-blur-xl hover:bg-white/10 transition-all"
              >
                Veure tots els events
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- Upcoming Events Grid -->
      <section id="events" class="container mx-auto px-6 py-24">
        <div class="flex justify-between items-end mb-12">
          <div>
            <h2
              class="font-headline text-4xl font-bold tracking-tight text-on-surface uppercase"
            >
              Pròxims <span class="text-primary italic">Esdeveniments</span>
            </h2>
            <div
              class="h-1 w-24 bg-gradient-to-r from-primary to-tertiary mt-2"
            ></div>
          </div>
          <NuxtLink
            v-if="authStore.isAdmin"
            to="/admin"
            class="text-tertiary font-headline font-bold flex items-center gap-2 group hover:gap-3 transition-all"
          >
            Gestionar
            <span
              class="material-symbols-outlined group-hover:translate-x-1 transition-transform"
              >settings</span
            >
          </NuxtLink>
        </div>

        <!-- Loading state -->
        <div
          v-if="pending"
          class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8"
        >
          <div
            v-for="i in 3"
            :key="i"
            class="bg-surface-container-low rounded-[2rem] overflow-hidden h-80 animate-pulse border border-outline-variant/10"
          >
            <div class="h-64 bg-white/5"></div>
            <div class="p-6 space-y-3">
              <div class="h-4 bg-white/5 rounded w-2/3"></div>
              <div class="h-3 bg-white/5 rounded w-1/2"></div>
            </div>
          </div>
        </div>

        <!-- Empty state -->
        <div
          v-else-if="!events || events.length === 0"
          class="text-center py-24 text-on-surface-variant"
        >
          <span class="material-symbols-outlined text-6xl opacity-20 block mb-4"
            >event_note</span
          >
          <p class="text-lg font-medium mb-4">
            No hi ha esdeveniments disponibles.
          </p>
          <NuxtLink
            v-if="authStore.isAdmin"
            to="/admin"
            class="px-6 py-3 rounded-xl border border-primary/30 text-primary font-bold hover:bg-primary/10 transition-colors inline-block"
          >
            Crear un event al panel d'administració
          </NuxtLink>
        </div>

        <!-- Events grid -->
        <div
          v-else
          class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8"
        >
          <NuxtLink
            v-for="(event, index) in events"
            :key="event.id"
            :to="`/event/${event.id}`"
            class="group relative bg-surface-container-low rounded-[2rem] overflow-hidden flex flex-col transition-all duration-500 hover:-translate-y-2 border border-outline-variant/10"
          >
            <div class="h-64 relative overflow-hidden">
              <img
                class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                :alt="event.title"
                :src="cardImages[index % cardImages.length]"
              />
              <div
                class="absolute inset-0 bg-gradient-to-t from-surface-container-low via-transparent to-transparent"
              ></div>
              <!-- Seats count badge -->
              <div class="absolute top-4 left-4 flex gap-2">
                <span
                  v-if="event.seats_count > 0"
                  class="signature-pulse px-4 py-1 rounded-full text-[10px] font-black text-on-secondary-fixed uppercase tracking-widest"
                  >{{ event.seats_count }} SEIENTS</span
                >
              </div>
              <!-- Zone pills -->
              <div
                v-if="event.zones?.length"
                class="absolute bottom-4 left-4 flex gap-1.5"
              >
                <div
                  v-for="zone in event.zones.slice(0, 2)"
                  :key="zone.name"
                  class="px-3 py-1 rounded-lg font-bold text-[10px] uppercase tracking-widest"
                  :style="{
                    backgroundColor: zone.color + '33',
                    color: zone.color,
                    border: '1px solid ' + zone.color + '55',
                  }"
                >
                  {{ zone.name }}
                </div>
              </div>
            </div>
            <div class="p-8 flex-1 flex flex-col">
              <div class="flex justify-between items-start mb-4">
                <h3
                  class="font-headline text-2xl font-bold text-white group-hover:text-primary transition-colors line-clamp-1"
                >
                  {{ event.title }}
                </h3>
                <div class="text-right ml-3 flex-shrink-0">
                  <p
                    class="text-zinc-500 text-[10px] font-bold uppercase tracking-widest"
                  >
                    Des de
                  </p>
                  <p class="text-primary font-bold text-lg">
                    {{ minPrice(event) }}
                  </p>
                </div>
              </div>
              <div class="space-y-2 mb-8 flex-1">
                <div class="flex items-center gap-2 text-zinc-400 text-sm">
                  <span class="material-symbols-outlined text-xs">event</span>
                  <span class="font-medium">{{
                    formatEventDate(event.date)
                  }}</span>
                </div>
                <div
                  v-if="event.venue"
                  class="flex items-center gap-2 text-zinc-400 text-sm"
                >
                  <span class="material-symbols-outlined text-xs">stadium</span>
                  <span class="font-medium">{{ event.venue }}</span>
                </div>
              </div>
              <span
                class="w-full py-4 bg-surface-container-highest border border-primary/20 rounded-full text-primary font-bold hover:bg-primary hover:text-on-primary transition-all flex items-center justify-center gap-2"
              >
                Comprar Entrades
              </span>
            </div>
          </NuxtLink>
        </div>
      </section>
    </main>

    <!-- Footer -->
    <footer class="w-full py-16 px-6 border-t border-white/5 bg-zinc-950">
      <div
        class="container mx-auto flex flex-col md:flex-row justify-between items-center gap-12"
      >
        <div
          class="text-zinc-600 text-[10px] tracking-[0.3em] uppercase font-bold"
        >
          © 2026 TICKETMONSTER. ENGINEERED FOR ENCORE.
        </div>
      </div>
    </footer>

    <!-- BottomNavBar (Mobile Only) -->
    <nav
      class="md:hidden fixed bottom-0 left-0 w-full z-50 flex justify-around items-center h-20 bg-zinc-950/90 backdrop-blur-2xl rounded-t-3xl border-t border-white/10 shadow-[0_-10px_40px_rgba(0,221,221,0.1)]"
    >
      <button
        class="flex flex-col items-center justify-center text-cyan-400 drop-shadow-[0_0_8px_rgba(0,221,221,0.6)] active:scale-110 duration-300"
      >
        <span class="material-symbols-outlined">explore</span>
        <span class="font-body text-[10px] uppercase tracking-widest mt-1"
          >Explorar</span
        >
      </button>
      <NuxtLink
        to="/my-tickets"
        class="flex flex-col items-center justify-center text-zinc-500 active:bg-white/5 transition-all active:scale-110 duration-300"
      >
        <span class="material-symbols-outlined">person</span>
        <span class="font-body text-[10px] uppercase tracking-widest mt-1"
          >Entrades</span
        >
      </NuxtLink>
    </nav>
  </div>
</template>

<script setup>
useHead({
  title: "TICKETMONSTER | Esdeveniments en Directe",
  meta: [
    {
      name: "description",
      content:
        "Compra entrades per als concerts i esdeveniments més emocionants. TICKETMONSTER — la teva destinació premium per a l'entreteniment en viu.",
    },
  ],
  link: [
    {
      rel: "stylesheet",
      href: "https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Manrope:wght@300;400;500;600;700;800&display=swap",
    },
    {
      rel: "stylesheet",
      href: "https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap",
    },
  ],
  htmlAttrs: { class: "dark", lang: "ca" },
});

const config = useRuntimeConfig();
const authStore = useAuthStore();
const router = useRouter();

const handleLogout = async () => {
  await authStore.logout();
  router.push("/login");
};

// Fetch real events from API — server:false forces browser-side fetch
// (inside Docker, the frontend container can't reach localhost:8000 via SSR)
const { data: events, pending } = useFetch(
  () => `${config.public.apiUrl}/api/events`,
  { server: false, default: () => [] },
);

const featuredEvent = computed(() => events.value?.[0] || null);

// Cycle through concert images for cards
const cardImages = [
  "https://lh3.googleusercontent.com/aida-public/AB6AXuB-RLxr5rnCQ2jtTgmWsi8RMc1I3UHsZjXplhzwpZjeTajIRSeOPP4mxj-Q3gGH9tDWRa-cdk5hEp2IPv1zNwMwTHx7VlcBZrvdNG-2_mgsB9breqVORnO1Tz40O44O1g8e3tPoaknl0DQfjdXFZPiSPcixfuLK83NlZnRpnjyFaJwzOPd8Z731hh2iLRnOj7ozddfAboP58o-jC0xdKp640qXVIBUyXIWVE7lXfG25YtnxxmbKvD_wHRr23xXWVf-WgHFO5ExfjyA",
  "https://lh3.googleusercontent.com/aida-public/AB6AXuA-3vWJ5LIzyGoNWPS28sKAsmEHFS2tXs2hDTCu87y7QeHH7j2CSC_glYU5W9CX_yHqlN2wGpH6bH8fNCuKerivWDTiFo8G1FgFvI3V93qxB8fHHea7q1Ud-zbQYVwIQp1Jvme-89h_tg1mQnihb2J8nWj8vrq5lUrpoHHSLXsMf8t2va7UDZbxLdLltezDmIxRCyqkVgXxdQvvxkHL3v0IieKQOsedpoUl9ULzhDDqfo93zJ-87wiYLrb5vskJxwULCGaBfgwtEnA",
  "https://lh3.googleusercontent.com/aida-public/AB6AXuA3_sBEv_UPvC8pa2rTpnKD6tu68VnmRDsCfm9C8UEQOnUVyQYyH3xsNldmIvzhQFcJKssHqTm1PPiRurtZpYs1_D5Xx1HngEU4Yd8QbTElyE1zvJ9FvSwEbxuhISHBCnVvuX1BlxMeoG_mcFl9TErRwvG7AeT9RGWYUoBZL8AAjSNDz_074iO5ra1nYA-u1Y0MayDo3SMpMNPpDeiiot33A-TzXtaG7AcKZqHKwj1dXudg0DWDF9l2qd5ttp7fbkC--YX3nUsZV60",
  "https://lh3.googleusercontent.com/aida-public/AB6AXuA8hpNkwm1PcuUuIgJxfpzKypnIBSXUVTFU6ZBB8gnG6TIMVYIt2coX5feoiKgjmLSIdLV49OuzZCxWClFC5wLKUZ3PSM4RfjPbgcCI-YQD76qjQDq6qYkciBnW4jNJyyeFcM07yzxK7-VT_EV1PwWFzVzf79XTKnQa16oFwSB2VvqwBrxUQxLG0vMIa8Gf5oAqs3_OJqbM0fspJW7iTjsZ13krYjX0KF_aBQcUYx-6KJvmTx4ecTzRDRw6XN_fo2vccUwdNJlTvDs",
];

const formatEventDate = (dateStr) => {
  if (!dateStr) return "";
  return new Date(dateStr).toLocaleString("ca-ES", {
    day: "2-digit",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
};

const minPrice = (event) => {
  if (event.zones?.length) {
    const prices = event.zones.map((z) => z.price).filter((p) => p != null);
    if (prices.length) return `€${Math.min(...prices).toFixed(2)}`;
  }
  return "Gratuït";
};

const scrollToEvents = () => {
  document.getElementById("events")?.scrollIntoView({ behavior: "smooth" });
};
</script>
