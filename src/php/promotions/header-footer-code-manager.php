<?php
namespace Code_Snippets\Promotions;

use function Code_Snippets\code_snippets;

class Header_Footer_Code_Manager extends Promotion_Base {

	public function get_plugin_slug(): string {
		return 'header-footer-code-manager';
	}

    public function get_plugin_admin_screens(): array {
        return [
            'toplevel_page_hfcm-list',
            'hfcm_page_hfcm-tools',
            'hfcm_page_hfcm-create',
            'admin_page_hfcm-update',
        ];
    }

	public function get_promotion_heading(): string {
		return esc_html__( 'Looking for a better way to manage your custom code?', 'code-snippets' );
	}

	public function get_promotion_message(): string {
		return esc_html__( 'Code Snippets Pro provides a powerful and user-friendly alternative to Header Footer Code Manager, with cloud sync, advanced features, and an intuitive interface.', 'code-snippets' );
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