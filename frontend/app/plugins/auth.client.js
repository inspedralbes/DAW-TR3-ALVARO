// plugins/auth.client.js
// On every client-side page load, restore user info from the stored token
export default defineNuxtPlugin(async () => {
  const authStore = useAuthStore()
  if (authStore.token && !authStore.user) {
    await authStore.fetchMe()
  }
})
