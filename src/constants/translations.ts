export interface Translations {
  listenToArticle: string;
  listenToArticleAria: string;
  audioPlayerAria: string;
  play: string;
  pause: string;
  playPauseTitle: string;
  audioProgressAria: string;
  volumeTitle: string;
  volumeAria: string;
  volumeLevelAria: string;
  statusReady: string;
  statusLoading: string;
  statusPlaying: string;
  statusPaused: string;
  statusError: string;
  statusBuffering: string;
  statusFinished: string;
  statusChecking: string;
  statusGenerating: string;
  statusNotReady: string;
  statusCheckFailed: string;
  statusFailed: string;
  statusSkipped: string;
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
    volumeTitle: 'Volume',
    volumeAria: 'Volume',
    volumeLevelAria: 'Volume level',
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
    statusSkipped: 'Audio generation skipped'
  },
  ar: {
    listenToArticle: 'استمع للخبر الآن',
    listenToArticleAria: 'استمع للخبر الآن',
    audioPlayerAria: 'مشغل صوتي',
    play: 'تشغيل',
    pause: 'إيقاف',
    playPauseTitle: 'تشغيل/إيقاف',
    audioProgressAria: 'تقدم التشغيل',
    volumeTitle: 'مستوى الصوت',
    volumeAria: 'مستوى الصوت',
    volumeLevelAria: 'مستوى الصوت',
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
    statusSkipped: 'تم تخطي إنشاء الصوت'
  }
};

export const STATUS_TO_TRANSLATION_KEY: Record<string, keyof Translations> = {
  'Ready': 'statusReady',
  'Loading...': 'statusLoading',
  'Playing': 'statusPlaying',
  'Paused': 'statusPaused',
  'Error': 'statusError',
  'Buffering...': 'statusBuffering',
  'Finished': 'statusFinished',
  'Checking status...': 'statusChecking',
  'Audio is being generated...': 'statusGenerating',
  'Audio not ready': 'statusNotReady',
  'Status check failed': 'statusCheckFailed',
  'Audio generation failed': 'statusFailed',
  'Audio generation skipped': 'statusSkipped'
};
