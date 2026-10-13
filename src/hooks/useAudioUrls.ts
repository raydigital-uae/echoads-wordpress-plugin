import { useState, useEffect } from 'react'
import { fetchAudioUrls } from '../lib/api'
import { getVisitorId } from '../lib/session'
import type { AudioData } from '../types'

// Replaces the server-rendered ad with a fresh per-visitor one; keeps the rendered values on failure.
export const useAudioUrls = (renderedAudioData: AudioData) => {
  const endpoint = renderedAudioData.audioUrlsEndpoint
  const [audioData, setAudioData] = useState(renderedAudioData)
  const [isLoading, setIsLoading] = useState(!!endpoint)

  useEffect(() => {
    if (!endpoint) return
    let cancelled = false

    getVisitorId()
      .then((visitorId) =>
        fetchAudioUrls(endpoint, renderedAudioData.apiKey, visitorId)
      )
      .then((urls) => {
        if (cancelled) return
        if (urls) setAudioData({ ...renderedAudioData, ...urls })
        setIsLoading(false)
      })

    return () => {
      cancelled = true
    }
  }, [renderedAudioData, endpoint])

  return { audioData, isLoading }
}
