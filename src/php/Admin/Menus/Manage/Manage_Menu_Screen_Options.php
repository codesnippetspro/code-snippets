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
		add_action( 'admin_init', [ $this, 'save_truncation_preference' ] );
		add_filter( 'set-screen-option', [ $this, 'save_per_page_option' ], 10, 3 );
	}

	/**
	 * Load the screen options for the current page.
	 *
	 * @return void
	 */
	public function load() {
		$screen = get_current_screen();

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
	 * Whether the current request renders Community Cloud.
	 *
	 * @return bool
	 */
	public function is_cloud_community_view(): bool {
		return 'cloud-community' === $this->get_current_subpage();
	}

	/**
	 * Render the manage table controls.
	 *
	 * @param string $screen_settings Existing screen settings HTML.
	 *
	 * @return string
	 */
	public function render( string $screen_settings ): string {
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
