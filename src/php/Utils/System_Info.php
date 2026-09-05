<?php

namespace Code_Snippets\Utils;

use const Code_Snippets\PLUGIN_VERSION;

/**
 * Describes the site a feedback report came from.
 *
 * Everything here is collected on the server rather than in the browser, so a report carries
 * the version numbers needed to reproduce a problem without the reporter having to look any
 * of them up. The summary is the subset shown in the panel before the report is sent, so
 * nothing leaves the site that the reporter has not been told about.
 *
 * @package Code_Snippets
 */
class System_Info {

	/**
	 * Collect the details describing this installation.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_system_info(): array {
		global $wp_version, $wpdb;

		$theme = wp_get_theme();
		$plugins = self::get_active_plugins();

		$info = [
			'plugin_version'     => PLUGIN_VERSION,
			'edition'            => self::get_edition(),
			'wordpress_version'  => $wp_version,
			'php_version'        => PHP_VERSION,
			'database'           => $wpdb->db_server_info(),
			'active_theme'       => trim( sprintf( '%s %s', $theme->get( 'Name' ), $theme->get( 'Version' ) ) ),
			'active_plugins'     => $plugins,
			'plugin_count'       => count( $plugins ),
			'multisite'          => is_multisite(),
			'locale'             => get_locale(),
			'wp_debug'           => defined( 'WP_DEBUG' ) && WP_DEBUG,
			'wp_memory_limit'    => defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : '',
			'php_memory_limit'   => ini_get( 'memory_limit' ),
			'max_execution_time' => ini_get( 'max_execution_time' ),
			'server_software'    => isset( $_SERVER['SERVER_SOFTWARE'] )
				? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) )
				: '',
			'site_url'           => site_url(),
		];

		return apply_filters( 'code_snippets_feedback_system_info', $info );
	}

	/**
	 * Describe the collected details in the form shown in the panel.
	 *
	 * Every value the report carries appears here. The panel is where the reporter is told
	 * what they are about to send, so anything left out would go without disclosure.
	 *
	 * @param array<string, mixed> $info Collected system information.
	 *
	 * @return array<string, string> Label and value pairs.
	 */
	public static function get_summary( array $info ): array {
		$version = sprintf(
			'%s (%s)',
			$info['plugin_version'],
			'pro' === $info['edition']
				? __( 'Pro', 'code-snippets' )
				: __( 'Free', 'code-snippets' )
		);

		$wordpress = $info['multisite']
			? sprintf( '%s (%s)', $info['wordpress_version'], __( 'multisite', 'code-snippets' ) )
			: $info['wordpress_version'];

		$limits = sprintf(
			/* translators: 1: WordPress memory limit, 2: PHP memory limit, 3: maximum execution time, in seconds. */
			__( 'WordPress %1$s, PHP %2$s, %3$ss execution', 'code-snippets' ),
			$info['wp_memory_limit'],
			$info['php_memory_limit'],
			$info['max_execution_time']
		);

		return [
			__( 'Code Snippets', 'code-snippets' ) => $version,
			__( 'WordPress', 'code-snippets' )     => $wordpress,
			__( 'PHP', 'code-snippets' )           => $info['php_version'],
			__( 'Database', 'code-snippets' )      => $info['database'],
			__( 'Theme', 'code-snippets' )         => $info['active_theme'],
			__( 'Server', 'code-snippets' )        => $info['server_software'],
			__( 'Language', 'code-snippets' )      => $info['locale'],
			__( 'Debug mode', 'code-snippets' )    => $info['wp_debug'] ? __( 'on', 'code-snippets' ) : __( 'off', 'code-snippets' ),
			__( 'Limits', 'code-snippets' )        => $limits,
			__( 'Site address', 'code-snippets' )  => $info['site_url'],
			__( 'Plugins', 'code-snippets' )       => $info['active_plugins']
				? implode( ', ', $info['active_plugins'] )
				: __( 'none active', 'code-snippets' ),
		];
	}

	/**
	 * Which edition of the plugin is running.
	 *
	 * @return string Either 'free' or 'pro'.
	 */
	public static function get_edition(): string {
		return defined( 'CODE_SNIPPETS_PRO' ) && CODE_SNIPPETS_PRO ? 'pro' : 'free';
	}

	/**
	 * List the name and version of every active plugin, sorted for a stable comparison
	 * between one report and the next.
	 *
	 * @return string[]
	 */
	private static function get_active_plugins(): array {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = [];

		foreach ( get_plugins() as $file => $data ) {
			if ( is_plugin_active( $file ) || is_plugin_active_for_network( $file ) ) {
				$plugins[] = trim( sprintf( '%s %s', $data['Name'], $data['Version'] ) );
			}
		}

		sort( $plugins );

		return $plugins;
	}
}
