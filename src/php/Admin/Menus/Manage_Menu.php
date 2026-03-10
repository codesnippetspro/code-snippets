<?php

namespace Code_Snippets\Admin\Menus;

use Code_Snippets\Admin\Contextual_Help;
use Code_Snippets\Utils\Code_Highlighter;
use function Code_Snippets\code_snippets;
use function Code_Snippets\get_snippets;
use function Code_Snippets\Settings\get_setting;
use const Code_Snippets\PLUGIN_FILE;
use const Code_Snippets\PLUGIN_VERSION;

/**
 * This class handles the manage snippets menu
 *
 * @since   2.4.0
 * @package Code_Snippets
 */
class Manage_Menu extends Admin_Menu {

	/**
	 * Handle for JavaScript asset file.
	 */
	public const JS_HANDLE = 'code-snippets-manage-menu';

	/**
	 * Handle for CSS asset file.
	 */
	public const CSS_HANDLE = 'code-snippets-manage';

	/**
	 * Class constructor
	 */
	public function __construct() {
		parent::__construct(
			'manage',
			_x( 'All Snippets', 'menu label', 'code-snippets' ),
			__( 'Snippets', 'code-snippets' )
		);

		if ( code_snippets()->is_compact_menu() ) {
			add_action( 'admin_menu', array( $this, 'register_compact_menu' ), 2 );
			add_action( 'network_admin_menu', array( $this, 'register_compact_menu' ), 2 );
		}

		add_action( 'admin_menu', array( $this, 'register_upgrade_menu' ), 500 );
		add_action( 'admin_init', array( $this, 'save_truncation_preference' ) );
		add_filter( 'set-screen-option', array( $this, 'save_screen_option' ), 10, 3 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_menu_css' ] );
		add_action( 'wp_ajax_update_code_snippet', array( $this, 'ajax_callback' ) );
	}

	/**
	 * Register the top-level 'Snippets' menu and associated 'Manage' subpage
	 */
	public function register() {
		add_menu_page(
			__( 'Snippets', 'code-snippets' ),
			_x( 'Snippets', 'top-level menu label', 'code-snippets' ),
			code_snippets()->get_cap(),
			code_snippets()->get_menu_slug(),
			array( $this, 'render' ),
			'none', // Added through CSS as a mask to prevent loading 'blinking'.
			apply_filters( 'code_snippets/admin/menu_position', is_network_admin() ? 21 : 67 )
		);

		// Register the sub-menu.
		parent::register();
	}

	/**
	 * Register the 'upgrade' menu item.
	 *
	 * @return void
	 */
	public function register_upgrade_menu() {
		if ( code_snippets()->licensing->is_licensed() || get_setting( 'general', 'hide_upgrade_menu' ) ) {
			return;
		}

		$menu_title = sprintf(
			'<span class="button button-primary code-snippets-upgrade-button">%s %s</span>',
			_x( 'Go Pro', 'top-level menu label', 'code-snippets' ),
			'<span class="dashicons dashicons-external"></span>'
		);

		$hook = add_submenu_page(
			code_snippets()->get_menu_slug(),
			__( 'Upgrade to Pro', 'code-snippets' ),
			$menu_title,
			code_snippets()->get_cap(),
			'code_snippets_upgrade',
			'__return_empty_string',
			100
		);

		add_action( "load-$hook", [ $this, 'load_upgrade_menu' ] );
	}

	/**
	 * Print CSS required for the admin menu icon.
	 *
	 * @return void
	 */
	public function enqueue_menu_css() {
		wp_enqueue_style(
			'code-snippets-menu',
			plugins_url( 'dist/menu.css', PLUGIN_FILE ),
			[],
			PLUGIN_VERSION
		);
	}

	/**
	 * Redirect the user upon opening the upgrade menu.
	 *
	 * @return void
	 */
	public function load_upgrade_menu() {
		wp_safe_redirect( 'https://snipco.de/JE2f' );
		exit;
	}

	/**
	 * Add menu pages for the compact menu
	 */
	public function register_compact_menu() {

		if ( ! code_snippets()->is_compact_menu() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Value is matched to known classes.
		$sub = code_snippets()->get_menu_slug( isset( $_GET['sub'] ) ? sanitize_key( $_GET['sub'] ) : 'snippets' );

		$classmap = array(
			'snippets'             => 'manage',
			'add-snippet'          => 'edit',
			'edit-snippet'         => 'edit',
			'import-code-snippets' => 'import',
			'snippets-settings'    => 'settings',
		);

		$menus = code_snippets()->admin->menus;
		$class = isset( $classmap[ $sub ], $menus[ $classmap[ $sub ] ] ) ? $menus[ $classmap[ $sub ] ] : $this;

		/* Add a submenu to the Tools menu */
		$hook = add_submenu_page(
			'tools.php',
			__( 'Snippets', 'code-snippets' ),
			_x( 'Snippets', 'tools submenu label', 'code-snippets' ),
			code_snippets()->get_cap(),
			code_snippets()->get_menu_slug(),
			array( $class, 'render' )
		);

		add_action( 'load-' . $hook, array( $class, 'load' ) );
	}

	/**
	 * Executed when the admin page is loaded.
	 */
	public function load() {
		parent::load();

		$screen = get_current_screen();

		if ( $screen ) {
			add_filter( "manage_{$screen->id}_columns", array( $this, 'get_screen_columns' ) );
			add_filter( 'screen_settings', array( $this, 'render_screen_settings' ), 10, 2 );
		}

		$contextual_help = new Contextual_Help( 'edit' );
		$contextual_help->load();

		add_screen_option(
			'per_page',
			array(
				'label'   => __( 'Snippets per page', 'code-snippets' ),
				'default' => 999,
				'option'  => 'snippets_per_page',
			)
		);
	}

	/**
	 * Enqueue scripts and stylesheets for the admin page.
	 */
	public function enqueue_assets() {
		$plugin = code_snippets();

		wp_enqueue_style(
			self::CSS_HANDLE,
			plugins_url( 'dist/manage.css', PLUGIN_FILE ),
			self::$style_deps,
			PLUGIN_VERSION
		);

		wp_enqueue_script(
			self::JS_HANDLE,
			plugins_url( 'dist/manage.js', PLUGIN_FILE ),
			self::$script_deps,
			PLUGIN_VERSION,
			[ 'in_footer' => true ]
		);

		Code_Highlighter::enqueue_all_prism_themes();

		wp_set_script_translations( self::JS_HANDLE, 'code-snippets' );
		$plugin->localize_script( self::JS_HANDLE );

		wp_localize_script(
			self::JS_HANDLE,
			'CODE_SNIPPETS_MANAGE',
			[
				'hasNetworkCap'    => current_user_can( code_snippets()->get_network_cap_name() ),
				'hiddenColumns'     => $this->get_hidden_manage_columns(),
				'truncateRowValues' => (int) $this->truncate_row_values(),
				'snippetsPerPage'  => $this->get_snippets_per_page(),
				'isSafeModeActive' => code_snippets()->evaluate_functions->is_safe_mode_active(),
				'snippetsList'     => array_map(
					function ( $snippet ) {
						return $snippet->get_fields();
					},
					get_snippets()
				),
			]
		);
	}

	/**
	 * Get the number of snippets to show per page.
	 *
	 * @return int
	 */
	protected function get_snippets_per_page(): int {
		$per_page = (int) get_user_option( 'snippets_per_page' );

		if ( empty( $per_page ) || $per_page < 1 ) {
			$per_page = 999;
		}

		return (int) apply_filters( 'snippets_per_page', $per_page );
	}

	/**
	 * Render the snippets table interface.
	 *
	 * @return void
	 */
	public function render() {
		echo '<div id="manage-snippets-container"></div>';
	}

	/**
	 * Return the columns available in Screen Options for the snippets table.
	 *
	 * @param string[] $columns Existing columns.
	 *
	 * @return string[]
	 */
	public function get_screen_columns( array $columns = array() ): array {
		return array_merge(
			$columns,
			array(
				'_title'   => __( 'Columns', 'code-snippets' ),
				'activate' => __( 'Active', 'code-snippets' ),
				'name'     => __( 'Name', 'code-snippets' ),
				'type'     => __( 'Type', 'code-snippets' ),
				'desc'     => __( 'Description', 'code-snippets' ),
				'tags'     => __( 'Tags', 'code-snippets' ),
				'date'     => __( 'Modified', 'code-snippets' ),
				'priority' => __( 'Priority', 'code-snippets' ),
			)
		);
	}

	/**
	 * Get the list of columns hidden for the current user on the snippets screen.
	 *
	 * @return string[]
	 */
	protected function get_hidden_manage_columns(): array {
		$screen = get_current_screen();

		return $screen ? get_hidden_columns( $screen ) : array();
	}

	/**
	 * Whether to truncate long row values in the snippets table.
	 *
	 * @return bool
	 */
	protected function truncate_row_values(): bool {
		$setting = get_user_option( 'snippets_table_truncate_row_values' );

		return false === $setting ? true : (bool) $setting;
	}

	/**
	 * Render extra Screen Options controls for the snippets table.
	 *
	 * @param string     $screen_settings Existing screen settings HTML.
	 * @param \WP_Screen $screen          Current screen object.
	 *
	 * @return string
	 */
	public function render_screen_settings( string $screen_settings, \WP_Screen $screen ): string {
		ob_start();
		?>
		<fieldset class="metabox-prefs">
			<legend><?php esc_html_e( 'Table Options', 'code-snippets' ); ?></legend>
			<div class="metabox-prefs-container">
				<label for="snippets-table-truncate-row-values">
					<input
						id="snippets-table-truncate-row-values"
						name="snippets_table_truncate_row_values"
						type="checkbox"
						value="1"
						<?php checked( $this->truncate_row_values() ); ?>
					/>
					<?php esc_html_e( 'Truncate long row values', 'code-snippets' ); ?>
				</label>
			</div>
		</fieldset>
		<?php

		return $screen_settings . ob_get_clean();
	}

	/**
	 * Persist the snippets table truncation preference from Screen Options.
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
	 * Handles saving the user's snippets per page preference
	 *
	 * @param mixed  $status Current screen option status.
	 * @param string $option The screen option name.
	 * @param mixed  $value  Screen option value.
	 *
	 * @return mixed
	 */
	public function save_screen_option( $status, string $option, $value ) {
		return 'snippets_per_page' === $option ? $value : $status;
	}
}
