import { useState, useRef, useEffect } from 'react'
import type { Translations } from '../../constants/translations'

interface WaveformProps {
  currentTime: number
  duration: number
  onSeek: (time: number) => void
  allowSeeking: boolean
  translations: Translations
}

export const Waveform = ({
  currentTime,
  duration,
  onSeek,
  allowSeeking,
  translations,
}: WaveformProps) => {
  const [isDragging, setIsDragging] = useState(false)
  const waveformRef = useRef<HTMLDivElement>(null)
  const progress = duration > 0 ? (currentTime / duration) * 100 : 0
  const totalBars = 24
  const activeBars = Math.floor((progress / 100) * totalBars)

  const handleSeek = (e: React.MouseEvent | React.TouchEvent) => {
    if (!allowSeeking || !waveformRef.current) return

    const rect = waveformRef.current.getBoundingClientRect()
    const clientX = 'touches' in e ? e.touches[0].clientX : e.clientX
    const clickX = clientX - rect.left
    const width = rect.width
    const clickPercent = Math.max(0, Math.min(1, clickX / width))

    if (duration) {
      onSeek(clickPercent * duration)
    }
  }

  const handleMouseDown = (e: React.MouseEvent) => {
    if (!allowSeeking) return
    setIsDragging(true)
    handleSeek(e)
  }

  const handleTouchStart = (e: React.TouchEvent) => {
    if (!allowSeeking) return
    e.preventDefault()
    setIsDragging(true)
    handleSeek(e)
  }

  useEffect(() => {
    const handleMouseMove = (e: MouseEvent) => {
      if (isDragging && allowSeeking && waveformRef.current) {
        const rect = waveformRef.current.getBoundingClientRect()
        const clickX = e.clientX - rect.left
        const width = rect.width
        const clickPercent = Math.max(0, Math.min(1, clickX / width))

        if (duration) {
          onSeek(clickPercent * duration)
        }
      }
    }

    const handleMouseUp = () => {
      setIsDragging(false)
    }

    if (isDragging) {
      document.addEventListener('mousemove', handleMouseMove)
      document.addEventListener('mouseup', handleMouseUp)
    }

    return () => {
      document.removeEventListener('mousemove', handleMouseMove)
      document.removeEventListener('mouseup', handleMouseUp)
    }
  }, [isDragging, allowSeeking, duration, onSeek])

  return (
    <div
      ref={waveformRef}
      className='echoads-waveform'
      role='slider'
      aria-label={translations.audioProgressAria}
      aria-valuemin={0}
      aria-valuemax={100}
      aria-valuenow={Math.round(progress)}
      tabIndex={0}
      style={{ cursor: allowSeeking ? 'pointer' : 'default' }}
      data-seeking-disabled={!allowSeeking}
      onClick={handleSeek}
      onMouseDown={handleMouseDown}
      onTouchStart={handleTouchStart}
    >
      <div className='echoads-waveform-bars'>
        {Array.from({ length: totalBars }).map((_, index) => (
          <div
            key={index}
            className={`echoads-bar ${index < activeBars ? 'active' : ''}`}
            data-index={index}
          />
        ))}
      </div>
    </div>
  )
}
