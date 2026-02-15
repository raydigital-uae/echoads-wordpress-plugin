# EchoAds Audio Player - React Migration

## Overview

The audio player has been successfully migrated from vanilla JavaScript (878 lines) to a modern React + TypeScript + Vite architecture.

## What Changed

### Before
- Single monolithic file: `assets/js/audio-player.js` (878 lines)
- jQuery dependency for AJAX
- Manual DOM manipulation
- Hard to maintain and test

### After
- Modular React components with TypeScript
- Modern hooks-based architecture
- Native `fetch()` API (no jQuery)
- Bundled with Vite for optimal performance
- Much easier to maintain and extend

## Project Structure

```
echoads-wordpress-plugin/
├── src/
│   ├── main.tsx                      # Entry point
│   ├── App.tsx                       # Main AudioPlayer component
│   ├── components/
│   │   ├── ListenButton/             # Initial CTA button
│   │   ├── PlayerControls/           # Main player UI
│   │   ├── Waveform/                 # Waveform visualization
│   │   └── VolumeControl/            # Volume popup
│   ├── hooks/
│   │   ├── useConfig.ts              # Config API fetching
│   │   ├── useTranslations.ts        # i18n support
│   │   ├── useTracking.ts            # Analytics tracking
│   │   └── useAudioPlayer.ts         # Audio playback logic
│   ├── lib/
│   │   ├── api.ts                    # API functions (fetch-based)
│   │   └── session.ts                # Session & visitor ID
│   ├── types/
│   │   └── index.ts                  # TypeScript interfaces
│   └── constants/
│       └── translations.ts           # Translation strings
├── assets/
│   ├── dist/                         # Build output (gitignored)
│   │   ├── echoads-audio-player.js   # Bundled JS (516KB)
│   │   └── echoads-audio-player.css  # Bundled CSS (8.8KB)
│   ├── css/
│   │   └── audio-player.css          # Original CSS (imported by Vite)
│   └── js/
│       └── audio-player.js.backup    # Original vanilla JS (backup)
├── package.json
├── vite.config.ts
└── tsconfig.json
```

## Development

### Install Dependencies
```bash
bun install
```

### Build for Production
```bash
bun run build
```

### Development Mode (watch)
```bash
bun run dev
```

## Features Preserved

All original functionality has been maintained:

- ✅ Config API fetch + cache
- ✅ Hide player until config loads (no EN→AR flash)
- ✅ CLICK_TO_PLAY: listen button → show player, play when canplay
- ✅ AUTOPLAY: show player, check status, auto-play
- ✅ Pre-roll, article, post-roll tracks with seeking rules
- ✅ Status check API before play
- ✅ Tracking: start + 5-second events, visitorId, sessionId
- ✅ Translations (en/ar), RTL support
- ✅ Keyboard and touch support
- ✅ Waveform seeking for article track only
- ✅ Volume popup, mute, slider

## WordPress Integration

The PHP file (`includes/class-audio-player.php`) has been updated to:
1. Render a minimal wrapper div
2. Enqueue the built React bundle from `assets/dist/`
3. Pass audio data via inline script (same as before)

React mounts into the wrapper and renders the entire player UI.

## Dependencies

- **React 18.2.0** - UI library
- **@fingerprintjs/fingerprintjs 4.2.0** - Visitor identification (bundled, no CDN)
- **Vite 5.1.0** - Build tool
- **TypeScript 5.2.2** - Type safety

## Rollback

If you need to rollback to the vanilla JS version:

1. Restore the backup:
   ```bash
   mv assets/js/audio-player.js.backup assets/js/audio-player.js
   ```

2. Revert PHP changes in `includes/class-audio-player.php`:
   - Restore full HTML markup in `render_audio_player()`
   - Update `enqueue_assets()` to load `assets/js/audio-player.js` instead of `assets/dist/`

## Notes

- The built files in `assets/dist/` are gitignored. Run `npm run build` after pulling changes.
- CSS is imported in `src/main.tsx` and bundled by Vite.
- The bundle size is larger (516KB) due to React, but gzips to 168KB.
- No jQuery dependency anymore - all AJAX uses native `fetch()`.
