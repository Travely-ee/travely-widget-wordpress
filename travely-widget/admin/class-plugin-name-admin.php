<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/admin
 * @author     Your Name <email@example.com>
 */
class Plugin_Name_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
       public function __construct( $plugin_name, $version ) {

               $this->plugin_name = $plugin_name;
               $this->version = $version;

               add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
               add_action( 'admin_init', array( $this, 'page_init' ) );

       }

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Plugin_Name_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Plugin_Name_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/plugin-name-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
        public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Plugin_Name_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Plugin_Name_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

               wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/plugin-name-admin.js', array( 'jquery' ), $this->version, false );

       }

       public function add_plugin_page() {
               add_options_page(
                       'Travely Widget Settings',
                       'Travely Widget',
                       'manage_options',
                       'travely-widget',
                       array( $this, 'create_admin_page' )
               );
       }

       public function create_admin_page() {
               ?>
               <div class="wrap">
                       <h1><?php esc_html_e( 'Travely Widget Settings', 'travely-widget' ); ?></h1>
                       <form method="post" action="options.php">
                               <?php
                               settings_fields( 'travely_widget_option_group' );
                               do_settings_sections( 'travely-widget-admin' );
                               submit_button();
                               ?>
                       </form>
               </div>
               <?php
       }

       public function page_init() {
               register_setting( 'travely_widget_option_group', 'travely_widget_key' );
               register_setting( 'travely_widget_option_group', 'travely_widget_path_to_search' );

               add_settings_section( 'travely_widget_setting_section', '', null, 'travely-widget-admin' );

               add_settings_field(
                       'travely_widget_key',
                       __( 'API Key', 'travely-widget' ),
                       array( $this, 'key_callback' ),
                       'travely-widget-admin',
                       'travely_widget_setting_section'
               );

               add_settings_field(
                       'travely_widget_path_to_search',
                       __( 'Path to Search', 'travely-widget' ),
                       array( $this, 'path_callback' ),
                       'travely-widget-admin',
                       'travely_widget_setting_section'
               );
       }

       public function key_callback() {
               printf(
                       '<input type="text" id="travely_widget_key" name="travely_widget_key" value="%s" />',
                       esc_attr( get_option( 'travely_widget_key', '' ) )
               );
       }

       public function path_callback() {
               $value = get_option( 'travely_widget_path_to_search', '/tour-search' );
               printf(
                       '<input type="text" id="travely_widget_path_to_search" name="travely_widget_path_to_search" value="%s" />',
                       esc_attr( $value )
               );
       }

}
