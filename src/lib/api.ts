import type {
  AudioData,
  PlayerConfig,
  StatusResponse,
  TrackingPayload,
} from '../types'

const CONFIG_CACHE_KEY = 'echoads_player_config'

export const fetchPlayerConfig = async (
  configEndpoint: string,
  apiKey: string,
  pluginVersion?: string
): Promise<PlayerConfig> => {
  if (!configEndpoint) {
    return Promise.resolve({
      language: { code: 'en' },
    })
  }

  if (typeof (window as any)[CONFIG_CACHE_KEY] === 'undefined') {
    ;(window as any)[CONFIG_CACHE_KEY] = fetch(configEndpoint, {
      method: 'GET',
      headers: {
        ...(apiKey ? { 'x-api-key': apiKey } : {}),
        ...(pluginVersion ? { 'x-plugin-version': pluginVersion } : {}),
      },
      signal: AbortSignal.timeout(8000),
    })
      .then(async (response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`)
        }
        const json = await response.json()
        const data = json && json.data ? json.data : json
        return {
          language: data && data.language ? data.language : { code: 'en' },
        }
      })
      .catch(() => {
        return {
          language: { code: 'en' },
        }
      })
  }

  return (window as any)[CONFIG_CACHE_KEY]
}

export type AudioUrls = Pick<
  AudioData,
  | 'preRoll'
  | 'article'
  | 'postRoll'
  | 'preRollAudioId'
  | 'postRollAudioId'
  | 'articleAudioId'
>

// Resolved in the browser so a page cache can't pin a stale/deleted campaign.
// Returns null on any failure so the caller falls back to the server-rendered values.
export const fetchAudioUrls = async (
  audioUrlsEndpoint: string,
  apiKey: string,
  visitorId: string | null
): Promise<AudioUrls | null> => {
  try {
    const url = new URL(audioUrlsEndpoint)
    if (visitorId) url.searchParams.set('visitorId', visitorId)

    const response = await fetch(url.toString(), {
      method: 'GET',
      headers: apiKey ? { 'x-api-key': apiKey } : {},
      cache: 'no-store',
      signal: AbortSignal.timeout(8000),
    })

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    // Same response shapes as EchoAds_Audio_Player::parse_audio_response
    const json = await response.json()
    const data = json && json.data ? json.data : json
    const urls = data && data.audioUrls ? data.audioUrls : data
    if (
      !urls ||
      (!urls.preRollAudioLink &&
        !urls.postRollAudioLink &&
        !urls.articleAudioLink)
    ) {
      throw new Error('No audio links in response')
    }

    return {
      preRoll: urls.preRollAudioLink || '',
      article: urls.articleAudioLink || '',
      postRoll: urls.postRollAudioLink || '',
      preRollAudioId: urls.preRollAudioId ?? null,
      postRollAudioId: urls.postRollAudioId ?? null,
      articleAudioId: urls.articleAudioId ?? null,
    }
  } catch (error) {
    console.error('Error fetching audio URLs, using rendered fallback:', error)
    return null
  }
}

export const checkAudioStatus = async (
  statusEndpoint: string,
  apiKey: string
): Promise<{ canPlay: boolean; status: string }> => {
  if (!statusEndpoint || !apiKey) {
    return { canPlay: true, status: 'COMPLETED' }
  }

  try {
    const response = await fetch(statusEndpoint, {
      method: 'GET',
      headers: {
        'x-api-key': apiKey,
      },
      signal: AbortSignal.timeout(10000),
    })

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    const json: StatusResponse = await response.json()

    let status = null
    if (json.success && json.data && json.data.audioStatus) {
      status = json.data.audioStatus
    } else if (json.audioStatus) {
      status = json.audioStatus
    }

    if (status === 'COMPLETED') {
      return { canPlay: true, status: 'COMPLETED' }
    } else {
      return { canPlay: false, status: status || 'Unknown' }
    }
  } catch (error) {
    console.error('Error checking audio status:', error)
    return { canPlay: true, status: 'Error' }
  }
}

export const sendTracking = async (
  url: string,
  apiKey: string,
  payload: TrackingPayload
): Promise<void> => {
  if (!url) return

  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        ...(apiKey ? { 'x-api-key': apiKey } : {}),
      },
      body: JSON.stringify(payload),
    })

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    console.log('Tracking call successful')
  } catch (error) {
    console.error('Tracking call failed:', error)
  }
}
