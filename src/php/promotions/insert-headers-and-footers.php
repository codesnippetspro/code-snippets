<?php
namespace Code_Snippets\Promotions;

class Insert_Headers_And_Footers extends Promotion_Base {

	public function get_plugin_slug(): string {
		return 'insert-headers-and-footers';
	}

	public function get_plugin_admin_screens(): array {
		return [
			'settings_page_wp-headers-and-footers'
		];
	}

	public function get_promotion_heading(): string {
		return esc_html__( 'Looking for a better way to manage your custom code?', 'code-snippets' );
	}

	public function get_promotion_message(): string {
		return esc_html__( 'Code Snippets Pro provides a powerful and user-friendly alternative to Header Footer Code Manager, with cloud sync, advanced features, and an intuitive interface.', 'code-snippets' );
	}
}