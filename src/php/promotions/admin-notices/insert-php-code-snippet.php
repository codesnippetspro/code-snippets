<?php
namespace Code_Snippets\Promotions;

use function Code_Snippets\code_snippets;
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
		$buttons = [];

		if ( $this->has_snippets() ) {
			$buttons[] = [
				'url'    => add_query_arg( 'tab', 'plugins', code_snippets()->get_menu_url( 'import' ) ),
				'text'   => esc_html__( 'Migrate to Code Snippets', 'code-snippets' ),
				'class'  => 'button button-primary',
			];
		} else {
			$buttons[] = [
				'url'    => code_snippets()->get_menu_url(),
				'text'   => esc_html__( 'Manage your snippets', 'code-snippets' ),
				'class'  => 'button button-primary',
			];
		}

		$buttons[] = [
			'url'    => 'https://codesnippets.pro/pricing/?utm_source=' . $this->get_plugin_slug() . '&utm_medium=promotion&utm_campaign=custom-code',
			'text'   => esc_html__( 'Learn More', 'code-snippets' ),
			'class'  => 'button button-secondary',
			'target' => '_blank',
		];
		
		return $buttons;
	}

	protected function has_snippets(): bool {
		$importer = new Insert_PHP_Code_Snippet_Importer();
		$data = $importer->get_data();
		return ! empty( $data );
	}
}