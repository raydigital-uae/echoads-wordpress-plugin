import { useState, useEffect } from 'react'
import { ListenButton } from './components/ListenButton'
import { PlayerControls } from './components/PlayerControls'
import { useConfig } from './hooks/useConfig'
import { useTranslations } from './hooks/useTranslations'
import { useAudioPlayer } from './hooks/useAudioPlayer'
import { useAudioUrls } from './hooks/useAudioUrls'
import { useTracking } from './hooks/useTracking'
import { getOrCreatePlaySessionId } from './lib/session'
import type { AudioData } from './types'

interface AudioPlayerProps {
  audioData: AudioData
  bgColor: string
}

export const AudioPlayer = ({ audioData, bgColor }: AudioPlayerProps) => {
  const [showPlayer, setShowPlayer] = useState(false)
  const [playSessionId] = useState(() => getOrCreatePlaySessionId())

  const { config, isLoading } = useConfig(
    audioData.configEndpoint,
    audioData.apiKey,
    audioData.pluginVersion
  )
  const { audioData: resolvedAudioData, isLoading: isAudioUrlsLoading } =
    useAudioUrls(audioData)
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
    completedTrack,
    loadTrack,
    play,
    pause,
    seek,
  } = useAudioPlayer(resolvedAudioData)

  const { sendTrackingOnce } = useTracking(
    audioData.apiKey,
    playSessionId,
    audioData.articleExternalId,
    audioData.pluginVersion
  )

  // play() must run synchronously in the click: browsers (Safari especially) block it after an await.
  const handleListenClick = () => {
    setShowPlayer(true)
    if (!audioRef.current?.src) {
      loadTrack(0)
    }
    play()
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

  // Send ad completion tracking
  useEffect(() => {
    if (completedTrack && tracks[completedTrack.index]) {
      sendTrackingOnce(
        tracks[completedTrack.index],
        completedTrack.positionSeconds,
        completedTrack.index
      )
    }
  }, [completedTrack, tracks, sendTrackingOnce])

  // Hide (not unmount) until resolved: <audio> must exist from mount so useAudioPlayer attaches its listeners.
  const isReady =
    !isLoading && !isAudioUrlsLoading && !!resolvedAudioData.article

  return (
    <div
      className={`echoads-player-wrapper ${isRtl ? 'echoads-rtl' : ''} ${isReady ? '' : 'echoads-hidden'}`}
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
