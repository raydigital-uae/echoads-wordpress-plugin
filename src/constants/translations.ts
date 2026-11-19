export interface Translations {
  listenToArticle: string
  listenToArticleAria: string
  audioPlayerAria: string
  play: string
  pause: string
  playPauseTitle: string
  audioProgressAria: string
  statusReady: string
  statusLoading: string
  statusPlaying: string
  statusPaused: string
  statusError: string
  statusBuffering: string
  statusFinished: string
  statusChecking: string
  statusGenerating: string
  statusNotReady: string
  statusCheckFailed: string
  statusFailed: string
  statusSkipped: string
  trackPreRoll: string
  trackArticle: string
  trackPostRoll: string
}

export const TRANSLATIONS: Record<string, Translations> = {
  en: {
    listenToArticle: 'Listen to this Article',
    listenToArticleAria: 'Listen to this article',
    audioPlayerAria: 'Audio Player',
    play: 'Play',
    pause: 'Pause',
    playPauseTitle: 'Play/Pause',
    audioProgressAria: 'Audio progress',
    statusReady: 'Ready',
    statusLoading: 'Loading...',
    statusPlaying: 'Playing',
    statusPaused: 'Paused',
    statusError: 'Error',
    statusBuffering: 'Buffering...',
    statusFinished: 'Finished',
    statusChecking: 'Checking status...',
    statusGenerating: 'Audio is being generated...',
    statusNotReady: 'Audio not ready',
    statusCheckFailed: 'Status check failed',
    statusFailed: 'Audio generation failed',
    statusSkipped: 'Audio generation skipped',
    trackPreRoll: 'Pre-roll ad',
    trackArticle: 'Article audio',
    trackPostRoll: 'Post-roll ad',
  },
  ar: {
    listenToArticle: 'استمع للخبر الآن',
    listenToArticleAria: 'استمع للخبر الآن',
    audioPlayerAria: 'مشغل صوتي',
    play: 'تشغيل',
    pause: 'إيقاف',
    playPauseTitle: 'تشغيل/إيقاف',
    audioProgressAria: 'تقدم التشغيل',
    statusReady: 'جاهز',
    statusLoading: 'جاري التحميل...',
    statusPlaying: 'جاري التشغيل',
    statusPaused: 'متوقف',
    statusError: 'خطأ',
    statusBuffering: 'جاري التخزين المؤقت...',
    statusFinished: 'انتهى',
    statusChecking: 'جاري التحقق...',
    statusGenerating: 'جاري إنشاء الصوت...',
    statusNotReady: 'الصوت غير جاهز',
    statusCheckFailed: 'فشل التحقق من الحالة',
    statusFailed: 'فشل إنشاء الصوت',
    statusSkipped: 'تم تخطي إنشاء الصوت',
    trackPreRoll: 'إعلان قبل الخبر',
    trackArticle: 'الخبر الصوتي',
    trackPostRoll: 'إعلان بعد الخبر',
  },
}

export const STATUS_TO_TRANSLATION_KEY: Record<string, keyof Translations> = {
  Ready: 'statusReady',
  'Loading...': 'statusLoading',
  Playing: 'statusPlaying',
  Paused: 'statusPaused',
  Error: 'statusError',
  'Buffering...': 'statusBuffering',
  Finished: 'statusFinished',
  'Checking status...': 'statusChecking',
  'Audio is being generated...': 'statusGenerating',
  'Audio not ready': 'statusNotReady',
  'Status check failed': 'statusCheckFailed',
  'Audio generation failed': 'statusFailed',
  'Audio generation skipped': 'statusSkipped',
}

// Track names come from useAudioPlayer in English; screen readers get them in the player's language.
export const TRACK_NAME_TO_TRANSLATION_KEY: Record<string, keyof Translations> =
  {
    'Pre-Roll Ad': 'trackPreRoll',
    'Article Audio': 'trackArticle',
    'Post-Roll Ad': 'trackPostRoll',
  }

export const translateStatus = (status: string, t: Translations): string => {
  const key = STATUS_TO_TRANSLATION_KEY[status]
  return key ? t[key] : status
}

export const translateTrackName = (
  name: string | undefined,
  t: Translations
): string => {
  if (!name) return t.audioPlayerAria
  const key = TRACK_NAME_TO_TRANSLATION_KEY[name]
  return key ? t[key] : name
}
