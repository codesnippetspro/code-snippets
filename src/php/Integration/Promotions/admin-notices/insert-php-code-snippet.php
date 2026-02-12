<?php
namespace Code_Snippets\Promotions;

use Code_Snippets\Insert_PHP_Code_Snippet_Importer;

class Insert_PHP_Code_Snippet extends Promotion_Base {

	public function get_plugin_name(): string {
		return esc_html__( 'Insert PHP Code Snippet', 'code-snippets' );
	}

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
		return sprintf(
			esc_html__( 'Code Snippets provides a powerful and user-friendly alternative to "%s", with cloud sync, advanced features, and an intuitive interface.', 'code-snippets' ),
			$this->get_plugin_name()
		);
	}

	public function get_promotion_buttons(): array {
		return $this->get_default_buttons( $this->has_snippets() );
	}

	protected function has_snippets(): bool {
		$importer = new Insert_PHP_Code_Snippet_Importer();
		$data = $importer->get_data();
		return ! empty( $data );
	}
}
