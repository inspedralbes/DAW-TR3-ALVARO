<template>
  <div class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-4 pointer-events-none">
    
    <!-- Video Call Interface -->
    <Transition name="slide-up">
      <div v-if="callState !== 'idle'" class="pointer-events-auto w-[320px] sm:w-[380px] bg-zinc-950/95 backdrop-blur-xl border border-white/10 rounded-2xl shadow-2xl overflow-hidden flex flex-col">
        
        <!-- Header -->
        <div class="px-4 py-3 border-b border-white/10 flex items-center justify-between bg-zinc-900/50">
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-sm" :class="{'animate-pulse text-secondary': callState === 'connected'}">
              {{ callState === 'connected' ? 'record_voice_over' : 'support_agent' }}
            </span>
            <span class="text-xs font-bold uppercase tracking-wider text-on-surface">Assistència en directe</span>
          </div>
          <button @click="endCall" class="text-on-surface-variant hover:text-error transition-colors p-1">
            <span class="material-symbols-outlined text-base">close</span>
          </button>
        </div>

        <!-- Video Area -->
        <div class="relative bg-black aspect-video flex items-center justify-center overflow-hidden">
          
          <!-- Remote Video (Admin) -->
          <video 
            ref="remoteVideo" 
            autoplay 
            playsinline 
            class="w-full h-full object-cover"
            :class="{'opacity-0': callState !== 'connected'}"
          ></video>

          <!-- Local Video (User) - Picture in Picture -->
          <video 
            ref="localVideo" 
            autoplay 
            playsinline 
            muted 
            class="absolute bottom-4 right-4 w-24 aspect-video bg-zinc-800 rounded-lg border border-white/20 object-cover shadow-lg"
            :class="{'hidden': !localStream}"
          ></video>

          <!-- States Overlay -->
          <div v-if="callState === 'requesting'" class="absolute inset-0 flex flex-col items-center justify-center bg-zinc-950/80 backdrop-blur-sm z-10">
            <div class="w-12 h-12 rounded-full border-2 border-primary border-t-transparent animate-spin mb-3"></div>
            <p class="text-sm font-bold text-on-surface">Cridant a un agent...</p>
            <p class="text-xs text-on-surface-variant mt-1">Si us plau, espera</p>
          </div>
          
          <div v-if="callState === 'connecting'" class="absolute inset-0 flex flex-col items-center justify-center bg-zinc-950/80 backdrop-blur-sm z-10">
            <span class="material-symbols-outlined text-4xl text-secondary animate-pulse mb-2">wifi_calling_3</span>
            <p class="text-sm font-bold text-on-surface">Establint connexió...</p>
          </div>

          <div v-if="error" class="absolute inset-0 flex flex-col items-center justify-center bg-zinc-950/90 backdrop-blur-sm z-20 p-6 text-center">
            <span class="material-symbols-outlined text-4xl text-error mb-2">videocam_off</span>
            <p class="text-sm font-bold text-error mb-1">Error de connexió</p>
            <p class="text-xs text-error/70">{{ error }}</p>
            <button @click="reset" class="mt-4 px-4 py-1.5 rounded-full bg-white/10 hover:bg-white/20 text-xs font-bold transition-colors">D'acord</button>
          </div>
        </div>

        <!-- Controls -->
        <div class="p-4 bg-zinc-900/50 flex justify-center gap-4">
          <button @click="toggleAudio" class="w-10 h-10 rounded-full flex items-center justify-center transition-colors" :class="isAudioMuted ? 'bg-error/20 text-error hover:bg-error/30' : 'bg-white/10 text-white hover:bg-white/20'">
            <span class="material-symbols-outlined text-sm">{{ isAudioMuted ? 'mic_off' : 'mic' }}</span>
          </button>
          <button @click="toggleVideo" class="w-10 h-10 rounded-full flex items-center justify-center transition-colors" :class="isVideoMuted ? 'bg-error/20 text-error hover:bg-error/30' : 'bg-white/10 text-white hover:bg-white/20'">
            <span class="material-symbols-outlined text-sm">{{ isVideoMuted ? 'videocam_off' : 'videocam' }}</span>
          </button>
          <button @click="endCall" class="w-10 h-10 rounded-full bg-error text-white hover:bg-error/80 flex items-center justify-center transition-colors shadow-[0_0_15px_rgba(255,82,82,0.4)]">
            <span class="material-symbols-outlined text-sm">call_end</span>
          </button>
        </div>
      </div>
    </Transition>

    <!-- Trigger Button -->
    <Transition name="fade">
      <button 
        v-if="callState === 'idle'" 
        @click="requestSupport" 
        class="pointer-events-auto group flex items-center gap-3 px-5 py-3.5 bg-primary hover:bg-primary/90 text-on-primary rounded-full shadow-[0_8px_30px_rgba(149,170,255,0.3)] transition-all hover:scale-105"
      >
        <span class="material-symbols-outlined text-xl">support_agent</span>
        <span class="font-headline font-bold text-sm tracking-wide">Necessites ajuda?</span>
      </button>
    </Transition>

  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const { $socket } = useNuxtApp()
const authStore = useAuthStore()

// DOM Refs
const localVideo = ref(null)
const remoteVideo = ref(null)

// State
const callState = ref('idle') // idle, requesting, connecting, connected
const error = ref(null)
const isAudioMuted = ref(false)
const isVideoMuted = ref(false)

// WebRTC logic
let localStream = null
let peerConnection = null
let adminSocketId = null

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
    console.error('Error accessing media devices.', err)
    error.value = "No s'ha pogut accedir a la càmera o l'àudio. Revisa els permisos."
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

  // Add local stream tracks to the connection
  localStream.getTracks().forEach(track => {
    peerConnection.addTrack(track, localStream)
  })

  // Handle incoming streams
  peerConnection.ontrack = (event) => {
    console.log('[WebRTC] Received remote track')
    if (remoteVideo.value && remoteVideo.value.srcObject !== event.streams[0]) {
      remoteVideo.value.srcObject = event.streams[0]
      callState.value = 'connected'
    }
  }

  // Handle ICE candidates
  peerConnection.onicecandidate = (event) => {
    if (event.candidate) {
      $socket.emit('webrtc_ice_candidate', {
        target: adminSocketId,
        candidate: event.candidate
      })
    }
  }

  peerConnection.onconnectionstatechange = () => {
    console.log('[WebRTC] Connection state:', peerConnection.connectionState)
    if (peerConnection.connectionState === 'disconnected' || peerConnection.connectionState === 'failed') {
      endCall()
    }
  }
}

const requestSupport = async () => {
  error.value = null
  callState.value = 'requesting'
  
  try {
    await getMedia()
    $socket.emit('support_request', { userName: authStore.user?.name || 'Anònim' })
  } catch (e) {
    callState.value = 'idle'
  }
}

const handleSupportAccepted = async (data) => {
  if (callState.value !== 'requesting') return
  
  callState.value = 'connecting'
  adminSocketId = data.adminId
  
  createPeerConnection()
  
  try {
    const offer = await peerConnection.createOffer()
    await peerConnection.setLocalDescription(offer)
    
    $socket.emit('webrtc_offer', {
      target: adminSocketId,
      sdp: offer
    })
  } catch (e) {
    console.error('Error creating offer', e)
    error.value = "Error creant connexió per vídeo."
  }
}

const handleWebRTCAnswer = async (data) => {
  if (!peerConnection) return
  if (data.answerer !== adminSocketId) return
  
  try {
    const remoteDesc = new RTCSessionDescription(data.sdp)
    await peerConnection.setRemoteDescription(remoteDesc)
  } catch (e) {
    console.error('Error setting remote description from answer', e)
  }
}

const handleIceCandidate = async (data) => {
  if (!peerConnection) return
  try {
    await peerConnection.addIceCandidate(data.candidate)
  } catch (e) {
    console.error('Error adding received ice candidate', e)
  }
}

const toggleAudio = () => {
  if (!localStream) return
  isAudioMuted.value = !isAudioMuted.value
  localStream.getAudioTracks().forEach(t => t.enabled = !isAudioMuted.value)
}

const toggleVideo = () => {
  if (!localStream) return
  isVideoMuted.value = !isVideoMuted.value
  localStream.getVideoTracks().forEach(t => t.enabled = !isVideoMuted.value)
}

const endCall = () => {
  if (callState.value === 'requesting') {
    $socket.emit('support_cancel')
  } else if (adminSocketId) {
    $socket.emit('webrtc_end', { target: adminSocketId })
  }
  
  reset()
}

const handleRemoteEnd = () => {
  reset()
}

const reset = () => {
  stopMedia()
  if (peerConnection) {
    peerConnection.close()
    peerConnection = null
  }
  adminSocketId = null
  error.value = null
  callState.value = 'idle'
  isAudioMuted.value = false
  isVideoMuted.value = false
}

// Socket Listeners
onMounted(() => {
  $socket.on('support_accepted', handleSupportAccepted)
  $socket.on('webrtc_answer', handleWebRTCAnswer)
  $socket.on('webrtc_ice_candidate', handleIceCandidate)
  $socket.on('webrtc_ended', handleRemoteEnd)
})

onUnmounted(() => {
  $socket.off('support_accepted', handleSupportAccepted)
  $socket.off('webrtc_answer', handleWebRTCAnswer)
  $socket.off('webrtc_ice_candidate', handleIceCandidate)
  $socket.off('webrtc_ended', handleRemoteEnd)
  reset()
})

</script>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translateY(20px) scale(0.95);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
