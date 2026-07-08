import { useState, useRef, useCallback, useEffect } from 'react'
import type { AudioData, AudioTrack } from '../types'

export const useAudioPlayer = (audioData: AudioData) => {
  const audioRef = useRef<HTMLAudioElement>(null)
  const [currentTrack, setCurrentTrack] = useState(0)
  const [isPlaying, setIsPlaying] = useState(false)
  const [currentTime, setCurrentTime] = useState(0)
  const [duration, setDuration] = useState(0)
  const [status, setStatus] = useState('Ready')
  const [fiveSecondTrackingSent, setFiveSecondTrackingSent] = useState(false)
  const [completedTrack, setCompletedTrack] = useState<{
    index: number
    positionSeconds: number
  } | null>(null)

  const tracks: AudioTrack[] = [
    {
      url: audioData.preRoll,
      name: 'Pre-Roll Ad',
      trackingUrl: audioData.prerollTrackingUrl,
      campaignAudioId: audioData.preRollAudioId,
      allowSeeking: false,
    },
    {
      url: audioData.article,
      name: 'Article Audio',
      trackingUrl: null,
      campaignAudioId: audioData.articleAudioId,
      allowSeeking: true,
    },
    {
      url: audioData.postRoll,
      name: 'Post-Roll Ad',
      trackingUrl: audioData.postrollTrackingUrl,
      campaignAudioId: audioData.postRollAudioId,
      allowSeeking: false,
    },
  ].filter((track) => track.url)

  const loadTrack = useCallback(
    (index: number) => {
      if (index >= tracks.length || index < 0 || !audioRef.current) return

      setCurrentTrack(index)
      setFiveSecondTrackingSent(false)
      setCompletedTrack(null)
      setStatus('Loading...')

      audioRef.current.src = tracks[index].url
      audioRef.current.load()
    },
    [tracks]
  )

  const play = useCallback(() => {
    if (!audioRef.current) return

    const playPromise = audioRef.current.play()
    if (playPromise !== undefined) {
      playPromise.catch((error: any) => {
        console.error('Play failed:', error)
        if (error && error.name === 'NotAllowedError') {
          setStatus('Ready')
          return
        }
        setStatus('Error')
      })
    }
  }, [])

  const pause = useCallback(() => {
    if (!audioRef.current) return
    audioRef.current.pause()
  }, [])

  const seek = useCallback((time: number) => {
    if (!audioRef.current) return
    audioRef.current.currentTime = time
  }, [])

  useEffect(() => {
    const audio = audioRef.current
    if (!audio) return

    const handleLoadedMetadata = () => {
      setDuration(audio.duration)
      setStatus('Ready')
    }

    const handleTimeUpdate = () => {
      setCurrentTime(audio.currentTime)

      if (!fiveSecondTrackingSent && Math.floor(audio.currentTime) >= 5) {
        setFiveSecondTrackingSent(true)
      }
    }

    const handlePlay = () => {
      setIsPlaying(true)
      setStatus('Playing')
    }

    const handlePause = () => {
      setIsPlaying(false)
      setStatus('Paused')
    }

    const handleEnded = () => {
      setCompletedTrack({
        index: currentTrack,
        positionSeconds: Math.floor(audio.duration || audio.currentTime),
      })
      if (currentTrack < tracks.length - 1) {
        loadTrack(currentTrack + 1)
        setTimeout(() => {
          play()
        }, 100)
      } else {
        setIsPlaying(false)
        setStatus('Finished')
        setCurrentTime(0)
      }
    }

    const handleWaiting = () => {
      setStatus('Buffering...')
    }

    const handleCanPlay = () => {
      if (!isPlaying) {
        setStatus('Ready')
      }
    }

    const handleError = () => {
      setStatus('Error')
      console.error('Audio error:', audio.error)
    }

    audio.addEventListener('loadedmetadata', handleLoadedMetadata)
    audio.addEventListener('timeupdate', handleTimeUpdate)
    audio.addEventListener('play', handlePlay)
    audio.addEventListener('pause', handlePause)
    audio.addEventListener('ended', handleEnded)
    audio.addEventListener('waiting', handleWaiting)
    audio.addEventListener('canplay', handleCanPlay)
    audio.addEventListener('error', handleError)

    return () => {
      audio.removeEventListener('loadedmetadata', handleLoadedMetadata)
      audio.removeEventListener('timeupdate', handleTimeUpdate)
      audio.removeEventListener('play', handlePlay)
      audio.removeEventListener('pause', handlePause)
      audio.removeEventListener('ended', handleEnded)
      audio.removeEventListener('waiting', handleWaiting)
      audio.removeEventListener('canplay', handleCanPlay)
      audio.removeEventListener('error', handleError)
    }
  }, [
    currentTrack,
    tracks.length,
    loadTrack,
    play,
    isPlaying,
    fiveSecondTrackingSent,
  ])

  return {
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
    setStatus,
  }
}
