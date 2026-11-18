#!/usr/bin/env bash
# Uploads the standalone player (`bun run build:standalone`) to the public bucket
# that the Drupal module loads it from.
#
#   scripts/upload-player.sh <version> [build-dir]
#
# - player/<version>/ keeps each release forever. It is never overwritten:
#   browsers and Google's edge cache keep it for a year.
# - player/v<major>/ always serves the latest release of that major version
#   (the Drupal module loads player/v1/), cached for 5 minutes.
#
# Needs gcloud signed in with write access to the bucket. The release workflow
# signs in through Workload Identity Federation (no stored key).
set -euo pipefail

version="${1:?usage: upload-player.sh <version> [build-dir]}"
src="${2:-build/standalone}"
if [[ ! "$version" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
  echo "Not a release version: $version" >&2
  exit 1
fi
major="${version%%.*}"
bucket="echoads-static-assets"
public="https://storage.googleapis.com/$bucket/player"
files=(echoads-audio-player.js echoads-audio-player.css)

for file in "${files[@]}"; do
  [[ -s "$src/$file" ]] || { echo "Missing $src/$file" >&2; exit 1; }
done
if grep -q 'FingerprintJS v4\.' "$src/echoads-audio-player.js"; then
  echo "This build bundles FingerprintJS 4 (BUSL-1.1); refusing to publish it." >&2
  exit 1
fi

sha256() { if command -v sha256sum >/dev/null; then sha256sum | cut -d' ' -f1; else shasum -a 256 | cut -d' ' -f1; fi; }
content_type() { [[ "$1" == *.js ]] && echo 'text/javascript; charset=utf-8' || echo 'text/css; charset=utf-8'; }
# What the public URL serves now (the query string skips Google's edge cache).
served_sha() { curl -fsS --compressed "$public/$1?check=$(date +%s%N)" 2>/dev/null | sha256 || true; }
upload() { # <file> <path> <cache-control> [extra gcloud args...]
  gcloud storage cp "$src/$1" "gs://$bucket/player/$2/$1" \
    --gzip-local-all \
    --content-type="$(content_type "$1")" \
    --cache-control="$3" \
    --custom-metadata="player-version=$version" \
    "${@:4}"
}

for file in "${files[@]}"; do
  local_sha="$(sha256 < "$src/$file")"
  if gcloud storage objects describe "gs://$bucket/player/$version/$file" >/dev/null 2>&1; then
    if [[ "$(served_sha "$version/$file")" == "$local_sha" ]]; then
      echo "player/$version/$file already holds this build; skipping."
    else
      echo "player/$version/$file already exists with different content; refusing to overwrite it." >&2
      exit 1
    fi
  else
    upload "$file" "$version" "public, max-age=31536000, immutable" --if-generation-match=0
  fi
  upload "$file" "v$major" "public, max-age=300"
done

# Check that both public URLs serve exactly what was built.
for path in "$version" "v$major"; do
  for file in "${files[@]}"; do
    if [[ "$(served_sha "$path/$file")" != "$(sha256 < "$src/$file")" ]]; then
      echo "$public/$path/$file does not match the build." >&2
      exit 1
    fi
  done
  echo "Verified $public/$path/"
done
