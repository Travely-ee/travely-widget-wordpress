<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://travely.ee
 * @since      1.0.1
 *
 * @package    Travely_Widget
 * @subpackage Travely_Widget/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Travely_Widget
 * @subpackage Travely_Widget/public
 * @author     Andrei Abozau <abozau@travely.ee>
 */
class Travely_Widget_Public {

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
	 * @since    1.0.1
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
       public function __construct( $plugin_name, $version ) {

               $this->plugin_name = $plugin_name;
               $this->version = $version;

       }

       public function register_shortcodes() {
               add_shortcode( 'travely-widget-search', array( $this, 'render_search' ) );
               add_shortcode( 'travely-widget-search-country', array( $this, 'render_search_country' ) );
               add_shortcode( 'travely-widget-country', array( $this, 'render_country' ) );
               add_shortcode( 'travely-widget-results', array( $this, 'render_results' ) );
       }

       public function register_blocks() {
               if ( function_exists( 'register_block_type' ) ) {
                       register_block_type( 'travely/widget-search', array(
                               'render_callback' => array( $this, 'render_search' ),
                       ) );
                       register_block_type( 'travely/widget-results', array(
                               'render_callback' => array( $this, 'render_results' ),
                       ) );
               }
       }

       private function enqueue_remote_assets() {
               wp_enqueue_style( 'travely-widget-remote-css', 'https://wgsearch.travely.ee/est/static/css/main.css', array(), $this->version );
               wp_enqueue_script( 'travely-widget-remote-js', 'https://wgsearch.travely.ee/est/static/js/main.js', array(), $this->version, true );
       }

       private function enqueue_local_assets() {
               wp_enqueue_script(
                       'travely-widget-init',
                       plugin_dir_url( __FILE__ ) . 'js/travely-widget-init.js',
                       array( 'travely-widget-remote-js' ),
                       $this->version,
                       true
               );
       }

       private function unique_id( $prefix ) {
               return $prefix . uniqid();
       }

       public function render_search() {
               $this->enqueue_remote_assets();
               $this->enqueue_local_assets();
               $id   = $this->unique_id( 'travely-widget-search-' );
               $path = esc_js( get_option( 'travely_widget_path_to_search', '/tour-search' ) );

               ob_start();
               ?>
               <div id="<?php echo esc_attr( $id ); ?>" class="travely-widget-search" data-travely-widget="true" data-mode="search" data-path="<?php echo esc_attr( $path ); ?>"></div>
               <?php
               return ob_get_clean();
       }
       public function render_search_country() {
            $this->enqueue_remote_assets();
            $this->enqueue_local_assets();
            $id   = $this->unique_id( 'travely-widget-search-country-' );
            $path = esc_js( get_option( 'travely_widget_path_to_search', '/tour-search' ) );

            ob_start();
            ?>
            <div id="<?php echo esc_attr( $id ); ?>" class="travely-widget-search-country" data-travely-widget="true" data-mode="search,country" data-path="<?php echo esc_attr( $path ); ?>"></div>
            <?php
            return ob_get_clean();
       }
       public function render_country() {
            $this->enqueue_remote_assets();
            $this->enqueue_local_assets();
            $id   = $this->unique_id( 'travely-widget-country-' );
            $path = esc_js( get_option( 'travely_widget_path_to_search', '/tour-search' ) );

            ob_start();
            ?>
            <div id="<?php echo esc_attr( $id ); ?>" class="travely-widget-country" data-travely-widget="true" data-mode="country" data-path="<?php echo esc_attr( $path ); ?>"></div>
            <?php
            return ob_get_clean();
        }

       public function render_results() {
               $this->enqueue_remote_assets();
               $this->enqueue_local_assets();
               $id   = $this->unique_id( 'travely-widget-results-' );
               $path = esc_js( get_option( 'travely_widget_path_to_search', '/tour-search' ) );
               $key  = esc_js( get_option( 'travely_widget_key', '' ) );

               ob_start();
               ?>
               <div id="<?php echo esc_attr( $id ); ?>" class="travely-widget-results" data-travely-widget="true" data-mode="results" data-path="<?php echo esc_attr( $path ); ?>" data-key="<?php echo esc_attr( $key ); ?>"></div>
               <?php
               return ob_get_clean();
       }

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
	}

}
