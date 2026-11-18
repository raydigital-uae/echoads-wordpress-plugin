Translation files for EchoAds Audio Player.

To generate or update the .pot template:
  wp i18n make-pot /path/to/echoads-audio-player languages/echoads-audio-player.pot --domain=echoads-audio-player

You can also use Poedit (https://poedit.net/) to create and edit .po files, then compile to .mo.

Place .po and .mo files here using the locale code, e.g.:
  echoads-audio-player-ar.po / echoads-audio-player-ar.mo  (Arabic)
  echoads-audio-player-fr_FR.po / echoads-audio-player-fr_FR.mo  (French)

WordPress will load the translation based on the site language (WPLANG / Settings > General).

Plugins hosted on WordPress.org get their translations from translate.wordpress.org automatically.
