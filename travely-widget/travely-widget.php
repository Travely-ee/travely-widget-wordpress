<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://travely.ee
 * @since             1.0.3
 * @package           Travely_Widget
 *
 * @wordpress-plugin
 * Plugin Name:       Travely Widget
 * Plugin URI:        http://travely.ee
 * Description:       The plugin allows you to use the Travely system widget on your website.
 * Version:           1.0.3
 * Author:            Travely OU
 * Author URI:        http://travely.ee/
 * License:           Commerce
 * Text Domain:       travely-widget
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'TRAVELY_WIDGET_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-travely-widget-activator.php
 */
function activate_travely_widget() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-travely-widget-activator.php';
	Travely_Widget_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-travely-widget-deactivator.php
 */
function deactivate_travely_widget() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-travely-widget-deactivator.php';
	Travely_Widget_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_travely_widget' );
register_deactivation_hook( __FILE__, 'deactivate_travely_widget' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-travely-widget.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_travely_widget() {

	$plugin = new Travely_Widget();
	$plugin->run();

}
run_travely_widget();
