<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://travely-solutions.eu
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
 * @author     Travely Solutions OÜ <info@travely.ee>
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
            <p><?php esc_html_e( 'Configure your Travely Widget public key, search results page path and language behavior.', 'travely-widget' ); ?></p>
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
            'travely_widget_path_mode',
            array(
                'type'              => 'string',
                'sanitize_callback' => array( $this, 'sanitize_path_mode' ),
                'default'           => 'single',
            )
        );

        register_setting(
            'travely_widget_option_group',
            'travely_widget_path_to_search_est',
            array(
                'type'              => 'string',
                'sanitize_callback' => array( $this, 'sanitize_optional_path_to_search' ),
                'default'           => '/tour-search',
            )
        );

        register_setting(
            'travely_widget_option_group',
            'travely_widget_path_to_search_eng',
            array(
                'type'              => 'string',
                'sanitize_callback' => array( $this, 'sanitize_optional_path_to_search' ),
                'default'           => '',
            )
        );

        register_setting(
            'travely_widget_option_group',
            'travely_widget_path_to_search_rus',
            array(
                'type'              => 'string',
                'sanitize_callback' => array( $this, 'sanitize_optional_path_to_search' ),
                'default'           => '',
            )
        );

        register_setting(
            'travely_widget_option_group',
            'travely_widget_path_to_search_lav',
            array(
                'type'              => 'string',
                'sanitize_callback' => array( $this, 'sanitize_optional_path_to_search' ),
                'default'           => '',
            )
        );

        register_setting(
            'travely_widget_option_group',
            'travely_widget_default_language',
            array(
                'type'              => 'string',
                'sanitize_callback' => array( $this, 'sanitize_language' ),
                'default'           => 'est',
            )
        );

        register_setting(
            'travely_widget_option_group',
            'travely_widget_force_default_language',
            array(
                'type'              => 'boolean',
                'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
                'default'           => false,
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
        add_settings_section(
            'travely_widget_language_paths_section',
            esc_html__( 'Language-specific paths', 'travely-widget' ),
            null,
            'travely-widget-admin'
        );

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
            'travely_widget_path_mode',
            esc_html__( 'Path mode', 'travely-widget' ),
            array( $this, 'path_mode_callback' ),
            'travely-widget-admin',
            'travely_widget_setting_section'
        );

        add_settings_field(
            'travely_widget_path_to_search_est',
            esc_html__( 'Estonian search path', 'travely-widget' ),
            array( $this, 'path_est_callback' ),
            'travely-widget-admin',
            'travely_widget_language_paths_section'
        );

        add_settings_field(
            'travely_widget_path_to_search_eng',
            esc_html__( 'English search path', 'travely-widget' ),
            array( $this, 'path_eng_callback' ),
            'travely-widget-admin',
            'travely_widget_language_paths_section'
        );

        add_settings_field(
            'travely_widget_path_to_search_rus',
            esc_html__( 'Russian search path', 'travely-widget' ),
            array( $this, 'path_rus_callback' ),
            'travely-widget-admin',
            'travely_widget_language_paths_section'
        );

        add_settings_field(
            'travely_widget_path_to_search_lav',
            esc_html__( 'Latvian search path', 'travely-widget' ),
            array( $this, 'path_lav_callback' ),
            'travely-widget-admin',
            'travely_widget_language_paths_section'
        );

        add_settings_field(
            'travely_widget_default_language',
            esc_html__( 'Default language', 'travely-widget' ),
            array( $this, 'default_language_callback' ),
            'travely-widget-admin',
            'travely_widget_setting_section'
        );

        add_settings_field(
            'travely_widget_force_default_language',
            esc_html__( 'Force default language', 'travely-widget' ),
            array( $this, 'force_default_language_callback' ),
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

    public function path_mode_callback() {
        $value = $this->sanitize_path_mode( get_option( 'travely_widget_path_mode', 'single' ) );

        $options = array(
            'single'   => __( 'Single path for all languages', 'travely-widget' ),
            'language' => __( 'Separate path for each language', 'travely-widget' ),
        );

        echo '<select id="travely_widget_path_mode" name="travely_widget_path_mode">';

        foreach ( $options as $mode => $label ) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr( $mode ),
                selected( $value, $mode, false ),
                esc_html( $label )
            );
        }

        echo '</select>';

        printf(
            '<p class="description">%s</p>',
            esc_html__(
                'Choose whether all widget languages use the same search results page path or each language has its own path.',
                'travely-widget'
            )
        );
    }

    public function default_language_callback() {
        $value     = Travely_Widget_Language::normalize( get_option( 'travely_widget_default_language', 'est' ) );
        $languages = Travely_Widget_Language::allowed_languages();

        echo '<select id="travely_widget_default_language" name="travely_widget_default_language">';

        foreach ( $languages as $code => $label ) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr( $code ),
                selected( $value, $code, false ),
                esc_html( $label )
            );
        }

        echo '</select>';

        printf(
            '<p class="description">%s</p>',
            esc_html__( 'Used when language cannot be detected automatically.', 'travely-widget' )
        );
    }

    public function force_default_language_callback() {
        $value = (bool) get_option( 'travely_widget_force_default_language', false );

        echo '<input type="hidden" name="travely_widget_force_default_language" value="0" />';

        printf(
            '<label><input type="checkbox" name="travely_widget_force_default_language" value="1" %s /> %s</label>',
            checked( true, $value, false ),
            esc_html__( 'Always use the selected default language.', 'travely-widget' )
        );

        printf(
            '<p class="description">%s</p>',
            esc_html__( 'When enabled, URL language, Polylang/WPML language and WordPress locale are ignored.', 'travely-widget' )
        );
    }

    private function language_path_input( $option_name, $label_description = '', $default = '' ) {
        $value = get_option( $option_name, $default );

        printf(
            '<input type="text" id="%1$s" name="%1$s" value="%2$s" />',
            esc_attr( $option_name ),
            esc_attr( $value )
        );

        if ( '' !== $label_description ) {
            printf(
                '<p class="description">%s</p>',
                esc_html( $label_description )
            );
        }
    }

    public function path_est_callback() {
        $this->language_path_input(
            'travely_widget_path_to_search_est',
            __( 'Used only when Path mode is set to Separate path for each language.', 'travely-widget' ),
            '/tour-search'
        );
    }

    public function path_eng_callback() {
        $this->language_path_input(
            'travely_widget_path_to_search_eng',
            __( 'Used only when Path mode is set to Separate path for each language.', 'travely-widget' )
        );
    }

    public function path_rus_callback() {
        $this->language_path_input(
            'travely_widget_path_to_search_rus',
            __( 'Used only when Path mode is set to Separate path for each language.', 'travely-widget' )
        );
    }

    public function path_lav_callback() {
        $this->language_path_input(
            'travely_widget_path_to_search_lav',
            __( 'Used only when Path mode is set to Separate path for each language.', 'travely-widget' )
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

    public function sanitize_optional_path_to_search( $value ) {
        $value = sanitize_text_field( $value );
        $value = trim( $value );

        if ( '' === $value ) {
            return '';
        }

        if ( preg_match( '#^https?://#i', $value ) ) {
            $path  = wp_parse_url( $value, PHP_URL_PATH );
            $value = $path ? $path : '';
        }

        if ( '' !== $value && '/' !== substr( $value, 0, 1 ) ) {
            $value = '/' . $value;
        }

        return $value;
    }

    public function sanitize_path_mode( $value ) {
        $value = sanitize_text_field( $value );

        return in_array( $value, array( 'single', 'language' ), true ) ? $value : 'single';
    }

    public function sanitize_language( $value ) {
        return Travely_Widget_Language::normalize( $value );
    }

    public function sanitize_checkbox( $value ) {
        return ! empty( $value );
    }
}
