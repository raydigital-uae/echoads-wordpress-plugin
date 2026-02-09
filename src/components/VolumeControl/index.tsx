import { useState, useRef, useEffect, CSSProperties } from 'react';
import type { Translations } from '../../constants/translations';

interface VolumeControlProps {
  volume: number;
  isMuted: boolean;
  onVolumeChange: (volume: number) => void;
  onToggleMute: () => void;
  translations: Translations;
}

export const VolumeControl = ({ volume, isMuted, onVolumeChange, onToggleMute, translations }: VolumeControlProps) => {
  const [isOpen, setIsOpen] = useState(false);
  const controlRef = useRef<HTMLDivElement>(null);
  const volumePercent = Math.round(volume * 100);

  const handleVolumeChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const newVolume = parseInt(e.target.value) / 100;
    onVolumeChange(newVolume);
  };

  const handleVolumeButtonClick = (e: React.MouseEvent) => {
    e.stopPropagation();
    onToggleMute();
  };

  const handleVolumeButtonMouseEnter = (e: React.MouseEvent) => {
    e.stopPropagation();
    setIsOpen((prev) => !prev);
  };

  useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      if (controlRef.current && !controlRef.current.contains(e.target as Node)) {
        setIsOpen(false);
      }
    };

    if (isOpen) {
      document.addEventListener('click', handleClickOutside);
    }

    return () => {
      document.removeEventListener('click', handleClickOutside);
    };
  }, [isOpen]);

  const showMutedIcon = isMuted || volume === 0;

  return (
    <div ref={controlRef} className={`echoads-volume-control ${isOpen ? 'active' : ''}`}>
      <button
        className="echoads-volume-btn"
        title={translations.volumeTitle}
        aria-label={translations.volumeAria}
        aria-expanded={isOpen}
        tabIndex={0}
        onMouseEnter={handleVolumeButtonMouseEnter}
        onClick={handleVolumeButtonClick}
      >
        <svg
          className="volume-icon"
          style={{ display: showMutedIcon ? 'none' : 'block' }}
          width="24"
          height="24"
          viewBox="0 0 24 24"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
        >
          <path
            d="M3.15838 13.9306C2.44537 12.7423 2.44537 11.2577 3.15838 10.0694C3.37596 9.70674 3.73641 9.45272 4.1511 9.36978L5.84413 9.03117C5.94499 9.011 6.03591 8.95691 6.10176 8.87788L8.17085 6.39498C9.3534 4.97592 9.94468 4.26638 10.4723 4.45742C11 4.64846 11 5.57207 11 7.41928L11 16.5807C11 18.4279 11 19.3515 10.4723 19.5426C9.94468 19.7336 9.3534 19.0241 8.17085 17.605L6.10176 15.1221C6.03591 15.0431 5.94499 14.989 5.84413 14.9688L4.1511 14.6302C3.73641 14.5473 3.37596 14.2933 3.15838 13.9306Z"
            stroke="white"
            strokeWidth="2"
          />
          <path
            d="M15.5355 8.46447C16.4684 9.39732 16.9948 10.6611 17 11.9803C17.0052 13.2996 16.4888 14.5674 15.5633 15.5076"
            stroke="white"
            strokeWidth="2"
            strokeLinecap="round"
          />
          <path
            d="M19.6569 6.34314C21.1494 7.83572 21.9916 9.85769 21.9999 11.9685C22.0083 14.0793 21.182 16.1078 19.7012 17.6121"
            stroke="white"
            strokeWidth="2"
            strokeLinecap="round"
          />
        </svg>
        <svg
          className="volume-muted-icon"
          style={{ display: showMutedIcon ? 'block' : 'none' }}
          width="24"
          height="24"
          viewBox="0 0 24 24"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
        >
          <path
            d="M3.15838 13.9306C2.44537 12.7423 2.44537 11.2577 3.15838 10.0694C3.37596 9.70674 3.73641 9.45272 4.1511 9.36978L5.84413 9.03117C5.94499 9.011 6.03591 8.95691 6.10176 8.87788L8.17085 6.39498C9.3534 4.97592 9.94468 4.26638 10.4723 4.45742C11 4.64846 11 5.57207 11 7.41928L11 16.5807C11 18.4279 11 19.3515 10.4723 19.5426C9.94468 19.7336 9.3534 19.0241 8.17085 17.605L6.10176 15.1221C6.03591 15.0431 5.94499 14.989 5.84413 14.9688L4.1511 14.6302C3.73641 14.5473 3.37596 14.2933 3.15838 13.9306Z"
            stroke="white"
            strokeWidth="2"
          />
        </svg>
      </button>

      <div className="echoads-volume-popup">
        <div
          className="echoads-volume-slider-wrapper"
          style={{ '--volume-percent': `${volumePercent}%` } as CSSProperties}
        >
          <input
            type="range"
            className="echoads-volume-slider"
            min="0"
            max="100"
            value={volumePercent}
            onChange={handleVolumeChange}
            onClick={(e) => e.stopPropagation()}
            aria-label={translations.volumeLevelAria}
          />
          <div className="echoads-volume-track">
            <div
              className="echoads-volume-fill"
              style={{ height: `${volumePercent}%` }}
            />
          </div>
        </div>
      </div>
    </div>
  );
};
