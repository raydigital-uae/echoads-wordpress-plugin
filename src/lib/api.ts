import type { PlayerConfig, StatusResponse, TrackingPayload } from '../types'

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
