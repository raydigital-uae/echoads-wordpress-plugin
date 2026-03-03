import { createRoot } from 'react-dom/client'
import { AudioPlayer } from './App'
import '../assets/css/audio-player.css'
import type { AudioData } from './types'

// Extend window interface for TypeScript
declare global {
  interface Window {
    EchoAdsAudioPlayers: Record<string, AudioData>
    EchoAdsAudioPlayersPendingInit: string[]
    EchoAdsAudioController: {
      init: (playerId: string) => void
    }
  }
}

// Initialize audio player for a specific player ID
const init = (playerId: string) => {
  const audioData = window.EchoAdsAudioPlayers?.[playerId]
  if (!audioData) {
    console.error(`No audio data found for player: ${playerId}`)
    return
  }

  const wrapper = document.getElementById(`${playerId}-wrapper`)
  if (!wrapper) {
    console.error(`Wrapper element not found for player: ${playerId}`)
    return
  }

  // Get background color from data attribute or default
  const bgColor = wrapper.getAttribute('data-bg-color') || '#5D33F5'

  // Create React root and render
  const root = createRoot(wrapper)
  root.render(<AudioPlayer audioData={audioData} bgColor={bgColor} />)
}

// Export controller to window
window.EchoAdsAudioController = {
  init,
}

// Process any players that were registered before this script loaded
if (window.EchoAdsAudioPlayersPendingInit?.length) {
  const pending = [...window.EchoAdsAudioPlayersPendingInit]
  window.EchoAdsAudioPlayersPendingInit = []

  pending.forEach((playerId) => {
    if (window.EchoAdsAudioPlayers?.[playerId]) {
      init(playerId)
    }
  })
}
