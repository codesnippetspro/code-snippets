<?php

namespace Code_Snippets\Integration\Promotions\Notices;

use Code_Snippets\Integration\Promotions\Promotion_Base;

class WP_Headers_And_Footers extends Promotion_Base {

	public function get_plugin_name(): string {
		return esc_html__( 'Insert Headers and Footers', 'code-snippets' );
	}

	public function get_plugin_slug(): string {
		return 'wp-headers-and-footers';
	}

	public function get_plugin_admin_screens(): array {
		return [
			'settings_page_wp-headers-and-footers',
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
		return $this->get_default_buttons();
	}
}
