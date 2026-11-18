import { expect, mock, test } from 'bun:test'

const load = mock(async (_options?: { monitoring?: boolean }) => ({
  get: async () => ({ visitorId: 'fp-visitor' }),
}))

mock.module('@fingerprintjs/fingerprintjs', () => ({ default: { load } }))

test('loads FingerprintJS once, with its openfpcdn.io usage-stats request off', async () => {
  const { getVisitorId } = await import('./session')

  expect(await getVisitorId()).toBe('fp-visitor')
  expect(await getVisitorId()).toBe('fp-visitor')

  expect(load).toHaveBeenCalledTimes(1)
  expect(load.mock.calls[0][0]).toEqual({ monitoring: false })
})
