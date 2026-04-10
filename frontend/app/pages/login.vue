<template>
  <div class="min-h-screen bg-background flex items-center justify-center px-4 relative overflow-hidden">

    <!-- Background glows -->
    <div class="absolute inset-0 pointer-events-none">
      <div class="absolute top-1/4 left-1/4 w-[600px] h-[600px] bg-primary/10 rounded-full blur-[120px]"></div>
      <div class="absolute bottom-1/4 right-1/4 w-[500px] h-[500px] bg-tertiary/10 rounded-full blur-[120px]"></div>
    </div>

    <div class="relative w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-8">
        <NuxtLink to="/" class="inline-block">
          <div class="font-headline text-3xl font-black tracking-tighter bg-gradient-to-r from-primary via-fuchsia-400 to-tertiary bg-clip-text text-transparent uppercase">
            TICKETMONSTER
          </div>
        </NuxtLink>
        <p class="text-on-surface-variant text-sm mt-2">Inicia sessió al teu compte</p>
      </div>

      <!-- Card -->
      <div class="glass-panel rounded-3xl border border-white/10 p-8 shadow-2xl">
        <!-- Tabs -->
        <div class="flex bg-white/5 rounded-xl p-1 mb-8">
          <button
            @click="mode = 'login'"
            :class="['flex-1 py-2 rounded-lg text-sm font-headline font-bold transition-all',
              mode === 'login' ? 'bg-primary/20 text-primary' : 'text-on-surface-variant hover:text-on-surface']"
          >
            Iniciar Sessió
          </button>
          <button
            @click="mode = 'register'"
            :class="['flex-1 py-2 rounded-lg text-sm font-headline font-bold transition-all',
              mode === 'register' ? 'bg-primary/20 text-primary' : 'text-on-surface-variant hover:text-on-surface']"
          >
            Registrar-se
          </button>
        </div>

        <!-- Error toast -->
        <Transition name="fade">
          <div v-if="errorMsg" class="flex items-center gap-3 p-4 bg-error/10 border border-error/20 rounded-xl mb-6">
            <span class="material-symbols-outlined text-error text-base">error</span>
            <p class="text-sm text-error flex-1">{{ errorMsg }}</p>
            <button @click="errorMsg = ''" class="material-symbols-outlined text-error/60 text-base hover:text-error">close</button>
          </div>
        </Transition>

        <!-- LOGIN FORM -->
        <form v-if="mode === 'login'" @submit.prevent="handleLogin" class="space-y-5">
          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">Correu electrònic</label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-outline text-base">mail</span>
              <input
                v-model="loginForm.email"
                type="email"
                required
                autocomplete="email"
                placeholder="admin@ticketmonster.com"
                class="w-full bg-surface-container-lowest border border-outline-variant/30 rounded-xl pl-11 pr-4 py-3 text-on-surface placeholder:text-outline focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/30 transition-all"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">Contrasenya</label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-outline text-base">lock</span>
              <input
                v-model="loginForm.password"
                :type="showPassword ? 'text' : 'password'"
                required
                autocomplete="current-password"
                placeholder="••••••••"
                class="w-full bg-surface-container-lowest border border-outline-variant/30 rounded-xl pl-11 pr-12 py-3 text-on-surface placeholder:text-outline focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/30 transition-all"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-outline hover:text-on-surface transition-colors text-base"
              >{{ showPassword ? 'visibility_off' : 'visibility' }}</button>
            </div>
          </div>

          <button
            type="submit"
            :disabled="loading"
            class="w-full py-3.5 signature-pulse text-white font-headline font-bold uppercase tracking-wider rounded-xl hover:scale-[1.02] transition-all disabled:opacity-60 disabled:cursor-not-allowed disabled:scale-100 flex items-center justify-center gap-2"
          >
            <span v-if="loading" class="material-symbols-outlined animate-spin text-base">progress_activity</span>
            {{ loading ? 'Iniciant sessió...' : 'Iniciar Sessió' }}
          </button>
        </form>

        <!-- REGISTER FORM -->
        <form v-else @submit.prevent="handleRegister" class="space-y-4">
          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">Nom complet</label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-outline text-base">person</span>
              <input
                v-model="registerForm.name"
                type="text"
                required
                placeholder="Joan Garcia"
                class="w-full bg-surface-container-lowest border border-outline-variant/30 rounded-xl pl-11 pr-4 py-3 text-on-surface placeholder:text-outline focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/30 transition-all"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">Correu electrònic</label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-outline text-base">mail</span>
              <input
                v-model="registerForm.email"
                type="email"
                required
                placeholder="exemple@correu.com"
                class="w-full bg-surface-container-lowest border border-outline-variant/30 rounded-xl pl-11 pr-4 py-3 text-on-surface placeholder:text-outline focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/30 transition-all"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">Contrasenya</label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-outline text-base">lock</span>
              <input
                v-model="registerForm.password"
                :type="showPassword ? 'text' : 'password'"
                required
                minlength="8"
                placeholder="Mínim 8 caràcters"
                class="w-full bg-surface-container-lowest border border-outline-variant/30 rounded-xl pl-11 pr-12 py-3 text-on-surface placeholder:text-outline focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/30 transition-all"
              />
              <button type="button" @click="showPassword = !showPassword" class="absolute right-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-outline hover:text-on-surface transition-colors text-base">
                {{ showPassword ? 'visibility_off' : 'visibility' }}
              </button>
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">Confirmar contrasenya</label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-outline text-base">lock_reset</span>
              <input
                v-model="registerForm.password_confirmation"
                :type="showPassword ? 'text' : 'password'"
                required
                placeholder="Repeteix la contrasenya"
                class="w-full bg-surface-container-lowest border border-outline-variant/30 rounded-xl pl-11 pr-4 py-3 text-on-surface placeholder:text-outline focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/30 transition-all"
              />
            </div>
          </div>

          <button
            type="submit"
            :disabled="loading"
            class="w-full py-3.5 signature-pulse text-white font-headline font-bold uppercase tracking-wider rounded-xl hover:scale-[1.02] transition-all disabled:opacity-60 disabled:cursor-not-allowed disabled:scale-100 flex items-center justify-center gap-2"
          >
            <span v-if="loading" class="material-symbols-outlined animate-spin text-base">progress_activity</span>
            {{ loading ? 'Creant compte...' : 'Crear compte' }}
          </button>
        </form>

        <!-- Back to site -->
        <div class="mt-6 text-center">
          <NuxtLink to="/" class="text-xs text-on-surface-variant hover:text-on-surface transition-colors flex items-center justify-center gap-1">
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            Tornar a la pàgina principal
          </NuxtLink>
        </div>
      </div>

      <!-- Admin hint -->
      <p class="text-center text-xs text-on-surface-variant/40 mt-4">
        Admin per defecte: admin@ticketmonster.com / admin1234
      </p>
    </div>
  </div>
</template>

<script setup>
useHead({
  title: 'Iniciar Sessió | TICKETMONSTER',
  htmlAttrs: { class: 'dark', lang: 'ca' },
})

const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()

// If already logged in, redirect
if (authStore.isLoggedIn) {
  await navigateTo(authStore.isAdmin ? '/admin' : '/')
}

const mode = ref('login')
const loading = ref(false)
const errorMsg = ref('')
const showPassword = ref(false)

const loginForm = reactive({ email: '', password: '' })
const registerForm = reactive({ name: '', email: '', password: '', password_confirmation: '' })

const handleLogin = async () => {
  errorMsg.value = ''
  loading.value = true
  try {
    const data = await authStore.login(loginForm.email, loginForm.password)
    const redirect = route.query.redirect || (data.user.role === 'admin' ? '/admin' : '/')
    await router.push(redirect)
  } catch (e) {
    const errors = e.response?._data?.errors
    if (errors?.email) {
      errorMsg.value = errors.email[0]
    } else {
      errorMsg.value = e.response?._data?.message || 'Error iniciant sessió.'
    }
  } finally {
    loading.value = false
  }
}

const handleRegister = async () => {
  errorMsg.value = ''
  if (registerForm.password !== registerForm.password_confirmation) {
    errorMsg.value = 'Les contrasenyes no coincideixen.'
    return
  }
  loading.value = true
  try {
    await authStore.register(
      registerForm.name,
      registerForm.email,
      registerForm.password,
      registerForm.password_confirmation
    )
    await router.push('/')
  } catch (e) {
    const errors = e.response?._data?.errors
    if (errors) {
      errorMsg.value = Object.values(errors).flat()[0]
    } else {
      errorMsg.value = e.response?._data?.message || 'Error creant el compte.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
