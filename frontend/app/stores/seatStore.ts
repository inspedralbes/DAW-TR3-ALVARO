import { defineStore } from 'pinia'

interface Seat {
  id: number
  row: string
  number: number
  status: 'available' | 'reserved' | 'sold'
  price: number
}

export const useSeatStore = defineStore('seatStore', {
  state: () => ({
    seats: [] as Seat[],
    loading: false,
    error: null as string | null,
    sessionId: ''
  }),

  actions: {
    async fetchSeats() {
      this.loading = true
      try {
        const config = useRuntimeConfig()
        const data = await $fetch<Seat[]>(`${config.public.apiUrl}/api/seats`)
        this.seats = data
      } catch (err: any) {
        this.error = err.message
      } finally {
        this.loading = false
      }
    },

    updateSeat(seatId: number, status: Seat['status']) {
      const seat = this.seats.find(s => s.id === seatId)
      if (seat) {
        seat.status = status
      }
    },

    setSessionId(id: string) {
      this.sessionId = id
    }
  }
})
