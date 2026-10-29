<?php

namespace Code_Snippets\Integration\Promotions\Notices;

use function Code_Snippets\code_snippets;
use function Code_Snippets\Settings\get_setting;
use const Code_Snippets\PLUGIN_FILE;

/**
 * Base class for plugin promotion notices.
 */
abstract class Promotion_Base {

	/**
	 * AJAX action name for dismissing the promotion.
	 */
	private const AJAX_ACTION = 'code_snippets_dismiss_promotion';

	/**
	 * User meta key to store dismissed promotions.
	 */
	private const USER_META_KEY = 'code_snippets_dismissed_promotions';

	/**
	 * Get the name of the plugin being promoted.
	 *
	 * @return string The name of the plugin.
	 */
	abstract public function get_plugin_name(): string;

	/**
	 * Get the slug of the plugin being promoted.
	 *
	 * @return string The slug of the plugin.
	 */
	abstract public function get_plugin_slug(): string;

	/**
	 * Get the admin screen IDs where the promotion should be displayed.
	 *
	 * @return array An array of admin screen IDs.
	 */
	abstract public function get_plugin_admin_screens(): array;

	/**
	 * Get the heading text for the promotion notice.
	 *
	 * @return string The promotion heading.
	 */
	public function get_promotion_heading(): string {
		return __( 'Clean up your plugin list today', 'code-snippets' );
	}

	/**
	 * Get the message text for the promotion notice.
	 *
	 * @return string The promotion message.
	 */
	public function get_promotion_message(): string {
		// translators: %s: plugin name.
		$message = __( 'Code Snippets provides a powerful and user-friendly alternative to "%s", with cloud sync, advanced features, and an intuitive interface.', 'code-snippets' );
		return sprintf( $message, $this->get_plugin_name() );
	}

	/**
	 * Constructor to initialize the promotion notice.
	 */
	public function __construct() {
		add_action( 'current_screen', [ $this, 'register_promotion_hooks' ] );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, [ $this, 'dismiss_promotion_ajax_handler' ] );
	}

	/**
	 * Register hooks to display the promotion notice if conditions are met.
	 */
	public function register_promotion_hooks() {
		if ( $this->is_plugin_admin_screen() && ! $this->is_promotion_dismissed() ) {
			add_action( 'admin_notices', [ $this, 'display_promotion' ], 1 );
			add_action( 'admin_print_styles', [ $this, 'print_promotion_styles' ] );
			add_action( 'admin_print_footer_scripts', [ $this, 'print_dismiss_script' ], 99 );
		}
	}

	/**
	 * Determine if the current admin screen is one of the plugin's admin screens.
	 *
	 * @return bool True if it's a plugin admin screen, false otherwise.
	 */
	public function is_plugin_admin_screen(): bool {
		if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$current_screen = get_current_screen();
		return $current_screen && in_array( $current_screen->id, $this->get_plugin_admin_screens(), true );
	}

	/**
	 * Check if the promotion has been dismissed by the current user.
	 *
	 * @return bool True if the promotion is dismissed, false otherwise.
	 */
	public function is_promotion_dismissed(): bool {
		if ( get_setting( 'general', 'hide_upgrade_menu' ) ) {
			return true;
		}

		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		$dismissed_promotions = get_user_meta( $user_id, self::USER_META_KEY, true );
		return is_array( $dismissed_promotions ) && in_array( $this->get_plugin_slug(), $dismissed_promotions, true );
	}

	/**
	 * Print the CSS styles for the promotion notice.
	 */
	public function print_promotion_styles() {
		?>
		<style>
			.code-snippets-promotion.notice,
			.code-snippets-promotion.info,
			.code-snippets-promotion.warning,
			.code-snippets-promotion.error {
				padding: 0;
			}
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
                margin-block: 0 .5em;
            }
		</style>
		<?php
	}

	/**
	 * Print the JavaScript to handle dismissing the promotion notice.
	 */
	public function print_dismiss_script() {
		?>
		<script>
			jQuery(document).ready(function ($) {
				$('.code-snippets-promotion').on('click', '.notice-dismiss', function () {
					$.post(ajaxurl, {
						action: '<?php echo esc_attr( self::AJAX_ACTION ); ?>',
						nonce: '<?php echo esc_attr( wp_create_nonce( self::AJAX_ACTION ) ); ?>',
						promotion_id: $(this).closest('.code-snippets-promotion').data('promotion-id')
					})
				})
			})
		</script>
		<?php
	}

	/**
	 * Display the promotion notice in the admin area.
	 */
	public function display_promotion() {
		?>
		<div
			class="notice notice-info is-dismissible code-snippets-promotion"
			data-promotion-id="<?php echo esc_attr( $this->get_plugin_slug() ); ?>"
			aria-label="<?php esc_attr_e( 'Code Snippets Promotion', 'code-snippets' ); ?>"
			role="region"
		>
			<div class="code-snippets-promotion-icon">
				<img
					src="<?php echo esc_url( plugins_url( 'assets/icon.svg', PLUGIN_FILE ) ); ?>"
					alt="<?php esc_attr_e( 'Code Snippets Logo', 'code-snippets' ); ?>"
					width="32"
					height="32"
				/>
			</div>
			<div class="code-snippets-promotion-content">
				<p><strong><?php echo esc_html( $this->get_promotion_heading() ); ?></strong></p>
				<p><?php echo esc_html( $this->get_promotion_message() ); ?></p>
				<p><?php $this->print_promotion_buttons(); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX handler to dismiss the promotion notice.
	 */
	public function dismiss_promotion_ajax_handler() {
		check_ajax_referer( self::AJAX_ACTION, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized', 403 );
		}

		$promotion_id = isset( $_POST['promotion_id'] ) ? sanitize_text_field( wp_unslash( $_POST['promotion_id'] ) ) : '';

		if ( empty( $promotion_id ) ) {
			wp_send_json_error( 'Invalid promotion id.', 400 );
		}

		$user_id = get_current_user_id();
		$dismissed_promotions = get_user_meta( $user_id, self::USER_META_KEY, true );

		if ( ! is_array( $dismissed_promotions ) ) {
			$dismissed_promotions = [];
		}

		if ( ! in_array( $promotion_id, $dismissed_promotions, true ) ) {
			$dismissed_promotions[] = $promotion_id;
			update_user_meta( $user_id, self::USER_META_KEY, $dismissed_promotions );
		}

		wp_send_json_success();
	}

	/**
	 * Check if the user should see the migration button.
	 *
	 * @return bool Whether the user should see the migration button.
	 */
	public function show_migration_button(): bool {
		return false;
	}

	/**
	 * Render buttons for the promotion notice.
	 *
	 * @uses show_migration_button() to determine which buttons to show.
	 */
	protected function print_promotion_buttons() {
		if ( $this->show_migration_button() ) {
			$primary_text = __( 'Migrate to Code Snippets', 'code-snippets' );
			$primary_url = add_query_arg(
				[
					'tab'  => 'migrate',
					'from' => $this->get_plugin_slug(),
				],
				code_snippets()->get_menu_url( 'import' )
			);
		} else {
			$primary_text = __( 'Manage your snippets', 'code-snippets' );
			$primary_url = code_snippets()->get_menu_url();
		}

		printf(
			'<a href="%s" class="button button-primary">%s</a>',
			esc_url( $primary_url ),
			esc_html( $primary_text )
		);

		$secondary_url = add_query_arg(
			[
				'utm_source'   => $this->get_plugin_slug(),
				'utm_medium'   => 'promotion',
				'utm_campaign' => 'custom-code',
			],
			'https://codesnippets.pro/pricing'
		);

		printf(
			' <a href="%s" class="button button-secondary" target="_blank" rel="noopener noreferrer">%s</a> ',
			esc_url( $secondary_url ),
			esc_html__( 'Learn more', 'code-snippets' )
		);
	}
}
