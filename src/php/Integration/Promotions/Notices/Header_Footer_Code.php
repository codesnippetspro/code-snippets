<?php

namespace Code_Snippets\Integration\Promotions\Notices;

/**
 * Promotion class for Header & Footer Code plugin.
 *
 * @link http://wordpress.org/plugins/head-footer-code
 */
class Header_Footer_Code extends Promotion_Base {

	/**
	 * Get the name of the plugin being promoted.
	 *
	 * @return string The plugin name.
	 */
	public function get_plugin_name(): string {
		return __( 'Head & Footer Code', 'code-snippets' );
	}

	/**
	 * Get the slug of the plugin being promoted.
	 *
	 * @return string The plugin slug.
	 */
	public function get_plugin_slug(): string {
		return 'head-footer-code';
	}

	/**
	 * Get the admin screens where the promotion should be displayed.
	 *
	 * @return array An array of admin screen IDs.
	 */
	public function get_plugin_admin_screens(): array {
		return [
			'tools_page_head-footer-code',
		];
	}

	/**
	 * Get the heading text for the promotion notice.
	 *
	 * @return string The promotion heading.
	 */
	public function get_promotion_heading(): string {
		return __( 'Stop worrying about breaking your site', 'code-snippets' );
	}

	/**
	 * Get the message text for the promotion notice.
	 *
	 * @return string The promotion message.
	 */
	public function get_promotion_message(): string {
		return __( 'Why risk manual edits? Migrate to Code Snippets Pro to get enterprise-grade Safe Mode that automatically catches errors before they go live.', 'code-snippets' );
	}
}
