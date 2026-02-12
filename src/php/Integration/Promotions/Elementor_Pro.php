<?php

namespace Code_Snippets\Integration\Promotions;

use Elementor\Controls_Manager;
use Elementor\Element_Base;
use Elementor\Widget_Base;
use function Code_Snippets\code_snippets;
use function Code_Snippets\Settings\get_setting;

/**
 * Elementor Pro promotion class.
 */
class Elementor_Pro {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		if ( ! get_setting( 'general', 'hide_upgrade_menu' ) ) {
			add_action( 'admin_notices', [ $this, 'promotion_in_custom_code_screen' ] );
			add_action( 'elementor/init', [ $this, 'promotion_in_custom_css_section' ] );
		}
	}

	/**
	 * Promotion on the Custom Code post type screen in WordPress admin.
	 *
	 * @return void
	 */
	public function promotion_in_custom_code_screen() {
		if ( ! $this->is_custom_code_screen() ) {
			return;
		}
		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<strong><?php esc_html_e( 'Looking for a better way to manage your custom code?', 'code-snippets' ); ?></strong>
			</p>
			<p>
				<?php esc_html_e( 'Code Snippets Pro provides a powerful and user-friendly alternative to Elementor Custom Code, with cloud sync, advanced features, and an intuitive interface.', 'code-snippets' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( code_snippets()->get_menu_url() ); ?>"
				   class="button button-primary">
					<?php esc_html_e( 'Manage your snippets', 'code-snippets' ); ?>
				</a>
				<a href="https://codesnippets.pro/pricing/?utm_source=elementor&utm_medium=banner&utm_campaign=custom-code"
				   class="button button-secondary"
				   target="_blank">
					<?php esc_html_e( 'Learn More', 'code-snippets' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Check if we're on the Custom Code admin screen.
	 *
	 * @return bool
	 */
	private function is_custom_code_screen(): bool {
		if ( ! is_admin() ) {
			return false;
		}

		$current_screen = get_current_screen();
		return $current_screen && in_array( $current_screen->id, [ 'edit-elementor_snippet', 'elementor_snippet' ], true );
	}

	/**
	 * Promotion on the Custom CSS section, inside the Elementor Editor.
	 *
	 * @return void
	 */
	public function promotion_in_custom_css_section() {
		add_action( 'elementor/element/common/section_custom_css/before_section_end', [ $this, 'add_promotion_to_custom_css_section' ], 10, 2 );
	}

	/**
	 * Register promotion section after the Custom CSS section.
	 *
	 * @param Widget_Base|Element_Base $element The Elementor element.
	 */
	public function add_promotion_to_custom_css_section( $element ) {
		$element->add_control(
			'code_snippets_promotion_notice',
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
		$message = esc_html__( 'Code Snippets Pro provides a powerful and user-friendly alternative to Elementor Custom Code, with cloud sync, conditional logic, and advanced features.', 'code-snippets' );

		if ( code_snippets()->licensing->is_licensed() ) {
			$link_text = esc_html__( 'Manage CSS snippets', 'code-snippets' );
			$url = add_query_arg( 'type', 'css', code_snippets()->get_menu_url( 'manage' ) );
		} else {
			$link_text = esc_html__( 'Learn More', 'code-snippets' );
			$url = 'https://codesnippets.pro/pricing/?utm_source=elementor&utm_medium=banner&utm_campaign=elementor-addon-custom-code';
		}

		return sprintf( '%s <br><br><a href="%s" target="_blank" class="e-btn e-info" style="color: #fff;">%s</a>', $message, $url, $link_text );
	}
}
