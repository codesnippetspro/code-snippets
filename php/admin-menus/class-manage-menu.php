<?php

namespace Code_Snippets;

/**
 * This class handles the manage snippets menu
 *
 * @since   2.4.0
 * @package Code_Snippets
 */
class Manage_Menu extends Admin_Menu {

	/**
	 * Holds the list table class
	 *
	 * @var List_Table
	 */
	public $list_table;

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

		add_filter( 'set-screen-option', array( $this, 'save_screen_option' ), 10, 3 );
		add_action( 'wp_ajax_update_code_snippet', array( $this, 'ajax_callback' ) );
	}

	/**
	 * Register the top-level 'Snippets' menu and associated 'Manage' subpage
	 */
	public function register() {

		// Register the top-level menu.
		add_menu_page(
			__( 'Snippets', 'code-snippets' ),
			_x( 'Snippets', 'top-level menu label', 'code-snippets' ),
			code_snippets()->get_cap(),
			code_snippets()->get_menu_slug(),
			array( $this, 'render' ),
			'div', // Icon is added through CSS.
			is_network_admin() ? 21 : 67
		);

		// Register the sub-menu.
		parent::register();
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

		if ( isset( $classmap[ $sub ], code_snippets()->admin->menus[ $classmap[ $sub ] ] ) ) {
			/** Menu class @var Admin_Menu $class */
			$class = code_snippets()->admin->menus[ $classmap[ $sub ] ];
		} else {
			$class = $this;
		}

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

		/* Load the contextual help tabs */
		$contextual_help = new Contextual_Help( 'manage' );
		$contextual_help->load();

		/* Initialize the list table class */
		$this->list_table = new List_Table();
		$this->list_table->prepare_items();
	}

	/**
	 * Enqueue scripts and stylesheets for the admin page
	 */
	public function enqueue_assets() {
		$plugin = code_snippets();
		$rtl = is_rtl() ? '-rtl' : '';

		wp_enqueue_style(
			'code-snippets-manage',
			plugins_url( "css/min/manage$rtl.css", $plugin->file ),
			array(),
			$plugin->version
		);

		wp_enqueue_script(
			'code-snippets-manage-js',
			plugins_url( 'js/min/manage.js', $plugin->file ),
			array(),
			$plugin->version,
			true
		);

		wp_localize_script(
			'code-snippets-manage-js',
			'code_snippets_manage_i18n',
			array(
				'activate'         => __( 'Activate', 'code-snippets' ),
				'deactivate'       => __( 'Deactivate', 'code-snippets' ),
				'activation_error' => __( 'An error occurred when attempting to activate', 'code-snippets' ),
			)
		);
	}

	/**
	 * Print the status and error messages
	 */
	protected function print_messages() {

		/* Output a warning if safe mode is active */
		if ( defined( 'CODE_SNIPPETS_SAFE_MODE' ) && CODE_SNIPPETS_SAFE_MODE ) {
			echo '<div id="message" class="error fade"><p>';
			echo wp_kses_post( __( '<strong>Warning:</strong> Safe mode is active and snippets will not execute! Remove the <code>CODE_SNIPPETS_SAFE_MODE</code> constant from <code>wp-config.php</code> to turn off safe mode. <a href="https://help.codesnippets.pro/article/12-safe-mode" target="_blank">Help</a>', 'code-snippets' ) );
			echo '</p></div>';
		}

		$this->print_result_message(
			array(
				'executed'          => __( 'Snippet <strong>executed</strong>.', 'code-snippets' ),
				'activated'         => __( 'Snippet <strong>activated</strong>.', 'code-snippets' ),
				'activated-multi'   => __( 'Selected snippets <strong>activated</strong>.', 'code-snippets' ),
				'deactivated'       => __( 'Snippet <strong>deactivated</strong>.', 'code-snippets' ),
				'deactivated-multi' => __( 'Selected snippets <strong>deactivated</strong>.', 'code-snippets' ),
				'deleted'           => __( 'Snippet <strong>deleted</strong>.', 'code-snippets' ),
				'deleted-multi'     => __( 'Selected snippets <strong>deleted</strong>.', 'code-snippets' ),
				'cloned'            => __( 'Snippet <strong>cloned</strong>.', 'code-snippets' ),
				'cloned-multi'      => __( 'Selected snippets <strong>cloned</strong>.', 'code-snippets' ),
			)
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
	public function save_screen_option( $status, $option, $value ) {
		if ( 'snippets_per_page' === $option ) {
			return $value;
		}

		return $status;
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
		); // db call ok.

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
			} else {

				if ( $snippet->active ) {
					$result = activate_snippet( $snippet->id, $snippet->network );
					if ( ! $result ) {
						wp_send_json_error(
							array(
								'type'    => 'action_error',
								'message' => 'error activating snippet',
							)
						);
					}
				} else {
					deactivate_snippet( $snippet->id, $snippet->network );
				}
			}
		}

		wp_send_json_success();
	}
}
