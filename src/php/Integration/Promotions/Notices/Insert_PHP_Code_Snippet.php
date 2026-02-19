<?php

namespace Code_Snippets\Integration\Promotions\Notices;

use Code_Snippets\REST_API\Import\Plugins\Insert_PHP_Code_Snippet_Plugin_Importer;

/**
 * Promotion class for Insert PHP Code Snippet plugin.
 *
 * @link https://wordpress.org/plugins/insert-php-code-snippet/
 */
class Insert_PHP_Code_Snippet extends Promotion_Base {

	/**
	 * Get the name of the plugin being promoted.
	 *
	 * @return string The plugin name.
	 */
	public function get_plugin_name(): string {
		return __( 'Insert PHP Code Snippet', 'code-snippets' );
	}

	/**
	 * Get the slug of the plugin being promoted.
	 *
	 * @return string The plugin slug.
	 */
	public function get_plugin_slug(): string {
		return 'insert-php-code-snippet';
	}

	/**
	 * Get the admin screens where the promotion should be displayed.
	 *
	 * @return array An array of admin screen IDs.
	 */
	public function get_plugin_admin_screens(): array {
		return [
			'toplevel_page_insert-php-code-snippet-manage',
			'insert-php-code-snippet_page_insert-php-code-snippet-settings',
			'insert-php-code-snippet_page_insert-php-code-snippet-about',
			'insert-php-code-snippet_page_insert-php-code-snippet-suggest-features',
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

	/**
	 * Check if the user should see the migration button.
	 *
	 * @return bool Whether the user should see the migration button.
	 */
	public function show_migration_button(): bool {
		$importer = new Insert_PHP_Code_Snippet_Plugin_Importer();
		$data = $importer->get_data();
		return ! empty( $data );
	}
}
