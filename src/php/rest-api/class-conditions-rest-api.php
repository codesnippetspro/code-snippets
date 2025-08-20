<?php

namespace Code_Snippets\REST_API;

use WP_Error;
use WP_HTTP_Response;
use WP_REST_Server;
use WP_REST_Response;
use function Code_Snippets\code_snippets;
use function wp_roles;
use const Code_Snippets\REST_API_NAMESPACE;

/**
 * Provides object data to the snippet condition editor.
 *
 * @package Code_Snippets
 */
final class Conditions_REST_API {

	/**
	 * Current API version.
	 */
	public const VERSION = 1;

	/**
	 * The base route for these API endpoints.
	 */
	public const BASE_ROUTE = 'conditions';

	/**
	 * Retrieve this controller's REST API base path, including namespace.
	 *
	 * @return string
	 */
	public static function get_base_route(): string {
		return REST_API_NAMESPACE . self::VERSION . '/' . self::BASE_ROUTE;
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes() {
		$plugin = code_snippets();

		$readable_routes = [
			'/roles'        => [ $this, 'get_role_names' ],
			'/capabilities' => [ $this, 'get_capabilities' ],
			'/locales'      => [ $this, 'get_available_locales' ],
			'/plugins'      => [ $this, 'get_installed_plugins' ],
			'/themes'       => [ $this, 'get_installed_themes' ],
		];

		foreach ( $readable_routes as $route => $callback ) {
			register_rest_route(
				REST_API_NAMESPACE . self::VERSION,
				self::BASE_ROUTE . $route,
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => $callback,
					'permission_callback' => [ $plugin, 'current_user_can' ],
				],
			);
		}
	}

	/**
	 * Retrieve a list of role information.
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function get_role_names() {
		$roles = [];

		foreach ( wp_roles()->get_names() as $role => $display_name ) {
			$roles[] = [
				'role' => $role,
				'name' => $display_name,
			];
		}

		return rest_ensure_response( $roles );
	}

	/**
	 * Retrieve a full list of capabilities.
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function get_capabilities() {
		$caps = [];

		foreach ( wp_roles()->roles as $role_info ) {
			$caps = array_merge( $caps, array_keys( $role_info['capabilities'] ) );
		}

		return rest_ensure_response( array_unique( $caps ) );
	}

	/**
	 * Retrieve a list of available locales.
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function get_available_locales() {
		require_once ABSPATH . 'wp-admin/includes/translation-install.php';
		$locales = [];

		foreach ( wp_get_available_translations() as $locale => $translation ) {
			$locales[] = [
				'locale' => $locale,
				'name'   => $translation['native_name'],
			];
		}

		return rest_ensure_response( $locales );
	}

	/**
	 * Retrieve a list of plugins installed on the site.
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function get_installed_plugins() {
		$plugins = [];

		foreach ( get_plugins() as $filename => $plugin_data ) {
			$plugins[] = [
				'filename' => $filename,
				'name'     => $plugin_data['Title'],
			];
		}

		return rest_ensure_response( $plugins );
	}

	/**
	 * Retrieve a list of themes installed on the site.
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function get_installed_themes() {
		$themes = [];

		foreach ( search_theme_directories() as $slug => $location ) {
			$theme = wp_get_theme( $slug, $location['theme_root'] );

			$themes[] = [
				'name'       => $theme->display( 'Name', false ),
				'stylesheet' => $theme->get_stylesheet(),
				'template'   => $theme->get_template(),
			];
		}

		return rest_ensure_response( $themes );
	}
}
