export interface AudioData {
  preRoll: string
  article: string
  postRoll: string
  prerollTrackingUrl: string
  postrollTrackingUrl: string
  apiKey: string
  configEndpoint: string
  audioUrlsEndpoint?: string
  preRollAudioId: string | null
  postRollAudioId: string | null
  articleAudioId: string | null
  articleExternalId: string
  pluginVersion: string
}

export interface PlayerConfig {
  language: {
    code: string
  }
}

export type AdSlot = 'INTRO' | 'OUTRO'

export interface AudioTrack {
  url: string
  name: string
  trackingUrl: string | null
  campaignAudioId: string | null
  allowSeeking: boolean
  adSlot?: AdSlot
}

export interface TrackingPayload {
  campaignAudioId: string | null
  playSessionId: string
  visitorId: string | null
  playPositionSeconds: number
  articleExternalId?: string
  pluginVersion?: string
  // Lets the backend tell intro from outro plays of the default (house) ad, which have no campaignAudioId.
  adSlot?: AdSlot
}
