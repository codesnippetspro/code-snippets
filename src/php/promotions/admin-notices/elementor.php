<?php
namespace Code_Snippets\Promotions;

use function Code_Snippets\code_snippets;

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
		return esc_html__( 'Looking for a better way to manage your custom code?', 'code-snippets' );
	}

	public function get_promotion_message(): string {
		return sprintf(
			esc_html__( 'Code Snippets provides a powerful and user-friendly alternative to "%s", with cloud sync, advanced features, and an intuitive interface.', 'code-snippets' ),
			$this->get_plugin_name()
		);
	}

	public function get_promotion_buttons(): array {
		return [
			[
				'url'    => code_snippets()->get_menu_url(),
				'text'   => esc_html__( 'Manage your snippets', 'code-snippets' ),
				'class'  => 'button button-primary',
			],
			[
				'url'    => 'https://codesnippets.pro/pricing/?utm_source=' . $this->get_plugin_slug() . '&utm_medium=promotion&utm_campaign=custom-code',
				'text'   => esc_html__( 'Learn More', 'code-snippets' ),
				'class'  => 'button button-secondary',
				'target' => '_blank',
			],
		];
	}
}
