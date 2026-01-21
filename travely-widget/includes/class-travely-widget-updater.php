<?php

/**
 * Plugin updater class for GitHub releases
 *
 * Handles automatic updates for the plugin through GitHub releases.
 * Integrates with WordPress update system to check for new versions,
 * display update information, and install updates from GitHub.
 *
 * @link       http://travely.ee
 * @since      1.0.11
 *
 * @package    Travely_Widget
 * @subpackage Travely_Widget/includes
 */

/**
 * Plugin updater class for GitHub releases.
 *
 * This class integrates with the WordPress plugin update system to provide
 * automatic updates from GitHub releases. It checks for new versions,
 * displays update information, and handles the installation process.
 *
 * @package    Travely_Widget
 * @subpackage Travely_Widget/includes
 * @author     Travely OU <info@travely.ee>
 */
class Travely_Widget_Updater {

	/**
	 * The plugin file path.
	 *
	 * @since    1.0.11
	 * @access   private
	 * @var      string    $plugin_file    The main plugin file path.
	 */
	private $plugin_file;

	/**
	 * The plugin basename.
	 *
	 * @since    1.0.11
	 * @access   private
	 * @var      string    $plugin_basename    The plugin basename.
	 */
	private $plugin_basename;

	/**
	 * GitHub repository owner.
	 *
	 * @since    1.0.11
	 * @access   private
	 * @var      string    $github_owner    The GitHub repository owner.
	 */
	private $github_owner;

	/**
	 * GitHub repository name.
	 *
	 * @since    1.0.11
	 * @access   private
	 * @var      string    $github_repo    The GitHub repository name.
	 */
	private $github_repo;

	/**
	 * Current plugin version.
	 *
	 * @since    1.0.11
	 * @access   private
	 * @var      string    $current_version    The current plugin version.
	 */
	private $current_version;

	/**
	 * Initialize the updater.
	 *
	 * @since    1.0.11
	 * @param    string    $plugin_file    The main plugin file path.
	 */
	public function __construct( $plugin_file ) {
		$this->plugin_file     = $plugin_file;
		$this->plugin_basename = plugin_basename( $plugin_file );
		$this->github_owner    = 'travely-ee';
		$this->github_repo     = 'travely-widget-wordpress';
		$this->current_version = TRAVELY_WIDGET_VERSION;

		// Hook into WordPress update system
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'modify_transient' ), 10, 1 );
		add_filter( 'plugins_api', array( $this, 'plugin_popup' ), 10, 3 );
		add_filter( 'upgrader_post_install', array( $this, 'after_install' ), 10, 3 );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );

		// Clear cache on plugin deactivation
		register_deactivation_hook( $plugin_file, array( $this, 'clear_update_cache' ) );
	}

	/**
	 * Clear the update cache.
	 *
	 * Removes the cached GitHub release information.
	 *
	 * @since    1.0.11
	 */
	public function clear_update_cache() {
		delete_transient( 'travely_widget_github_release' );
	}

	/**
	 * Get repository information from GitHub API.
	 *
	 * Fetches the latest release information from GitHub and caches it
	 * for 12 hours to avoid rate limiting issues.
	 *
	 * @since    1.0.11
	 * @return   object|WP_Error    The repository information or WP_Error on failure.
	 */
	private function get_repository_info() {
		$cache_key = 'travely_widget_github_release';
		$cache     = get_transient( $cache_key );

		if ( false !== $cache ) {
			return $cache;
		}

		$api_url  = sprintf(
			'https://api.github.com/repos/%s/%s/releases/latest',
			$this->github_owner,
			$this->github_repo
		);
		$response = wp_remote_get( $api_url, array(
			'headers' => array(
				'Accept'     => 'application/vnd.github.v3+json',
				'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
			),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			return new WP_Error( 'github_api_error', 'GitHub API request failed' );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body );

		if ( ! $data || ! isset( $data->tag_name ) ) {
			return new WP_Error( 'invalid_response', 'Invalid GitHub API response' );
		}

		// Cache for 12 hours
		set_transient( $cache_key, $data, 12 * HOUR_IN_SECONDS );

		return $data;
	}

	/**
	 * Modify the plugin update transient.
	 *
	 * Checks for updates on GitHub and adds update information to the
	 * WordPress transient if a newer version is available.
	 *
	 * @since    1.0.11
	 * @param    object    $transient    The update_plugins transient object.
	 * @return   object    The modified transient object.
	 */
	public function modify_transient( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$repo_info = $this->get_repository_info();

		if ( is_wp_error( $repo_info ) ) {
			return $transient;
		}

		// Parse version from tag (remove 'v' prefix)
		$remote_version = ltrim( $repo_info->tag_name, 'v' );

		// Compare versions
		if ( version_compare( $this->current_version, $remote_version, '<' ) ) {
			$plugin_data = array(
				'slug'        => dirname( $this->plugin_basename ),
				'plugin'      => $this->plugin_basename,
				'new_version' => $remote_version,
				'url'         => sprintf(
					'https://github.com/%s/%s',
					$this->github_owner,
					$this->github_repo
				),
				'package'     => $this->get_download_url( $repo_info ),
				'icons'       => array(),
				'banners'     => array(),
				'tested'      => '',
				'requires'    => '4.3',
			);

			$transient->response[ $this->plugin_basename ] = (object) $plugin_data;
		}

		return $transient;
	}

	/**
	 * Get the download URL for the release.
	 *
	 * Searches for a ZIP asset in the release, falls back to zipball_url.
	 *
	 * @since    1.0.11
	 * @param    object    $repo_info    The repository information from GitHub.
	 * @return   string    The download URL.
	 */
	private function get_download_url( $repo_info ) {
		// Look for ZIP asset in release
		if ( isset( $repo_info->assets ) && is_array( $repo_info->assets ) ) {
			foreach ( $repo_info->assets as $asset ) {
				if ( isset( $asset->browser_download_url ) && preg_match( '/\.zip$/i', $asset->name ) ) {
					return $asset->browser_download_url;
				}
			}
		}

		// Fallback to zipball_url
		return isset( $repo_info->zipball_url ) ? $repo_info->zipball_url : '';
	}

	/**
	 * Display plugin information popup.
	 *
	 * Provides detailed plugin information for the WordPress update modal.
	 *
	 * @since    1.0.11
	 * @param    false|object|array    $result    The result object or array.
	 * @param    string                $action    The type of information being requested.
	 * @param    object                $args      Plugin API arguments.
	 * @return   false|object|array    Modified result object or original value.
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

		$remote_version = ltrim( $repo_info->tag_name, 'v' );

		$plugin_data = array(
			'name'          => 'Travely Widget',
			'slug'          => dirname( $this->plugin_basename ),
			'version'       => $remote_version,
			'author'        => '<a href="http://travely.ee">Travely OU</a>',
			'homepage'      => sprintf(
				'https://github.com/%s/%s',
				$this->github_owner,
				$this->github_repo
			),
			'requires'      => '4.3',
			'tested'        => '',
			'downloaded'    => 0,
			'last_updated'  => $repo_info->published_at,
			'sections'      => array(
				'description' => isset( $repo_info->body ) ? $repo_info->body : 'Update available from GitHub.',
			),
			'download_link' => $this->get_download_url( $repo_info ),
		);

		return (object) $plugin_data;
	}

	/**
	 * Post-install actions.
	 *
	 * Handles file structure after installation and reactivates the plugin
	 * if it was previously active.
	 *
	 * @since    1.0.11
	 * @param    bool    $true           Install result.
	 * @param    mixed   $hook_extra     Extra arguments passed to the filter.
	 * @param    array   $result         Installation result data.
	 * @return   bool    Install result.
	 */
	public function after_install( $true, $hook_extra, $result ) {
		global $wp_filesystem;

		if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_basename ) {
			return $true;
		}

		// Initialize the WP filesystem if needed
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		// Check if plugin was active before update
		$was_active = is_plugin_active( $this->plugin_basename );

		$plugin_folder = WP_PLUGIN_DIR . '/' . dirname( $this->plugin_basename );

		// Only move if destination is different from expected plugin folder
		// GitHub zipball extracts to "owner-repo-hash" folder, release asset extracts to "travely-widget"
		if ( $result['destination'] !== $plugin_folder ) {
			// Remove old plugin folder if exists
			if ( $wp_filesystem->exists( $plugin_folder ) ) {
				$wp_filesystem->delete( $plugin_folder, true );
			}

			$wp_filesystem->move( $result['destination'], $plugin_folder );
			$result['destination'] = $plugin_folder;
		}

		// Reactivate plugin if it was active before update
		if ( $was_active ) {
			activate_plugin( $this->plugin_basename );
		}

		return $result;
	}

	/**
	 * Add plugin row meta links.
	 *
	 * Adds a link to the GitHub repository in the plugin row.
	 *
	 * @since    1.0.11
	 * @param    array     $links    An array of the plugin's metadata.
	 * @param    string    $file     Path to the plugin file relative to the plugins directory.
	 * @return   array     Modified array of links.
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( $file === $this->plugin_basename ) {
			$github_link = sprintf(
				'<a href="https://github.com/%s/%s" target="_blank">%s</a>',
				$this->github_owner,
				$this->github_repo,
				__( 'View on GitHub', 'travely-widget' )
			);
			$links[] = $github_link;
		}

		return $links;
	}
}
