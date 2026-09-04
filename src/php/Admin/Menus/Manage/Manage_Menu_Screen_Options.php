<?php

namespace Code_Snippets\Admin\Menus\Manage;

use function Code_Snippets\code_snippets;

/**
 * Provides Screen Options for the manage snippets table.
 */
class Manage_Menu_Screen_Options {

	/**
	 * Register hooks this class.
	 */
	public function __construct() {
		add_action( 'wp_loaded', [ $this, 'save_truncation_preference' ] );
		add_filter( 'set-screen-option', [ $this, 'save_per_page_option' ], 10, 3 );
	}

	/**
	 * Load the screen options for the current page.
	 *
	 * @return void
	 */
	public function load() {
		$screen = get_current_screen();

		if ( $this->is_upsell_view() ) {
			// The upsell page has no table or settings of its own, so the whole
			// screen-meta-links block (Screen Options + Help tabs) is unhooked.
			add_filter( 'screen_options_show_screen', '__return_false' );
			return;
		}

		if ( $screen && ! $this->is_cloud_community_view() ) {
			add_filter( "manage_{$screen->id}_columns", [ $this, 'get_columns' ] );
			add_filter( 'screen_settings', [ $this, 'render' ] );
		}

		add_screen_option(
			'per_page',
			[
				'label'   => __( 'Snippets per page', 'code-snippets' ),
				'default' => Manage_Menu::get_default_snippets_per_page(),
				'option'  => 'snippets_per_page',
			]
		);
	}

	/**
	 * Add the snippets table columns.
	 *
	 * @param string[] $columns Existing columns.
	 *
	 * @return string[]
	 */
	public function get_columns( array $columns = [] ): array {
		return array_merge(
			$columns,
			[
				'_title'   => __( 'Columns', 'code-snippets' ),
				'activate' => __( 'Active', 'code-snippets' ),
				'name'     => __( 'Name', 'code-snippets' ),
				'type'     => __( 'Type', 'code-snippets' ),
				'desc'     => __( 'Description', 'code-snippets' ),
				'tags'     => __( 'Tags', 'code-snippets' ),
				'date'     => __( 'Modified', 'code-snippets' ),
				'priority' => __( 'Priority', 'code-snippets' ),
			]
		);
	}

	/**
	 * Get the columns hidden for the current user.
	 *
	 * @return string[]
	 */
	public function get_hidden_columns(): array {
		$screen = get_current_screen();

		return $screen ? get_hidden_columns( $screen ) : [];
	}

	/**
	 * Whether long row values should be truncated.
	 *
	 * @return bool
	 */
	public function should_truncate_rows(): bool {
		$setting = get_user_option( 'snippets_table_truncate_row_values' );
		return false === $setting || $setting;
	}

	/**
	 * Read the current manage subpage.
	 *
	 * @return string
	 */
	public function get_current_subpage(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only routing parameter.
		return isset( $_REQUEST['subpage'] ) ? sanitize_key( wp_unslash( $_REQUEST['subpage'] ) ) : '';
	}

	/**
	 * Whether the current request renders the snippets table.
	 *
	 * @return bool
	 */
	public function is_manage_table_view(): bool {
		return ! $this->get_current_subpage();
	}

	/**
	 * Whether the current request renders the AI Agent demo.
	 *
	 * The demo has no screen options or help tabs of its own, so it remains an
	 * upsell view for {@see load()}; this detection exists only so its runtime
	 * data can be localized on the subpage that uses it.
	 *
	 * @return bool
	 */
	public function is_ai_agent_view(): bool {
		return 'ai-agent' === $this->get_current_subpage();
	}

	/**
	 * Whether the current request renders Community Cloud.
	 *
	 * The "Bundles" tab within Community Cloud is a Pro-only upsell rather than
	 * genuine Community Cloud content, so it is excluded here and picked up by
	 * {@see is_upsell_view()} instead.
	 *
	 * @return bool
	 */
	public function is_cloud_community_view(): bool {
		if ( 'cloud-community' !== $this->get_current_subpage() ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only routing parameter.
		$tab = isset( $_REQUEST['tab'] ) ? sanitize_key( wp_unslash( $_REQUEST['tab'] ) ) : '';

		return 'bundles' !== $tab;
	}

	/**
	 * Whether the current request renders an upsell page.
	 *
	 * Every genuine Core subpage has its own positive `is_*_view()` detection
	 * above; a subpage that matches none of them (Blueprints, Cloud Library,
	 * and any future Pro-only addition) has no real content of its own and
	 * falls through to the upsell placeholder.
	 *
	 * @return bool
	 */
	public function is_upsell_view(): bool {
		return ! $this->is_manage_table_view() && ! $this->is_cloud_community_view();
	}

	/**
	 * Render the manage table controls.
	 *
	 * Anything may filter `screen_settings` before this runs, and a callback
	 * that forgets to return its value hands the next one null. Declaring the
	 * parameter as a string turned that into a fatal error, and because it is
	 * raised while the screen meta is being rendered, the page dies after the
	 * admin chrome but before any content: the snippets screen appears blank
	 * while the rest of the admin looks fine.
	 *
	 * @param mixed $screen_settings Existing screen settings HTML, from an
	 *                               unknown number of earlier callbacks.
	 *
	 * @return string
	 */
	public function render( $screen_settings ): string {
		$screen_settings = is_string( $screen_settings ) ? $screen_settings : '';

		if ( $this->is_cloud_community_view() ) {
			return $screen_settings;
		}

		ob_start();
		?>
		<fieldset class="metabox-prefs table-options-prefs">
			<legend><?php esc_html_e( 'Table Options', 'code-snippets' ); ?></legend>
			<div class="metabox-prefs-container">
				<label for="snippets-table-truncate-row-values">
					<input
						id="snippets-table-truncate-row-values"
						name="snippets_table_truncate_row_values"
						type="checkbox"
						value="1"
						<?php checked( $this->should_truncate_rows() ); ?>
					/>
					<?php esc_html_e( 'Truncate long snippet names and descriptions', 'code-snippets' ); ?>
				</label>
			</div>
		</fieldset>
		<?php

		return $screen_settings . ob_get_clean();
	}

	/**
	 * Persist the row truncation preference.
	 *
	 * @return void
	 */
	public function save_truncation_preference(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified below before persisting the option.
		if ( empty( $_POST['wp_screen_options'] ) || ! is_array( $_POST['wp_screen_options'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Sanitized and matched to a known admin page.
		$page = isset( $_REQUEST['page'] ) ? sanitize_key( wp_unslash( $_REQUEST['page'] ) ) : '';

		if ( code_snippets()->get_menu_slug() !== $page || ! current_user_can( code_snippets()->get_cap() ) ) {
			return;
		}

		if ( $this->is_cloud_community_view() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified here before persisting the option.
		$nonce = isset( $_POST['screenoptionnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['screenoptionnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'screen-options-nonce' ) ) {
			return;
		}

		update_user_option(
			get_current_user_id(),
			'snippets_table_truncate_row_values',
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified above before reading the checkbox state.
			isset( $_POST['snippets_table_truncate_row_values'] ) ? 1 : 0
		);
	}

	/**
	 * Save only the snippets per-page Screen Option.
	 *
	 * @param mixed  $status Current screen option status.
	 * @param string $option The screen option name.
	 * @param mixed  $value  Screen option value.
	 *
	 * @return mixed
	 */
	public function save_per_page_option( $status, string $option, $value ) {
		return 'snippets_per_page' === $option ? $value : $status;
	}
}
