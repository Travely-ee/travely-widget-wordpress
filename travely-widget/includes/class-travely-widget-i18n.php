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

        load_plugin_textdomain(
            'travely-widget',
            false,
            dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
        );

    }



}

