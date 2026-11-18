import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import { resolve } from 'path'
import { readFileSync } from 'fs'

// FingerprintJS is bundled into the player. Its MIT licence requires the
// notice to go with every copy, but the minifier drops the library's own
// header comment, so it is added back as a /*! */ comment, which is kept.
// The build fails if the installed version is not MIT: 4.x is BUSL-1.1.
const fingerprintjsDir = resolve(
  __dirname,
  'node_modules/@fingerprintjs/fingerprintjs'
)
const fingerprintjsPackage = JSON.parse(
  readFileSync(resolve(fingerprintjsDir, 'package.json'), 'utf8')
)
if (fingerprintjsPackage.license !== 'MIT') {
  throw new Error(
    `@fingerprintjs/fingerprintjs ${fingerprintjsPackage.version} is licensed ` +
      `${fingerprintjsPackage.license}, not MIT. Use 5.0.0 or later.`
  )
}
const fingerprintjsNotice = [
  `/*! FingerprintJS v${fingerprintjsPackage.version} (https://github.com/fingerprintjs/fingerprintjs)`,
  ...readFileSync(resolve(fingerprintjsDir, 'LICENSE'), 'utf8')
    .trim()
    .split('\n')
    .map((line) => ` * ${line}`.trimEnd()),
  ' */',
].join('\n')

export default defineConfig({
  plugins: [react()],
  define: {
    'process.env.NODE_ENV': JSON.stringify(
      process.env.NODE_ENV ?? 'production'
    ),
  },
  build: {
    outDir: 'assets/dist',
    emptyOutDir: true,
    lib: {
      entry: resolve(__dirname, 'src/main.tsx'),
      name: 'EchoAdsAudioController',
      formats: ['iife'],
      fileName: () => 'echoads-audio-player.js',
    },
    rollupOptions: {
      external: [],
      output: {
        banner: fingerprintjsNotice,
        intro: "var process = { env: { NODE_ENV: 'production' } };",
        globals: {},
        assetFileNames: (assetInfo) => {
          if (assetInfo.name === 'style.css') {
            return 'echoads-audio-player.css'
          }
          return assetInfo.name || 'asset'
        },
      },
    },
  },
})
