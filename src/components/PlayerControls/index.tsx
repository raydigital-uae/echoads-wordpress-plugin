import { Waveform } from '../Waveform';
import { VolumeControl } from '../VolumeControl';
import type { Translations } from '../../constants/translations';
import type { AudioTrack } from '../../types';

interface PlayerControlsProps {
  tracks: AudioTrack[];
  currentTrack: number;
  isPlaying: boolean;
  currentTime: number;
  duration: number;
  volume: number;
  isMuted: boolean;
  translations: Translations;
  bgColor: string;
  onPlayPause: () => void;
  onSeek: (time: number) => void;
  onVolumeChange: (volume: number) => void;
  onToggleMute: () => void;
}

const formatTime = (seconds: number): string => {
  if (isNaN(seconds)) return '0:00';
  const minutes = Math.floor(seconds / 60);
  const secs = Math.floor(seconds % 60);
  return `${minutes}:${secs < 10 ? '0' : ''}${secs}`;
};

export const PlayerControls = ({
  tracks,
  currentTrack,
  isPlaying,
  currentTime,
  duration,
  volume,
  isMuted,
  translations,
  bgColor,
  onPlayPause,
  onSeek,
  onVolumeChange,
  onToggleMute
}: PlayerControlsProps) => {
  const allowSeeking = tracks[currentTrack]?.allowSeeking || false;

  const handleKeyDown = (e: React.KeyboardEvent) => {
    switch (e.key) {
      case ' ':
      case 'Enter':
        e.preventDefault();
        onPlayPause();
        break;
      case 'ArrowLeft':
        e.preventDefault();
        if (allowSeeking && duration) {
          onSeek(Math.max(0, currentTime - 10));
        }
        break;
      case 'ArrowRight':
        e.preventDefault();
        if (allowSeeking && duration) {
          onSeek(Math.min(duration, currentTime + 10));
        }
        break;
      case 'ArrowUp':
        e.preventDefault();
        onVolumeChange(Math.min(1, volume + 0.1));
        break;
      case 'ArrowDown':
        e.preventDefault();
        onVolumeChange(Math.max(0, volume - 0.1));
        break;
      case 'm':
      case 'M':
        e.preventDefault();
        onToggleMute();
        break;
    }
  };

  return (
    <div
      className="echoads-audio-player"
      style={{ background: bgColor }}
      tabIndex={0}
      role="region"
      aria-label={translations.audioPlayerAria}
      onKeyDown={handleKeyDown}
    >
      <button
        className="echoads-play-pause-btn"
        title={translations.playPauseTitle}
        aria-label={isPlaying ? translations.pause : translations.play}
        tabIndex={0}
        onClick={onPlayPause}
      >
        <svg
          className="play-icon"
          style={{ display: isPlaying ? 'none' : 'block' }}
          width="24"
          height="24"
          viewBox="0 0 24 24"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
        >
          <path
            d="M16.2111 11.1056L9.73666 7.86833C8.93878 7.46939 8 8.04958 8 8.94164V15.0584C8 15.9504 8.93878 16.5306 9.73666 16.1317L16.2111 12.8944C16.9482 12.5259 16.9482 11.4741 16.2111 11.1056Z"
            fill="white"
            stroke="white"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
          />
        </svg>

        <svg
          className="pause-icon"
          style={{ display: isPlaying ? 'block' : 'none' }}
          width="24"
          height="24"
          viewBox="0 0 24 24"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
        >
          <rect x="6" y="5" width="4" height="14" rx="1" fill="white" />
          <rect x="14" y="5" width="4" height="14" rx="1" fill="white" />
        </svg>
      </button>

      <Waveform
        currentTime={currentTime}
        duration={duration}
        onSeek={onSeek}
        allowSeeking={allowSeeking}
        translations={translations}
      />

      <div className="echoads-time-display">
        <span>{formatTime(currentTime)}</span>
      </div>

      <VolumeControl
        volume={volume}
        isMuted={isMuted}
        onVolumeChange={onVolumeChange}
        onToggleMute={onToggleMute}
        translations={translations}
      />
    </div>
  );
};
