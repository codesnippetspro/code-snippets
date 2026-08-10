<?php

namespace Code_Snippets\Integration\Promotions\Notices;

use Code_Snippets\REST_API\Import\Plugins\Header_Footer_Code_Manager_Plugin_Importer;

/**
 * Promotion class for Header Footer Code Manager.
 *
 * @link https://wordpress.org/plugins/header-footer-code-manager/
 */
class Header_Footer_Code_Manager extends Promotion_Base {

	/**
	 * Get the name of the plugin being promoted.
	 *
	 * @return string The plugin name.
	 */
	public function get_plugin_name(): string {
		return __( 'Header Footer Code Manager', 'code-snippets' );
	}

	/**
	 * Get the slug of the plugin being promoted.
	 *
	 * @return string The plugin slug.
	 */
	public function get_plugin_slug(): string {
		return 'header-footer-code-manager';
	}

	/**
	 * Get the admin screens where the promotion should be displayed.
	 *
	 * @return array An array of admin screen IDs.
	 */
	public function get_plugin_admin_screens(): array {
		return [
			'toplevel_page_hfcm-list',
			'hfcm_page_hfcm-tools',
			'hfcm_page_hfcm-create',
			'admin_page_hfcm-update',
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

	/**
	 * Check if the user should see the migration button.
	 *
	 * @return bool Whether the user should see the migration button.
	 */
	public function show_migration_button(): bool {
		$importer = new Header_Footer_Code_Manager_Plugin_Importer();
		$data = $importer->get_data();
		return ! empty( $data );
	}
}
