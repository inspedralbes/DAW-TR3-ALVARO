import { defineStore } from 'pinia'

export const useAdminStore = defineStore('adminStore', {
  state: () => ({
    stats: { available: 0, reserved: 0, sold: 0, total: 0 },
    connectedClients: 0,
    activeReservations: [],
    events: [],
    loading: false,
    statsLoading: false,
    error: null,
  }),

  getters: {
    occupancyPercent: (state) => {
      if (state.stats.total === 0) return 0
      return Math.round(((state.stats.sold + state.stats.reserved) / state.stats.total) * 100)
    },
    soldPercent: (state) => {
      if (state.stats.total === 0) return 0
      return Math.round((state.stats.sold / state.stats.total) * 100)
    },
    availablePercent: (state) => {
      if (state.stats.total === 0) return 0
      return Math.round((state.stats.available / state.stats.total) * 100)
    },
    reservedPercent: (state) => {
      if (state.stats.total === 0) return 0
      return Math.round((state.stats.reserved / state.stats.total) * 100)
    },
  },

  actions: {
    async fetchStats(eventId = null) {
      this.statsLoading = true
      try {
        const config = useRuntimeConfig()
        const authStore = useAuthStore()
        const query = eventId ? `?event_id=${eventId}` : ''
        const data = await $fetch(`${config.public.apiUrl}/api/admin/stats${query}`, {
          headers: authStore.authHeaders(),
        })
        this.stats = data.stats
        this.activeReservations = data.active_reservations
      } catch (err) {
        console.error('Admin stats error:', err)
        this.error = err.message
      } finally {
        this.statsLoading = false
      }
    },

    async fetchReports(eventId = null) {
      this.loading = true
      try {
        const config = useRuntimeConfig()
        const authStore = useAuthStore()
        const query = eventId ? `?event_id=${eventId}` : ''
        return await $fetch(`${config.public.apiUrl}/api/admin/reports${query}`, {
          headers: authStore.authHeaders(),
        })
      } catch (err) {
        console.error('Admin reports error:', err)
        this.error = err.message
        return null
      } finally {
        this.loading = false
      }
    },

    async fetchEvents() {
      this.loading = true
      try {
        const config = useRuntimeConfig()
        this.events = await $fetch(`${config.public.apiUrl}/api/events`)
      } catch (err) {
        this.error = err.message
      } finally {
        this.loading = false
      }
    },

    async createEvent(eventData) {
      const config = useRuntimeConfig()
      const authStore = useAuthStore()
      const data = await $fetch(`${config.public.apiUrl}/api/admin/events`, {
        method: 'POST',
        headers: authStore.authHeaders(),
        body: eventData,
      })
      await this.fetchEvents()
      return data
    },

    async updateEvent(id, eventData) {
      const config = useRuntimeConfig()
      const authStore = useAuthStore()
      const data = await $fetch(`${config.public.apiUrl}/api/admin/events/${id}`, {
        method: 'PUT',
        headers: authStore.authHeaders(),
        body: eventData,
      })
      await this.fetchEvents()
      return data
    },

    async deleteEvent(id) {
      const config = useRuntimeConfig()
      const authStore = useAuthStore()
      await $fetch(`${config.public.apiUrl}/api/admin/events/${id}`, {
        method: 'DELETE',
        headers: authStore.authHeaders(),
      })
      this.events = this.events.filter(e => e.id !== id)
    },

    async generateSeats(id) {
      const config = useRuntimeConfig()
      const authStore = useAuthStore()
      const data = await $fetch(`${config.public.apiUrl}/api/admin/events/${id}/generate-seats`, {
        method: 'POST',
        headers: authStore.authHeaders(),
      })
      await this.fetchEvents()
      return data
    },
  }
})
