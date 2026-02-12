<?php
namespace Code_Snippets\Promotions;

class Header_Footer_Code extends Promotion_Base {

	public function get_plugin_name(): string {
		return esc_html__( 'Head & Footer Code', 'code-snippets' );
	}

	public function get_plugin_slug(): string {
		return 'head-footer-code';
	}

	public function get_plugin_admin_screens(): array {
		return [
			'tools_page_head-footer-code'
		];
	}

	public function get_promotion_heading(): string {
		return esc_html__( 'Stop worrying about breaking your site', 'code-snippets' );
	}

	public function get_promotion_message(): string {
		return esc_html__( 'Why risk manual edits? Migrate to Code Snippets Pro to get enterprise-grade Safe Mode that automatically catches errors before they go live.', 'code-snippets' );
	}

	public function get_promotion_buttons(): array {
		return $this->get_default_buttons();
	}
}
