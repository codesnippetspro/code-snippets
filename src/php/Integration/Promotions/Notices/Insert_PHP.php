<?php

namespace Code_Snippets\Integration\Promotions\Notices;

/**
 * Promotion class for the Woody Code Snippets plugin.
 *
 * @link https://wordpress.org/plugins/insert-php/
 */
class Insert_PHP extends Promotion_Base {

	/**
	 * Get the name of the plugin being promoted.
	 *
	 * @return string The plugin name.
	 */
	public function get_plugin_name(): string {
		return __( 'Woody Code Snippets', 'code-snippets' );
	}

	/**
	 * Get the slug of the plugin being promoted.
	 *
	 * @return string The plugin slug.
	 */
	public function get_plugin_slug(): string {
		return 'insert-php';
	}

	/**
	 * Get the admin screens where the promotion should be displayed.
	 *
	 * @return array An array of admin screen IDs.
	 */
	public function get_plugin_admin_screens(): array {
		return [
			'wbcr-snippets',
			'edit-wbcr-snippets',
			'edit-wbcr-snippet-tags',
			'wbcr-snippets_page_winp-new-item',
			'wbcr-snippets_page_winp-settings',
			'wbcr-snippets_page_snippet-library',
			'wbcr-snippets_page_ti-about-insert_php',
		];
	}

	/**
	 * Get the message text for the promotion notice.
	 *
	 * @return string The promotion message.
	 */
	public function get_promotion_message(): string {
		return __( 'Move your functionality to Code Snippets and reduce your dependency on third-party plugins.', 'code-snippets' );
	}
}
