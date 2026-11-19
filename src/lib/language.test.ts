import { expect, test } from 'bun:test'
import {
  isRtlLanguage,
  normalizeLanguage,
  resolvePlayerLanguage,
} from './language'

test('normalizes language codes and locales', () => {
  expect(normalizeLanguage('en')).toBe('en')
  expect(normalizeLanguage(' EN-us ')).toBe('en')
  expect(normalizeLanguage('ar_SA')).toBe('ar')
  expect(normalizeLanguage('')).toBe('')
  expect(normalizeLanguage(undefined)).toBe('')
  expect(normalizeLanguage('english')).toBe('')
})

test("the post's language wins over the website's", () => {
  expect(resolvePlayerLanguage('en', 'ar')).toBe('en')
  expect(resolvePlayerLanguage('ar', 'en')).toBe('ar')
})

test("falls back to the website's language, then English", () => {
  expect(resolvePlayerLanguage('', 'ar')).toBe('ar')
  expect(resolvePlayerLanguage(undefined, undefined)).toBe('en')
  expect(resolvePlayerLanguage('not a code', null)).toBe('en')
})

test('right-to-left languages', () => {
  expect(isRtlLanguage('ar')).toBe(true)
  expect(isRtlLanguage('he')).toBe(true)
  expect(isRtlLanguage('fa-IR')).toBe(true)
  expect(isRtlLanguage('ur')).toBe(true)
  expect(isRtlLanguage('en')).toBe(false)
  expect(isRtlLanguage('fr')).toBe(false)
})
