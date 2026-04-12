<template>
  <div
    class="min-h-screen bg-background text-on-background font-body selection:bg-primary/30"
  >
    <!-- Background glow blobs -->
    <div
      class="fixed inset-0 -z-10 pointer-events-none overflow-hidden blur-[100px] opacity-20"
    >
      <div
        class="absolute top-1/4 left-1/4 w-[500px] h-[500px] bg-primary rounded-full mix-blend-screen opacity-30"
      ></div>
      <div
        class="absolute bottom-1/4 right-1/4 w-[600px] h-[600px] bg-tertiary rounded-full mix-blend-screen opacity-20"
      ></div>
    </div>

    <!-- Top Nav -->
    <header
      class="fixed top-0 w-full z-50 bg-background/80 backdrop-blur-xl border-b border-white/5 flex items-center px-6 h-16 gap-4"
    >
      <NuxtLink
        :to="'/event/' + eventId"
        class="flex items-center gap-2 text-on-surface-variant hover:text-on-surface transition-colors group"
      >
        <span
          class="material-symbols-outlined group-hover:-translate-x-1 transition-transform"
          >arrow_back</span
        >
        <span class="font-headline text-sm uppercase tracking-wider"
          >Tornar al plànol</span
        >
      </NuxtLink>
      <div class="flex-1"></div>
      <div
        class="text-xl font-black italic tracking-tighter text-transparent bg-clip-text bg-gradient-to-r from-fuchsia-500 via-purple-500 to-cyan-400 font-headline"
      >
        TICKETMONSTER
      </div>
    </header>

    <!-- Expired state -->
    <div
      v-if="isExpired"
      class="flex flex-col items-center justify-center min-h-screen gap-6 px-6"
    >
      <span class="material-symbols-outlined text-error text-7xl"
        >timer_off</span
      >
      <h1 class="font-headline text-3xl font-bold text-on-surface">
        Reserva Caducada
      </h1>
      <p class="text-on-surface-variant text-center max-w-md">
        La teva reserva de seients ha esgotat el temps de límit (10 minuts). Si us plau, torna i selecciona els teus seients de nou.
      </p>
      <NuxtLink
        :to="'/event/' + eventId"
        class="px-8 py-4 signature-pulse text-on-secondary-fixed font-bold rounded-xl uppercase tracking-widest hover:scale-105 transition-transform"
      >
        Tornar a Escollir Seients
      </NuxtLink>
    </div>

    <!-- Success state -->
    <div
      v-else-if="isSuccess"
      class="flex flex-col items-center justify-center min-h-screen gap-6 px-6"
    >
      <div
        class="w-24 h-24 rounded-full signature-pulse flex items-center justify-center shadow-[0_0_40px_rgba(255,171,243,0.4)]"
      >
        <span class="material-symbols-outlined text-on-secondary-fixed text-5xl"
          >check_circle</span
        >
      </div>
      <h1
        class="font-headline text-4xl font-bold text-on-surface text-center mt-2"
      >
        Compra Completada!
      </h1>
      <p class="text-on-surface-variant text-center max-w-md">
        Les teves entrades s'han confirmat correctament. Consulta el teu correu electrònic. Ens veiem al concert! 🎶
      </p>
      <div
        class="glass-panel rounded-2xl p-6 border border-white/10 w-full max-w-sm text-center"
      >
        <p
          class="text-on-surface-variant text-xs uppercase tracking-wider mb-1"
        >
          Núm Confirmació
        </p>
        <p class="font-headline font-bold text-xl text-primary">
          {{ confirmationCode }}
        </p>
      </div>
      <NuxtLink
        to="/"
        class="px-8 py-4 bg-surface-container-high border border-primary/20 text-primary font-bold rounded-xl uppercase tracking-widest hover:bg-primary/10 transition-all"
      >
        Tornar als Esdeveniments
      </NuxtLink>
    </div>

    <!-- Main checkout flow -->
    <main
      v-else
      class="pt-24 pb-12 px-4 md:px-6 min-h-screen flex flex-col xl:flex-row gap-8 max-w-7xl mx-auto"
    >
      <!-- Left: Form -->
      <section class="flex-1">
        <h1 class="font-headline text-3xl font-bold text-on-surface mb-2">
          Completa la Teva Comanda
        </h1>
        <p class="text-on-surface-variant mb-8">
          Introdueix les teves dades per assegurar les entrades.
        </p>

        <form @submit.prevent="submitCheckout" class="space-y-6">
          <!-- Personal Details -->
          <div
            class="glass-panel rounded-2xl p-6 border border-white/10 space-y-5"
          >
            <h2
              class="font-headline text-lg font-bold text-on-surface flex items-center gap-2"
            >
              <span class="material-symbols-outlined text-primary">person</span>
              Dades Personals
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label
                  class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2"
                  >Nom *</label
                >
                <input
                  v-model="form.firstName"
                  type="text"
                  required
                  placeholder="Alex"
                  class="w-full bg-surface-container-lowest border border-outline-variant/30 rounded-xl px-4 py-3 text-on-surface placeholder:text-outline focus:outline-none focus:border-primary/60 focus:ring-1 focus:ring-primary/40 transition-all"
                />
              </div>
              <div>
                <label
                  class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2"
                  >Cognoms *</label
                >
                <input
                  v-model="form.lastName"
                  type="text"
                  required
                  placeholder="García"
                  class="w-full bg-surface-container-lowest border border-outline-variant/30 rounded-xl px-4 py-3 text-on-surface placeholder:text-outline focus:outline-none focus:border-primary/60 focus:ring-1 focus:ring-primary/40 transition-all"
                />
              </div>
            </div>

            <div>
              <label
                class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2"
                >Correu Electrònic *</label
              >
              <input
                v-model="form.email"
                type="email"
                required
                placeholder="alex@exemple.cat"
                class="w-full bg-surface-container-lowest border border-outline-variant/30 rounded-xl px-4 py-3 text-on-surface placeholder:text-outline focus:outline-none focus:border-primary/60 focus:ring-1 focus:ring-primary/40 transition-all"
              />
              <p class="text-xs text-on-surface-variant mt-1">
                Les entrades s'enviaran a aquesta adreça electrònica.
              </p>
            </div>

            <div>
              <label
                class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2"
                >Telèfon (opcional)</label
              >
              <input
                v-model="form.phone"
                type="tel"
                placeholder="+34 600 000 000"
                class="w-full bg-surface-container-lowest border border-outline-variant/30 rounded-xl px-4 py-3 text-on-surface placeholder:text-outline focus:outline-none focus:border-primary/60 focus:ring-1 focus:ring-primary/40 transition-all"
              />
            </div>
          </div>

          <!-- Payment Details -->
          <div
            class="glass-panel rounded-2xl p-6 border border-white/10 space-y-5"
          >
            <h2
              class="font-headline text-lg font-bold text-on-surface flex items-center gap-2"
            >
              <span class="material-symbols-outlined text-tertiary"
                >credit_card</span
              >
              Dades de Pagament
            </h2>

            <div>
              <label
                class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2"
                >Número de Targeta *</label
              >
              <input
                v-model="form.cardNumber"
                type="text"
                required
                maxlength="19"
                placeholder="1234 5678 9012 3456"
                @input="formatCard"
                class="w-full bg-surface-container-lowest border border-outline-variant/30 rounded-xl px-4 py-3 text-on-surface placeholder:text-outline focus:outline-none focus:border-tertiary/60 focus:ring-1 focus:ring-tertiary/40 transition-all font-mono tracking-widest"
              />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label
                  class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2"
                  >Caducitat *</label
                >
                <input
                  v-model="form.expiry"
                  type="text"
                  required
                  maxlength="5"
                  placeholder="MM/YY"
                  @input="formatExpiry"
                  class="w-full bg-surface-container-lowest border border-outline-variant/30 rounded-xl px-4 py-3 text-on-surface placeholder:text-outline focus:outline-none focus:border-tertiary/60 focus:ring-1 focus:ring-tertiary/40 transition-all font-mono"
                />
              </div>
              <div>
                <label
                  class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2"
                  >CVV *</label
                >
                <input
                  v-model="form.cvv"
                  type="password"
                  required
                  maxlength="4"
                  placeholder="•••"
                  class="w-full bg-surface-container-lowest border border-outline-variant/30 rounded-xl px-4 py-3 text-on-surface placeholder:text-outline focus:outline-none focus:border-tertiary/60 focus:ring-1 focus:ring-tertiary/40 transition-all font-mono"
                />
              </div>
            </div>

            <div>
              <label
                class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2"
                >Titular de la Targeta *</label
              >
              <input
                v-model="form.cardName"
                type="text"
                required
                placeholder="ALEX GARCÍA"
                class="w-full bg-surface-container-lowest border border-outline-variant/30 rounded-xl px-4 py-3 text-on-surface placeholder:text-outline focus:outline-none focus:border-tertiary/60 focus:ring-1 focus:ring-tertiary/40 transition-all uppercase tracking-wider"
              />
            </div>
          </div>

          <!-- Terms -->
          <div class="flex items-start gap-3">
            <input
              id="terms"
              v-model="form.acceptTerms"
              type="checkbox"
              required
              class="mt-1 w-4 h-4 rounded border-outline-variant bg-surface-container-lowest accent-primary"
            />
            <label for="terms" class="text-sm text-on-surface-variant">
              Accepto les
              <a href="#" class="text-primary hover:underline"
                >Condicions de Servei</a
              >
              i la
              <a href="#" class="text-primary hover:underline">Política de Devolucions</a
              >. Entenc que les entrades no són transferibles.
            </label>
          </div>

          <!-- Submit button (mobile only) -->
          <button
            type="submit"
            :disabled="isSubmitting"
            class="xl:hidden w-full py-5 rounded-xl signature-pulse text-white font-headline font-bold text-lg uppercase tracking-widest shadow-[0_0_30px_rgba(255,171,243,0.25)] hover:scale-[1.02] active:scale-95 transition-all disabled:opacity-60 disabled:cursor-not-allowed"
          >
            {{ isSubmitting ? "Processant..." : `Pagar €${totalFromQuery}` }}
          </button>
        </form>
      </section>

      <!-- Right: Order Summary + Timer -->
      <aside class="w-full xl:w-[420px] space-y-6">
        <!-- Timer -->
        <div
          class="glass-panel rounded-2xl p-5 border flex items-center gap-4"
          :class="
            timeLeft < 120
              ? 'border-error/40 shadow-[0_0_20px_rgba(255,180,171,0.15)]'
              : 'border-white/10'
          "
        >
          <span
            class="material-symbols-outlined text-3xl"
            :class="
              timeLeft < 120 ? 'text-error animate-pulse' : 'text-tertiary'
            "
            >timer</span
          >
          <div class="flex-1">
            <p
              class="text-xs font-bold uppercase tracking-wider"
              :class="timeLeft < 120 ? 'text-error' : 'text-on-surface-variant'"
            >
              {{
                timeLeft < 120
                  ? "⚠ Caduca aviat!"
                  : "Temps restant per comprar"
              }}
            </p>
            <p
              class="font-headline font-bold text-3xl tracking-tight"
              :class="timeLeft < 120 ? 'text-error' : 'text-on-surface'"
            >
              {{ formattedTime }}
            </p>
          </div>
        </div>

        <!-- Order Summary -->
        <div
          class="glass-panel rounded-2xl border border-white/10 overflow-hidden"
        >
          <div class="px-6 py-5 border-b border-white/5">
            <h2 class="font-headline text-lg font-bold text-on-surface">
              Resum de la Comanda
            </h2>
            <p class="text-xs text-on-surface-variant mt-1">
              {{ eventTitle || 'CONCERT' }} • {{ eventVenue || 'RECINTE' }}
            </p>
          </div>

          <!-- Seat list -->
          <div class="max-h-60 overflow-y-auto">
            <div
              v-for="seatId in seatIds"
              :key="seatId"
              class="flex items-center justify-between px-6 py-4 border-b border-white/5 last:border-0 hover:bg-white/5 transition-colors"
            >
              <div class="flex items-center gap-3">
                <div
                  class="w-8 h-8 rounded-lg bg-primary/20 border border-primary/30 flex items-center justify-center"
                >
                  <span class="material-symbols-outlined text-primary text-sm"
                    >event_seat</span
                  >
                </div>
                <div>
                  <p class="font-headline text-sm font-bold text-on-surface">
                    Seient ID: #{{ seatId }}
                  </p>
                  <p class="text-xs text-on-surface-variant">
                    Entrada Electrònica
                  </p>
                </div>
              </div>
              <span class="font-headline font-bold text-on-surface"
                >€{{ pricePerSeat.toFixed(2) }}</span
              >
            </div>
          </div>

          <!-- Pricing breakdown -->
          <div class="px-6 py-5 space-y-3 border-t border-white/5">
            <div class="flex justify-between text-sm text-on-surface-variant">
              <span>Subtotal ({{ seatIds.length }} entrades)</span>
              <span>€{{ subtotal.toFixed(2) }}</span>
            </div>
            <div class="flex justify-between text-sm text-on-surface-variant">
              <span>Despeses de gestió</span>
              <span>€{{ serviceFee.toFixed(2) }}</span>
            </div>
            <div
              class="flex justify-between font-headline font-bold text-lg text-on-surface pt-3 border-t border-white/5"
            >
              <span>Total</span>
              <span class="text-primary">€{{ grandTotal.toFixed(2) }}</span>
            </div>
          </div>
        </div>

        <!-- Security badges -->
        <div
          class="flex items-center justify-center gap-6 text-on-surface-variant"
        >
          <div class="flex items-center gap-1.5 text-xs">
            <span class="material-symbols-outlined text-sm text-tertiary"
              >lock</span
            >
            Xifrat SSL
          </div>
          <div class="flex items-center gap-1.5 text-xs">
            <span class="material-symbols-outlined text-sm text-tertiary"
              >shield</span
            >
            Protecció Antifrau
          </div>
          <div class="flex items-center gap-1.5 text-xs">
            <span class="material-symbols-outlined text-sm text-tertiary"
              >verified</span
            >
            Venedor Verificat
          </div>
        </div>

        <!-- Pay button (desktop) -->
        <button
          @click="submitCheckout"
          :disabled="isSubmitting"
          class="hidden xl:block w-full py-5 rounded-xl signature-pulse text-white font-headline font-bold text-lg uppercase tracking-widest shadow-[0_0_30px_rgba(255,171,243,0.25)] hover:scale-[1.02] active:scale-95 transition-all disabled:opacity-60 disabled:cursor-not-allowed"
        >
          {{ isSubmitting ? "Processant..." : `Pagar €${grandTotal.toFixed(2)}` }}
        </button>

        <p
          class="text-center text-[10px] text-outline font-body uppercase tracking-tighter opacity-60"
        >
          En completar la compra acceptes els nostres termes. Les entrades no són reemborsables.
        </p>
      </aside>
    </main>

    <!-- Custom Error Modal -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="transform scale-95 opacity-0"
        enter-to-class="transform scale-100 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="transform scale-100 opacity-100"
        leave-to-class="transform scale-95 opacity-0"
      >
        <div v-if="errorAlert" class="fixed inset-0 z-[200] flex items-center justify-center p-4">
          <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" @click="errorAlert = null"></div>
          <div class="relative w-full max-w-sm bg-surface-container rounded-2xl border border-error/50 shadow-2xl z-10 p-7 text-center">
            <div class="w-16 h-16 rounded-full bg-error/15 border border-error/30 flex items-center justify-center mx-auto mb-5">
              <span class="material-symbols-outlined text-error text-3xl">error</span>
            </div>
            <h3 class="font-headline font-bold text-on-surface text-lg mb-2">Atenció</h3>
            <p class="text-on-surface-variant text-sm mb-7">{{ errorAlert }}</p>
            <button @click="errorAlert = null" class="w-full py-3 rounded-xl bg-error/20 border border-error/30 text-error font-bold hover:bg-error/30 transition-colors text-sm uppercase tracking-wider">
              D'acord
            </button>
          </div>
        </div>
      </Transition>
    </Teleport>
    
    <!-- Assistència Directa (WebRTC) Widget -->
    <SupportWidget />

  </div>
</template>

<script setup>
import SupportWidget from "~/components/SupportWidget.vue";

useHead({
  title: "Checkout | TICKETMONSTER",
  htmlAttrs: { class: "dark", lang: "ca" },
  link: [
    { rel: "preconnect", href: "https://fonts.googleapis.com" },
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

const route = useRoute();
const config = useRuntimeConfig();
const authStore = useAuthStore();

// Parse query params sent from SeatMap
const seatIds = computed(() => {
  const raw = route.query.seats;
  if (!raw) return [];
  return raw
    .split(",")
    .map(Number)
    .filter((n) => !isNaN(n) && n > 0);
});

const sessionId = computed(() => route.query.session || "");
const totalFromQuery = computed(() => route.query.total || "0");
const eventId = computed(() => route.query.eventId || "1");
const eventTitle = computed(() => route.query.eventTitle || "");
const eventVenue = computed(() => route.query.eventVenue || "");
const pricePerSeat = 50;
const subtotal = computed(() => seatIds.value.length * pricePerSeat);
const serviceFee = computed(
  () => Math.round(subtotal.value * 0.08 * 100) / 100,
);
const grandTotal = computed(() => subtotal.value + serviceFee.value);

// 10-minute countdown (600 seconds)
const TIMEOUT_SECONDS = 600;
const timeLeft = ref(TIMEOUT_SECONDS);
const isExpired = ref(false);
let countdownInterval = null;

const formattedTime = computed(() => {
  const m = Math.floor(timeLeft.value / 60)
    .toString()
    .padStart(2, "0");
  const s = (timeLeft.value % 60).toString().padStart(2, "0");
  return `${m}:${s}`;
});

onMounted(() => {
  countdownInterval = setInterval(() => {
    timeLeft.value--;
    if (timeLeft.value <= 0) {
      clearInterval(countdownInterval);
      isExpired.value = true;
    }
  }, 1000);
});

onUnmounted(async () => {
  if (countdownInterval) clearInterval(countdownInterval);

  // If the component is closed / user navigates back without success, release the seats dynamically.
  if (!isSuccess.value && seatIds.value.length > 0 && sessionId.value) {
    try {
      for (const seatId of seatIds.value) {
        // Enviar silenciosamente la solicitud para liberar la sala en background
        $fetch(`${config.public.apiUrl}/api/seats/release`, {
          method: "POST",
          body: { seat_id: seatId, session_id: sessionId.value },
        }).catch(() => {});
      }
    } catch (e) {
      console.warn("No s'han pogut des-reservar els seients: ", e);
    }
  }
});

// Form state
const form = reactive({
  firstName: "",
  lastName: "",
  email: "",
  phone: "",
  cardNumber: "",
  expiry: "",
  cvv: "",
  cardName: "",
  acceptTerms: false,
});

// Pre-fill form for logged-in users
onMounted(() => {
  if (authStore.user) {
    const parts = (authStore.user.name || "").trim().split(" ");
    form.firstName = parts[0] || "";
    form.lastName = parts.slice(1).join(" ") || "";
    form.email = authStore.user.email || "";
  }
});

const isSubmitting = ref(false);
const isSuccess = ref(false);
const confirmationCode = ref("");
const errorAlert = ref(null);

const formatCard = (e) => {
  let val = e.target.value.replace(/\D/g, "").substring(0, 16);
  form.cardNumber = val.replace(/(.{4})/g, "$1 ").trim();
};

const formatExpiry = (e) => {
  let val = e.target.value.replace(/\D/g, "").substring(0, 4);
  if (val.length >= 2) val = val.substring(0, 2) + "/" + val.substring(2);
  form.expiry = val;
};

const submitCheckout = async () => {
  if (isSubmitting.value || isExpired.value) return;
  if (
    !form.firstName ||
    !form.lastName ||
    !form.email ||
    !form.cardNumber ||
    !form.expiry ||
    !form.cvv ||
    !form.cardName
  ) {
    errorAlert.value = "Si us plau, emplena tots els camps obligatoris abans de continuar.";
    return;
  }
  if (!form.acceptTerms) {
    errorAlert.value = "Has d'acceptar els Termes de Servei i la Política de Devolucions.";
    return;
  }

  isSubmitting.value = true;

  try {
    const response = await $fetch(
      `${config.public.apiUrl}/api/seats/checkout`,
      {
        method: "POST",
        body: {
          seat_ids: seatIds.value,
          session_id: sessionId.value,
          name: `${form.firstName} ${form.lastName}`,
          email: form.email,
        },
      },
    );

    if (response.success) {
      clearInterval(countdownInterval);
      confirmationCode.value =
        "ES-" + Math.random().toString(36).toUpperCase().substring(2, 10);
      isSuccess.value = true;
    }
  } catch (e) {
    const msg = e.response?._data?.error || e.message;
    errorAlert.value = "El pagament ha fallat: " + msg;
  } finally {
    isSubmitting.value = false;
  }
};
</script>
