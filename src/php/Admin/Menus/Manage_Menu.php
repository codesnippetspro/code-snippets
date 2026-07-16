<?php

namespace Code_Snippets\Admin\Menus;

use Code_Snippets\Admin\Contextual_Help;
use Code_Snippets\Controller\Cloud_Search_Controller;
use Code_Snippets\Integration\Evaluate_Functions;
use Code_Snippets\Migration\Export\Download_Code;
use Code_Snippets\Model\Snippet;
use Code_Snippets\Utils\Code_Highlighter;
use WP_Error;
use function Code_Snippets\code_snippets;
use function Code_Snippets\get_snippet;
use function Code_Snippets\get_snippets;
use function Code_Snippets\Settings\get_setting;
use function Code_Snippets\Utils\enqueue_code_preview_editor;
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
	 * Default number of snippets shown per page in the manage table.
	 */
	public const DEFAULT_SNIPPETS_PER_PAGE = 100;

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
		add_action( 'admin_init', array( $this, 'handle_bulk_download_request' ) );
		add_action( 'admin_init', array( $this, 'save_truncation_preference' ) );
		add_filter( 'set-screen-option', array( $this, 'save_screen_option' ), 10, 3 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_menu_css' ) );
		add_action( 'wp_ajax_update_code_snippet', array( $this, 'ajax_callback' ) );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_menu_css' ] );
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
			'<span class="dashicons dashicons-external" aria-hidden="true"></span>'
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

		if ( $screen && ! $this->is_cloud_community_view() ) {
			add_filter( "manage_{$screen->id}_columns", [ $this, 'get_screen_columns' ] );
			add_filter( 'screen_settings', [ $this, 'render_screen_settings' ] );
		}

		add_screen_option(
			'per_page',
			[
				'label'   => __( 'Snippets per page', 'code-snippets' ),
				'default' => $this->get_default_snippets_per_page(),
				'option'  => 'snippets_per_page',
			]
		);

		$contextual_help = new Contextual_Help( 'edit' );
		$contextual_help->load();
	}

	/**
	 * Enqueue scripts and stylesheets for the admin page.
	 */
	public function enqueue_assets() {
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

		enqueue_code_preview_editor( 'php' );

		wp_set_script_translations( self::JS_HANDLE, 'code-snippets' );
		code_snippets()->localize_script( self::JS_HANDLE );

		$localized = [
			'hasNetworkCap'        => current_user_can( code_snippets()->get_network_cap_name() ),
			'hiddenColumns'        => $this->get_hidden_manage_columns(),
			'truncateRowValues'    => (int) $this->truncate_row_values(),
			'snippetsPerPage'      => self::get_snippets_per_page(),
			'cloudSearchPerPage'   => $this->get_cloud_search_per_page(),
			'isSafeModeActive'     => Evaluate_Functions::is_safe_mode_active(),
			'bulkDownloadNonce'    => wp_create_nonce( 'code_snippets_bulk_download' ),
			'supportsZipDownloads' => class_exists( 'ZipArchive' ),
			'editorTheme'          => get_setting( 'editor', 'theme' ),
			'typeCounts'           => $this->count_snippets_by_type(),
		];

		// Only the manage-table view consumes the full snippets list; skip the
		// table scan and inline payload on subpages that don't render the table.
		if ( $this->is_manage_table_view() ) {
			$snippets = get_snippets();

			// The inline payload includes full code bodies, so its size grows with
			// library content; larger libraries defer to the REST fetch instead.
			if ( count( $snippets ) <= 100 ) {
				$localized['snippetsList'] = array_map(
					function ( $snippet ) {
						return $snippet->get_fields();
					},
					$snippets
				);
			}
		}

		wp_localize_script( self::JS_HANDLE, 'CODE_SNIPPETS_MANAGE', $localized );
	}

	/**
	 * Get the number of snippets to show per page in the cloud search.
	 *
	 * The value defaults to the user's local snippets-per-page preference but can
	 * be overridden independently via the `code_snippets/cloud_search/per_page` filter.
	 *
	 * @return int
	 */
	public static function get_cloud_search_per_page(): int {
		$per_page = intval( apply_filters( 'code_snippets/cloud_search/per_page', self::get_snippets_per_page() ) );
		return min( Cloud_Search_Controller::MAX_RESULTS_PER_PAGE, max( 1, $per_page ) );
	}

	/**
	 * Get the default number of snippets to show per page.
	 *
	 * @return int
	 */
	public static function get_default_snippets_per_page(): int {
		$default = apply_filters( 'code_snippets/snippets_per_page_default', self::DEFAULT_SNIPPETS_PER_PAGE );
		return max( 1, intval( $default ) );
	}

	/**
	 * Count non-trashed snippets per type with a single grouped query, so the
	 * type tabs can render their counts without loading full snippet data.
	 *
	 * @return array<string, int> Map of type name to snippet count, including 'all'.
	 */
	private function count_snippets_by_type(): array {
		global $wpdb;
		$table = code_snippets()->db->get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotSafe -- table name from trusted source; counts are cheap and rendered per-request.
		$results = $wpdb->get_results( "SELECT scope, COUNT(*) AS count FROM $table WHERE active >= 0 GROUP BY scope" );

		$counts = [ 'all' => 0 ];

		foreach ( $results as $row ) {
			$type = Snippet::get_type_from_scope( $row->scope );
			$counts[ $type ] = ( $counts[ $type ] ?? 0 ) + (int) $row->count;
			$counts['all'] += (int) $row->count;
		}

		return $counts;
	}

	/**
	 * Get the number of snippets to show per page.
	 *
	 * @return int
	 */
	public static function get_snippets_per_page(): int {
		$per_page = intval( get_user_option( 'snippets_per_page' ) );

		return intval(
			apply_filters(
				'snippets_per_page',
				$per_page > 0 ? $per_page : self::get_default_snippets_per_page()
			)
		);
	}

	/**
	 * Render the snippets table interface.
	 *
	 * @return void
	 */
	public function render() {
		echo '<div id="manage-snippets-container" class="wrap"></div>';
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
		return false === $setting || $setting;
	}

	/**
	 * Read the current `subpage` routing parameter.
	 *
	 * @return string
	 */
	protected function get_current_subpage(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only routing parameter.
		return isset( $_REQUEST['subpage'] ) ? sanitize_key( wp_unslash( $_REQUEST['subpage'] ) ) : '';
	}

	/**
	 * Whether the current request renders the snippets-table view (the default subpage).
	 *
	 * @return bool
	 */
	protected function is_manage_table_view(): bool {
		return ! $this->get_current_subpage();
	}

	/**
	 * Whether the current manage subpage is the Community Cloud view.
	 *
	 * @return bool
	 */
	protected function is_cloud_community_view(): bool {
		return 'cloud-community' === $this->get_current_subpage();
	}

	/**
	 * Render extra Screen Options controls for the snippets table.
	 *
	 * @param string $screen_settings Existing screen settings HTML.
	 *
	 * @return string
	 */
	public function render_screen_settings( string $screen_settings ): string {
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
						<?php checked( $this->truncate_row_values() ); ?>
					/>
					<?php esc_html_e( 'Truncate long snippet names and descriptions', 'code-snippets' ); ?>
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

	/**
	 * Handle bulk snippet code downloads from the manage screen.
	 *
	 * @return void
	 */
	public function handle_bulk_download_request(): void {
		if ( ! $this->is_bulk_download_request() ) {
			return;
		}

		if ( ! current_user_can( code_snippets()->get_cap() ) ) {
			$this->send_download_error( __( 'You are not allowed to download these snippets.', 'code-snippets' ), 403 );
		}

		$nonce = filter_input( INPUT_POST, 'code_snippets_bulk_download_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ?? '';

		if ( ! wp_verify_nonce( $nonce, 'code_snippets_bulk_download' ) ) {
			$this->send_download_error( __( 'The download request is no longer valid. Please refresh and try again.', 'code-snippets' ), 403 );
		}

		$snippets_json = wp_unslash( filter_input( INPUT_POST, 'snippets', FILTER_DEFAULT ) ?? '' );
		$snippets = $this->get_requested_download_snippets( $snippets_json );

		if ( $snippets instanceof WP_Error ) {
			$status = $snippets->get_error_data( 'status' );
			$this->send_download_error( $snippets->get_error_message(), is_numeric( $status ) ? (int) $status : 403 );
		}

		if ( empty( $snippets ) ) {
			$this->send_download_error( __( 'No snippets were selected for download.', 'code-snippets' ) );
		}

		$download = 1 === count( $snippets )
			? Download_Code::build_snippet_download( $snippets[0] )
			: Download_Code::build_archive_download( $snippets );

		if ( $download instanceof WP_Error ) {
			$status = $download->get_error_data( 'status' );
			$this->send_download_error( $download->get_error_message(), is_numeric( $status ) ? (int) $status : 500 );
		}

		$this->send_download_response( $download );
	}

	/**
	 * Determine whether the current request is a bulk download request.
	 *
	 * @return bool
	 */
	private function is_bulk_download_request(): bool {
		$page = sanitize_key( filter_input( INPUT_GET, 'page' ) ?? '' );
		$action = sanitize_key( filter_input( INPUT_POST, 'code_snippets_action' ) ?? '' );

		return code_snippets()->get_menu_slug() === $page && 'bulk-download' === $action;
	}

	/**
	 * Resolve the snippets requested for download.
	 *
	 * @param string $snippets_json JSON-encoded list of requested snippets.
	 *
	 * @return Snippet[]|WP_Error
	 */
	private function get_requested_download_snippets( string $snippets_json ) {
		$payload = '' === $snippets_json ? [] : json_decode( $snippets_json, true );

		if ( ! is_array( $payload ) ) {
			return [];
		}

		$snippets = [];

		foreach ( $payload as $snippet_data ) {
			if ( ! is_array( $snippet_data ) || empty( $snippet_data['id'] ) ) {
				continue;
			}

			if ( ! empty( $snippet_data['network'] ) && ! current_user_can( code_snippets()->get_network_cap_name() ) ) {
				return new WP_Error(
					'code_snippets_forbidden_network_download',
					__( 'You are not allowed to download network snippets.', 'code-snippets' ),
					[ 'status' => 403 ]
				);
			}

			$snippet = get_snippet(
				absint( $snippet_data['id'] ),
				! empty( $snippet_data['network'] )
			);

			if ( $snippet->id ) {
				$snippets[] = $snippet;
			}
		}

		return $snippets;
	}

	/**
	 * Send a download file response and end execution.
	 *
	 * @param array{filename:string, content_type:string, content:string} $download Download data.
	 *
	 * @return void
	 */
	private function send_download_response( array $download ): void {
		while ( ob_get_level() ) {
			ob_end_clean();
		}

		nocache_headers();
		send_nosniff_header();

		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: ' . $download['content_type'] );
		header( 'Content-Disposition: attachment; filename="' . $download['filename'] . '"' );
		header( 'Content-Length: ' . strlen( $download['content'] ) );
		header( 'X-Suggested-Filename: ' . $download['filename'] );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary download payload.
		echo $download['content'];
		exit;
	}

	/**
	 * Send a plain text download error response and end execution.
	 *
	 * @param string $message Error message.
	 * @param int    $status  HTTP status code.
	 *
	 * @return void
	 */
	private function send_download_error( string $message, int $status = 400 ): void {
		while ( ob_get_level() ) {
			ob_end_clean();
		}

		status_header( $status );
		nocache_headers();
		send_nosniff_header();
		header( 'Content-Type: text/plain; charset=' . get_option( 'blog_charset' ) );

		echo esc_html( $message );
		exit;
	}
}
