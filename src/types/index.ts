export interface AudioData {
  preRoll: string
  article: string
  postRoll: string
  prerollTrackingUrl: string
  postrollTrackingUrl: string
  apiKey: string
  statusEndpoint: string
  configEndpoint: string
  preRollAudioId: string | null
  postRollAudioId: string | null
  articleAudioId: string | null
}

export interface PlayerConfig {
  language: {
    code: string
  }
}

export interface AudioTrack {
  url: string
  name: string
  trackingUrl: string | null
  campaignAudioId: string | null
  allowSeeking: boolean
}

export interface TrackingPayload {
  campaignAudioId: string | null
  playSessionId: string
  visitorId: string | null
  playPositionSeconds: number
}

export type AudioStatus =
  | 'COMPLETED'
  | 'PENDING'
  | 'PROCESSING'
  | 'FAILED'
  | 'SKIPPED'
  | string

export interface StatusResponse {
  success?: boolean
  data?: {
    audioStatus: AudioStatus
  }
  audioStatus?: AudioStatus
}
