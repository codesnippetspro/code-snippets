<?php
namespace Code_Snippets\Promotions;

class Insert_PHP_Code_Snippet extends Promotion_Base {

	public function get_plugin_slug(): string {
		return 'insert-php-code-snippet';
	}

	public function get_plugin_admin_screens(): array {
		return [
			'toplevel_page_insert-php-code-snippet-manage',
			'insert-php-code-snippet_page_insert-php-code-snippet-settings',
			'insert-php-code-snippet_page_insert-php-code-snippet-about',
			'insert-php-code-snippet_page_insert-php-code-snippet-suggest-features',
		];
	}

	public function get_promotion_heading(): string {
		return esc_html__( 'Looking for a better way to manage your custom code?', 'code-snippets' );
	}

	public function get_promotion_message(): string {
		return esc_html__( 'Code Snippets Pro provides a powerful and user-friendly alternative to Header Footer Code Manager, with cloud sync, advanced features, and an intuitive interface.', 'code-snippets' );
	}
}