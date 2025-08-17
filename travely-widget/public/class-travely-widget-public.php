<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://travely.ee
 * @since      1.0.0
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

    private $path;

    private $key;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
       public function __construct( $plugin_name, $version ) {

               $this->plugin_name = $plugin_name;
               $this->version = $version;

               $this->path = get_option( 'travely_widget_path_to_search', '/tour-search' );
               $this->key  = get_option( 'travely_widget_key', '' );
       }

       public function register_shortcodes() {
               add_shortcode( 'travely-widget-search', array( $this, 'render_search' ) );
               add_shortcode( 'travely-widget-country', array( $this, 'render_countries' ) );
               add_shortcode( 'travely-widget-best', array( $this, 'render_best_tours' ) );
               add_shortcode( 'travely-widget-results', array( $this, 'render_results' ) );
       }

       public function register_blocks() {
               if ( function_exists( 'register_block_type' ) ) {
                       register_block_type( 'travely/widget-search', array(
                               'render_callback' => array( $this, 'render_search' ),
                       ) );
                       register_block_type( 'travely/widget-country', array(
                               'render_callback' => array( $this, 'render_countries' ),
                       ) );
                       register_block_type( 'travely/widget-best', array(
                               'render_callback' => array( $this, 'render_best_tours' ),
                       ) );
                       register_block_type( 'travely/widget-results', array(
                               'render_callback' => array( $this, 'render_results' ),
                       ) );
               }
       }

       private function enqueue_remote_assets() {
               // wp_enqueue_style( 'travely-widget-remote-css', 'https://devwidget.travely.ee/est/static/css/main.css', array(), $this->version );
               wp_enqueue_script( 'travely-widget-remote-js', 'https://devwidget.travely.ee/est/static/js/main.js', array(), $this->version, true );
       }

       private function unique_id( $prefix ) {
               return $prefix . uniqid();
       }

       public function render_search() {
               $this->enqueue_remote_assets();
               $id   = $this->unique_id( 'travely-widget-search-global-' );

               ob_start();
               ?>
               <travely-widget-search id="<?php echo esc_attr( $id ); ?>" data-key="<?php echo esc_attr( $this->key ); ?>" data-path="<?php echo esc_attr( $this->path ); ?>"></travely-widget-search>
               <?php
               return ob_get_clean();
       }

       public function render_countries() {
               $this->enqueue_remote_assets();
               $id   = $this->unique_id( 'travely-widget-search-country-' );

               ob_start();
               ?>
               <travely-widget-country id="<?php echo esc_attr( $id ); ?>" data-key="<?php echo esc_attr( $this->key ); ?>" data-path="<?php echo esc_attr( $this->path ); ?>"></travely-widget-country>
               <?php
               return ob_get_clean();
       }

        public function render_best_tours() {
               $this->enqueue_remote_assets();
               $id   = $this->unique_id( 'travely-widget-search-best-tours-' );

               ob_start();
               ?>
               <travely-widget-best id="<?php echo esc_attr( $id ); ?>" data-key="<?php echo esc_attr( $this->key ); ?>" data-path="<?php echo esc_attr( $this->path ); ?>"></travely-widget-best>
               <?php
               return ob_get_clean();
        }

    public function render_results() {
               $this->enqueue_remote_assets();
               $id   = $this->unique_id( 'travely-widget-results-' );

               ob_start();
               ?>
               <travely-widget-results id="<?php echo esc_attr( $id ); ?>" data-key="<?php echo esc_attr( $this->key ); ?>" data-path="<?php echo esc_attr( $this->path ); ?>"></travely-widget-results>
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
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/travely-widget-public.js', array( 'jquery' ), $this->version, false );
        wp_enqueue_script( $this->plugin_name . '-component', plugin_dir_url( __FILE__ ) . 'js/travely-widget-component.js', array(), $this->version, false );
	}

}
