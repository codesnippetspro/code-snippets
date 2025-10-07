<?php

namespace Code_Snippets;

use Code_Snippets\Cloud\Cloud_Search_List_Table;
use function Code_Snippets\Settings\get_setting;

/**
 * This class handles the manage snippets menu
 *
 * @since   2.4.0
 * @package Code_Snippets
 */
class Manage_Menu extends Admin_Menu {

	/**
	 * Instance of the list table class.
	 *
	 * @var List_Table
	 */
	public List_Table $list_table;

	/**
	 * Instance of the cloud list table class for search results.
	 *
	 * @var Cloud_Search_List_Table
	 */
	public Cloud_Search_List_Table $cloud_search_list_table;

	/**
	 * Class constructor
	 */
	public function __construct() {
		parent::__construct(
			'manage',
			_x( 'All Snippets', 'menu label', 'code-snippets' ),
			__( 'Snippets', 'code-snippets' )
		);
	}

	/**
	 * Register action and filter hooks
	 */
	public function run() {
		parent::run();

		if ( code_snippets()->is_compact_menu() ) {
			add_action( 'admin_menu', array( $this, 'register_compact_menu' ), 2 );
			add_action( 'network_admin_menu', array( $this, 'register_compact_menu' ), 2 );
		}

		add_action( 'admin_menu', array( $this, 'register_upgrade_menu' ), 500 );
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
	 * Executed when the admin page is loaded
	 */
	public function load() {
		parent::load();

		$contextual_help = new Contextual_Help( 'manage' );
		$contextual_help->load();

		$this->cloud_search_list_table = new Cloud_Search_List_Table();
		$this->cloud_search_list_table->prepare_items();

		$this->list_table = new List_Table();
		$this->list_table->prepare_items();
	}

	/**
	 * Enqueue scripts and stylesheets for the admin page.
	 */
	public function enqueue_assets() {
		$plugin = code_snippets();
		$rtl = is_rtl() ? '-rtl' : '';

		wp_enqueue_style(
			'code-snippets-manage',
			plugins_url( "dist/manage$rtl.css", $plugin->file ),
			[],
			$plugin->version
		);

		wp_enqueue_script(
			'code-snippets-manage-js',
			plugins_url( 'dist/manage.js', $plugin->file ),
			[ 'wp-i18n' ],
			$plugin->version,
			true
		);

		wp_set_script_translations( 'code-snippets-manage-js', 'code-snippets' );

		if ( 'cloud' === $this->get_current_type() || 'cloud_search' === $this->get_current_type() ) {
			Front_End::enqueue_all_prism_themes();
		}
	}

	/**
	 * Get the currently displayed snippet type.
	 *
	 * @return string
	 */
	protected function get_current_type(): string {
		$types = Plugin::get_types();
		$current_type = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : 'all';
		return isset( $types[ $current_type ] ) ? $current_type : 'all';
	}

	/**
	 * Print the status and error messages
	 *
	 * @return void
	 */
	protected function print_messages() {
		$this->render_view( 'partials/list-table-notices' );
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
	 * Update the priority value for a snippet.
	 *
	 * @param Snippet $snippet Snippet to update.
	 *
	 * @return void
	 */
	private function update_snippet_priority( Snippet $snippet ) {
		global $wpdb;
		$table = code_snippets()->db->get_table_name( $snippet->network );

		$wpdb->update(
			$table,
			array( 'priority' => $snippet->priority ),
			array( 'id' => $snippet->id ),
			array( '%d' ),
			array( '%d' )
		);

		clean_snippets_cache( $table );
	}

	/**
	 * Handle AJAX requests
	 */
	public function ajax_callback() {
		check_ajax_referer( 'code_snippets_manage_ajax' );

		if ( ! isset( $_POST['field'], $_POST['snippet'] ) ) {
			wp_send_json_error(
				array(
					'type'    => 'param_error',
					'message' => 'incomplete request',
				)
			);
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$snippet_data = array_map( 'sanitize_text_field', json_decode( wp_unslash( $_POST['snippet'] ), true ) );

		$snippet = new Snippet( $snippet_data );
		$field = sanitize_key( $_POST['field'] );

		if ( 'priority' === $field ) {

			if ( ! isset( $snippet_data['priority'] ) || ! is_numeric( $snippet_data['priority'] ) ) {
				wp_send_json_error(
					array(
						'type'    => 'param_error',
						'message' => 'missing snippet priority data',
					)
				);
			}

			$this->update_snippet_priority( $snippet );

		} elseif ( 'active' === $field ) {

			if ( ! isset( $snippet_data['active'] ) ) {
				wp_send_json_error(
					array(
						'type'    => 'param_error',
						'message' => 'missing snippet active data',
					)
				);
			}

			if ( $snippet->shared_network ) {
				$active_shared_snippets = get_option( 'active_shared_network_snippets', array() );

				if ( in_array( $snippet->id, $active_shared_snippets, true ) !== $snippet->active ) {

					$active_shared_snippets = $snippet->active ?
						array_merge( $active_shared_snippets, array( $snippet->id ) ) :
						array_diff( $active_shared_snippets, array( $snippet->id ) );

					update_option( 'active_shared_network_snippets', $active_shared_snippets );
					clean_active_snippets_cache( code_snippets()->db->ms_table );
				}
			} elseif ( $snippet->active ) {
				$result = activate_snippet( $snippet->id, $snippet->network );
				if ( is_string( $result ) ) {
					wp_send_json_error(
						array(
							'type'    => 'action_error',
							'message' => $result,
						)
					);
				}
			} else {
				deactivate_snippet( $snippet->id, $snippet->network );
			}
		}

		wp_send_json_success();
	}
}
