<?php

/**
 * Plugin updater class for GitHub releases.
 *
 * @link       https://travely-solutions.eu
 * @since      1.0.14
 *
 * @package    Travely_Widget
 * @subpackage Travely_Widget/includes
 */
class Travely_Widget_Updater {

    /**
     * The plugin file path.
     *
     * @var string
     */
    private $plugin_file;

    /**
     * The plugin basename.
     *
     * @var string
     */
    private $plugin_basename;

    /**
     * GitHub repository owner.
     *
     * @var string
     */
    private $github_owner;

    /**
     * GitHub repository name.
     *
     * @var string
     */
    private $github_repo;

    /**
     * Current plugin version.
     *
     * @var string
     */
    private $current_version;

    /**
     * Update URI for this plugin.
     *
     * @var string
     */
    private $update_uri;

    /**
     * Initialize the updater.
     *
     * @param string $plugin_file The main plugin file path.
     */
    public function __construct( $plugin_file ) {
        $this->plugin_file     = $plugin_file;
        $this->plugin_basename = plugin_basename( $plugin_file );
        $this->github_owner    = 'Travely-ee';
        $this->github_repo     = 'travely-widget-wordpress';
        $this->current_version = TRAVELY_WIDGET_VERSION;
        $this->update_uri      = 'https://github.com/Travely-ee/travely-widget-wordpress';

        add_filter( 'update_plugins_github.com', array( $this, 'check_update' ), 10, 4 );
        add_filter( 'plugins_api', array( $this, 'plugin_popup' ), 10, 3 );
        add_filter( 'upgrader_post_install', array( $this, 'after_install' ), 10, 3 );
        add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );

        register_deactivation_hook( $plugin_file, array( $this, 'clear_update_cache' ) );
    }

    /**
     * Clear the update cache.
     */
    public function clear_update_cache() {
        delete_transient( 'travely_widget_github_release' );
    }

    /**
     * Get repository information from GitHub API.
     *
     * @return object|WP_Error
     */
    private function get_repository_info() {
        $cache = get_transient( 'travely_widget_github_release' );

        if ( false !== $cache ) {
            return $cache;
        }

        $api_url  = sprintf( 'https://api.github.com/repos/%s/%s/releases/latest', $this->github_owner, $this->github_repo );
        $response = wp_remote_get(
            $api_url,
            array(
                'headers' => array(
                    'Accept'     => 'application/vnd.github.v3+json',
                    'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return new WP_Error( 'github_api_error', __( 'GitHub API request failed.', 'travely-widget' ) );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ) );

        if ( ! is_object( $data ) || empty( $data->tag_name ) ) {
            return new WP_Error( 'invalid_response', __( 'Invalid GitHub API response.', 'travely-widget' ) );
        }

        set_transient( 'travely_widget_github_release', $data, 12 * HOUR_IN_SECONDS );

        return $data;
    }

    /**
     * Check for updates via WordPress 5.8+ external update API.
     *
     * @param array|false $update      Update data.
     * @param array       $plugin_data Plugin headers.
     * @param string      $plugin_file Plugin basename.
     * @param array       $locales     Installed locales.
     * @return array|false
     */
    public function check_update( $update, $plugin_data, $plugin_file, $locales ) {
        unset( $locales );

        if ( $plugin_file !== $this->plugin_basename ) {
            return $update;
        }

        if ( isset( $plugin_data['UpdateURI'] ) && $this->update_uri !== $plugin_data['UpdateURI'] ) {
            return $update;
        }

        $repo_info = $this->get_repository_info();

        if ( is_wp_error( $repo_info ) ) {
            return $update;
        }

        $remote_version = ltrim( sanitize_text_field( $repo_info->tag_name ), 'v' );
        if ( ! version_compare( $this->current_version, $remote_version, '<' ) ) {
            return $update;
        }

        $package_url = $this->get_download_url( $repo_info );
        if ( empty( $package_url ) ) {
            return $update;
        }

        return array(
            'id'           => $this->update_uri,
            'slug'         => dirname( $this->plugin_basename ),
            'version'      => $remote_version,
            'url'          => 'https://github.com/Travely-ee/travely-widget-wordpress/releases/tag/' . rawurlencode( sanitize_text_field( $repo_info->tag_name ) ),
            'package'      => $package_url,
            'tested'       => '5.8',
            'requires_php' => '',
            'icons'        => array(),
            'banners'      => array(),
            'banners_rtl'  => array(),
            'translations' => array(),
        );
    }

    /**
     * Get the download URL from release assets.
     *
     * @param object $repo_info Repository information from GitHub.
     * @return string
     */
    private function get_download_url( $repo_info ) {
        if ( isset( $repo_info->assets ) && is_array( $repo_info->assets ) ) {
            foreach ( $repo_info->assets as $asset ) {
                if (
                    isset( $asset->browser_download_url, $asset->name ) &&
                    preg_match( '/^travely-widget-v?\d+\.\d+\.\d+.*\.zip$/i', $asset->name )
                ) {
                    return esc_url_raw( $asset->browser_download_url );
                }
            }
        }

        return '';
    }

    /**
     * Display plugin information popup.
     *
     * @param false|object|array $result Result.
     * @param string             $action Action.
     * @param object             $args API args.
     * @return false|object|array
     */
    public function plugin_popup( $result, $action, $args ) {
        if ( 'plugin_information' !== $action ) {
            return $result;
        }

        if ( ! isset( $args->slug ) || $args->slug !== dirname( $this->plugin_basename ) ) {
            return $result;
        }

        $repo_info = $this->get_repository_info();
        if ( is_wp_error( $repo_info ) ) {
            return $result;
        }

        $package_url = $this->get_download_url( $repo_info );
        if ( empty( $package_url ) ) {
            return $result;
        }

        $remote_version = ltrim( sanitize_text_field( $repo_info->tag_name ), 'v' );
        $release_notes  = isset( $repo_info->body ) ? wp_kses_post( $repo_info->body ) : esc_html__( 'Update available from GitHub.', 'travely-widget' );
        $published_at   = isset( $repo_info->published_at ) ? sanitize_text_field( $repo_info->published_at ) : '';

        return (object) array(
            'name'          => 'Travely Widget',
            'slug'          => dirname( $this->plugin_basename ),
            'version'       => $remote_version,
            'author'        => '<a href="https://travely-solutions.eu/">Travely Solutions OÜ</a>',
            'homepage'      => esc_url( $this->update_uri ),
            'requires'      => '5.8',
            'tested'        => '5.8',
            'downloaded'    => 0,
            'last_updated'  => $published_at,
            'sections'      => array(
                'description' => $release_notes,
                'changelog'   => $release_notes,
            ),
            'download_link' => $package_url,
        );
    }

    /**
     * Post-install actions.
     *
     * @param bool  $response   Install result.
     * @param array $hook_extra Extra arguments.
     * @param array $result     Installation result data.
     * @return bool
     */
    public function after_install( $response, $hook_extra, $result ) {
        unset( $result );

        if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_basename ) {
            return $response;
        }

        $this->clear_update_cache();

        return $response;
    }

    /**
     * Add plugin row meta links.
     *
     * @param array  $links An array of the plugin metadata.
     * @param string $file  Path to plugin file relative to plugins dir.
     * @return array
     */
    public function plugin_row_meta( $links, $file ) {
        if ( $file === $this->plugin_basename ) {
            $links[] = sprintf(
                '<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
                esc_url( $this->update_uri ),
                esc_html__( 'View on GitHub', 'travely-widget' )
            );
        }

        return $links;
    }
}

