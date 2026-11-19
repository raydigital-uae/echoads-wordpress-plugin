import { expect, test } from 'bun:test'
import {
  TRANSLATIONS,
  translateStatus,
  translateTrackName,
} from './translations'

test('screen-reader status follows the player language', () => {
  expect(translateStatus('Ready', TRANSLATIONS.ar)).toBe('جاهز')
  expect(translateStatus('Playing', TRANSLATIONS.en)).toBe('Playing')
  expect(translateStatus('Something new', TRANSLATIONS.ar)).toBe(
    'Something new'
  )
})

test('screen-reader track names follow the player language', () => {
  expect(translateTrackName('Pre-Roll Ad', TRANSLATIONS.ar)).toBe(
    'إعلان قبل الخبر'
  )
  expect(translateTrackName('Article Audio', TRANSLATIONS.ar)).toBe(
    'الخبر الصوتي'
  )
  expect(translateTrackName('Post-Roll Ad', TRANSLATIONS.en)).toBe(
    'Post-roll ad'
  )
  expect(translateTrackName(undefined, TRANSLATIONS.ar)).toBe(
    TRANSLATIONS.ar.audioPlayerAria
  )
})

test('every language has every string', () => {
  const keys = Object.keys(TRANSLATIONS.en).sort()
  for (const [lang, strings] of Object.entries(TRANSLATIONS)) {
    expect([lang, Object.keys(strings).sort()]).toEqual([lang, keys])
  }
})
