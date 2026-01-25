<?php
namespace Code_Snippets\Promotions;

use function Code_Snippets\code_snippets;

class Elementor_Editor {

	public function __construct() {
		add_action( 'elementor/init', [ $this, 'promotion_in_custom_css_section' ] );
	}

	/**
	 * Promotion on the Custom CSS section, inside the Elementor Editor.
	 */
	public function promotion_in_custom_css_section() {
		// Elementor Core
		add_action( 'elementor/element/common/section_custom_css_pro/after_section_start', [ $this, 'add_promotion_to_custom_css_section_in_elementor_core' ], 10, 2 );
		// Elementor Pro
		add_action( 'elementor/element/common/section_custom_css/after_section_start', [ $this, 'add_promotion_to_custom_css_section_in_elementor_pro' ], 10, 2 );
	}

	/**
	 * Register promotion section in the Custom CSS section.
	 *
	 * @param \Elementor\Widget_Base|\Elementor\Element_Base $element The Elementor element.
	 */
	public function add_promotion_to_custom_css_section_in_elementor_core( $element ) {
		$element->add_control(
			'code_snippets_promotion_notice_elementor_core',
			[
				'type' => \Elementor\Controls_Manager::NOTICE,
				'notice_type' => 'info',
				'dismissible' => true,
				'heading' => esc_html__( 'Manage your custom styles', 'code-snippets' ),
				'content' => $this->get_promotion_content(),
			]
		);
	}

	/**
	 * Register promotion section in the Custom CSS section.
	 *
	 * @param \Elementor\Widget_Base|\Elementor\Element_Base $element The Elementor element.
	 */
	public function add_promotion_to_custom_css_section_in_elementor_pro( $element ) {
		$element->add_control(
			'code_snippets_promotion_notice_elementor_pro',
			[
				'type' => \Elementor\Controls_Manager::NOTICE,
				'notice_type' => 'info',
				'dismissible' => true,
				'heading' => esc_html__( 'Manage your custom styles', 'code-snippets' ),
				'content' => $this->get_promotion_content(),
			]
		);
	}

	/**
	 * Get the promotion content with appropriate link.
	 *
	 * @return string
	 */
	private function get_promotion_content(): string {
		$message = sprintf(
			esc_html__( 'Code Snippets provides a powerful and user-friendly alternative to "%s", with cloud sync, advanced features, and an intuitive interface.', 'code-snippets' ),
			esc_html__( 'Elementor Custom Code', 'code-snippets' )
		);

		if ( $this->is_code_snippets_pro() ) {
			$link_text = esc_html__( 'Manage CSS snippets', 'code-snippets' );
			$url = add_query_arg( 'type', 'css', code_snippets()->get_menu_url() );
		} else {
			$link_text = esc_html__( 'Learn More', 'code-snippets' );
			$url = 'https://codesnippets.pro/pricing/?utm_source=elementor&utm_medium=banner&utm_campaign=elementor-addon-custom-code';
		}

		return sprintf( '%s <br><br><a href="%s" target="_blank" class="e-btn e-info" style="color:#fff;">%s</a>', $message, $url, $link_text );
	}

	/**
	 * Check if pro version is installed and active.
	 *
	 * @return bool
	 */
	private function is_code_snippets_pro(): bool {
		return code_snippets()->licensing->is_licensed();
	}
}
