<?php

namespace Code_Snippets\Integration\Promotions\Notices;

/**
 * Promotion class for the Insert Headers and Footers plugin.
 *
 * @link https://wordpress.org/plugins/wp-headers-and-footers/
 */
class WP_Headers_And_Footers extends Promotion_Base {

	/**
	 * Get the name of the plugin being promoted.
	 *
	 * @return string The plugin name.
	 */
	public function get_plugin_name(): string {
		return __( 'Insert Headers and Footers', 'code-snippets' );
	}

	/**
	 * Get the slug of the plugin being promoted.
	 *
	 * @return string The plugin slug.
	 */
	public function get_plugin_slug(): string {
		return 'wp-headers-and-footers';
	}

	/**
	 * Get the admin screens where the promotion should be displayed.
	 *
	 * @return array An array of admin screen IDs.
	 */
	public function get_plugin_admin_screens(): array {
		return [
			'settings_page_wp-headers-and-footers',
		];
	}

	/**
	 * Get the heading text for the promotion notice.
	 *
	 * @return string The promotion heading.
	 */
	public function get_promotion_heading(): string {
		return __( 'Looking for a better way to manage your custom code?', 'code-snippets' );
	}
}
