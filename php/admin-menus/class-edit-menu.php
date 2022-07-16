<?php

namespace Code_Snippets;

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

		/* Only preserve the edit menu if we are currently editing a snippet */
		if ( ! isset( $_REQUEST['page'] ) || $_REQUEST['page'] !== $this->slug ) {
			remove_submenu_page( $this->base_slug, $this->slug );
		}

		/* Add New Snippet menu */
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
		$edit_hook = get_plugin_page_hookname( $this->slug, $this->base_slug );
		if ( $screen->in_admin( 'network' ) ) {
			$edit_hook .= '-network';
		}

		// Disallow visiting the edit snippet page without a valid ID.
		if ( $screen->base === $edit_hook && ( empty( $_REQUEST['id'] ) || 0 === $this->snippet->id || null === $this->snippet->id ) ) {
			wp_safe_redirect( code_snippets()->get_menu_url( 'add' ) );
			exit;
		}

		// Process any submitted actions.
		$this->process_actions();

		// Load the contextual help tabs.
		$contextual_help = new Contextual_Help( 'edit' );
		$contextual_help->load();

		// Register action hooks.
		if ( get_setting( 'general', 'enable_description' ) ) {
			add_action( 'code_snippets_edit_snippet', array( $this, 'render_description_editor' ), 9 );
		}

		if ( get_setting( 'general', 'enable_tags' ) ) {
			add_action( 'code_snippets_edit_snippet', array( $this, 'render_tags_editor' ) );
		}

		add_action( 'code_snippets_below_editor', array( $this, 'render_priority_setting' ), 0 );

		if ( is_network_admin() ) {
			add_action( 'code_snippets_edit_snippet', array( $this, 'render_multisite_sharing_setting' ), 1 );
		}

		if ( apply_filters( 'code_snippets/extra_save_buttons', true ) ) {
			add_action( 'code_snippets/admin/code_editor_toolbar', array( $this, 'render_extra_submit_buttons' ) );
		}

		if ( apply_filters( 'code_snippets/enable_code_direction', is_rtl() ) ) {
			add_action( 'code_snippets/admin/code_editor_toolbar', array( $this, 'render_direction_setting' ), 11, 0 );
		}

		$this->process_actions();
	}

	/**
	 * Load the data for the snippet currently being edited
	 */
	public function load_snippet_data() {
		$edit_id = isset( $_REQUEST['id'] ) && intval( $_REQUEST['id'] ) ? absint( $_REQUEST['id'] ) : 0;

		$this->snippet = get_snippet( $edit_id );
		$snippet = $this->snippet;

		if ( 0 === $edit_id && isset( $_GET['type'] ) && $_GET['type'] !== $snippet->type ) {
			if ( 'php' === $_GET['type'] ) {
				$snippet->scope = 'global';
			} elseif ( 'css' === $_GET['type'] ) {
				$snippet->scope = 'site-css';
			} elseif ( 'html' === $_GET['type'] ) {
				$snippet->scope = 'content';
			} elseif ( 'js' === $_GET['type'] ) {
				$snippet->scope = 'site-head-js';
			}
		}
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
				$export = new Export( $snippet_id );
				$export->export_snippets();
			}

			/* Download the snippet if the button was clicked */
			if ( isset( $_POST['download_snippet'] ) ) {
				$export = new Export( $snippet_id );
				$export->download_snippets();
			}
		}
	}

	/**
	 * Remove the sharing status from a network snippet
	 *
	 * @param int $snippet_id Snippet ID.
	 */
	private function unshare_network_snippet( $snippet_id ) {
		$shared_snippets = get_site_option( 'shared_network_snippets', array() );

		if ( ! in_array( $snippet_id, $shared_snippets, true ) ) {
			return;
		}

		/* Remove the snippet ID from the array */
		$shared_snippets = array_diff( $shared_snippets, array( $snippet_id ) );
		update_site_option( 'shared_network_snippets', array_values( $shared_snippets ) );

		/* Deactivate on all sites */
		$sites = get_sites( [ 'fields' => 'ids' ] );

		foreach ( $sites as $site ) {
			switch_to_blog( $site );
			$active_shared_snippets = get_option( 'active_shared_network_snippets' );

			if ( is_array( $active_shared_snippets ) ) {
				$active_shared_snippets = array_diff( $active_shared_snippets, array( $snippet_id ) );
				update_option( 'active_shared_network_snippets', $active_shared_snippets );
			}

			clean_active_snippets_cache( code_snippets()->db->ms_table );
		}

		restore_current_blog();
	}

	/**
	 * Display a custom error message when a code error is encountered
	 *
	 * @param string $out Error message content.
	 *
	 * @return string New error message.
	 */
	private function code_error_callback( $out ) {
		$error = error_get_last();

		if ( is_null( $error ) ) {
			return $out;
		}

		$m = '<h3>' . esc_html__( "Don't Panic", 'code-snippets' ) . '</h3>';
		/* translators: %d: line where error was produced */
		$m .= '<p>' . sprintf( esc_html__( 'The code snippet you are trying to save produced a fatal error on line %d:', 'code-snippets' ), intval( $error['line'] ) ) . '</p>';
		$m .= '<strong>' . esc_html( $error['message'] ) . '</strong>';
		$m .= '<p>' . esc_html__( 'The previous version of the snippet is unchanged, and the rest of this site should be functioning normally as before.', 'code-snippets' ) . '</p>';
		$m .= '<p>' . esc_html__( 'Please use the back button in your browser to return to the previous page and try to fix the code error.', 'code-snippets' );
		$m .= ' ' . esc_html__( 'If you prefer, you can close this page and discard the changes you just made. No changes will be made to this site.', 'code-snippets' ) . '</p>';

		return $m;
	}

	/**
	 * Validate the snippet code before saving to database
	 *
	 * @param Snippet $snippet Snippet object.
	 *
	 * @return bool Whether the code produces errors.
	 */
	private function test_code( Snippet $snippet ) {

		if ( empty( $snippet->code ) || 'php' !== $snippet->type ) {
			return false;
		}

		ob_start( array( $this, 'code_error_callback' ) );

		$result = eval( $snippet->code );

		ob_end_clean();

		do_action( 'code_snippets/after_execute_snippet', $snippet->id, $snippet->code, $result );

		return false === $result;
	}

	/**
	 * Save the posted snippet data to the database and redirect
	 */
	private function save_posted_snippet() {

		/* Build snippet object from fields with 'snippet_' prefix */
		$snippet = new Snippet();

		foreach ( $_POST as $field => $value ) {
			if ( 'snippet_' === substr( $field, 0, 8 ) ) {

				/* Remove the 'snippet_' prefix from field name and set it on the object */
				$snippet->set_field( substr( $field, 8 ), stripslashes( $value ) );
			}
		}

		if ( isset( $_POST['save_snippet_execute'] ) && 'single-use' !== $snippet->scope ) {
			unset( $_POST['save_snippet_execute'] );
			$_POST['save_snippet'] = 'yes';
		}

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

		if ( 'php' === $snippet->type ) {

			/* Remove <?php and <? from beginning of snippet */
			$snippet->code = preg_replace( '|^\s*<\?(php)?|', '', $snippet->code );
			/* Remove ?> from end of snippet */
			$snippet->code = preg_replace( '|\?>\s*$|', '', $snippet->code );

			/* Deactivate snippet if code contains errors */
			if ( $snippet->active && 'single-use' !== $snippet->scope ) {
				$validator = new Validator( $snippet->code );
				$code_error = $validator->validate();

				if ( ! $code_error ) {
					$code_error = $this->test_code( $snippet );
				}

				if ( $code_error ) {
					$snippet->active = 0;
				}
			}
		}

		/* Save the snippet to the database */
		$snippet_id = save_snippet( $snippet );

		/* Update the shared network snippets if necessary */
		if ( $snippet_id && is_network_admin() ) {

			if ( isset( $_POST['snippet_sharing'] ) && 'on' === $_POST['snippet_sharing'] ) {
				$shared_snippets = get_site_option( 'shared_network_snippets', array() );

				/* Add the snippet ID to the array if it isn't already */
				if ( ! in_array( $snippet_id, $shared_snippets, true ) ) {
					$shared_snippets[] = $snippet_id;
					update_site_option( 'shared_network_snippets', array_values( $shared_snippets ) );
				}
			} else {
				$this->unshare_network_snippet( $snippet_id );
			}
		}

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
	 * Add a description editor to the single snippet page
	 *
	 * @param Snippet $snippet The snippet being used for this page.
	 */
	public function render_description_editor( Snippet $snippet ) {
		$settings = Settings\get_settings_values();
		$settings = $settings['description_editor'];

		echo '<h2><label for="snippet_description">', esc_html__( 'Description', 'code-snippets' ), '</label></h2>';

		remove_editor_styles(); // Stop custom theme styling interfering with the editor.

		wp_editor(
			$snippet->desc,
			'description',
			apply_filters(
				'code_snippets/admin/description_editor_settings',
				array(
					'textarea_name' => 'snippet_description',
					'textarea_rows' => $settings['rows'],
					'teeny'         => ! $settings['use_full_mce'],
					'media_buttons' => $settings['media_buttons'],
				)
			)
		);
	}

	/**
	 * Render the interface for editing snippet tags
	 *
	 * @param Snippet $snippet The snippet currently being edited.
	 */
	public function render_tags_editor( Snippet $snippet ) {

		?>
		<h2 style="margin: 25px 0 10px;">
			<label for="snippet_tags" style="cursor: auto;">
				<?php esc_html_e( 'Tags', 'code-snippets' ); ?>
			</label>
		</h2>

		<input type="text" id="snippet_tags" name="snippet_tags" style="width: 100%;"
		       placeholder="<?php esc_html_e( 'Enter a list of tags; separated by commas', 'code-snippets' ); ?>"
		       value="<?php echo esc_attr( $snippet->tags_list ); ?>"/>
		<?php
	}

	/**
	 * Render the snippet priority setting
	 *
	 * @param Snippet $snippet The snippet currently being edited.
	 */
	public function render_priority_setting( Snippet $snippet ) {
		if ( 'html' === $snippet->type ) {
			return;
		}

		?>
		<p class="snippet-priority"
		   title="<?php esc_attr_e( 'Snippets with a lower priority number will run before those with a higher number.', 'code-snippets' ); ?>">
			<label for="snippet_priority"><?php esc_html_e( 'Priority', 'code-snippets' ); ?></label>

			<input name="snippet_priority" type="number" id="snippet_priority"
			       value="<?php echo esc_attr( $snippet->priority ); ?>">
		</p>
		<?php
	}

	/**
	 * Render the setting for shared network snippets
	 *
	 * @param Snippet $snippet The snippet currently being edited.
	 */
	public function render_multisite_sharing_setting( $snippet ) {
		$shared_snippets = get_site_option( 'shared_network_snippets', array() );
		?>

		<div class="snippet-sharing-setting">
			<h2 class="screen-reader-text"><?php esc_html_e( 'Sharing Settings', 'code-snippets' ); ?></h2>
			<label for="snippet_sharing">
				<input type="checkbox" name="snippet_sharing"
					<?php checked( in_array( $snippet->id, $shared_snippets, true ) ); ?>>
				<?php esc_html_e( 'Allow this snippet to be activated on individual sites on the network', 'code-snippets' ); ?>
			</label>
		</div>

		<?php
	}

	/**
	 * Render additional save buttons above the snippet editor.
	 *
	 * @param Snippet $snippet Snippet currently being edited.
	 */
	public function render_extra_submit_buttons( Snippet $snippet ) {

		$actions['save_snippet'] = array(
			__( 'Save Changes', 'code-snippets' ),
			__( 'Save Snippet', 'code-snippets' ),
		);

		if ( 'html' !== $snippet->type ) {

			if ( 'single-use' === $snippet->scope ) {
				$actions['save_snippet_execute'] = array(
					__( 'Execute Once', 'code-snippets' ),
					__( 'Save Snippet and Execute Once', 'code-snippets' ),
				);

			} elseif ( ! $snippet->shared_network || ! is_network_admin() ) {

				if ( $snippet->active ) {
					$actions['save_snippet_deactivate'] = array(
						__( 'Deactivate', 'code-snippets' ),
						__( 'Save Snippet and Deactivate', 'code-snippets' ),
					);

				} else {
					$actions['save_snippet_activate'] = array(
						__( 'Activate', 'code-snippets' ),
						__( 'Save Snippet and Activate', 'code-snippets' ),
					);
				}
			}
		}

		foreach ( $actions as $action => $labels ) {
			$other_attributes = array(
				'title' => $labels[1],
				'id'    => $action . '_extra',
			);
			submit_button( $labels[0], 'secondary small', $action, false, $other_attributes );
		}
	}

	/**
	 * Render a control for changing the code editor text direction
	 */
	public function render_direction_setting() {
		?>
		<label class="screen-reader-text" for="snippet-code-direction">
			<?php esc_html_e( 'Code Direction', 'code-snippets' ); ?>
		</label>
		<select id="snippet-code-direction">
			<option value="ltr"><?php esc_html_e( 'LTR', 'code-snippets' ); ?></option>
			<option value="rtl"><?php esc_html_e( 'RTL', 'code-snippets' ); ?></option>
		</select>
		<?php
	}

	/**
	 * Retrieve the first error in a snippet's code
	 *
	 * @param int $snippet_id Snippet ID.
	 *
	 * @return array|bool Error if execution failed, otherwise false.
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
			return;
		}

		$messages = array(
			'added'                   => __( 'Snippet <strong>added</strong>.', 'code-snippets' ),
			'updated'                 => __( 'Snippet <strong>updated</strong>.', 'code-snippets' ),
			'added-and-activated'     => __( 'Snippet <strong>added</strong> and <strong>activated</strong>.', 'code-snippets' ),
			'updated-and-executed'    => __( 'Snippet <strong>added</strong> and <strong>executed</strong>.', 'code-snippets' ),
			'updated-and-activated'   => __( 'Snippet <strong>updated</strong> and <strong>activated</strong>.', 'code-snippets' ),
			'updated-and-deactivated' => __( 'Snippet <strong>updated</strong> and <strong>deactivated</strong>.', 'code-snippets' ),
		);

		if ( isset( $messages[ $result ] ) ) {
			echo '<div id="message" class="updated fade"><p>', wp_kses( $messages[ $result ], [ 'strong' => [] ] ), '</p></div>';

		}
	}

	/**
	 * Enqueue assets for the edit menu
	 */
	public function enqueue_assets() {
		$plugin = code_snippets();
		$rtl = is_rtl() ? '-rtl' : '';

		enqueue_code_editor( $this->snippet->type );

		wp_enqueue_style(
			'code-snippets-edit',
			plugins_url( "dist/edit$rtl.css", $plugin->file ),
			[ 'code-editor' ],
			$plugin->version
		);

		wp_enqueue_script(
			'code-snippets-edit-menu',
			plugins_url( 'dist/edit.js', $plugin->file ),
			[ 'code-snippets-code-editor' ],
			$plugin->version,
			true
		);

		wp_localize_script(
			'code-snippets-edit-menu',
			'code_snippets_edit_i18n',
			[
				'missing_title_code' => esc_attr__( 'This snippet has no code or title. Continue?', 'code-snippets' ),
				'missing_title'      => esc_attr__( 'This snippet has no title. Continue?', 'code-snippets' ),
				'missing_code'       => esc_attr__( 'This snippet has no snippet code. Continue?', 'code-snippets' ),
			]
		);

		$this->enqueue_tag_assets();
	}

	/**
	 * Enqueue the necessary assets for the tag editor
	 */
	protected function enqueue_tag_assets() {

		if ( ! get_setting( 'general', 'enable_tags' ) ) {
			return;
		}

		wp_enqueue_script(
			'code-snippets-edit-menu-tags',
			plugins_url( 'dist/tags.js', code_snippets()->file ),
			[],
			code_snippets()->version,
			true
		);

		$options = apply_filters(
			'code_snippets/tag_editor_options',
			array(
				'allow_spaces'   => true,
				'available_tags' => get_all_snippet_tags(),
			)
		);

		$inline_script = 'var code_snippets_tags = ' . wp_json_encode( $options ) . ';';

		wp_add_inline_script( 'code-snippets-edit-menu-tags', $inline_script, 'before' );
	}

	/**
	 * Remove the old CodeMirror version used by the Debug Bar Console plugin
	 * that is messing up the snippet editor
	 */
	public function remove_debug_bar_codemirror() {

		/* Try to discern if we are on the single snippet page as good as we can at this early time */
		if ( ! is_admin() || 'admin.php' !== $GLOBALS['pagenow'] ) {
			return;
		}

		if ( ! isset( $_GET['page'] ) || code_snippets()->get_menu_slug( 'edit' ) !== $_GET['page'] && code_snippets()->get_menu_slug( 'settings' ) !== $_GET['page'] ) {
			return;
		}

		remove_action( 'debug_bar_enqueue_scripts', 'debug_bar_console_scripts' );
	}

	/**
	 * Retrieve a list of submit actions for a given snippet
	 *
	 * @param Snippet $snippet       The snippet currently being edited.
	 * @param bool    $extra_actions Whether to include additional actions alongside save actions.
	 *
	 * @return array Two-dimensional array with action name keyed to description.
	 */
	public function get_actions_list( $snippet, $extra_actions = true ) {
		$actions = [ 'save_snippet' => __( 'Save Changes', 'code-snippets' ) ];

		if ( 'single-use' === $snippet->scope ) {
			$actions['save_snippet_execute'] = __( 'Save Changes and Execute Once', 'code-snippets' );

		} elseif ( ! $snippet->shared_network || ! is_network_admin() ) {

			if ( $snippet->active ) {
				$actions['save_snippet_deactivate'] = __( 'Save Changes and Deactivate', 'code-snippets' );
			} else {
				$actions['save_snippet_activate'] = __( 'Save Changes and Activate', 'code-snippets' );
			}
		}

		// Make the 'Save and Activate' button the default if the setting is enabled.
		if ( ! $snippet->active && 'single-use' !== $snippet->scope &&
		     get_setting( 'general', 'activate_by_default' ) ) {
			$actions = array_reverse( $actions );
		}

		if ( $extra_actions && 0 !== $snippet->id ) {

			if ( apply_filters( 'code_snippets/enable_downloads', true ) ) {
				$actions['download_snippet'] = __( 'Download', 'code-snippets' );
			}

			$actions['export_snippet'] = __( 'Export', 'code-snippets' );
			$actions['delete_snippet'] = __( 'Delete', 'code-snippets' );
		}

		return apply_filters( 'code_snippets/admin/submit_actions', $actions, $snippet, $extra_actions );
	}

	/**
	 * Render the submit buttons for a code snippet
	 *
	 * @param Snippet $snippet       The snippet currently being edited.
	 * @param string  $size          Additional size classes to pass to button.
	 * @param bool    $extra_actions Whether to include additional buttons alongside save buttons.
	 */
	public function render_submit_buttons( $snippet, $size = '', $extra_actions = true ) {

		$actions = $this->get_actions_list( $snippet, $extra_actions );
		$type = 'primary';
		$size = $size ? ' ' . $size : '';

		foreach ( $actions as $action => $label ) {
			$other = null;

			if ( 'delete_snippet' === $action ) {
				$other = sprintf(
					'onclick="%s"',
					esc_js(
						sprintf(
							'return confirm("%s");',
							esc_html__( 'You are about to permanently delete this snippet.', 'code-snippets' ) . "\n" .
							esc_html__( "'Cancel' to stop, 'OK' to delete.", 'code-snippets' )
						)
					)
				);
			}

			submit_button( $label, $type . $size, $action, false, $other );

			if ( 'primary' === $type ) {
				$type = 'secondary';
			}
		}
	}

	/**
	 * Render a list of scopes as ratio controls
	 *
	 * @param array $scopes List of scopes to render, with scope name keyed to label.
	 */
	public function print_scopes_list( $scopes ) {
		$scope_icons = Snippet::get_scope_icons();

		foreach ( $scopes as $scope => $label ) {
			printf( '<label><input type="radio" name="snippet_scope" value="%s"', esc_attr( $scope ) );
			checked( $scope, $this->snippet->scope );
			printf( '> <span class="dashicons dashicons-%s"></span> %s</label>', esc_attr( $scope_icons[ $scope ] ), esc_html( $label ) );
		}
	}

	/**
	 * Render a keyboard shortcut as HTML.
	 *
	 * @param array|string $modifiers Modifier keys. Can be 'Cmd', 'Ctrl', 'Shift', 'Option', 'Alt'.
	 * @param string       $key       Keyboard key.
	 *
	 * @return void
	 */
	protected function render_keyboard_shortcut( $modifiers, $key ) {
		static $keys = null;

		if ( is_null( $keys ) ) {
			$keys = array(
				'Cmd'    => _x( 'Cmd', 'keyboard key', 'code-snippets' ),
				'Ctrl'   => _x( 'Ctrl', 'keyboard key', 'code-snippets' ),
				'Shift'  => _x( 'Shift', 'keyboard key', 'code-snippets' ),
				'Option' => _x( 'Option', 'keyboard key', 'code-snippets' ),
				'Alt'    => _x( 'Alt', 'keyboard key', 'code-snippets' ),
				'F'      => _x( 'F', 'keyboard key', 'code-snippets' ),
				'G'      => _x( 'G', 'keyboard key', 'code-snippets' ),
				'R'      => _x( 'R', 'keyboard key', 'code-snippets' ),
				'S'      => _x( 'S', 'keyboard key', 'code-snippets' ),
			);
		}

		if ( ! is_array( $modifiers ) ) {
			$modifiers = array( $modifiers );
		}

		foreach ( $modifiers as $modifier ) {
			if ( 'Ctrl' === $modifier || 'Cmd' === $modifier ) {
				echo '<kbd class="pc-key">', esc_html( $keys['Ctrl'] ), '</kbd>';
				echo '<kbd class="mac-key">', esc_html( $keys['Cmd'] ), '</kbd>&hyphen;';
			} elseif ( 'Option' === $modifier ) {
				echo '<span class="mac-key"><kbd class="mac-key">', esc_html( $keys['Option'] ), '</kbd>&hyphen;</span>';
			} else {
				echo '<kbd>', esc_html( $keys[ $modifier ] ), '</kbd>&hyphen;';
			}
		}

		echo '<kbd>', esc_html( $keys[ $key ] ), '</kbd>';
	}
}
