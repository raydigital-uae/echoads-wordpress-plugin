<?php
/**
 * Language support for multilingual sites.
 *
 * On a site translated with Polylang or WPML every translation is its own post. EchoAds
 * treats each language as a website of its own (its own voice, ads and stats) under the
 * same API key, so every API call for a post carries that post's language as
 * ?language=<code>. Sites without a multilingual plugin send no language, exactly as before.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EchoAds_Language {

    /**
     * Status saved when EchoAds has no website for the post's language yet.
     */
    const STATUS_NOT_SET_UP = 'LANGUAGE_NOT_SET_UP';

    /**
     * Error code the EchoAds API returns (HTTP 422) for a language with no website.
     */
    const API_ERROR_NOT_SET_UP = 'WEBSITE_LANGUAGE_NOT_CONFIGURED';

    /**
     * Languages written right to left.
     */
    const RTL_LANGUAGES = array( 'ar', 'he', 'fa', 'ur' );

    /**
     * The multilingual plugin in use: 'polylang', 'wpml' or '' (none).
     *
     * @return string
     */
    public static function get_multilingual_plugin() {
        if ( function_exists( 'pll_get_post_language' ) ) {
            return 'polylang';
        }
        if ( defined( 'ICL_SITEPRESS_VERSION' ) || has_filter( 'wpml_post_language_details' ) ) {
            return 'wpml';
        }
        return '';
    }

    /**
     * The post's language as an ISO 639-1 code ('ar', 'en'), or '' when the site has no
     * multilingual plugin or the post has no language.
     *
     * @param int $post_id Post ID.
     * @return string
     */
    public static function get_post_language( $post_id ) {
        $code = '';

        switch ( self::get_multilingual_plugin() ) {
            case 'polylang':
                // The locale (ar, en_US) is the reliable part: Polylang slugs can be renamed.
                $code = self::normalize( pll_get_post_language( $post_id, 'locale' ) );
                if ( '' === $code ) {
                    $code = self::normalize( pll_get_post_language( $post_id, 'slug' ) );
                }
                break;
            case 'wpml':
                $details = apply_filters( 'wpml_post_language_details', null, $post_id );
                if ( is_array( $details ) ) {
                    $code = self::normalize( isset( $details['locale'] ) ? $details['locale'] : '' );
                    if ( '' === $code && isset( $details['language_code'] ) ) {
                        $code = self::normalize( $details['language_code'] );
                    }
                }
                break;
        }

        /**
         * Filters the language EchoAds uses for a post. Lets other translation plugins supply
         * it; return '' to send no language.
         *
         * @param string $code    ISO 639-1 code, or ''.
         * @param int    $post_id Post ID.
         */
        return self::normalize( apply_filters( 'echoads_post_language', $code, $post_id ) );
    }

    /**
     * The site's languages as code => display name, from the multilingual plugin.
     *
     * @return array<string, string>
     */
    public static function get_site_languages() {
        $languages = array();

        switch ( self::get_multilingual_plugin() ) {
            case 'polylang':
                if ( function_exists( 'pll_languages_list' ) ) {
                    $locales = pll_languages_list( array( 'fields' => 'locale' ) );
                    $names   = pll_languages_list( array( 'fields' => 'name' ) );
                    foreach ( (array) $locales as $index => $locale ) {
                        $code = self::normalize( $locale );
                        if ( '' !== $code ) {
                            $languages[ $code ] = isset( $names[ $index ] ) ? $names[ $index ] : $code;
                        }
                    }
                }
                break;
            case 'wpml':
                $active = apply_filters( 'wpml_active_languages', null, array( 'skip_missing' => 0 ) );
                foreach ( (array) $active as $language ) {
                    $locale = isset( $language['default_locale'] ) ? $language['default_locale'] : '';
                    $code   = self::normalize( '' !== $locale ? $locale : ( isset( $language['code'] ) ? $language['code'] : '' ) );
                    if ( '' !== $code ) {
                        $languages[ $code ] = isset( $language['native_name'] ) ? $language['native_name'] : $code;
                    }
                }
                break;
        }

        return $languages;
    }

    /**
     * Adds the post's language to an EchoAds API URL (no change when it has none).
     *
     * @param string $url     API URL.
     * @param int    $post_id Post ID.
     * @return string
     */
    public static function add_to_url( $url, $post_id ) {
        $code = self::get_post_language( $post_id );
        if ( '' === $url || '' === $code ) {
            return $url;
        }
        return add_query_arg( 'language', $code, $url );
    }

    /**
     * Whether an API response says EchoAds has no website for the post's language.
     *
     * @param int|string $response_code HTTP status.
     * @param string     $response_body Response body.
     * @return bool
     */
    public static function is_not_set_up_response( $response_code, $response_body ) {
        return 422 === (int) $response_code && false !== strpos( (string) $response_body, self::API_ERROR_NOT_SET_UP );
    }

    /**
     * Display name for a language code, from the site's languages when known.
     *
     * @param string $code ISO 639-1 code.
     * @return string
     */
    public static function get_language_name( $code ) {
        $languages = self::get_site_languages();
        return isset( $languages[ $code ] ) ? $languages[ $code ] : strtoupper( $code );
    }

    /**
     * Whether a language is written right to left.
     *
     * @param string $code ISO 639-1 code.
     * @return bool
     */
    public static function is_rtl( $code ) {
        return in_array( self::normalize( $code ), self::RTL_LANGUAGES, true );
    }

    /**
     * Reduces 'en_US', 'en-GB', ' EN ' to 'en'. Anything that is not a language code becomes ''.
     *
     * @param mixed $code Language code or locale.
     * @return string
     */
    public static function normalize( $code ) {
        if ( ! is_string( $code ) ) {
            return '';
        }
        $parts   = preg_split( '/[-_]/', strtolower( trim( $code ) ) );
        $primary = isset( $parts[0] ) ? $parts[0] : '';
        return preg_match( '/^[a-z]{2,3}$/', $primary ) ? $primary : '';
    }
}
