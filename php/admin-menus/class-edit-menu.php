<?php

namespace Code_Snippets;

use Code_Snippets\REST_API\Snippets_REST_Controller;
use function Code_Snippets\Settings\get_setting;

/**
 * This class handles the add/edit menu
 */
class Edit_Menu extends Admin_Menu {

	/**
	 * The snippet object currently being edited
	 *
	 * @var Snippet
	 * @see Edit_Menu::load_snippet_data()
	 */
	protected $snippet = null;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			'edit',
			_x( 'Edit Snippet', 'menu label', 'code-snippets' ),
			__( 'Edit Snippet', 'code-snippets' )
		);
	}

	/**
	 * Register action and filter hooks
	 */
	public function run() {
		parent::run();
		$this->remove_debug_bar_codemirror();
	}

	/**
	 * Register the admin menu
	 */
	public function register() {
		parent::register();

		// Only preserve the edit menu if we are currently editing a snippet.
		if ( ! isset( $_REQUEST['page'] ) || $_REQUEST['page'] !== $this->slug ) {
			remove_submenu_page( $this->base_slug, $this->slug );
		}

		// Add New Snippet menu.
		$this->add_menu(
			code_snippets()->get_menu_slug( 'add' ),
			_x( 'Add New', 'menu label', 'code-snippets' ),
			__( 'Add New Snippet', 'code-snippets' )
		);
	}

	/**
	 * Executed when the menu is loaded
	 */
	public function load() {
		parent::load();

		// Retrieve the current snippet object.
		$this->load_snippet_data();

		$screen = get_current_screen();
		$edit_hook =
			get_plugin_page_hookname( $this->slug, $this->base_slug ) .
			$screen->in_admin( 'network' ) ? '-network' : '';

		// Disallow visiting the edit snippet page without a valid ID.
		if ( $screen->base === $edit_hook && ( empty( $_REQUEST['id'] ) || 0 === $this->snippet->id || null === $this->snippet->id ) &&
		     ! isset( $_REQUEST['preview'] ) ) {
			wp_safe_redirect( code_snippets()->get_menu_url( 'add' ) );
			exit;
		}

		// Process any submitted actions.
		$this->process_actions();

		// Load the contextual help tabs.
		$contextual_help = new Contextual_Help( 'edit' );
		$contextual_help->load();
	}

	/**
	 * Render the edit menu interface.
	 *
	 * @return void
	 */
	public function render() {
		echo '<div class="wrap"><h1>';

		if ( $this->snippet->id ) {
			esc_html_e( 'Edit Snippet', 'code-snippets' );
			$this->page_title_actions( [ 'add' ] );
		} else {
			esc_html_e( 'Add New Snippet', 'code-snippets' );
		}

		if ( code_snippets()->is_compact_menu() ) {
			$this->page_title_actions( [ 'manage', 'import', 'settings' ] );
		}

		echo '</h1>';

		printf(
			'<div id="edit-snippet-form-container" data-snippet-id="%s"></div>',
			esc_attr( $this->snippet->id )
		);

		echo '</div>';
	}

	/**
	 * Load the data for the snippet currently being edited.
	 */
	public function load_snippet_data() {
		$edit_id = isset( $_REQUEST['id'] ) ? absint( $_REQUEST['id'] ) : 0;

		$this->snippet = get_snippet( $edit_id );

		if ( 0 === $edit_id && isset( $_GET['type'] ) && sanitize_key( $_GET['type'] ) !== $this->snippet->type ) {
			$type = sanitize_key( $_GET['type'] );

			$default_scopes = [
				'php'  => 'global',
				'css'  => 'site-css',
				'html' => 'content',
				'js'   => 'site-head-js',
				'cond' => 'condition',
			];

			if ( isset( $default_scopes[ $type ] ) ) {
				$this->snippet->scope = $default_scopes[ $type ];
			}
		}

		$this->snippet = apply_filters( 'code_snippets/admin/load_snippet_data', $this->snippet );
	}

	/**
	 * Process data sent from the edit page
	 */
	private function process_actions() {

		/* Check for a valid nonce */
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'save_snippet' ) ) {
			return;
		}

		if ( isset( $_POST['save_snippet'] ) || isset( $_POST['save_snippet_execute'] ) ||
		     isset( $_POST['save_snippet_activate'] ) || isset( $_POST['save_snippet_deactivate'] ) ) {
			$this->save_posted_snippet();
		}

		if ( isset( $_POST['snippet_id'] ) ) {
			$snippet_id = intval( $_POST['snippet_id'] );

			/* Delete the snippet if the button was clicked */
			if ( isset( $_POST['delete_snippet'] ) ) {
				delete_snippet( $snippet_id );
				wp_safe_redirect( add_query_arg( 'result', 'delete', code_snippets()->get_menu_url( 'manage' ) ) );
				exit;
			}

			/* Export the snippet if the button was clicked */
			if ( isset( $_POST['export_snippet'] ) ) {
				$export = new Export_Attachment( $snippet_id );
				$export->download_snippets_json();
			}

			/* Download the snippet if the button was clicked */
			if ( isset( $_POST['download_snippet'] ) ) {
				$export = new Export_Attachment( $snippet_id );
				$export->download_snippets_code();
			}

			do_action( 'code_snippets/admin/process_actions', $snippet_id );
		}
	}

	/**
	 * Save the posted snippet data to the database and redirect
	 */
	private function save_posted_snippet() {

		/* Build snippet object from fields with 'snippet_' prefix */
		$snippet = new Snippet();

		/* Activate or deactivate the snippet before saving if we clicked the button */

		if ( isset( $_POST['save_snippet_execute'] ) ) {
			$snippet->active = 1;
		} elseif ( isset( $_POST['snippet_sharing'] ) && 'on' === $_POST['snippet_sharing'] ) {
			// Shared network snippets cannot be network-activated.
			$snippet->active = 0;
			unset( $_POST['save_snippet_activate'], $_POST['save_snippet_deactivate'] );
		} elseif ( isset( $_POST['save_snippet_activate'] ) ) {
			$snippet->active = 1;
		} elseif ( isset( $_POST['save_snippet_deactivate'] ) ) {
			$snippet->active = 0;
		}

		/* Save the snippet to the database */
		$snippet_id = save_snippet( $snippet );

		/* If the saved snippet ID is invalid, display an error message */
		if ( ! $snippet_id || $snippet_id < 1 ) {
			/* An error occurred */
			wp_safe_redirect( add_query_arg( 'result', 'save-error', code_snippets()->get_menu_url( 'add' ) ) );
			exit;
		}

		/* Display message if a parse error occurred */
		if ( isset( $code_error ) && $code_error ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'id'     => $snippet_id,
						'result' => 'code-error',
					),
					code_snippets()->get_menu_url( 'edit' )
				)
			);
			exit;
		}

		/* Set the result depending on if the snippet was just added */
		$result = isset( $_POST['snippet_id'] ) ? 'updated' : 'added';

		/* Append a suffix if the snippet was activated or deactivated */
		if ( isset( $_POST['save_snippet_activate'] ) ) {
			$result .= '-and-activated';
		} elseif ( isset( $_POST['save_snippet_deactivate'] ) ) {
			$result .= '-and-deactivated';
		} elseif ( isset( $_POST['save_snippet_execute'] ) ) {
			$result .= '-and-executed';
		}

		/* Redirect to edit snippet page */
		$redirect_uri = add_query_arg(
			array(
				'id'     => $snippet_id,
				'result' => $result,
			),
			code_snippets()->get_menu_url( 'edit' )
		);

		wp_safe_redirect( esc_url_raw( $redirect_uri ) );
		exit;
	}

	/**
	 * Retrieve the first error in a snippet's code
	 *
	 * @param int $snippet_id Snippet ID.
	 *
	 * @return array<string, mixed>|bool Error if execution failed, otherwise false.
	 */
	private function get_snippet_error( $snippet_id ) {

		if ( ! intval( $snippet_id ) ) {
			return false;
		}

		$snippet = get_snippet( intval( $snippet_id ) );

		if ( '' === $snippet->code ) {
			return false;
		}

		$validator = new Validator( $snippet->code );
		$error = $validator->validate();

		if ( $error ) {
			return $error;
		}

		ob_start();
		$result = eval( $snippet->code );
		ob_end_clean();

		if ( false !== $result ) {
			return false;
		}

		$error = error_get_last();

		if ( is_null( $error ) ) {
			return false;
		}

		return $error;
	}

	/**
	 * Print the status and error messages
	 */
	protected function print_messages() {

		if ( ! isset( $_REQUEST['result'] ) ) {
			return;
		}

		$result = sanitize_key( $_REQUEST['result'] );

		if ( 'code-error' === $result ) {
			$error = isset( $_REQUEST['id'] ) ? $this->get_snippet_error( intval( $_REQUEST['id'] ) ) : false;

			if ( $error ) {
				/* translators: %d: line of file where error originated */
				$text = __( 'The snippet has been deactivated due to an error on line %d:', 'code-snippets' );

				printf(
					'<div id="message" class="error fade"><p>%s</p><p><strong>%s</strong></p></div>',
					sprintf( esc_html( $text ), intval( $error['line'] ) ),
					wp_kses_post( $error['message'] )
				);

			} else {
				echo '<div id="message" class="error fade"><p>';
				esc_html_e( 'The snippet has been deactivated due to an error in the code.', 'code-snippets' );
				echo '</p></div>';
			}

			return;
		}

		if ( 'save-error' === $result ) {
			echo '<div id="message" class="error fade"><p>';
			esc_html_e( 'An error occurred when saving the snippet.', 'code-snippets' );
			echo '</p></div>';
		}
	}

	/**
	 * Enqueue assets for the edit menu
	 */
	public function enqueue_assets() {
		$plugin = code_snippets();
		$rtl = is_rtl() ? '-rtl' : '';

		$settings = Settings\get_settings_values();
		$tags_enabled = $settings['general']['enable_tags'];
		$desc_enabled = $settings['general']['enable_description'];

		enqueue_code_editor( $this->snippet->type );

		$css_deps = [
			'code-editor',
			'wp-components'
		];

		$js_deps = [
			'code-snippets-code-editor',
			'react',
			'react-dom',
			'wp-url',
			'wp-i18n',
			'wp-api-fetch',
			'wp-components'
		];

		wp_enqueue_style(
			'code-snippets-edit',
			plugins_url( "dist/edit$rtl.css", $plugin->file ),
			$css_deps,
			$plugin->version
		);

		wp_enqueue_script(
			'code-snippets-edit-menu',
			plugins_url( 'dist/edit.js', $plugin->file ),
			$js_deps,
			$plugin->version,
			true
		);

		if ( $desc_enabled ) {
			remove_editor_styles();
			wp_enqueue_editor();
		}

		wp_localize_script(
			'code-snippets-edit-menu',
			'CODE_SNIPPETS_EDIT',
			[
				'snippet'           => $this->snippet->get_fields(),
				'restAPI'           => [
					'base'  => esc_url_raw( rest_url( Snippets_REST_Controller::get_base_route() ) ),
					'nonce' => wp_create_nonce( 'wp_rest' ),
				],
				'isPreview'         => isset( $_REQUEST['preview'] ),
				'activateByDefault' => get_setting( 'general', 'activate_by_default' ),
				'editorTheme'       => get_setting( 'editor', 'theme' ),
				'extraSaveButtons'  => apply_filters( 'code_snippets/extra_save_buttons', true ),
				'enableDownloads'   => apply_filters( 'code_snippets/enable_downloads', true ),
				'enableDescription' => $desc_enabled,
				'tagOptions'        => apply_filters(
					'code_snippets/tag_editor_options',
					[
						'enabled'       => $tags_enabled,
						'allowSpaces'   => true,
						'availableTags' => $tags_enabled ? get_all_snippet_tags() : [],
					]
				),
				'descEditorOptions' => [
					'rows' => $settings['general']['visual_editor_rows'],
				],
			]
		);
	}

	/**
	 * Remove the old CodeMirror version used by the Debug Bar Console plugin that is messing up the snippet editor.
	 */
	public function remove_debug_bar_codemirror() {
		// Try to discern if we are on the single snippet page as good as we can at this early time.
		$is_codemirror_page =
			is_admin() && 'admin.php' === $GLOBALS['pagenow'] && isset( $_GET['page'] ) && (
				code_snippets()->get_menu_slug( 'edit' ) === $_GET['page'] ||
				code_snippets()->get_menu_slug( 'settings' ) === $_GET['page']
			);

		if ( $is_codemirror_page ) {
			remove_action( 'debug_bar_enqueue_scripts', 'debug_bar_console_scripts' );
		}
	}
}
