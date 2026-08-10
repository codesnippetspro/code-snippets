<?php

namespace Code_Snippets\Integration\Promotions\Other;

use Elementor\Controls_Manager;
use Elementor\Element_Base;
use Elementor\Widget_Base;
use function Code_Snippets\code_snippets;

/**
 * Handle adding promotions to the Elementor editor.
 */
class Elementor_Editor {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'elementor/init', [ $this, 'promotion_in_custom_css_section' ] );
	}

	/**
	 * Promotion on the Custom CSS section, inside the Elementor Editor.
	 */
	public function promotion_in_custom_css_section() {
		// Elementor Core.
		add_action(
			'elementor/element/common/section_custom_css/after_section_start',
			function ( $element ) {
				$this->add_promotion_control( $element, 'core' );
			}
		);

		// Elementor Pro.
		add_action(
			'elementor/element/common/section_custom_css_pro/after_section_start',
			function ( $element ) {
				$this->add_promotion_control( $element, 'pro' );
			}
		);
	}

	/**
	 * Register promotion section in the Custom CSS section.
	 *
	 * @param Widget_Base|Element_Base $element The Elementor element.
	 * @param string                   $suffix  Identifier to append.
	 */
	public function add_promotion_control( $element, string $suffix ) {
		$element->add_control(
			"code_snippets_promotion_notice_elementor_$suffix",
			[
				'type'        => Controls_Manager::NOTICE,
				'notice_type' => 'info',
				'dismissible' => true,
				'heading'     => esc_html__( 'Manage your custom styles', 'code-snippets' ),
				'content'     => $this->get_promotion_content(),
			]
		);
	}

	/**
	 * Get the promotion content with appropriate link.
	 *
	 * @return string
	 */
	private function get_promotion_content(): string {
		// translators: %s: plugin name.
		$message = __( 'Code Snippets provides a powerful and user-friendly alternative to "%s", with cloud sync, advanced features, and an intuitive interface.', 'code-snippets' );
		$message = sprintf( $message, __( 'Elementor Custom Code', 'code-snippets' ) );

		if ( code_snippets()->licensing->is_licensed() ) {
			$link_text = __( 'Manage CSS snippets', 'code-snippets' );
			$link_url = add_query_arg( 'type', 'css', code_snippets()->get_menu_url() );
		} else {
			$link_text = __( 'Learn more', 'code-snippets' );
			$link_url = 'https://codesnippets.pro/pricing/?utm_source=elementor&utm_medium=banner&utm_campaign=elementor-addon-custom-code';
		}

		return sprintf(
			'%s <br><br><a href="%s" target="_blank" rel="noopener noreferrer" class="e-btn e-info" style="color: #fff;">%s</a>',
			esc_html( $message ),
			esc_url( $link_url ),
			esc_html( $link_text )
		);
	}
}
