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

// In the WordPress build, React comes from WordPress's own script handles
// (react, react-jsx-runtime and wp-element, enqueued as dependencies in
// includes/class-audio-player.php) instead of being bundled. WordPress.org's
// Plugin Check rejects plugins that bundle React. createRoot comes from
// wp.element, which keeps that API stable across WordPress's React upgrades.
const wordpressReactGlobals = {
  react: 'React',
  'react/jsx-runtime': 'ReactJSXRuntime',
  'react-dom/client': 'wp.element',
}

// Two builds of the same player:
// - `bun run build` (default): the WordPress plugin's copy, in assets/dist,
//   using WordPress's React.
// - `bun run build:standalone`: React bundled in, in build/standalone, for
//   pages without WordPress. The Drupal module loads this copy from EchoAds's
//   storage bucket. It must never go into the WordPress plugin zip.
export default defineConfig(({ mode }) => {
  const standalone = mode === 'standalone'

  return {
    plugins: [react()],
    define: {
      'process.env.NODE_ENV': JSON.stringify(
        process.env.NODE_ENV ?? 'production'
      ),
    },
    build: {
      outDir: standalone ? 'build/standalone' : 'assets/dist',
      emptyOutDir: true,
      lib: {
        entry: resolve(__dirname, 'src/main.tsx'),
        name: 'EchoAdsAudioController',
        formats: ['iife'],
        fileName: () => 'echoads-audio-player.js',
      },
      rollupOptions: {
        external: standalone ? [] : Object.keys(wordpressReactGlobals),
        output: {
          banner: fingerprintjsNotice,
          intro: "var process = { env: { NODE_ENV: 'production' } };",
          globals: standalone ? {} : wordpressReactGlobals,
          assetFileNames: (assetInfo) => {
            if (assetInfo.name === 'style.css') {
              return 'echoads-audio-player.css'
            }
            return assetInfo.name || 'asset'
          },
        },
      },
    },
  }
})
