<?php
namespace Code_Snippets\Promotions;

use Code_Snippets\Header_Footer_Code_Manager_Importer;

class Header_Footer_Code_Manager extends Promotion_Base {

	public function get_plugin_name(): string {
		return esc_html__( 'Header Footer Code Manager', 'code-snippets' );
	}

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
		return esc_html__( 'Clean up your plugin list today', 'code-snippets' );
	}

	public function get_promotion_message(): string {
		return esc_html__( 'Move your functionality to Code Snippets Pro and reduce your dependency on third-party plugins. A leaner dashboard is a more secure dashboard.', 'code-snippets' );
	}

	public function get_promotion_buttons(): array {
		return $this->get_default_buttons( $this->has_snippets() );
	}

	protected function has_snippets(): bool {
		$importer = new Header_Footer_Code_Manager_Importer();
		$data = $importer->get_data();
		return ! empty( $data );
	}
}
