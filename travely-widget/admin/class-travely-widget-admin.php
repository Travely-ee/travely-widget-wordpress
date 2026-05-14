<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://travely.ee
 * @since      1.0.0
 *
 * @package    Travely_Widget
 * @subpackage Travely_Widget/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Travely_Widget
 * @subpackage Travely_Widget/admin
 * @author     Andrei Abozau <andrei@travely.ee>
 */
class Travely_Widget_Admin {

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
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
    public function enqueue_scripts() {
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
               register_setting(
                        'travely_widget_option_group',
                        'travely_widget_key',
                        array(
                            'type'              => 'string',
                            'sanitize_callback' => array( $this, 'sanitize_api_key' ),
                            'default'           => '',
                        )
               );

               register_setting(
                        'travely_widget_option_group',
                        'travely_widget_path_to_search',
                        array(
                            'type'              => 'string',
                            'sanitize_callback' => array( $this, 'sanitize_path_to_search' ),
                            'default'           => '/tour-search',
                        )
               );

               register_setting(
                        'travely_widget_option_group',
                        'travely_widget_remove_data_on_uninstall',
                        array(
                            'type'              => 'boolean',
                            'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
                            'default'           => false,
                        )
               );

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

               add_settings_field(
                       'travely_widget_remove_data_on_uninstall',
                       __( 'Remove data on uninstall', 'travely-widget' ),
                       array( $this, 'remove_data_callback' ),
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

        public function remove_data_callback() {
               $value = (bool) get_option( 'travely_widget_remove_data_on_uninstall', false );

               printf(
                       '<input type="hidden" name="travely_widget_remove_data_on_uninstall" value="0" />'
               );

               printf(
                       '<label><input type="checkbox" name="travely_widget_remove_data_on_uninstall" value="1" %s /> %s</label>',
                       checked( true, $value, false ),
                       esc_html__( 'Remove plugin settings when uninstalling the plugin.', 'travely-widget' )
               );
        }

        public function sanitize_api_key( $value ) {
               return sanitize_text_field( $value );
        }

        public function sanitize_path_to_search( $value ) {
               $value = sanitize_text_field( $value );
               $value = trim( $value );

               if ( '' === $value ) {
                      return '/tour-search';
               }

               if ( preg_match( '#^https?://#i', $value ) ) {
                      $path = wp_parse_url( $value, PHP_URL_PATH );
                      $value = $path ? $path : '/tour-search';
               }

               if ( '/' !== substr( $value, 0, 1 ) ) {
                      $value = '/' . $value;
               }

               return $value;
        }

        public function sanitize_checkbox( $value ) {
            return ! empty( $value );
        }
}
