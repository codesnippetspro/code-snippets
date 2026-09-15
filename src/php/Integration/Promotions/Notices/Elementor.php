<?php

namespace Code_Snippets\Integration\Promotions\Notices;

use Code_Snippets\REST_API\Import\Plugins\Elementor_Custom_Code_Plugin_Importer;

/**
 * Promotion class for Elementor Custom Code.
 *
 * @link https://elementor.com/help/custom-code-pro/
 */
class Elementor extends Promotion_Base {

	/**
	 * Get the name of the plugin being promoted.
	 *
	 * @return string The name of the plugin.
	 */
	public function get_plugin_name(): string {
		return __( 'Elementor Custom Code', 'code-snippets' );
	}

	/**
	 * Get the slug of the plugin being promoted.
	 *
	 * @return string The slug of the plugin.
	 */
	public function get_plugin_slug(): string {
		return 'elementor';
	}

	/**
	 * Get the admin screens where the promotion should be displayed.
	 *
	 * @return array An array of admin screen IDs.
	 */
	public function get_plugin_admin_screens(): array {
		return [
			// Elementor Core.
			'elementor_custom_code',
			'elementor_page_elementor_custom_code',
			// Elementor Pro.
			'edit-elementor_snippet',
			'elementor_snippet',
			// New Elementor One.
			'elementor_page_e-custom-code',
		];
	}

	/**
	 * Get the heading text for the promotion notice.
	 *
	 * @return string The promotion heading.
	 */
	public function get_promotion_heading(): string {
		return __( 'Upgrade to the industry standard for code management', 'code-snippets' );
	}

	/**
	 * Get the message text for the promotion notice.
	 *
	 * @return string The promotion message.
	 */
	public function get_promotion_message(): string {
		return __( 'Move your custom logic into a dedicated dashboard built for professionals. Experience a cleaner workflow with advanced security and global organization.', 'code-snippets' );
	}

	/**
	 * Check if the user should see the migration button.
	 *
	 * @return bool Whether the user should see the migration button.
	 */
	public function show_migration_button(): bool {
		$importer = new Elementor_Custom_Code_Plugin_Importer();
		$data = $importer->get_data();
		return ! empty( $data );
	}
}
