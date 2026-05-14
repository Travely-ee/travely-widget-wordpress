<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://travely-solutions.eu
 * @since      1.0.0
 *
 * @package    Travely_Widget
 * @subpackage Travely_Widget/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Travely_Widget
 * @subpackage Travely_Widget/includes
 * @author     Travely Solutions OÜ <info@travely.ee>
 */
class Travely_Widget_Deactivator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        delete_transient( 'travely_widget_github_release' );
    }

}

