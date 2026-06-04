<?php

/**
 * Language helper for Travely Widget.
 *
 * @link       https://travely-solutions.eu
 * @since      1.0.0
 *
 * @package    Travely_Widget
 * @subpackage Travely_Widget/includes
 */

/**
 * Resolves external language sources to Travely widget language codes.
 *
 * @since      1.0.0
 * @package    Travely_Widget
 * @subpackage Travely_Widget/includes
 */
class Travely_Widget_Language {

    /**
     * Get supported Travely widget languages.
     *
     * @return array<string,string>
     */
    public static function allowed_languages() {
        return array(
            'est' => __( 'Estonian', 'travely-widget' ),
            'eng' => __( 'English', 'travely-widget' ),
            'rus' => __( 'Russian', 'travely-widget' ),
            'lav' => __( 'Latvian', 'travely-widget' ),
        );
    }

    /**
     * Normalize an external language code to a whitelisted Travely language.
     *
     * @param string $language Language code to normalize.
     * @param string $fallback Fallback language code.
     * @return string
     */
    public static function normalize( $language, $fallback = 'est' ) {
        $fallback = strtolower( sanitize_text_field( (string) $fallback ) );

        if ( ! in_array( $fallback, array( 'est', 'eng', 'rus', 'lav' ), true ) ) {
            $fallback = 'est';
        }

        $language = strtolower( sanitize_text_field( (string) $language ) );

        $map = array(
            'et'  => 'est',
            'ee'  => 'est',
            'est' => 'est',

            'en'  => 'eng',
            'eng' => 'eng',

            'ru'  => 'rus',
            'rus' => 'rus',

            'lv'  => 'lav',
            'lav' => 'lav',
        );

        return isset( $map[ $language ] ) ? $map[ $language ] : $fallback;
    }

    /**
     * Get configured default language.
     *
     * @return string
     */
    public static function get_default_language() {
        return self::normalize( get_option( 'travely_widget_default_language', 'est' ), 'est' );
    }

    /**
     * Check whether the configured default language should always be used.
     *
     * @return bool
     */
    public static function is_force_default_language() {
        return (bool) get_option( 'travely_widget_force_default_language', false );
    }

    /**
     * Get language from URL query parameters.
     *
     * @return string
     */
    public static function get_query_language() {
        if ( isset( $_GET['language'] ) ) {
            if ( is_array( $_GET['language'] ) ) {
                return '';
            }

            return sanitize_text_field( wp_unslash( $_GET['language'] ) );
        }

        if ( isset( $_GET['lang'] ) ) {
            if ( is_array( $_GET['lang'] ) ) {
                return '';
            }

            return sanitize_text_field( wp_unslash( $_GET['lang'] ) );
        }

        return '';
    }

    /**
     * Get current language from Polylang.
     *
     * @return string
     */
    public static function get_polylang_language() {
        if ( function_exists( 'pll_current_language' ) ) {
            $language = pll_current_language( 'slug' );

            if ( ! empty( $language ) ) {
                return sanitize_text_field( $language );
            }
        }

        return '';
    }

    /**
     * Get current language from WPML.
     *
     * @return string
     */
    public static function get_wpml_language() {
        if ( defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE ) {
            return sanitize_text_field( ICL_LANGUAGE_CODE );
        }

        return '';
    }

    /**
     * Get language code from the WordPress locale.
     *
     * @return string
     */
    public static function get_wordpress_locale_language() {
        $locale = get_locale();

        if ( empty( $locale ) ) {
            return '';
        }

        return substr( sanitize_text_field( $locale ), 0, 2 );
    }

    /**
     * Resolve the page language according to Travely Widget priority rules.
     *
     * @return string
     */
    public static function resolve_language() {
        $default_language = self::get_default_language();

        if ( self::is_force_default_language() ) {
            return $default_language;
        }

        $query_language = self::get_query_language();

        if ( ! empty( $query_language ) ) {
            return self::normalize( $query_language, $default_language );
        }

        $polylang_language = self::get_polylang_language();

        if ( ! empty( $polylang_language ) ) {
            return self::normalize( $polylang_language, $default_language );
        }

        $wpml_language = self::get_wpml_language();

        if ( ! empty( $wpml_language ) ) {
            return self::normalize( $wpml_language, $default_language );
        }

        $locale_language = self::get_wordpress_locale_language();

        if ( ! empty( $locale_language ) ) {
            return self::normalize( $locale_language, $default_language );
        }

        return $default_language;
    }
}
