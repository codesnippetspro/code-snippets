<?php

namespace Code_Snippets\Integration\Promotions\Notices;

/**
 * Promotion class for Insert HTML Snippet.
 *
 * @link https://wordpress.org/plugins/insert-html-snippet/
 */
class Insert_HTML_Snippet extends Promotion_Base {

	/**
	 * Get the name of the plugin being promoted.
	 *
	 * @return string The plugin name.
	 */
	public function get_plugin_name(): string {
		return __( 'Insert HTML Snippet', 'code-snippets' );
	}

	/**
	 * Get the slug of the plugin being promoted.
	 *
	 * @return string The plugin slug.
	 */
	public function get_plugin_slug(): string {
		return 'insert-html-snippet';
	}

	/**
	 * Get the admin screens where the promotion should be displayed.
	 *
	 * @return array An array of admin screen IDs.
	 */
	public function get_plugin_admin_screens(): array {
		return [
			'toplevel_page_insert-html-snippet-manage',
			'insert-html-snippet_page_insert-html-snippet-settings',
			'insert-html-snippet_page_insert-html-snippet-about',
			'insert-html-snippet_page_insert-html-snippet-suggest-features',
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
