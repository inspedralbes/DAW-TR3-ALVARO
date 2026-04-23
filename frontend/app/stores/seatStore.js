import { defineStore } from 'pinia'

export const useSeatStore = defineStore('seatStore', {
  state: () => ({
    seats: [],
    loading: false,
    error: null,
    sessionId: ''
  }),

  actions: {
    async fetchSeats(eventId = null) {
      this.loading = true
      try {
        const config = useRuntimeConfig()
        const url = eventId ? `/api/seats?event_id=${eventId}` : `/api/seats`
        const data = await $fetch(`${config.public.apiUrl}${url}`)
        this.seats = data
      } catch (err) {
        this.error = err.message
      } finally {
        this.loading = false
      }
    },

    updateSeat(seatId, status) {
      const seat = this.seats.find(s => s.id === seatId)
      if (seat) {
        seat.status = status
      }
    },

    setSessionId(id) {
      this.sessionId = id
    }
  }
})
