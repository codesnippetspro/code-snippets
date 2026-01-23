<?php

namespace Code_Snippets\Promotions;

use function Code_Snippets\code_snippets;
use function Code_Snippets\Settings\get_setting;

abstract class Promotion_Base {

	abstract public function get_plugin_slug(): string;

	abstract public function get_plugin_admin_screens(): array;

	abstract public function get_promotion_heading(): string;

	abstract public function get_promotion_message(): string;

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

	public function display_promotion() {
		if ( ! $this->is_plugin_admin_screen() ) {
			return;
		}

		if ( get_setting( 'general', 'hide_promotion_' . $this->get_plugin_slug() ) ) {
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
					<strong><?php echo $this->get_promotion_heading(); ?></strong>
				</p>
				<p>
					<?php echo $this->get_promotion_message(); ?>
				</p>
				<p>
					<a href="<?php echo esc_url( code_snippets()->get_menu_url() ); ?>" class="button button-primary">
						<?php esc_html_e( 'Manage your snippets', 'code-snippets' ); ?>
					</a>
					<a href="https://codesnippets.pro/pricing/?utm_source=<?php echo $this->get_plugin_slug(); ?>&utm_medium=promotion&utm_campaign=custom-code" class="button button-secondary" target="_blank">
						<?php esc_html_e( 'Learn More', 'code-snippets' ); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
	}

	public function __construct() {
		add_action( 'admin_notices', [ $this, 'display_promotion' ] );
	}    
}
