import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { resolve } from 'path';

export default defineConfig({
  plugins: [react()],
  define: {
    'process.env.NODE_ENV': JSON.stringify(process.env.NODE_ENV ?? 'production'),
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
        intro: "var process = { env: { NODE_ENV: 'production' } };",
        globals: {},
        assetFileNames: (assetInfo) => {
          if (assetInfo.name === 'style.css') {
            return 'echoads-audio-player.css';
          }
          return assetInfo.name || 'asset';
        },
      },
    },
  },
});
