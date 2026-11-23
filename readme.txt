=== EchoAds Audio Player ===
Contributors: echoads, amirhamdani
Tags: audio, text to speech, podcast, audio ads, monetization
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.9.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Turn every post into an audio article with a built-in player and pre-roll and post-roll audio ads, powered by EchoAds.

== Description ==

EchoAds Audio Player connects your WordPress site to EchoAds, an audio article and audio advertising platform by AdvertOnClick. When you publish a post, the plugin sends it to EchoAds, which generates a narrated audio version. Once the audio is ready, a player appears above or below the article so readers can listen instead of read.

EchoAds can play a short audio ad before and after the article. Ads are chosen for each listener by the EchoAds service.

Features:

* Audio is generated automatically when a post is published.
* "Generate", "Regenerate", "Check status" and "Preview" controls in the post editor sidebar.
* An audio status icon for every post on the All Posts screen, and a "Generate audio" button in Quick Edit for posts without audio.
* Player placement above or below the article, and a custom background color.
* Multilingual sites (Polylang or WPML): each language is handled as its own EchoAds website, with its own voice and ads, under one API key, and the player shows in the post's language (right to left for Arabic).
* Player language and right-to-left layout follow your EchoAds account settings on single-language sites.
* Works without WP-Cron: if your host blocks it, the editor checks the audio status when you open the post.

An EchoAds publisher account and API key are required. The plugin does nothing until an API key is entered.

== External services ==

This plugin relies on the EchoAds service to generate audio and serve the player and its ads. It cannot work without it.

Data is sent to the EchoAds API (the Base URL set in Settings > EchoAds) in these cases. On a multilingual site (Polylang or WPML), every request also includes the post's language code (for example `ar` or `en`), so EchoAds uses the website set up for that language.

* **When a post is published, or when an editor clicks Generate or Regenerate audio:** the post ID, title, content, excerpt, slug, permalink, author display name, tags, category names, featured image URL, publish date and publish status, together with your API key.
* **When an editor opens a post or clicks Check status, and every minute through WP-Cron while audio is generating:** the post ID and your API key, to read the audio status.
* **When Test Connection is clicked in the settings:** your API key, to check that the connection works.
* **When a visitor views a post that has audio:** the visitor's browser loads the player settings and the audio and ad URLs for that post from the EchoAds API, then plays the audio files from EchoAds storage. The request includes the post ID, your site's API key and a visitor identifier.
* **When a visitor plays audio:** the browser reports play progress for the ads (ad ID, play position, ad slot, post ID, a per-tab play session ID and the visitor identifier) so that plays can be counted and billed.

The visitor identifier is calculated in the visitor's browser from browser and device characteristics by the open-source FingerprintJS library, which is included in the plugin. It is sent only to EchoAds, which uses it to avoid repeating an ad a visitor has already heard on the same post and to count unique listeners. FingerprintJS stores nothing in the browser. The player keeps the play session ID in the browser's session storage, which is cleared when the tab is closed. Depending on where your visitors are, privacy laws such as the GDPR may require you to mention this in your site's privacy policy or to ask for consent.

EchoAds Terms of Service: https://rc.mediaoutlet.echoads.advertonclick.com/terms-and-conditions

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

= Does the plugin work with multilingual sites? =

Yes, with Polylang or WPML. Each language is sent to EchoAds as a website of its own, with its own voice, ads and statistics, using the same API key. In your EchoAds dashboard, add every language of your site to the website; Settings > EchoAds lists the languages the plugin found. Posts in a language that is not set up get no audio, and the post editor says so. Other translation plugins can supply a post's language with the `echoads_post_language` filter.

= Does the plugin work with caching plugins? =

Yes. The audio and ad links are loaded in the visitor's browser on every page view, so cached pages still show the latest audio.

When a post's audio status changes (sent, ready, or no longer available), the plugin clears that post's cached page so visitors see the change right away. It works with Breeze (including Cloudways Varnish and Cloudflare), Cloudways Varnish without Breeze, LiteSpeed Cache, WP Rocket, W3 Total Cache, WP Super Cache, WP Fastest Cache, Cache Enabler, Hummingbird, SiteGround Optimizer, Nginx Helper, Proxy Cache Purge and WP Engine. Changing the player position, colour, API key or EchoAds URL in Settings > EchoAds clears the whole site's page cache, because those settings are part of every player. For any other cache, use the `echoads_purge_post_cache` action (post ID, URL) and the `echoads_purge_all_cache` action to clear it, or the `echoads_purge_page_cache` and `echoads_purge_all_page_cache` filters to turn this off.

== Source code ==

The player script in `assets/dist/` is built from the TypeScript and React source in `src/`, which is included in the plugin. To rebuild it, run `npm install` and then `npm run build` (or `bun install` and `bun run build`). The player uses the copy of React that ships with WordPress, and does not bundle its own.

== Changelog ==

= 1.9.3 =
* Fix: on Cloudways sites without Breeze (for example with WP Super Cache), the plugin now clears the server's Varnish itself when a post's audio status or a player setting changes. Before, visitors could keep getting an old cached page without the player.

= 1.9.2 =
* Fix: on Breeze sites (Cloudways), a post's page is now also cleared from Varnish right away when its audio status changes. Before, Varnish could keep serving an old copy without the player.

= 1.9.1 =
* Fix: changing the player position, colour, API key or EchoAds URL in Settings > EchoAds now clears the site's page cache, so visitors see the change right away instead of cached pages with the old player.

= 1.9.0 =
* New: when a post's audio status changes (sent, ready, or no longer available), the plugin clears that post's cached page, so visitors see the player right away instead of an old cached copy. Works with Breeze (including Cloudways Varnish and Cloudflare), LiteSpeed Cache, WP Rocket, W3 Total Cache, WP Super Cache, WP Fastest Cache, Cache Enabler, Hummingbird, SiteGround Optimizer, Nginx Helper, Proxy Cache Purge and WP Engine.

= 1.8.1 =
* Fix: a post whose audio EchoAds can't find, for example an English post sent before English was added in EchoAds, no longer stays "in progress" with "Failed to fetch audio status". It shows "No audio in EchoAds for this post" with a Generate Audio button.

= 1.8.0 =
* New: multilingual sites. With Polylang or WPML, each post is sent with its language, and EchoAds handles each language as its own website under the same API key, with its own voice and ads.
* New: the player shows in the post's language, right to left for Arabic, Hebrew, Persian and Urdu.
* New: posts in a language that isn't set up in EchoAds show a clear notice in the editor and on All Posts, instead of getting audio in the wrong voice.
* New: Settings > EchoAds lists the languages found on the site.
* Fix: screen readers announce the player's track and status in the player's language.

= 1.7.1 =
* Readme: completed for the WordPress.org plugin directory.

= 1.7.0 =
* New: the All Posts screen shows each post's audio status, and Quick Edit has a Generate audio button for posts without audio.
* New: ready for the WordPress.org plugin directory, with translatable strings, escaped output, sanitized settings, uninstall cleanup and the player's source code included.
* Change: requires WordPress 6.6 or later. The player now uses the React that ships with WordPress, which makes its script about 75% smaller.
* Change: settings are stored under new names. Existing settings are copied over automatically when you update.
* Change: debug logging only runs when WP_DEBUG is on, and no longer logs post content or API responses.

= 1.6.2 =
* Fix: the player uses FingerprintJS 5, which is MIT licensed, and no longer sends usage statistics to FingerprintJS's servers.

= 1.6.1 =
* Fix: audio generation and the player are more reliable, and no longer depend on WP-Cron.

= 1.6.0 =
* New: tracked plays report which ad slot (intro or outro) they belong to.

= 1.5.3 =
* Fix: queued posts are sent when WP-Cron can't run.

== Upgrade Notice ==

= 1.9.0 =
Posts' cached pages now update by themselves when their audio is ready. Pages cached before this update may need one last manual cache purge.

= 1.8.0 =
Adds multilingual support. On a multilingual site, add each language in your EchoAds dashboard so its posts get audio.

= 1.7.0 =
Requires WordPress 6.6 or later. Your EchoAds settings are kept.
