import { useState, useEffect } from 'react'
import { ListenButton } from './components/ListenButton'
import { PlayerControls } from './components/PlayerControls'
import { useConfig } from './hooks/useConfig'
import { useTranslations } from './hooks/useTranslations'
import { useAudioPlayer } from './hooks/useAudioPlayer'
import { useTracking } from './hooks/useTracking'
import { getOrCreatePlaySessionId } from './lib/session'
import { checkAudioStatus } from './lib/api'
import type { AudioData } from './types'

interface AudioPlayerProps {
  audioData: AudioData
  bgColor: string
}

export const AudioPlayer = ({ audioData, bgColor }: AudioPlayerProps) => {
  const [showPlayer, setShowPlayer] = useState(false)
  const [playSessionId] = useState(() => getOrCreatePlaySessionId())
  const [statusChecked, setStatusChecked] = useState(false)
  const [canPlay, setCanPlay] = useState(false)

  const { config, isLoading } = useConfig(
    audioData.configEndpoint,
    audioData.apiKey
  )
  const langCode = config?.language?.code || 'en'
  const translations = useTranslations(langCode)
  const isRtl = langCode.toLowerCase() === 'ar'

  const {
    audioRef,
    tracks,
    currentTrack,
    isPlaying,
    currentTime,
    duration,
    status,
    fiveSecondTrackingSent,
    loadTrack,
    play,
    pause,
    seek,
    setStatus,
  } = useAudioPlayer(audioData)

  const { sendTrackingOnce } = useTracking(
    audioData.apiKey,
    playSessionId,
    audioData.articleExternalId
  )

  // Check audio status
  const performStatusCheck = async () => {
    if (statusChecked) return canPlay

    setStatus('Checking status...')
    const result = await checkAudioStatus(
      audioData.statusEndpoint,
      audioData.apiKey
    )
    setStatusChecked(true)
    setCanPlay(result.canPlay)

    if (result.canPlay) {
      setStatus('Ready')
    } else {
      const status = result.status
      if (status === 'PENDING' || status === 'PROCESSING') {
        setStatus('Audio is being generated...')
      } else if (status === 'FAILED' || status === 'SKIPPED') {
        setStatus(`Audio generation ${status.toLowerCase()}`)
      } else {
        setStatus('Audio not ready')
      }
    }

    return result.canPlay
  }

  // Handle listen button click
  const handleListenClick = async () => {
    setShowPlayer(true)

    const canPlayAudio = await performStatusCheck()
    if (canPlayAudio && tracks.length > 0) {
      if (!audioRef.current?.src) {
        loadTrack(0)
      }

      // Wait for audio to be ready before playing
      const audio = audioRef.current
      if (audio) {
        if (audio.readyState >= 2) {
          play()
        } else {
          const onCanPlay = () => {
            audio.removeEventListener('canplay', onCanPlay)
            play()
          }
          audio.addEventListener('canplay', onCanPlay)
        }
      }
    }
  }

  // Handle play/pause toggle
  const handlePlayPause = () => {
    if (isPlaying) {
      pause()
    } else {
      play()
    }
  }

  // Send tracking when playing
  useEffect(() => {
    if (isPlaying && tracks[currentTrack]) {
      sendTrackingOnce(tracks[currentTrack], 0, currentTrack)
    }
  }, [isPlaying, currentTrack, tracks, sendTrackingOnce])

  // Send 5-second tracking
  useEffect(() => {
    if (fiveSecondTrackingSent && tracks[currentTrack]) {
      sendTrackingOnce(tracks[currentTrack], 5, currentTrack)
    }
  }, [fiveSecondTrackingSent, currentTrack, tracks, sendTrackingOnce])

  // Don't render anything until config is loaded
  if (isLoading) {
    return null
  }

  return (
    <div
      className={`echoads-player-wrapper ${isRtl ? 'echoads-rtl' : ''}`}
      dir={isRtl ? 'rtl' : 'ltr'}
    >
      {!showPlayer && (
        <ListenButton
          onClick={handleListenClick}
          translations={translations}
          bgColor={bgColor}
        />
      )}

      {showPlayer && (
        <PlayerControls
          tracks={tracks}
          currentTrack={currentTrack}
          isPlaying={isPlaying}
          currentTime={currentTime}
          duration={duration}
          translations={translations}
          bgColor={bgColor}
          onPlayPause={handlePlayPause}
          onSeek={seek}
        />
      )}

      <audio ref={audioRef} preload='metadata'>
        Your browser does not support the audio element.
      </audio>

      {/* Hidden elements for screen readers */}
      <span className='sr-only'>
        {tracks[currentTrack]?.name || 'Audio Player'}
      </span>
      <span className='sr-only'>{status}</span>
    </div>
  )
}
