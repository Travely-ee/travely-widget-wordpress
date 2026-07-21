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

    private function get_path_to_search( $language ) {
        $language  = Travely_Widget_Language::normalize( $language );
        $path_mode = get_option( 'travely_widget_path_mode', 'single' );
        $path      = get_option( 'travely_widget_path_to_search', '/tour-search' );

        if ( 'language' === $path_mode ) {
            $language_path_default = 'est' === $language ? '/tour-search' : '';
            $language_path         = get_option( 'travely_widget_path_to_search_' . $language, $language_path_default );

            if ( '' !== trim( (string) $language_path ) ) {
                $path = $language_path;
            }
        }

        if ( '' === trim( (string) $path ) ) {
            $path = '/tour-search';
        }

        return apply_filters( 'travely_widget_path_to_search', $path, $language );
    }

    private function sanitize_widget_background( $value ) {
        $value = sanitize_text_field( $value );
        $value = trim( $value );

        if ( '' === $value ) {
            return '';
        }

        if ( 'transparent' === strtolower( $value ) ) {
            return 'transparent';
        }

        if ( preg_match( '/^#(?:[0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $value ) ) {
            return $value;
        }

        if ( preg_match( '/^rgba?\([0-9\s,\.%]+\)$/i', $value ) || preg_match( '/^hsla?\([0-9\s,\.%a-z+-]+\)$/i', $value ) ) {
            return $value;
        }

        return '';
    }

    private function get_results_background( $shortcode_background = '' ) {
        $shortcode_background = $this->sanitize_widget_background( $shortcode_background );

        if ( '' !== $shortcode_background ) {
            return $shortcode_background;
        }

        return $this->sanitize_widget_background(
            get_option( 'travely_widget_results_background', '' )
        );
    }

    /**
     * Check whether the current singular page contains the results widget.
     *
     * Sites that render the widget from a theme template or a dynamic builder
     * can override the result with the travely_widget_enable_geolocation_policy
     * filter.
     *
     * @since 1.0.22
     * @return bool
     */
    private function current_page_has_results_widget() {
        if ( ! is_singular() ) {
            return false;
        }

        $post = get_queried_object();

        if ( ! $post instanceof WP_Post ) {
            return false;
        }

        return has_shortcode( $post->post_content, 'travely-widget-results' )
            || has_block( 'travely/widget-results', $post->post_content );
    }

    /**
     * Normalize a configured booking URL to an HTTP(S) origin.
     *
     * @since 1.0.22
     * @param string $url Booking application URL or origin.
     * @return string
     */
    private function normalize_booking_origin( $url ) {
        $parts = wp_parse_url( (string) $url );

        if (
            ! is_array( $parts )
            || empty( $parts['scheme'] )
            || empty( $parts['host'] )
            || ! in_array( strtolower( $parts['scheme'] ), array( 'http', 'https' ), true )
        ) {
            return '';
        }

        $origin = strtolower( $parts['scheme'] ) . '://' . $parts['host'];

        if ( ! empty( $parts['port'] ) ) {
            $origin .= ':' . absint( $parts['port'] );
        }

        return $origin;
    }

    /**
     * Replace one directive while preserving the rest of Permissions-Policy.
     *
     * @since 1.0.22
     * @param string $policy Existing Permissions-Policy value.
     * @param string $directive Directive name.
     * @param string $value Directive value.
     * @return string
     */
    private function replace_permissions_policy_directive( $policy, $directive, $value ) {
        $items       = array_filter( array_map( 'trim', explode( ',', (string) $policy ) ) );
        $replacement = $directive . '=' . $value;
        $result      = array();
        $replaced    = false;

        foreach ( $items as $item ) {
            if ( preg_match( '/^' . preg_quote( $directive, '/' ) . '\s*=/i', $item ) ) {
                if ( ! $replaced ) {
                    $result[] = $replacement;
                    $replaced = true;
                }

                continue;
            }

            $result[] = $item;
        }

        if ( ! $replaced ) {
            $result[] = $replacement;
        }

        return implode( ', ', $result );
    }

    /**
     * Allow the Travely booking iframe to request geolocation.
     *
     * Existing Permissions-Policy directives are retained. Only the
     * geolocation directive is added or replaced.
     *
     * @since 1.0.22
     * @param array $headers HTTP response headers generated by WordPress.
     * @return array
     */
    public function filter_permissions_policy_header( $headers ) {
        if ( ! is_array( $headers ) ) {
            return $headers;
        }

        $enabled = (bool) apply_filters(
            'travely_widget_enable_geolocation_policy',
            $this->current_page_has_results_widget(),
            get_queried_object()
        );

        if ( ! $enabled ) {
            return $headers;
        }

        $booking_origin = apply_filters(
            'travely_widget_booking_origin',
            'https://wgbooking.travely.ee'
        );
        $booking_origin = $this->normalize_booking_origin( $booking_origin );

        if ( '' === $booking_origin ) {
            return $headers;
        }

        $header_name = 'Permissions-Policy';

        foreach ( array_keys( $headers ) as $name ) {
            if ( 0 === strcasecmp( $name, $header_name ) ) {
                $header_name = $name;
                break;
            }
        }

        $current_policy = isset( $headers[ $header_name ] )
            ? (string) $headers[ $header_name ]
            : '';

        $headers[ $header_name ] = $this->replace_permissions_policy_directive(
            $current_policy,
            'geolocation',
            '(self "' . $booking_origin . '")'
        );

        return $headers;
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
        $path = $this->get_path_to_search( $language );

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
        $path = $this->get_path_to_search( $language );

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
        $path = $this->get_path_to_search( $language );

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
                'background' => '',
            ),
            $atts,
            'travely-widget-results'
        );

        $language = Travely_Widget_Language::resolve_language( $atts );
        $language = $this->get_page_language( $language );

        $this->enqueue_remote_assets( $language );
        $this->enqueue_local_assets();
        $id   = $this->unique_id( 'travely-widget-results-' );
        $path = $this->get_path_to_search( $language );
        $key  = get_option( 'travely_widget_key', '' );
        $background = $this->get_results_background( $atts['background'] );

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $id ); ?>" class="travely-widget-results" data-travely-widget="true" data-mode="results" data-path="<?php echo esc_attr( $path ); ?>" data-key="<?php echo esc_attr( $key ); ?>" data-language="<?php echo esc_attr( $language ); ?>" data-background="<?php echo esc_attr( $background ); ?>"></div>
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

