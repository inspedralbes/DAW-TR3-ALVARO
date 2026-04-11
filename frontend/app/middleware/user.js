// middleware/user.js
// Protects routes that require any logged-in user (admin or regular)
export default defineNuxtRouteMiddleware(() => {
  const authStore = useAuthStore()

  if (!authStore.isLoggedIn) {
    return navigateTo('/login?redirect=/my-tickets')
  }
})
