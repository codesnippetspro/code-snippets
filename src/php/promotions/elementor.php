<?php
namespace Code_Snippets\Promotions;

class Elementor extends Promotion_Base {

	public function get_plugin_slug(): string {
		return 'elementor';
	}

	public function get_plugin_admin_screens(): array {
		return [
			// Elementor Core
			'elementor_custom_code',
			'elementor_page_elementor_custom_code',
			// Elementor Pro
			'edit-elementor_snippet',
			'elementor_snippet',
			// New Elementor One
			'elementor_page_e-custom-code',
		];
	}

	public function get_promotion_heading(): string {
		return esc_html__( 'Looking for a better way to manage your custom code?', 'code-snippets' );
	}

	public function get_promotion_message(): string {
		return esc_html__( 'Code Snippets Pro provides a powerful and user-friendly alternative to Elementor Custom Code, with cloud sync, advanced features, and an intuitive interface.', 'code-snippets' );
	}
}
