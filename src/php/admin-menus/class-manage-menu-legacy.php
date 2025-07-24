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
class Manage_Menu_Legacy extends Admin_Menu {

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
			'manage-legacy',
			_x( 'All Snippets (legacy)', 'menu label', 'code-snippets' ),
			__( 'Snippets', 'code-snippets' )
		);
	}

	/**
	 * Register action and filter hooks
	 */
	public function run() {
		parent::run();

		add_filter( 'set-screen-option', array( $this, 'save_screen_option' ), 10, 3 );
		add_action( 'wp_ajax_update_code_snippet', array( $this, 'ajax_callback' ) );
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
	 * Render the menu
	 */
	public function render() {
		$this->render_view( 'manage' );
	}

	/**
	 * Enqueue scripts and stylesheets for the admin page.
	 */
	public function enqueue_assets() {
		$plugin = code_snippets();

		wp_enqueue_style(
			'code-snippets-manage-legacy',
			plugins_url( 'dist/manage-legacy.css', $plugin->file ),
			[],
			$plugin->version
		);

		wp_enqueue_script(
			'code-snippets-manage-legacy-js',
			plugins_url( 'dist/manage-legacy.js', $plugin->file ),
			[ 'wp-i18n' ],
			$plugin->version,
			true
		);

		wp_set_script_translations( 'code-snippets-manage-legacy-js', 'code-snippets' );

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
