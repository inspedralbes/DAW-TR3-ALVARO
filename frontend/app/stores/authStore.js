export const useAuthStore = defineStore('auth', () => {
  // Cookie is SSR-safe and persists across page reloads (7 days)
  const token = useCookie('auth_token', { maxAge: 60 * 60 * 24 * 7, sameSite: 'lax' })
  const user = ref(null)

  const isLoggedIn = computed(() => !!token.value)
  const isAdmin = computed(() => user.value?.role === 'admin')

  const config = useRuntimeConfig()
  const apiBase = () => config.public.apiUrl

  const authHeaders = () => ({
    Authorization: `Bearer ${token.value}`,
  })

  // ── Actions ─────────────────────────────────────────────────────────────

  const login = async (email, password) => {
    const data = await $fetch(`${apiBase()}/api/auth/login`, {
      method: 'POST',
      body: { email, password },
    })
    token.value = data.token
    user.value = data.user
    return data
  }

  const register = async (name, email, password, password_confirmation) => {
    const data = await $fetch(`${apiBase()}/api/auth/register`, {
      method: 'POST',
      body: { name, email, password, password_confirmation },
    })
    token.value = data.token
    user.value = data.user
    return data
  }

  const logout = async () => {
    try {
      await $fetch(`${apiBase()}/api/auth/logout`, {
        method: 'POST',
        headers: authHeaders(),
      })
    } catch (_) {
      // ignore errors on logout
    } finally {
      token.value = null
      user.value = null
    }
  }

  const fetchMe = async () => {
    if (!token.value) return
    try {
      const data = await $fetch(`${apiBase()}/api/auth/me`, {
        headers: authHeaders(),
      })
      user.value = data
    } catch (_) {
      // Token invalid — clear session
      token.value = null
      user.value = null
    }
  }

  const clear = () => {
    token.value = null
    user.value = null
  }

  return {
    token,
    user,
    isLoggedIn,
    isAdmin,
    authHeaders,
    login,
    register,
    logout,
    fetchMe,
    clear,
  }
})
