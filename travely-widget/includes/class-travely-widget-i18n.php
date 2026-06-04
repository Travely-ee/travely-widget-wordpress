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
 * @author     Travely Solutions OÜ <info@travely.ee>
 */
class Travely_Widget_i18n {


    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {
        $domain   = 'travely-widget';
        $locale   = determine_locale();
        $rel_path = dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/';
        $mofile   = plugin_dir_path( dirname( __FILE__ ) ) . 'languages/' . $domain . '-' . $locale . '.mo';

        $mofile_exists = file_exists( $mofile );
        $unloaded      = false;
        $loaded_direct = false;
        $loaded_plugin = false;

        if ( $mofile_exists ) {
            if ( is_textdomain_loaded( $domain ) && function_exists( 'unload_textdomain' ) ) {
                $unloaded = unload_textdomain( $domain );
            }

            $loaded_direct = load_textdomain( $domain, $mofile );
        }

        if ( ! $loaded_direct ) {
            $loaded_plugin = load_plugin_textdomain(
                $domain,
                false,
                $rel_path
            );
        }

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $this->log_diagnostics(
                $domain,
                $locale,
                $rel_path,
                $mofile,
                $mofile_exists,
                $unloaded,
                $loaded_direct,
                $loaded_plugin
            );
        }

    }

    /**
     * Log translation loading diagnostics when WP_DEBUG is enabled.
     *
     * @param string $domain Textdomain.
     * @param string $locale Current locale.
     * @param string $rel_path Relative plugin languages path.
     * @param string $mofile Absolute bundled .mo path.
     * @param bool   $mofile_exists Whether bundled .mo exists.
     * @param bool   $unloaded Whether an existing textdomain was unloaded before direct loading.
     * @param bool   $loaded_direct Result of load_textdomain().
     * @param bool   $loaded_plugin Result of load_plugin_textdomain().
     */
    private function log_diagnostics( $domain, $locale, $rel_path, $mofile, $mofile_exists, $unloaded, $loaded_direct, $loaded_plugin ) {
        error_log( 'Travely Widget i18n locale: ' . $locale );
        error_log( 'Travely Widget i18n relative path: ' . $rel_path );
        error_log( 'Travely Widget i18n bundled mofile: ' . $mofile );
        error_log( 'Travely Widget i18n bundled mofile exists: ' . ( $mofile_exists ? 'yes' : 'no' ) );
        error_log( 'Travely Widget i18n unloaded existing textdomain: ' . ( $unloaded ? 'yes' : 'no' ) );
        error_log( 'Travely Widget i18n load_textdomain result: ' . ( $loaded_direct ? 'yes' : 'no' ) );

        if ( ! $loaded_direct ) {
            error_log( 'Travely Widget i18n load_plugin_textdomain result: ' . ( $loaded_plugin ? 'yes' : 'no' ) );
        }

        error_log( 'Travely Widget i18n textdomain loaded: ' . ( is_textdomain_loaded( $domain ) ? 'yes' : 'no' ) );
        $this->log_translation_probe( $domain );
    }

    /**
     * Log selected translation probes when WP_DEBUG is enabled.
     *
     * @param string $domain Textdomain.
     */
    private function log_translation_probe( $domain ) {
        $probes = array(
            'Travely Widget Settings',
            'Default language',
            'Path mode',
            'API Key',
        );

        foreach ( $probes as $probe ) {
            error_log( 'Travely Widget i18n probe: ' . $probe . ' => ' . __( $probe, $domain ) );
        }
    }

}
