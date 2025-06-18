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

       }

       public function register_shortcodes() {
               add_shortcode( 'travely-widget-search', array( $this, 'render_search' ) );
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
               wp_enqueue_style( 'travely-widget-remote-css', 'https://devwidget.travely.ee/eng/static/css/main.css', array(), $this->version );
               wp_enqueue_script( 'travely-widget-remote-js', 'https://devwidget.travely.ee/eng/static/js/main.js', array(), $this->version, true );
       }

       private function unique_id( $prefix ) {
               return $prefix . uniqid();
       }

       public function render_search() {
               $this->enqueue_remote_assets();
               $id   = $this->unique_id( 'travely-widget-search-' );
               $path = esc_js( get_option( 'travely_widget_path_to_search', '/tour-search' ) );

               ob_start();
               ?>
               <div id="<?php echo esc_attr( $id ); ?>" class="travely-widget-search"></div>
               <script>(function(){if(!window.travelyWidgetInitialized){document.addEventListener('DOMContentLoaded',function(){if(window.TravelySearch){TravelySearch.initSearch('<?php echo esc_js( $id ); ?>','<?php echo $path; ?>');}});window.travelyWidgetInitialized=true;}})();</script>
               <?php
               return ob_get_clean();
       }

       public function render_results() {
               $this->enqueue_remote_assets();
               $id   = $this->unique_id( 'travely-widget-results-' );
               $path = esc_js( get_option( 'travely_widget_path_to_search', '/tour-search' ) );
               $key  = esc_js( get_option( 'travely_widget_key', '' ) );

               ob_start();
               ?>
               <div id="<?php echo esc_attr( $id ); ?>" class="travely-widget-results"></div>
               <script>(function(){if(!window.travelyWidgetInitialized){document.addEventListener('DOMContentLoaded',function(){if(window.TravelySearch){TravelySearch.initIframe('<?php echo esc_js( $id ); ?>','<?php echo $key; ?>','<?php echo $path; ?>');}});window.travelyWidgetInitialized=true;}})();</script>
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
