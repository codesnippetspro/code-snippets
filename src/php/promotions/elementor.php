<?php
namespace Code_Snippets\Promotions;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Elementor promotion class.
 */
class Elementor {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'admin_notices', [ $this, 'promotion_in_custom_code_screen' ] );
		add_action( 'elementor/init', [ $this, 'promotion_in_custom_css_section' ] );
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
		<style>
			.code-snippets-promotion {
				display: flex;
				padding: 0;
				border-inline-start-width: 4px;
			}
			.code-snippets-promotion-icon {
				padding: 10px;
				background-color: #F0F9FF;
			}
			.code-snippets-promotion-content {
				padding: 10px;
			}
			.code-snippets-promotion-content p {
				margin-block-start: 0;
				margin-block-end: 0.5em;
			}
		</style>
		<div class="notice notice-info is-dismissible code-snippets-promotion">
			<div class="code-snippets-promotion-icon">
				<img
					src="<?php echo esc_url( plugins_url( 'assets/icon.svg', CODE_SNIPPETS_FILE ) ); ?>"
					alt="<?php esc_attr_e( 'Code Snippets Logo', 'code-snippets' ); ?>"
					width="32"
					height="32"
				/>
			</div>
			<div class="code-snippets-promotion-content">
				<p>
					<strong><?php esc_html_e( 'Looking for a better way to manage your custom code?', 'code-snippets' ); ?></strong>
				</p>
				<p>
					<?php esc_html_e( 'Code Snippets Pro provides a powerful and user-friendly alternative to Elementor Custom Code, with cloud sync, advanced features, and an intuitive interface.', 'code-snippets' ); ?>
				</p>
				<p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=snippets' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Manage your snippets', 'code-snippets' ); ?>
					</a>
					<a href="https://codesnippets.pro/pricing/?utm_source=elementor&utm_medium=banner&utm_campaign=custom-code" class="button button-secondary" target="_blank">
						<?php esc_html_e( 'Learn More', 'code-snippets' ); ?>
					</a>
				</p>
			</div>
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

		if ( ! $current_screen ) {
			return false;
		}

		$is_custom_code_page = in_array(
			$current_screen->id,
			[
				// Elementor Core
				'elementor_custom_code',
				'elementor_page_elementor_custom_code',
				// Elementor Pro
				'edit-elementor_snippet',
				'elementor_snippet',
				// New Elementor One
				'elementor_page_e-custom-code',
			],
			true
		);

		return $is_custom_code_page;
	}

	/**
	 * Promotion on the Custom CSS section, inside the Elementor Editor.
	 *
	 * @return void
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
		$message = esc_html__( 'Code Snippets Pro provides a powerful and user-friendly alternative to Elementor Custom Code, with cloud sync, conditional logic, and advanced features.', 'code-snippets' );

		if ( $this->is_code_snippets_pro() ) {
			$link_text = esc_html__( 'Manage CSS snippets', 'code-snippets' );
			$url = admin_url( 'admin.php?page=snippets&type=css' );
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
		return defined( 'CODE_SNIPPETS_PRO' ) && CODE_SNIPPETS_PRO;
	}
}
