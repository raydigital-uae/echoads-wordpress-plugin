Translation files for EchoAds Posts Plugin.

To generate or update the .pot template:
  wp i18n make-pot /path/to/auto-send-plugin languages/echoads-posts-plugin.pot --domain=echoads-posts-plugin

You can also use Poedit (https://poedit.net/) to create and edit .po files, then compile to .mo.

Place .po and .mo files here using the locale code, e.g.:
  echoads-posts-plugin-ar.po / echoads-posts-plugin-ar.mo  (Arabic)
  echoads-posts-plugin-fr_FR.po / echoads-posts-plugin-fr_FR.mo  (French)

WordPress will load the translation based on the site language (WPLANG / Settings > General).
