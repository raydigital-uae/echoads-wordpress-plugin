const RTL_LANGUAGES = ['ar', 'he', 'fa', 'ur']

/** "en", "EN", "en-US" and "en_US" all become "en"; anything else becomes "". */
export const normalizeLanguage = (code?: string | null): string => {
  const primary = (code ?? '').trim().toLowerCase().split(/[-_]/)[0]
  return /^[a-z]{2,3}$/.test(primary) ? primary : ''
}

/**
 * The language the player shows. On a multilingual site the page passes the post's
 * own language, which wins: an English post on an Arabic-first site plays in English.
 * Otherwise it is the website's language from the EchoAds config, then English.
 */
export const resolvePlayerLanguage = (
  postLanguage?: string | null,
  configLanguage?: string | null
): string =>
  normalizeLanguage(postLanguage) || normalizeLanguage(configLanguage) || 'en'

export const isRtlLanguage = (code: string): boolean =>
  RTL_LANGUAGES.includes(normalizeLanguage(code))
