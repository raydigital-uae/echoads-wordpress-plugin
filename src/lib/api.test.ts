import { afterEach, expect, mock, test } from 'bun:test'
import { fetchAudioUrls } from './api'

const realFetch = globalThis.fetch
const realConsoleError = console.error
const ENDPOINT = 'https://api.test/website-articles/audio-urls?externalId=42'

const mockFetch = (body: unknown, ok = true) => {
  const fn = mock(async (_url: string, _init?: RequestInit) => ({
    ok,
    status: ok ? 200 : 500,
    json: async () => body,
  }))
  globalThis.fetch = fn as unknown as typeof fetch
  return fn
}

afterEach(() => {
  globalThis.fetch = realFetch
  console.error = realConsoleError
})

test('reads the { success, data } shape and sends visitorId without caching', async () => {
  const fetchMock = mockFetch({
    success: true,
    data: {
      preRollAudioLink: 'pre.mp3',
      articleAudioLink: 'a.mp3',
      preRollAudioId: 7,
    },
  })

  expect(await fetchAudioUrls(ENDPOINT, 'key', 'visitor-1')).toEqual({
    preRoll: 'pre.mp3',
    article: 'a.mp3',
    postRoll: '',
    preRollAudioId: 7,
    postRollAudioId: null,
    articleAudioId: null,
  })
  const [url, init] = fetchMock.mock.calls[0]
  expect(url).toBe(`${ENDPOINT}&visitorId=visitor-1`)
  expect(init?.cache).toBe('no-store')
  expect(init?.headers).toEqual({ 'x-api-key': 'key' })
})

test('reads the data.audioUrls and bare shapes', async () => {
  mockFetch({
    success: true,
    data: { audioUrls: { postRollAudioLink: 'post.mp3' } },
  })
  expect((await fetchAudioUrls(ENDPOINT, 'key', null))?.postRoll).toBe(
    'post.mp3'
  )

  mockFetch({ articleAudioLink: 'a.mp3', articleAudioId: 3 })
  expect((await fetchAudioUrls(ENDPOINT, 'key', null))?.articleAudioId).toBe(3)
})

test('returns null so the player falls back to rendered values', async () => {
  console.error = () => {}

  mockFetch({}, false)
  expect(await fetchAudioUrls(ENDPOINT, 'key', null)).toBeNull()

  mockFetch({ success: true, data: {} })
  expect(await fetchAudioUrls(ENDPOINT, 'key', null)).toBeNull()

  globalThis.fetch = (async () => {
    throw new Error('network down')
  }) as unknown as typeof fetch
  expect(await fetchAudioUrls(ENDPOINT, 'key', null)).toBeNull()
})
