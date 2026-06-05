<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://travely-solutions.eu
 * @since      1.0.0
 *
 * @package    Travely_Widget
 * @subpackage Travely_Widget/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Travely_Widget
 * @subpackage Travely_Widget/includes
 * @author     Travely Solutions OU <info@travely.ee>
 */
class Travely_Widget_i18n {

    /**
     * Whether this i18n instance has already loaded the textdomain.
     *
     * @var bool
     */
    private $loaded = false;

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     * @param bool $force Force bundled .mo loading even if the textdomain is already loaded.
     * @return bool
     */
    public function load_plugin_textdomain( $force = false ) {
        $domain = 'travely-widget';

        if ( $this->loaded && ! $force ) {
            return true;
        }

        $relative_path = $this->get_relative_languages_path();
        $loaded_direct = false;

        foreach ( $this->get_mofile_candidates( $domain ) as $mofile ) {
            if ( ! file_exists( $mofile ) ) {
                continue;
            }

            if ( is_textdomain_loaded( $domain ) && function_exists( 'unload_textdomain' ) ) {
                unload_textdomain( $domain );
            }

            $loaded_direct = load_textdomain( $domain, $mofile );

            if ( $loaded_direct ) {
                break;
            }
        }

        if ( ! $loaded_direct ) {
            load_plugin_textdomain(
                $domain,
                false,
                $relative_path
            );
        }

        $this->loaded = is_textdomain_loaded( $domain );

        return $this->loaded;
    }

    /**
     * Get candidate locales for bundled .mo loading.
     *
     * @return array
     */
    private function get_locale_candidates() {
        $candidates = array();

        if ( function_exists( 'determine_locale' ) ) {
            $candidates[] = determine_locale();
        }

        if ( is_admin() && function_exists( 'get_user_locale' ) ) {
            $candidates[] = get_user_locale();
        }

        $candidates[] = get_locale();

        return array_values( array_unique( array_filter( $candidates ) ) );
    }

    /**
     * Get absolute bundled .mo file candidates.
     *
     * @param string $domain Textdomain.
     * @return array
     */
    private function get_mofile_candidates( $domain ) {
        $candidates = array();
        $base_path  = plugin_dir_path( dirname( __FILE__ ) ) . 'languages/';

        foreach ( $this->get_locale_candidates() as $locale ) {
            $candidates[] = $base_path . $domain . '-' . $locale . '.mo';
        }

        return array_values( array_unique( $candidates ) );
    }

    /**
     * Get plugin languages path relative to the plugins directory.
     *
     * @return string
     */
    private function get_relative_languages_path() {
        return dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/';
    }

}
