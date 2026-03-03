import { Waveform } from '../Waveform'
import type { Translations } from '../../constants/translations'
import type { AudioTrack } from '../../types'
import PlayIcon from '../Icons/Play'
import PauseIcon from '../Icons/Pause'

interface PlayerControlsProps {
  tracks: AudioTrack[]
  currentTrack: number
  isPlaying: boolean
  currentTime: number
  duration: number
  translations: Translations
  bgColor: string
  onPlayPause: () => void
  onSeek: (time: number) => void
}

const formatTime = (seconds: number): string => {
  if (isNaN(seconds)) return '0:00'
  const minutes = Math.floor(seconds / 60)
  const secs = Math.floor(seconds % 60)
  return `${minutes}:${secs < 10 ? '0' : ''}${secs}`
}

export const PlayerControls = ({
  tracks,
  currentTrack,
  isPlaying,
  currentTime,
  duration,
  translations,
  bgColor,
  onPlayPause,
  onSeek,
}: PlayerControlsProps) => {
  const allowSeeking = tracks[currentTrack]?.allowSeeking || false

  const handleKeyDown = (e: React.KeyboardEvent) => {
    switch (e.key) {
      case ' ':
      case 'Enter':
        e.preventDefault()
        onPlayPause()
        break
      case 'ArrowLeft':
        e.preventDefault()
        if (allowSeeking && duration) {
          onSeek(Math.max(0, currentTime - 10))
        }
        break
      case 'ArrowRight':
        e.preventDefault()
        if (allowSeeking && duration) {
          onSeek(Math.min(duration, currentTime + 10))
        }
        break
    }
  }

  return (
    <div
      className='echoads-audio-player'
      style={{ background: bgColor }}
      tabIndex={0}
      role='region'
      aria-label={translations.audioPlayerAria}
      onKeyDown={handleKeyDown}
    >
      <button
        className='echoads-play-pause-btn'
        title={translations.playPauseTitle}
        aria-label={isPlaying ? translations.pause : translations.play}
        tabIndex={0}
        onClick={onPlayPause}
      >
        <PlayIcon className='play-icon' isPlaying={isPlaying} />

        <PauseIcon isPlaying={isPlaying} />
      </button>

      <Waveform
        currentTime={currentTime}
        duration={duration}
        onSeek={onSeek}
        allowSeeking={allowSeeking}
        translations={translations}
      />

      <div className='echoads-time-display'>
        <span>{formatTime(currentTime)}</span>
      </div>
    </div>
  )
}
