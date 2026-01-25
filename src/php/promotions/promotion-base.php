<?php

namespace Code_Snippets\Promotions;

abstract class Promotion_Base {

	const AJAX_ACTION = 'code_snippets_dismiss_promotion';
	const USER_META_KEY = 'code_snippets_dismissed_promotions';

	abstract public function get_plugin_name(): string;

	abstract public function get_plugin_slug(): string;

	abstract public function get_plugin_admin_screens(): array;

	abstract public function get_promotion_heading(): string;

	abstract public function get_promotion_message(): string;

	abstract public function get_promotion_buttons(): array;

	public function is_plugin_admin_screen(): bool {
		if ( ! is_admin() ) {
			return false;
		}

		$current_screen = get_current_screen();

		if ( ! $current_screen ) {
			return false;
		}

		$is_custom_code_page = in_array(
			$current_screen->id,
			$this->get_plugin_admin_screens() ?? [],
			true
		);

		return $is_custom_code_page;
	}

	public function is_promotion_dismissed(): bool {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}

		$dismissed_promotions = get_user_meta( $user_id, self::USER_META_KEY, true );
		if ( ! is_array( $dismissed_promotions ) ) {
			$dismissed_promotions = [];
		}

		return in_array( $this->get_plugin_slug(), $dismissed_promotions, true );
	}

	public function display_promotion() {
		if ( ! $this->is_plugin_admin_screen() ) {
			return;
		}

		if ( $this->is_promotion_dismissed() ) {
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
				margin-block: 0 .5em;
			}
		</style>
		<div
			class="notice notice-info is-dismissible code-snippets-promotion"
			data-promotion-id="<?php echo esc_attr( $this->get_plugin_slug() ); ?>"
		>
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
					<strong><?php echo $this->get_promotion_heading(); ?></strong>
				</p>
				<p>
					<?php echo $this->get_promotion_message(); ?>
				</p>
				<p>
					<?php
					$buttons = $this->get_promotion_buttons();

					foreach ( $buttons as $button ) {
						$url = isset( $button['url'] ) ? esc_url( $button['url'] ) : '';
						$text = isset( $button['text'] ) ? $button['text'] : '';
						$class = isset( $button['class'] ) ? esc_attr( $button['class'] ) : 'button';
						$target = isset( $button['target'] ) ? esc_attr( $button['target'] ) : '';
						
						printf(
							'<a href="%s" class="%s"%s>%s</a> ',
							$url,
							$class,
							$target ? ' target="' . $target . '"' : '',
							$text
						);
					}
					?>
				</p>
			</div>
		</div>
		<script>
		jQuery(document).ready(function($) {
			$('.code-snippets-promotion').on('click', '.notice-dismiss', function() {
				var $notice = $(this).closest('.code-snippets-promotion');
				var promotionId = $notice.data('promotion-id');

				$.post(ajaxurl, {
					action: '<?php echo self::AJAX_ACTION; ?>',
					nonce: '<?php echo wp_create_nonce( self::AJAX_ACTION ); ?>',
					promotion_id: promotionId
				});
			});
		});
		</script>
		<?php
	}

	public function dismiss_promotion_ajax_handler() {
		check_ajax_referer( self::AJAX_ACTION, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$promotion_id = isset( $_POST['promotion_id'] ) ? sanitize_text_field( $_POST['promotion_id'] ) : '';

		if ( empty( $promotion_id ) ) {
			wp_send_json_error( 'Invalid promotion id.' );
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

	public function __construct() {
		add_action( 'admin_notices', [ $this, 'display_promotion' ] );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, [ $this, 'dismiss_promotion_ajax_handler' ] );
	}
}
