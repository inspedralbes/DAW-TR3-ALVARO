<template>
  <div class="p-6 md:p-10 max-w-[1400px] mx-auto">

    <!-- Page header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-10">
      <div>
        <h1 class="font-headline text-3xl font-black tracking-tight text-on-surface">
          Gestió <span class="text-secondary italic">d'Esdeveniments</span>
        </h1>
        <p class="text-on-surface-variant text-sm mt-1">Crea, edita i gestiona events i els seus seients.</p>
      </div>
      <button
        @click="openCreate"
        class="flex items-center gap-2 px-5 py-3 rounded-xl signature-pulse text-white font-headline font-bold text-sm uppercase tracking-wider hover:scale-105 transition-transform"
      >
        <span class="material-symbols-outlined text-base">add</span>
        Nou Esdeveniment
      </button>
    </div>

    <!-- Loading -->
    <div v-if="store.loading" class="flex flex-col items-center justify-center py-32 text-on-surface-variant">
      <span class="material-symbols-outlined text-5xl animate-spin mb-4 opacity-40">progress_activity</span>
      <p class="text-sm">Carregant esdeveniments...</p>
    </div>

    <!-- Empty -->
    <div v-else-if="store.events.length === 0" class="glass-panel rounded-2xl border border-white/10 py-24 text-center">
      <span class="material-symbols-outlined text-6xl opacity-20 block mb-4">event_note</span>
      <h3 class="font-headline font-bold text-on-surface mb-2">Cap esdeveniment creat</h3>
      <p class="text-on-surface-variant text-sm mb-6">Crea el teu primer event per a commençar a vendre entrades.</p>
      <button @click="openCreate" class="px-6 py-3 rounded-xl border border-primary/30 text-primary font-bold hover:bg-primary/10 transition-colors">
        Crear primer event
      </button>
    </div>

    <!-- Events grid -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-5">
      <div
        v-for="event in store.events"
        :key="event.id"
        class="glass-panel rounded-2xl border border-white/10 overflow-hidden hover:border-primary/30 transition-all duration-300 hover:-translate-y-1"
      >
        <!-- Event card header -->
        <div class="h-1.5 w-full signature-pulse"></div>
        <div class="p-6">
          <div class="flex items-start justify-between mb-4">
            <div class="flex-1 min-w-0">
              <h3 class="font-headline font-bold text-on-surface text-lg leading-tight truncate">{{ event.title }}</h3>
              <div class="flex items-center gap-2 text-on-surface-variant text-xs mt-1">
                <span class="material-symbols-outlined text-xs">stadium</span>
                <span>{{ event.venue || 'Sense recinte' }}</span>
              </div>
            </div>
            <span class="ml-3 px-2 py-1 rounded bg-white/5 text-on-surface-variant text-[10px] font-bold uppercase tracking-wider flex-shrink-0">#{{ event.id }}</span>
          </div>

          <!-- Info row -->
          <div class="flex flex-wrap gap-3 mb-5">
            <div class="flex items-center gap-1.5 text-xs text-on-surface-variant">
              <span class="material-symbols-outlined text-xs text-primary">event</span>
              {{ formatDate(event.date) }}
            </div>
            <div class="flex items-center gap-1.5 text-xs text-on-surface-variant">
              <span class="material-symbols-outlined text-xs text-secondary">event_seat</span>
              {{ event.seats_count ?? 0 }} seients
            </div>
            <div v-if="event.zones?.length" class="flex items-center gap-1.5 text-xs text-on-surface-variant">
              <span class="material-symbols-outlined text-xs text-tertiary">layers</span>
              {{ event.zones.length }} zones
            </div>
          </div>

          <!-- Zone pills -->
          <div v-if="event.zones?.length" class="flex flex-wrap gap-2 mb-5">
            <div
              v-for="zone in event.zones"
              :key="zone.name"
              class="flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-[11px] font-bold"
              :style="{ borderColor: zone.color + '40', backgroundColor: zone.color + '15', color: zone.color }"
            >
              <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" :style="{ backgroundColor: zone.color }"></span>
              {{ zone.name }} · €{{ zone.price }}
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-2 pt-4 border-t border-white/5">
            <button
              @click="openEdit(event)"
              class="flex-1 flex items-center justify-center gap-1.5 py-2 rounded-lg border border-white/10 text-on-surface-variant hover:text-primary hover:border-primary/30 hover:bg-primary/5 transition-all text-xs font-bold font-headline"
            >
              <span class="material-symbols-outlined text-base">edit</span>
              Editar
            </button>
            <button
              @click="confirmGenerateSeats(event)"
              class="flex-1 flex items-center justify-center gap-1.5 py-2 rounded-lg border border-white/10 text-on-surface-variant hover:text-secondary hover:border-secondary/30 hover:bg-secondary/5 transition-all text-xs font-bold font-headline"
              :disabled="!event.zones?.length"
              :title="!event.zones?.length ? 'Defineix zones primer' : 'Generar seients'"
            >
              <span class="material-symbols-outlined text-base">grid_on</span>
              Seients
            </button>
            <button
              @click="confirmDelete(event)"
              class="flex items-center justify-center p-2 rounded-lg border border-white/10 text-on-surface-variant hover:text-error hover:border-error/30 hover:bg-error/5 transition-all"
            >
              <span class="material-symbols-outlined text-base">delete</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- === CREATE / EDIT MODAL === -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showModal" class="fixed inset-0 z-[200] flex items-start justify-center p-4 pt-16 overflow-y-auto">
          <!-- Backdrop -->
          <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" @click="closeModal"></div>

          <!-- Modal panel -->
          <div class="relative w-full max-w-2xl bg-surface-container rounded-2xl border border-white/10 shadow-2xl z-10 my-4">

            <!-- Modal header -->
            <div class="px-7 py-5 border-b border-white/5 flex items-center justify-between">
              <h2 class="font-headline text-xl font-bold text-on-surface">
                {{ editingEvent ? 'Editar Esdeveniment' : 'Nou Esdeveniment' }}
              </h2>
              <button @click="closeModal" class="material-symbols-outlined text-outline hover:text-white transition-colors">close</button>
            </div>

            <!-- Modal body -->
            <div class="px-7 py-6 space-y-6">

              <!-- Basic info -->
              <div class="space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-widest text-on-surface-variant flex items-center gap-2">
                  <span class="material-symbols-outlined text-sm text-primary">info</span>Informació bàsica
                </h3>

                <div>
                  <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">Títol *</label>
                  <input
                    v-model="form.title"
                    type="text"
                    placeholder="Nom del concert o event"
                    class="w-full bg-surface-container-lowest border border-outline-variant/30 rounded-xl px-4 py-3 text-on-surface placeholder:text-outline focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/30 transition-all"
                  />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">Data i hora *</label>
                    <input
                      v-model="form.date"
                      type="datetime-local"
                      class="w-full bg-surface-container-lowest border border-outline-variant/30 rounded-xl px-4 py-3 text-on-surface focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/30 transition-all"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">Recinte</label>
                    <input
                      v-model="form.venue"
                      type="text"
                      placeholder="Palau Sant Jordi, Barcelona"
                      class="w-full bg-surface-container-lowest border border-outline-variant/30 rounded-xl px-4 py-3 text-on-surface placeholder:text-outline focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/30 transition-all"
                    />
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">Descripció</label>
                  <textarea
                    v-model="form.description"
                    rows="2"
                    placeholder="Descripció breu de l'event..."
                    class="w-full bg-surface-container-lowest border border-outline-variant/30 rounded-xl px-4 py-3 text-on-surface placeholder:text-outline focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/30 transition-all resize-none"
                  ></textarea>
                </div>
              </div>

              <!-- Zone designer -->
              <div class="space-y-4">
                <div class="flex items-center justify-between">
                  <h3 class="text-xs font-bold uppercase tracking-widest text-on-surface-variant flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm text-secondary">layers</span>Zones i preus
                  </h3>
                  <button
                    @click="addZone"
                    type="button"
                    class="flex items-center gap-1.5 text-xs font-bold text-secondary hover:bg-secondary/10 px-3 py-1.5 rounded-lg transition-colors"
                  >
                    <span class="material-symbols-outlined text-sm">add_circle</span>
                    Afegir zona
                  </button>
                </div>

                <div v-if="form.zones.length === 0" class="text-center py-6 border border-dashed border-white/10 rounded-xl text-on-surface-variant">
                  <span class="material-symbols-outlined text-2xl opacity-30 block mb-1">layers</span>
                  <p class="text-xs">Afegeix zones per definir el plànol i preus</p>
                </div>

                <div v-else class="space-y-3">
                  <div
                    v-for="(zone, i) in form.zones"
                    :key="i"
                    class="p-4 bg-white/5 rounded-xl border border-white/10 space-y-3"
                  >
                    <div class="flex items-center gap-3">
                      <!-- Color picker -->
                      <div class="relative">
                        <input
                          type="color"
                          v-model="zone.color"
                          class="w-10 h-10 rounded-lg cursor-pointer border-none bg-transparent"
                          title="Color de la zona"
                        />
                      </div>
                      <!-- Zone name -->
                      <input
                        v-model="zone.name"
                        type="text"
                        placeholder="Nom zona (ex: VIP)"
                        class="flex-1 bg-surface-container-lowest border border-outline-variant/30 rounded-lg px-3 py-2 text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary/50 transition-all"
                      />
                      <!-- Remove zone -->
                      <button @click="removeZone(i)" type="button" class="material-symbols-outlined text-base text-outline hover:text-error transition-colors">
                        remove_circle
                      </button>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                      <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">Preu (€)</label>
                        <input
                          v-model.number="zone.price"
                          type="number"
                          min="0"
                          step="0.01"
                          placeholder="50.00"
                          class="w-full bg-surface-container-lowest border border-outline-variant/30 rounded-lg px-3 py-2 text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary/50 transition-all"
                        />
                      </div>
                      <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">Files (ex: A,B,C)</label>
                        <input
                          v-model="zone.rowsStr"
                          type="text"
                          placeholder="A,B,C"
                          class="w-full bg-surface-container-lowest border border-outline-variant/30 rounded-lg px-3 py-2 text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary/50 transition-all uppercase"
                        />
                      </div>
                      <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">Seients/fila</label>
                        <input
                          v-model.number="zone.seatsPerRow"
                          type="number"
                          min="1"
                          placeholder="20"
                          class="w-full bg-surface-container-lowest border border-outline-variant/30 rounded-lg px-3 py-2 text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary/50 transition-all"
                        />
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Total seats preview -->
                <div v-if="totalSeatsPreview > 0" class="flex items-center justify-between text-sm px-2">
                  <span class="text-on-surface-variant">Total seients generats:</span>
                  <span class="font-headline font-bold text-secondary">{{ totalSeatsPreview.toLocaleString() }} seients</span>
                </div>
              </div>
            </div>

            <!-- Modal footer -->
            <div class="px-7 py-5 border-t border-white/5 flex items-center gap-3 justify-end">
              <button @click="closeModal" class="px-5 py-2.5 rounded-xl border border-white/10 text-on-surface-variant hover:text-on-surface hover:bg-white/5 font-bold text-sm transition-all">
                Cancel·lar
              </button>
              <button
                @click="submitForm"
                :disabled="submitting || !form.title || !form.date"
                class="flex items-center gap-2 px-6 py-2.5 rounded-xl signature-pulse text-white font-headline font-bold text-sm uppercase tracking-wide hover:scale-[1.02] transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:scale-100"
              >
                <span v-if="submitting" class="material-symbols-outlined text-base animate-spin">progress_activity</span>
                <span v-else class="material-symbols-outlined text-base">check</span>
                {{ submitting ? 'Desant...' : (editingEvent ? 'Desar canvis' : 'Crear event') }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- === DELETE CONFIRM MODAL === -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="confirmDeleteEvent" class="fixed inset-0 z-[200] flex items-center justify-center p-4">
          <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" @click="confirmDeleteEvent = null"></div>
          <div class="relative w-full max-w-sm bg-surface-container rounded-2xl border border-error/20 shadow-2xl z-10 p-7 text-center">
            <div class="w-16 h-16 rounded-full bg-error/15 border border-error/30 flex items-center justify-center mx-auto mb-5">
              <span class="material-symbols-outlined text-error text-3xl">delete</span>
            </div>
            <h3 class="font-headline font-bold text-on-surface text-lg mb-2">Eliminar Esdeveniment</h3>
            <p class="text-on-surface-variant text-sm mb-1">
              Estàs a punt d'eliminar <strong class="text-on-surface">{{ confirmDeleteEvent?.title }}</strong>.
            </p>
            <p class="text-error text-xs mb-7">Aquesta acció eliminarà també tots els seients associats i no es pot desfer.</p>
            <div class="flex gap-3">
              <button @click="confirmDeleteEvent = null" class="flex-1 py-2.5 rounded-xl border border-white/10 text-on-surface-variant font-bold hover:bg-white/5 transition-colors text-sm">
                Cancel·lar
              </button>
              <button @click="doDelete" :disabled="submitting" class="flex-1 py-2.5 rounded-xl bg-error/20 border border-error/30 text-error font-bold hover:bg-error/30 transition-colors text-sm disabled:opacity-50">
                {{ submitting ? 'Eliminant...' : 'Sí, eliminar' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- === GENERATE SEATS CONFIRM === -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="confirmGenEvent" class="fixed inset-0 z-[200] flex items-center justify-center p-4">
          <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" @click="confirmGenEvent = null"></div>
          <div class="relative w-full max-w-sm bg-surface-container rounded-2xl border border-secondary/20 shadow-2xl z-10 p-7 text-center">
            <div class="w-16 h-16 rounded-full bg-secondary/15 border border-secondary/30 flex items-center justify-center mx-auto mb-5">
              <span class="material-symbols-outlined text-secondary text-3xl">grid_on</span>
            </div>
            <h3 class="font-headline font-bold text-on-surface text-lg mb-2">Generar Seients</h3>
            <p class="text-on-surface-variant text-sm mb-1">
              Es generaran <strong class="text-secondary">{{ confirmGenSeats }} seients</strong> per a <strong class="text-on-surface">{{ confirmGenEvent?.title }}</strong>.
            </p>
            <p class="text-tertiary text-xs mb-7">⚠ Els seients actuals d'aquest event s'eliminaran i es crearan de nou.</p>
            <div class="flex gap-3">
              <button @click="confirmGenEvent = null" class="flex-1 py-2.5 rounded-xl border border-white/10 text-on-surface-variant font-bold hover:bg-white/5 transition-colors text-sm">
                Cancel·lar
              </button>
              <button @click="doGenerateSeats" :disabled="submitting" class="flex-1 py-2.5 rounded-xl bg-secondary/20 border border-secondary/30 text-secondary font-bold hover:bg-secondary/30 transition-colors text-sm disabled:opacity-50">
                {{ submitting ? 'Generant...' : 'Generar' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Top toast for actions feedback -->
    <Teleport to="body">
      <Transition name="toast">
        <div v-if="actionToast.show" class="fixed top-20 right-6 z-[300] w-80">
          <div
            class="glass-panel p-4 rounded-xl shadow-2xl flex items-center gap-3 border-l-4"
            :class="actionToast.type === 'success' ? 'border-secondary' : 'border-error'"
          >
            <span class="material-symbols-outlined" :class="actionToast.type === 'success' ? 'text-secondary' : 'text-error'">
              {{ actionToast.type === 'success' ? 'check_circle' : 'error' }}
            </span>
            <p class="text-sm font-body text-on-surface flex-1">{{ actionToast.message }}</p>
            <button @click="actionToast.show = false" class="material-symbols-outlined text-outline text-base">close</button>
          </div>
        </div>
      </Transition>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAdminStore } from '~/stores/adminStore'

const store = useAdminStore()

// ── State ──────────────────────────────────────────────────────────────────
const showModal = ref(false)
const editingEvent = ref(null)
const submitting = ref(false)
const confirmDeleteEvent = ref(null)
const confirmGenEvent = ref(null)
const confirmGenSeats = ref(0)

const actionToast = ref({ show: false, message: '', type: 'success' })
let toastTimer = null

const showToast = (message, type = 'success') => {
  if (toastTimer) clearTimeout(toastTimer)
  actionToast.value = { show: true, message, type }
  toastTimer = setTimeout(() => { actionToast.value.show = false }, 4000)
}

// ── Form ───────────────────────────────────────────────────────────────────
const emptyForm = () => ({
  title: '',
  date: '',
  venue: '',
  description: '',
  zones: [],
})

const form = ref(emptyForm())

const totalSeatsPreview = computed(() =>
  form.value.zones.reduce((acc, z) => {
    const rows = z.rowsStr ? z.rowsStr.split(',').filter(r => r.trim()) : []
    return acc + rows.length * (z.seatsPerRow || 0)
  }, 0)
)

// ── Helpers ────────────────────────────────────────────────────────────────
const formatDate = (dateStr) => {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleString('ca-ES', {
    day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
  })
}

const openCreate = () => {
  editingEvent.value = null
  form.value = emptyForm()
  showModal.value = true
}

const openEdit = (event) => {
  editingEvent.value = event
  form.value = {
    title: event.title,
    date: event.date ? event.date.substring(0, 16) : '',
    venue: event.venue || '',
    description: event.description || '',
    zones: (event.zones || []).map(z => ({
      ...z,
      rowsStr: Array.isArray(z.rows) ? z.rows.join(',') : (z.rows || ''),
    })),
  }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  editingEvent.value = null
}

const addZone = () => {
  const colors = ['#95aaff', '#83fba5', '#ffd16f', '#ff6e84', '#b39ddb', '#4dd0e1']
  form.value.zones.push({
    name: '',
    color: colors[form.value.zones.length % colors.length],
    price: 50,
    rowsStr: '',
    seatsPerRow: 20,
  })
}

const removeZone = (i) => form.value.zones.splice(i, 1)

const serializeZones = () =>
  form.value.zones.map(z => ({
    name: z.name,
    color: z.color,
    price: z.price,
    rows: z.rowsStr.split(',').map(r => r.trim().toUpperCase()).filter(Boolean),
    seatsPerRow: z.seatsPerRow,
  }))

// ── CRUD ───────────────────────────────────────────────────────────────────
const submitForm = async () => {
  if (!form.value.title || !form.value.date) return
  submitting.value = true
  try {
    const payload = {
      title: form.value.title,
      date: form.value.date,
      venue: form.value.venue,
      description: form.value.description,
      zones: serializeZones(),
    }
    if (editingEvent.value) {
      await store.updateEvent(editingEvent.value.id, payload)
      showToast('Esdeveniment actualitzat correctament', 'success')
    } else {
      await store.createEvent(payload)
      showToast('Esdeveniment creat correctament', 'success')
    }
    closeModal()
  } catch (e) {
    showToast(e.response?._data?.message || e.message || 'Error desant l\'event', 'error')
  } finally {
    submitting.value = false
  }
}

const confirmDelete = (event) => {
  confirmDeleteEvent.value = event
}

const doDelete = async () => {
  if (!confirmDeleteEvent.value) return
  submitting.value = true
  try {
    await store.deleteEvent(confirmDeleteEvent.value.id)
    showToast('Esdeveniment eliminat', 'success')
    confirmDeleteEvent.value = null
  } catch (e) {
    showToast('Error eliminant l\'event', 'error')
  } finally {
    submitting.value = false
  }
}

const confirmGenerateSeats = (event) => {
  if (!event.zones?.length) return
  confirmGenEvent.value = event
  confirmGenSeats.value = event.zones.reduce((acc, z) => {
    const rows = Array.isArray(z.rows) ? z.rows.length : 0
    return acc + rows * (z.seatsPerRow || 0)
  }, 0)
}

const doGenerateSeats = async () => {
  if (!confirmGenEvent.value) return
  submitting.value = true
  try {
    const result = await store.generateSeats(confirmGenEvent.value.id)
    showToast(result.message || 'Seients generats correctament', 'success')
    confirmGenEvent.value = null
  } catch (e) {
    showToast(e.response?._data?.error || 'Error generant seients', 'error')
  } finally {
    submitting.value = false
  }
}

// ── Init ───────────────────────────────────────────────────────────────────
onMounted(() => {
  store.fetchEvents()
})
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
.toast-enter-active { transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
.toast-leave-active { transition: all 0.2s ease; }
.toast-enter-from { opacity: 0; transform: translateX(40px); }
.toast-leave-to { opacity: 0; transform: translateX(40px); }
</style>
