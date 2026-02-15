import { useState, useRef, useCallback, useEffect } from 'react';
import type { AudioData, AudioTrack } from '../types';

export const useAudioPlayer = (audioData: AudioData) => {
  const audioRef = useRef<HTMLAudioElement>(null);
  const [currentTrack, setCurrentTrack] = useState(0);
  const [isPlaying, setIsPlaying] = useState(false);
  const [currentTime, setCurrentTime] = useState(0);
  const [duration, setDuration] = useState(0);
  const [status, setStatus] = useState('Ready');
  const [volume, setVolume] = useState(0.8);
  const [isMuted, setIsMuted] = useState(false);
  const [fiveSecondTrackingSent, setFiveSecondTrackingSent] = useState(false);

  const tracks: AudioTrack[] = [
    { url: audioData.preRoll, name: 'Pre-Roll Ad', trackingUrl: audioData.prerollTrackingUrl, campaignAudioId: audioData.preRollAudioId, allowSeeking: false },
    { url: audioData.article, name: 'Article Audio', trackingUrl: null, campaignAudioId: audioData.articleAudioId, allowSeeking: true },
    { url: audioData.postRoll, name: 'Post-Roll Ad', trackingUrl: audioData.postrollTrackingUrl, campaignAudioId: audioData.postRollAudioId, allowSeeking: false }
  ].filter(track => track.url);

  const loadTrack = useCallback((index: number) => {
    if (index >= tracks.length || index < 0 || !audioRef.current) return;
    
    setCurrentTrack(index);
    setFiveSecondTrackingSent(false);
    setStatus('Loading...');
    
    audioRef.current.src = tracks[index].url;
    audioRef.current.load();
  }, [tracks]);

  const play = useCallback(() => {
    if (!audioRef.current) return;
    
    const playPromise = audioRef.current.play();
    if (playPromise !== undefined) {
      playPromise.catch((error: any) => {
        console.error('Play failed:', error);
        if (error && error.name === 'NotAllowedError') {
          // Autoplay blocked by browser policy; keep player usable
          setStatus('Ready');
          return;
        }
        setStatus('Error');
      });
    }
  }, []);

  const pause = useCallback(() => {
    if (!audioRef.current) return;
    audioRef.current.pause();
  }, []);

  const seek = useCallback((time: number) => {
    if (!audioRef.current) return;
    audioRef.current.currentTime = time;
  }, []);

  const changeVolume = useCallback((newVolume: number) => {
    if (!audioRef.current) return;
    const vol = Math.max(0, Math.min(1, newVolume));
    audioRef.current.volume = vol;
    audioRef.current.muted = vol === 0;
    setVolume(vol);
    setIsMuted(vol === 0);
  }, []);

  const toggleMute = useCallback(() => {
    if (!audioRef.current) return;
    
    if (isMuted) {
      audioRef.current.volume = volume;
      audioRef.current.muted = false;
      setIsMuted(false);
    } else {
      audioRef.current.volume = 0;
      audioRef.current.muted = true;
      setIsMuted(true);
    }
  }, [isMuted, volume]);

  useEffect(() => {
    const audio = audioRef.current;
    if (!audio) return;

    const handleLoadedMetadata = () => {
      setDuration(audio.duration);
      setStatus('Ready');
    };

    const handleTimeUpdate = () => {
      setCurrentTime(audio.currentTime);
      
      if (!fiveSecondTrackingSent && Math.floor(audio.currentTime) >= 5) {
        setFiveSecondTrackingSent(true);
      }
    };

    const handlePlay = () => {
      setIsPlaying(true);
      setStatus('Playing');
    };

    const handlePause = () => {
      setIsPlaying(false);
      setStatus('Paused');
    };

    const handleEnded = () => {
      if (currentTrack < tracks.length - 1) {
        loadTrack(currentTrack + 1);
        setTimeout(() => {
          play();
        }, 100);
      } else {
        setIsPlaying(false);
        setStatus('Finished');
        setCurrentTime(0);
      }
    };

    const handleWaiting = () => {
      setStatus('Buffering...');
    };

    const handleCanPlay = () => {
      if (!isPlaying) {
        setStatus('Ready');
      }
    };

    const handleError = () => {
      setStatus('Error');
      console.error('Audio error:', audio.error);
    };

    audio.addEventListener('loadedmetadata', handleLoadedMetadata);
    audio.addEventListener('timeupdate', handleTimeUpdate);
    audio.addEventListener('play', handlePlay);
    audio.addEventListener('pause', handlePause);
    audio.addEventListener('ended', handleEnded);
    audio.addEventListener('waiting', handleWaiting);
    audio.addEventListener('canplay', handleCanPlay);
    audio.addEventListener('error', handleError);

    return () => {
      audio.removeEventListener('loadedmetadata', handleLoadedMetadata);
      audio.removeEventListener('timeupdate', handleTimeUpdate);
      audio.removeEventListener('play', handlePlay);
      audio.removeEventListener('pause', handlePause);
      audio.removeEventListener('ended', handleEnded);
      audio.removeEventListener('waiting', handleWaiting);
      audio.removeEventListener('canplay', handleCanPlay);
      audio.removeEventListener('error', handleError);
    };
  }, [currentTrack, tracks.length, loadTrack, play, isPlaying, fiveSecondTrackingSent]);

  return {
    audioRef,
    tracks,
    currentTrack,
    isPlaying,
    currentTime,
    duration,
    status,
    volume,
    isMuted,
    fiveSecondTrackingSent,
    loadTrack,
    play,
    pause,
    seek,
    changeVolume,
    toggleMute,
    setStatus
  };
};
