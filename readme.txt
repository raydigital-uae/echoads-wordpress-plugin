=== EchoAds Audio Player ===
Contributors: TODO-wporg-username
Tags: audio, text to speech, podcast, audio ads, monetization
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.6.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Turn every post into an audio article with a built-in player and pre-roll and post-roll audio ads, powered by EchoAds.

== Description ==

EchoAds Audio Player connects your WordPress site to the EchoAds platform. When you publish a post, the plugin sends it to EchoAds, which generates a narrated audio version. Once the audio is ready, a player appears above or below the article so readers can listen instead of read.

EchoAds can play a short audio ad before and after the article. Ads are chosen for each listener by the EchoAds service.

Features:

* Audio is generated automatically when a post is published.
* "Generate", "Regenerate", "Check status" and "Preview" controls in the post editor sidebar.
* Player placement above or below the article, and a custom background color.
* Player language and right-to-left layout follow your EchoAds account settings.
* Works without WP-Cron: if your host blocks it, the editor checks the audio status when you open the post.

An EchoAds publisher account and API key are required. The plugin does nothing until an API key is entered.

== External services ==

This plugin relies on the EchoAds service to generate audio and serve the player and its ads. It cannot work without it.

Data is sent to the EchoAds API (the Base URL set in Settings > EchoAds) in these cases:

* **When a post is published, or when an editor clicks Generate or Regenerate audio:** the post ID, title, content, excerpt, slug, permalink, author display name, tags, category names, featured image URL, publish date and publish status, together with your API key.
* **When an editor opens a post or clicks Check status, and every minute through WP-Cron while audio is generating:** the post ID and your API key, to read the audio status.
* **When Test Connection is clicked in the settings:** your API key, to check that the connection works.
* **When a visitor views a post that has audio:** the visitor's browser loads the player settings and the audio and ad URLs for that post from the EchoAds API, then plays the audio files from EchoAds storage. The request includes the post ID, your site's API key and a visitor identifier.
* **When a visitor plays audio:** the browser reports play progress for the ads (ad ID, play position, ad slot, post ID, a per-tab play session ID and the visitor identifier) so that plays can be counted and billed.

EchoAds Terms of Service: TODO-terms-url
EchoAds Privacy Policy: TODO-privacy-url

== Installation ==

1. Install the plugin from Plugins > Add New, or upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin from the Plugins screen.
3. Go to Settings > EchoAds and enter the API key and Base URL from your EchoAds publisher dashboard.
4. Click Test Connection, then Save Settings.
5. Publish a post. The audio player appears on the post once its audio has been generated.

== Frequently Asked Questions ==

= Where do I get an API key? =

From your EchoAds publisher dashboard, after your website has been approved.

= Why doesn't the player show on my post yet? =

Audio generation takes a few minutes. Open the post in the editor to see its status in the EchoAds Audio Generation box, or click Check Audio Article Status.

= Which posts get audio? =

Standard posts, when they are first published. Pages and custom post types are not sent. Posts published before the plugin was set up can be sent with the Generate Audio button.

= Does the plugin work with caching plugins? =

Yes. The audio and ad links are loaded in the visitor's browser on every page view, so cached pages still show the latest audio.

== Source code ==

The player script in `assets/dist/` is built from the TypeScript and React source in `src/`, which is included in the plugin. To rebuild it, run `npm install` and then `npm run build` (or `bun install` and `bun run build`). The player uses the copy of React that ships with WordPress, and does not bundle its own.

== Changelog ==

= 1.6.1 =
* Fix: audio generation and the player are more reliable, and no longer depend on WP-Cron.

= 1.6.0 =
* New: tracked plays report which ad slot (intro or outro) they belong to.

= 1.5.3 =
* Fix: queued posts are sent when WP-Cron can't run.
