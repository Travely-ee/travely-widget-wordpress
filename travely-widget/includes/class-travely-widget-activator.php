<?php

/**
 * Fired during plugin activation
 *
 * @link       http://travely.ee
 * @since      1.0.0
 *
 * @package    Travely_Widget
 * @subpackage Travely_Widget/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Travely_Widget
 * @subpackage Travely_Widget/includes
 * @author     Andrei Abozau <andrei@travely.ee>
 */
class Travely_Widget_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
    public static function activate() {
        if ( version_compare( get_bloginfo( 'version' ), '5.8', '<' ) ) {
            deactivate_plugins( plugin_basename( dirname( __DIR__ ) . '/travely-widget.php' ) );

            wp_die(
                esc_html__( 'Travely Widget requires WordPress 5.8 or higher.', 'travely-widget' )
            );
        }

        if ( false === get_option( 'travely_widget_path_to_search', false ) ) {
            add_option( 'travely_widget_path_to_search', '/tour-search' );
        }

        delete_transient( 'travely_widget_github_release' );
    }

}
