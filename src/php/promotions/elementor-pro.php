<?php
namespace Code_Snippets\Promotions;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Elementor Pro promotion class.
 */
class Elementor_Pro {

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
		<div class="notice notice-info is-dismissibleX">
			<p>
				<strong><?php esc_html_e( '💡 Looking for a better way to manage custom code?', 'code-snippets' ); ?></strong>
			</p>
			<p>
				<?php
				printf(
					/* translators: %s: Code Snippets plugin name */
					esc_html__( '%s provides a powerful and user-friendly alternative to Elementor Custom Code, with cloud sync, advanced features, and an intuitive interface.', 'code-snippets' ),
					'<strong>Code Snippets Pro</strong>'
				);
				?>
			</p>
			<p>
				<a href="<?php echo esc_url( 'https://codesnippets.pro/pricing/?utm_source=elementor&utm_medium=banner&utm_campaign=custom-code' ); ?>" class="button button-primary" target="_blank">
					<?php esc_html_e( 'Learn More', 'code-snippets' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=snippets' ) ); ?>" class="button button-secondary">
					<?php esc_html_e( 'Try Code Snippets', 'code-snippets' ); ?>
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

		if ( ! $current_screen ) {
			return false;
		}

		return in_array(
			$current_screen->id,
			[
				'edit-elementor_snippet',
				'elementor_snippet',
			],
			true
		);
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
	 * @param \Elementor\Widget_Base|\Elementor\Element_Base $element The Elementor element.
	 */
	public function add_promotion_to_custom_css_section( $element ) {

		$element->add_control(
			'code_snippets_promotion_alert',
			[
				'type' => \Elementor\Controls_Manager::ALERT,
				'alert_type' => 'warning',
				'content' => esc_html__( 'Set locations and angle for each breakpoint to ensure the gradient adapts to different screen sizes.', 'code-snippets' ),
				'render_type' => 'ui',
			]
		);

		$element->add_control(
			'code_snippets_promotion_notice',
			[
				'type' => \Elementor\Controls_Manager::NOTICE,
				'notice_type' => 'info',
				'dismissible' => true,
				'heading' => esc_html__( '💡 Looking for a better way to manage custom code?', 'code-snippets' ),
				'content' => sprintf(
					/* translators: %s: Code Snippets plugin name */
					esc_html__( '%s provides a powerful and user-friendly alternative to Elementor Custom Code, with cloud sync, advanced features, and an intuitive interface.', 'code-snippets' ),
					'<strong>Code Snippets Pro</strong>'
				),
			]
		);

		$element->add_control(
			'code_snippets_promotion_raw_html',
			[
				'type'    => \Elementor\Controls_Manager::RAW_HTML,
				'raw'     => sprintf(
					'<div style="padding:12px;">
						<h3 style="margin-top:0; margin-bottom:8px; font-size:15px;">
							%s
						</h3>
						<p style="margin: 0 0 10px;">
							%s
						</p>
						<a href="%s" target="_blank" style="display:inline-block; padding:8px 14px; border-radius:4px; text-decoration:none; font-weight:500; border:1px solid #d33;">
							%s
						</a>
					</div>',
					esc_html__( '💡 Looking for a better way to manage custom code?', 'code-snippets' ),
					sprintf(
						/* translators: %s: Code Snippets plugin name */
						esc_html__( '%s provides a powerful and user-friendly alternative to Elementor Custom Code, with cloud sync, advanced features, and an intuitive interface.', 'code-snippets' ),
						'<strong>Code Snippets Pro</strong>'
					),
					esc_url( 'https://example.com/promo' ), // <-- Change to your promo URL.
					esc_html__( 'Learn More', 'code-snippets' )
				),
				'content_classes' => 'your-promo-class',
			]
		);
	}
}
