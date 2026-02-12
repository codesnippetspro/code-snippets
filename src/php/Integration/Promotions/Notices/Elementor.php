<?php

namespace Code_Snippets\Integration\Promotions\Notices;

use Code_Snippets\Integration\Promotions\Promotion_Base;

class Elementor extends Promotion_Base {

	public function get_plugin_name(): string {
		return esc_html__( 'Elementor Custom Code', 'code-snippets' );
	}

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
		return esc_html__( 'Upgrade to the Industry Standard for Code Management', 'code-snippets' );
	}

	public function get_promotion_message(): string {
		return esc_html__( 'Move your custom logic into a dedicated dashboard built for professionals. Experience a cleaner workflow with advanced security and global organization.', 'code-snippets' );
	}

	public function get_promotion_buttons(): array {
		return $this->get_default_buttons();
	}
}
