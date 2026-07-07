import { useState, useEffect } from 'react'
import { fetchPlayerConfig } from '../lib/api'
import type { PlayerConfig } from '../types'

export const useConfig = (
  configEndpoint: string,
  apiKey: string,
  pluginVersion?: string
) => {
  const [config, setConfig] = useState<PlayerConfig | null>(null)
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    fetchPlayerConfig(configEndpoint, apiKey, pluginVersion)
      .then((fetchedConfig) => {
        setConfig(fetchedConfig)
        setIsLoading(false)
      })
      .catch((error) => {
        console.error('Failed to fetch config:', error)
        setConfig({
          language: { code: 'en' },
        })
        setIsLoading(false)
      })
  }, [configEndpoint, apiKey, pluginVersion])

  return { config, isLoading }
}
