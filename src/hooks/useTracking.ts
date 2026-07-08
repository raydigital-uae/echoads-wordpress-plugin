import { useRef, useCallback } from 'react'
import { sendTracking } from '../lib/api'
import { getVisitorId } from '../lib/session'
import type { AudioTrack } from '../types'

export const useTracking = (
  apiKey: string,
  playSessionId: string,
  articleExternalId?: string,
  pluginVersion?: string
) => {
  const trackingSentRef = useRef<Record<string, boolean>>({})

  const sendTrackingOnce = useCallback(
    async (
      track: AudioTrack,
      playPositionSeconds: number,
      trackIndex: number
    ) => {
      if (!track || !track.trackingUrl) return

      const baseKey =
        track.campaignAudioId != null && track.campaignAudioId !== ''
          ? String(track.campaignAudioId)
          : `track-${trackIndex}`
      const key = `${baseKey}-${playPositionSeconds}`

      if (trackingSentRef.current[key]) return
      trackingSentRef.current[key] = true

      const visitorId = await getVisitorId()

      await sendTracking(track.trackingUrl, apiKey, {
        campaignAudioId: track.campaignAudioId,
        playSessionId,
        visitorId,
        playPositionSeconds,
        articleExternalId,
        pluginVersion,
      })
    },
    [apiKey, playSessionId, articleExternalId, pluginVersion]
  )

  return { sendTrackingOnce }
}
