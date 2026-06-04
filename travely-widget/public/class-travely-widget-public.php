<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://travely-solutions.eu
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
 * @author     Travely Solutions OÜ <info@travely.ee>
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
     * The resolved Travely widget language for the current page.
     *
     * @since    1.0.15
     * @access   private
     * @var      string    $resolved_page_language    The page-level Travely language.
     */
    private $resolved_page_language = '';

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
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }

        register_block_type(
            plugin_dir_path( dirname( __FILE__ ) ) . 'blocks/search',
            array(
                'render_callback' => array( $this, 'render_search' ),
            )
        );

        register_block_type(
            plugin_dir_path( dirname( __FILE__ ) ) . 'blocks/results',
            array(
                'render_callback' => array( $this, 'render_results' ),
            )
        );
    }

    private function enqueue_remote_assets( $language ) {
        $language = Travely_Widget_Language::normalize( $language );
        $base_url = 'https://wgsearch.travely.ee';

        wp_enqueue_style(
            'travely-widget-remote-css',
            $base_url . '/' . $language . '/static/css/main.css',
            array(),
            $this->version
        );

        wp_enqueue_script(
            'travely-widget-remote-js',
            $base_url . '/' . $language . '/static/js/main.js',
            array(),
            $this->version,
            true
        );
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

    private function get_page_language( $language ) {
        $language = Travely_Widget_Language::normalize( $language );

        if ( '' === $this->resolved_page_language ) {
            $this->resolved_page_language = $language;

            return $language;
        }

        if ( $this->resolved_page_language !== $language ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                trigger_error(
                    sprintf(
                        esc_html__(
                            'Travely Widget: multiple languages on one page are not supported. Requested "%s", using "%s".',
                            'travely-widget'
                        ),
                        esc_html( $language ),
                        esc_html( $this->resolved_page_language )
                    ),
                    E_USER_WARNING
                );
            }

            return $this->resolved_page_language;
        }

        return $language;
    }

    public function render_search( $atts = array() ) {
        $atts = shortcode_atts(
            array(
                'language' => 'auto',
            ),
            $atts,
            'travely-widget-search'
        );

        $language = Travely_Widget_Language::resolve_language( $atts );
        $language = $this->get_page_language( $language );

        $this->enqueue_remote_assets( $language );
        $this->enqueue_local_assets();
        $id   = $this->unique_id( 'travely-widget-search-' );
        $path = get_option( 'travely_widget_path_to_search', '/tour-search' );
        $path = apply_filters( 'travely_widget_path_to_search', $path, $language );

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $id ); ?>" class="travely-widget-search" data-travely-widget="true" data-mode="search" data-path="<?php echo esc_attr( $path ); ?>" data-language="<?php echo esc_attr( $language ); ?>"></div>
        <?php
        return ob_get_clean();
    }

    public function render_search_country( $atts = array() ) {
        $atts = shortcode_atts(
            array(
                'language' => 'auto',
            ),
            $atts,
            'travely-widget-search-country'
        );

        $language = Travely_Widget_Language::resolve_language( $atts );
        $language = $this->get_page_language( $language );

        $this->enqueue_remote_assets( $language );
        $this->enqueue_local_assets();
        $id   = $this->unique_id( 'travely-widget-search-country-' );
        $path = get_option( 'travely_widget_path_to_search', '/tour-search' );
        $path = apply_filters( 'travely_widget_path_to_search', $path, $language );

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $id ); ?>" class="travely-widget-search-country" data-travely-widget="true" data-mode="search,country" data-path="<?php echo esc_attr( $path ); ?>" data-language="<?php echo esc_attr( $language ); ?>"></div>
        <?php
        return ob_get_clean();
    }

    public function render_country( $atts = array() ) {
        $atts = shortcode_atts(
            array(
                'language' => 'auto',
            ),
            $atts,
            'travely-widget-country'
        );

        $language = Travely_Widget_Language::resolve_language( $atts );
        $language = $this->get_page_language( $language );

        $this->enqueue_remote_assets( $language );
        $this->enqueue_local_assets();
        $id   = $this->unique_id( 'travely-widget-country-' );
        $path = get_option( 'travely_widget_path_to_search', '/tour-search' );
        $path = apply_filters( 'travely_widget_path_to_search', $path, $language );

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $id ); ?>" class="travely-widget-country" data-travely-widget="true" data-mode="country" data-path="<?php echo esc_attr( $path ); ?>" data-language="<?php echo esc_attr( $language ); ?>"></div>
        <?php
        return ob_get_clean();
    }

    public function render_results( $atts = array() ) {
        $atts = shortcode_atts(
            array(
                'language' => 'auto',
            ),
            $atts,
            'travely-widget-results'
        );

        $language = Travely_Widget_Language::resolve_language( $atts );
        $language = $this->get_page_language( $language );

        $this->enqueue_remote_assets( $language );
        $this->enqueue_local_assets();
        $id   = $this->unique_id( 'travely-widget-results-' );
        $path = get_option( 'travely_widget_path_to_search', '/tour-search' );
        $path = apply_filters( 'travely_widget_path_to_search', $path, $language );
        $key  = get_option( 'travely_widget_key', '' );

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $id ); ?>" class="travely-widget-results" data-travely-widget="true" data-mode="results" data-path="<?php echo esc_attr( $path ); ?>" data-key="<?php echo esc_attr( $key ); ?>" data-language="<?php echo esc_attr( $language ); ?>"></div>
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

