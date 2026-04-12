<template>
  <div class="glass-panel rounded-2xl border border-white/10 overflow-hidden mb-8">
    <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between">
      <h2 class="font-headline font-bold text-on-surface flex items-center gap-2">
        <span class="material-symbols-outlined text-base text-secondary">support_agent</span>
        Assistència en Directe (WebRTC)
        <span v-if="pendingRequests.length > 0" class="ml-2 px-2 py-0.5 rounded-full bg-error/20 text-error text-xs font-bold animate-pulse">
          {{ pendingRequests.length }} pendent
        </span>
      </h2>
    </div>

    <div class="p-6">
      <!-- Main Video Interface -->
      <div v-if="activeCall" class="w-full bg-zinc-950 rounded-xl border border-white/10 overflow-hidden mb-6 flex flex-col sm:flex-row">
        <!-- Caller Info -->
        <div class="p-6 bg-zinc-900/50 border-b sm:border-b-0 sm:border-r border-white/10 sm:w-1/3 flex flex-col justify-between">
          <div>
            <span class="inline-flex items-center gap-1 px-2 py-1 rounded border border-secondary/30 bg-secondary/10 text-secondary text-[10px] font-bold uppercase tracking-wider mb-4">
              <span class="w-1.5 h-1.5 rounded-full bg-secondary animate-pulse"></span> Connectat
            </span>
            <h3 class="font-headline font-black text-xl text-on-surface mb-1">{{ activeCall.userName }}</h3>
            <p class="text-xs text-on-surface-variant flex items-center gap-1">
              <span class="material-symbols-outlined text-[14px]">id_card</span>
              {{ activeCall.userId.substring(0, 8) }}
            </p>
          </div>

          <div class="mt-8 space-y-3">
            <button @click="toggleAudio" class="w-full flex items-center justify-center gap-2 py-2.5 rounded-lg font-bold text-sm transition-colors" :class="isAudioMuted ? 'bg-error/10 text-error hover:bg-error/20' : 'bg-white/10 text-on-surface hover:bg-white/15'">
              <span class="material-symbols-outlined">{{ isAudioMuted ? 'mic_off' : 'mic' }}</span>
              {{ isAudioMuted ? 'Micròfon apagat' : 'Micròfon actiu' }}
            </button>
            <button @click="endCall" class="w-full flex items-center justify-center gap-2 py-2.5 rounded-lg bg-error hover:bg-error/80 text-white font-bold text-sm transition-colors shadow-lg">
              <span class="material-symbols-outlined">call_end</span>
              Finalitzar trucada
            </button>
          </div>
        </div>

        <!-- Video Area -->
        <div class="relative bg-black sm:w-2/3 aspect-video flex items-center justify-center pointer-events-none">
          <!-- Remote Video (Client) -->
          <video 
            ref="remoteVideo" 
            autoplay 
            playsinline 
            class="w-full h-full object-cover"
          ></video>

          <!-- Local Video (Admin) -->
          <video 
            ref="localVideo" 
            autoplay 
            playsinline 
            muted 
            class="absolute bottom-4 right-4 w-1/4 aspect-video bg-zinc-800 rounded-lg border-2 border-white/20 object-cover shadow-2xl"
          ></video>
          
          <div v-if="error" class="absolute inset-0 flex flex-col items-center justify-center bg-zinc-950/90 p-6 text-center z-10 pointer-events-auto">
            <span class="material-symbols-outlined text-4xl text-error mb-2">error</span>
            <p class="text-sm font-bold text-error mb-1">Error de connexió</p>
            <p class="text-xs text-error/70">{{ error }}</p>
            <button @click="endCall" class="mt-4 px-4 py-1.5 rounded bg-error/20 hover:bg-error/30 text-error text-xs font-bold transition-colors">Tancar</button>
          </div>
        </div>
      </div>

      <!-- Pending Requests Queue -->
      <div v-if="!activeCall">
        <div v-if="pendingRequests.length === 0" class="py-12 text-center text-on-surface-variant">
          <span class="material-symbols-outlined text-4xl opacity-30 block mb-2">headset_mic</span>
          <p class="text-sm">Cap client ha sol·licitat assistència per vídeo en aquest moment.</p>
        </div>
        
        <div v-else class="space-y-3">
          <div v-for="req in pendingRequests" :key="req.userId" class="flex items-center justify-between p-4 bg-white/5 border border-white/5 rounded-xl hover:bg-white/10 transition-colors">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-secondary/10 flex items-center justify-center">
                <span class="material-symbols-outlined text-secondary">person</span>
              </div>
              <div>
                <p class="font-headline font-bold text-sm text-on-surface">{{ req.userName }}</p>
                <p class="text-xs text-on-surface-variant">{{ req.message }}</p>
              </div>
            </div>
            
            <button @click="acceptRequest(req)" class="flex items-center gap-2 px-4 py-2 bg-secondary text-on-secondary rounded-lg font-headline font-bold text-sm hover:bg-secondary/90 transition-colors">
              <span class="material-symbols-outlined text-sm">video_camera_front</span>
              Atendre Trucada
            </button>
          </div>
        </div>
      </div>
      
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'

const { $socket } = useNuxtApp()

// State
const pendingRequests = ref([])
const activeCall = ref(null)
const error = ref(null)
const isAudioMuted = ref(false)

// DOM Refs
const localVideo = ref(null)
const remoteVideo = ref(null)

// WebRTC logic
let localStream = null
let peerConnection = null

const configuration = {
  iceServers: [
    { urls: 'stun:stun.l.google.com:19302' },
    { urls: 'stun:stun1.l.google.com:19302' }
  ]
}

const getMedia = async () => {
  try {
    localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true })
    if (localVideo.value) {
      localVideo.value.srcObject = localStream
    }
  } catch (err) {
    console.error('Error accessing media config on Admin side', err)
    error.value = "No s'ha pogut accedir a la teva càmera/micròfon. Comprova els permisos del navegador."
    throw err
  }
}

const stopMedia = () => {
  if (localStream) {
    localStream.getTracks().forEach(track => track.stop())
    localStream = null
  }
}

const createPeerConnection = () => {
  peerConnection = new RTCPeerConnection(configuration)

  localStream.getTracks().forEach(track => {
    peerConnection.addTrack(track, localStream)
  })

  peerConnection.ontrack = (event) => {
    console.log('[WebRTC Admin] Received remote track')
    if (remoteVideo.value && remoteVideo.value.srcObject !== event.streams[0]) {
      remoteVideo.value.srcObject = event.streams[0]
    }
  }

  peerConnection.onicecandidate = (event) => {
    if (event.candidate && activeCall.value) {
      $socket.emit('webrtc_ice_candidate', {
        target: activeCall.value.userId,
        candidate: event.candidate
      })
    }
  }

  peerConnection.onconnectionstatechange = () => {
    if (peerConnection.connectionState === 'disconnected' || peerConnection.connectionState === 'failed') {
      endCall()
    }
  }
}

const acceptRequest = async (req) => {
  error.value = null
  activeCall.value = req
  pendingRequests.value = pendingRequests.value.filter(r => r.userId !== req.userId)
  
  try {
    // Wait for Vue to render the <video> tags before assigning streams
    await nextTick()
    await getMedia()

    // Notify the user after media is ready, preventing 'getTracks of null' race conditions
    $socket.emit('support_accept', { targetId: req.userId })
  } catch (e) {
    activeCall.value = null
    console.error("Failed to accept request", e)
  }
}

const handleWebRTCOffer = async (payload) => {
  if (!activeCall.value || payload.caller !== activeCall.value.userId) return
  
  createPeerConnection()
  
  try {
    await peerConnection.setRemoteDescription(new RTCSessionDescription(payload.sdp))
    const answer = await peerConnection.createAnswer()
    await peerConnection.setLocalDescription(answer)
    
    $socket.emit('webrtc_answer', {
      target: payload.caller,
      sdp: answer
    })
  } catch (e) {
    console.error('Error handling WebRTC offer', e)
    error.value = "Error sincronitzant el flux d'àudio/vídeo."
  }
}

const handleIceCandidate = async (data) => {
  if (!peerConnection) return
  try {
    await peerConnection.addIceCandidate(data.candidate)
  } catch (e) {
    console.error('Error adding received ice candidate (Admin)', e)
  }
}

const toggleAudio = () => {
  if (!localStream) return
  isAudioMuted.value = !isAudioMuted.value
  localStream.getAudioTracks().forEach(t => t.enabled = !isAudioMuted.value)
}

const endCall = () => {
  if (activeCall.value) {
    $socket.emit('webrtc_end', { target: activeCall.value.userId })
  }
  reset()
}

const reset = () => {
  stopMedia()
  if (peerConnection) {
    peerConnection.close()
    peerConnection = null
  }
  activeCall.value = null
  error.value = null
  isAudioMuted.value = false
}

// Socket Responders
onMounted(() => {
  $socket.on('support_request_received', (data) => {
    // Prevent duplicates
    if (!pendingRequests.value.find(r => r.userId === data.userId)) {
      pendingRequests.value.push(data)
    }
  })
  
  $socket.on('support_request_cancelled', (data) => {
    pendingRequests.value = pendingRequests.value.filter(r => r.userId !== data.userId)
    if (activeCall.value?.userId === data.userId) {
      reset()
    }
  })
  
  $socket.on('support_request_handled', (data) => {
    // Another admin took the call
    pendingRequests.value = pendingRequests.value.filter(r => r.userId !== data.userId)
  })
  
  $socket.on('webrtc_offer', handleWebRTCOffer)
  $socket.on('webrtc_ice_candidate', handleIceCandidate)
  $socket.on('webrtc_ended', reset)
})

onUnmounted(() => {
  $socket.off('support_request_received')
  $socket.off('support_request_cancelled')
  $socket.off('support_request_handled')
  $socket.off('webrtc_offer', handleWebRTCOffer)
  $socket.off('webrtc_ice_candidate', handleIceCandidate)
  $socket.off('webrtc_ended', reset)
  reset()
})

</script>
