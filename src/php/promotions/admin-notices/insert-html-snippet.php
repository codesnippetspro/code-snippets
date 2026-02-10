<?php
namespace Code_Snippets\Promotions;

class Insert_HTML_Snippet extends Promotion_Base {

	public function get_plugin_name(): string {
		return esc_html__( 'Insert HTML Snippet', 'code-snippets' );
	}

	public function get_plugin_slug(): string {
		return 'insert-html-snippet';
	}

	public function get_plugin_admin_screens(): array {
		return [
			'toplevel_page_insert-html-snippet-manage',
            'insert-html-snippet_page_insert-html-snippet-settings',
            'insert-html-snippet_page_insert-html-snippet-about',
            'insert-html-snippet_page_insert-html-snippet-suggest-features',
		];
	}

	public function get_promotion_heading(): string {
		return esc_html__( 'Clean up your plugin list today', 'code-snippets' );
	}

	public function get_promotion_message(): string {
		return esc_html__( 'Move your functionality to Code Snippets Pro and reduce your dependency on third-party plugins. A leaner dashboard is a more secure dashboard.', 'code-snippets' );
	}

	public function get_promotion_buttons(): array {
		return $this->get_default_buttons();
	}
}
