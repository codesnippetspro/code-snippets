<?php

/**
 * This class handles the add/edit menu
 */
class Code_Snippets_Edit_Menu extends Code_Snippets_Admin_Menu {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'edit',
			__( 'Edit Snippet', 'code-snippets' ),
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

		/* Add edit menu if we are currently editing a snippet */
		if ( isset( $_REQUEST['page'] ) && code_snippets()->get_menu_slug( 'edit' ) === $_REQUEST['page'] ) {
			parent::register();
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

		/* Don't allow visiting the edit snippet page without a valid ID */
		if ( code_snippets()->get_menu_slug( 'edit' ) === $_REQUEST['page'] ) {
			if ( ! isset( $_REQUEST['id'] ) || 0 == $_REQUEST['id'] ) {
				wp_redirect( code_snippets()->get_menu_url( 'add' ) );
				exit;
			}
		}

		/* Load the contextual help tabs */
		$contextual_help = new Code_Snippets_Contextual_Help( 'edit' );
		$contextual_help->load();

		/* Enqueue the code editor and other scripts and styles */
		add_action( 'admin_enqueue_scripts', 'code_snippets_enqueue_codemirror' );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_tagit' ), 9 );

		/* Register action hooks */
		if ( code_snippets_get_setting( 'general', 'enable_description' ) ) {
			add_action( 'code_snippets/admin/single', array( $this, 'render_description_editor' ), 9 );
		}

		if ( code_snippets_get_setting( 'general', 'enable_tags' ) ) {
			add_action( 'code_snippets/admin/single', array( $this, 'render_tags_editor' ) );
		}

		if ( code_snippets_get_setting( 'general', 'snippet_scope_enabled' ) ) {
			add_action( 'code_snippets/admin/single/settings', array( $this, 'render_scope_setting' ) );
		}

		if ( get_current_screen()->in_admin( 'network' ) ) {
			add_action( 'code_snippets/admin/single/settings', array( $this, 'render_multisite_sharing_setting' ) );
		}

		$this->process_actions();
	}

	/**
	 * Process data sent from the edit page
	 */
	private function process_actions() {

		/* Check for a valid nonce */
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'save_snippet' ) ) {
			return;
		}

		if ( isset( $_POST['save_snippet'] ) || isset( $_POST['save_snippet_activate'] ) || isset( $_POST['save_snippet_deactivate'] ) ) {
			$this->save_posted_snippet();
		}

		if ( isset( $_POST['snippet_id'] ) ) {

			/* Delete the snippet if the button was clicked */
			if ( isset( $_POST['delete_snippet'] ) ) {
				delete_snippet( $_POST['snippet_id'] );
				wp_redirect( add_query_arg( 'result', 'delete', code_snippets()->get_menu_url( 'manage' ) ) );
				exit;
			}

			/* Export the snippet if the button was clicked */
			if ( isset( $_POST['export_snippet'] ) ) {
				export_snippets( $_POST['snippet_id'] );
			}
		}
	}

	/**
	 * Remove the sharing status from a network snippet
	 * @param int $snippet_id
	 */
	private function unshare_network_snippet( $snippet_id ) {
		$shared_snippets = get_site_option( 'shared_network_snippets', array() );

		if ( ! in_array( $snippet_id, $shared_snippets ) ) {
			return;
		}

		/* Remove the snippet ID from the array */
		$shared_snippets = array_diff( $shared_snippets, array( $snippet_id ) );
		update_site_option( 'shared_network_snippets', array_values( $shared_snippets ) );

		/* Deactivate on all sites */
		global $wpdb;
		if ( $sites = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" ) ) {

			foreach ( $sites as $site ) {
				switch_to_blog( $site );
				$active_shared_snippets = get_option( 'active_shared_network_snippets' );

				if ( is_array( $active_shared_snippets ) ) {
					$active_shared_snippets = array_diff( $active_shared_snippets, array( $snippet_id ) );
					update_option( 'active_shared_network_snippets', $active_shared_snippets );
				}
			}

			restore_current_blog();
		}
	}

	private function code_error_callback( $out ) {
		$error = error_get_last();

		if ( is_null( $error ) ) {
			return $out;
		}

		$m = '<h2>' . __( "Don't Panic", 'code-snippets' ) . '</h2>';
		$m .= '<p>' . sprintf( __( 'The code snippet you are trying to save produced a fatal error on line %d:', 'code_snippets' ), $error['line'] ) . '</p>';
		$m .= '<strong>' . $error['message'] . '</strong>';
		$m .= '<p>' . __( 'The previous version of the snippet is unchanged, and the rest of this site should be functioning normally as before.', 'code-snippets' ) . '</p>';
		$m .= '<p>' . __( 'Please use the back button in your browser to return to the previous page and try to fix the code error.', 'code-snippets' );
		$m .= ' ' . __( 'If you prefer, you can close this page and discard the changes you just made. No changes will be made to this site.', 'code-snippets' ) . '</p>';

		return $m;
	}

	/**
	 * Validate the snippet code before saving to database
	 *
	 * @param Snippet $snippet
	 *
	 * @return bool true if code produces errors
	 */
	private function validate_code( Snippet $snippet ) {

		if ( empty( $snippet->code ) ) {
			return false;
		}

		ob_start( array( $this, 'code_error_callback' ) );
		$result = eval( $snippet->code );
		ob_end_clean();

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

				/* Remove 'snippet_' prefix from field name */
				$field = substr( $field, 8 );
				$snippet->$field = stripslashes( $value );
			}
		}

		/* Activate or deactivate the snippet before saving if we clicked the button */

		// Shared network snippets cannot be network activated
		if ( isset( $_POST['snippet_sharing'] ) && 'on' === $_POST['snippet_sharing'] ) {
			$snippet->active = 0;
			unset( $_POST['save_snippet_activate'], $_POST['save_snippet_deactivate'] );
		} elseif ( isset( $_POST['save_snippet_activate'] ) ) {
			$snippet->active = 1;
		} elseif ( isset( $_POST['save_snippet_deactivate'] ) ) {
			$snippet->active = 0;
		}

		/* Deactivate snippet if code contains errors */
		if ( $snippet->active ) {
			if ( $code_error = $this->validate_code( $snippet ) ) {
				$snippet->active = 0;
			}
		}

		/* Save the snippet to the database */
		$snippet_id = save_snippet( $snippet );

		/* Update the shared network snippets if necessary */
		if ( $snippet_id && get_current_screen()->in_admin( 'network' ) ) {

			if ( isset( $_POST['snippet_sharing'] ) && 'on' === $_POST['snippet_sharing'] ) {
				$shared_snippets = get_site_option( 'shared_network_snippets', array() );

				/* Add the snippet ID to the array if it isn't already */
				if ( ! in_array( $snippet_id, $shared_snippets ) ) {
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
			wp_redirect( add_query_arg( 'result', 'save-error', code_snippets()->get_menu_url( 'add' ) ) );
			exit;
		}

		/* Display message if a parse error occurred */
		if ( isset( $code_error ) && $code_error ) {
			wp_redirect( add_query_arg(
				array( 'id' => $snippet_id, 'result' => 'code-error' ),
				code_snippets()->get_menu_url( 'edit' )
			) );
			exit;
		}

		/* Set the result depending on if the snippet was just added */
		$result = isset( $_POST['snippet_id'] ) ? 'updated' : 'added';

		/* Append a suffix if the snippet was activated or deactivated */
		if ( isset( $_POST['save_snippet_activate'] ) ) {
			$result .= '-and-activated';
		} elseif ( isset( $_POST['save_snippet_deactivate'] ) ) {
			$result .= '-and-deactivated';
		}

		/* Redirect to edit snippet page */
		wp_redirect( add_query_arg(
			array( 'id' => $snippet_id, 'result' => $result ),
			code_snippets()->get_menu_url( 'edit' )
		) );
		exit;
	}

	/**
	 * Add a description editor to the single snippet page
	 * @param Snippet $snippet The snippet being used for this page
	 */
	function render_description_editor( Snippet $snippet ) {
		$settings = code_snippets_get_settings();
		$settings = $settings['description_editor'];
		$heading = __( 'Description', 'code-snippets' );

		/* Hack to remove space between heading and editor tabs */
		if ( ! $settings['media_buttons'] && 'false' !== get_user_option( 'rich_editing' ) ) {
			$heading = "<div>$heading</div>";
		}

		echo '<label for="snippet_description"><h3>', $heading, '</h3></label>';

		remove_editor_styles(); // stop custom theme styling interfering with the editor

		wp_editor(
			$snippet->desc,
			'description',
			apply_filters( 'code_snippets/admin/description_editor_settings', array(
				'textarea_name' => 'snippet_description',
				'textarea_rows' => $settings['rows'],
				'teeny' => ! $settings['use_full_mce'],
				'media_buttons' => $settings['media_buttons'],
			) )
		);
	}

	/**
	* Render the interface for editing snippet tags
	* @param Snippet $snippet the snippet currently being edited
	*/
	function render_tags_editor( Snippet $snippet ) {

		?>
		<label for="snippet_tags" style="cursor: auto;">
			<h3><?php esc_html_e( 'Tags', 'code-snippets' ); ?></h3>
		</label>

		<input type="text" id="snippet_tags" name="snippet_tags" style="width: 100%;"
			placeholder="<?php esc_html_e( 'Enter a list of tags; separated by commas', 'code-snippets' ); ?>"
			   value="<?php echo esc_attr( $snippet->tags_list ); ?>" />

		<script type="text/javascript">
		jQuery('#snippet_tags').tagit({
			availableTags: <?php echo json_encode( get_all_snippet_tags() ); ?>,
			allowSpaces: true,
			removeConfirmation: true
		});
		</script>
		<?php
	}

	/**
	 * Render the snippet scope setting
	 * @param Snippet $snippet the snippet currently being edited
	 */
	function render_scope_setting( Snippet $snippet ) {

		$scopes = array(
			__( 'Run snippet everywhere', 'code-snippets' ),
			__( 'Only run in administration area', 'code-snippets' ),
			__( 'Only run on site front-end', 'code-snippets' ),
		);

		echo '<tr class="snippet-scope">';
		echo '<th scope="row">' . __( 'Scope', 'code-snippets' ) . '</th><td>';

		foreach ( $scopes as $scope => $label ) {
			printf( '<div><input type="radio" name="snippet_scope" value="%d"', $scope );
			checked( $scope, $snippet->scope );
			echo "> $label</div>";
		}

		echo '</td></tr>';
	}

	/**
	 * Render the setting for shared network snippets
	 * @param object $snippet The snippet currently being edited
	 */
	function render_multisite_sharing_setting( $snippet ) {
		$shared_snippets = get_site_option( 'shared_network_snippets', array() );
		?>

		<tr class="snippet-sharing-setting">
			<th scope="row"><?php _e( 'Sharing', 'code-snippets' ) ?></th>
			<td><label for="snippet_sharing">
				<input type="checkbox" name="snippet_sharing"
				<?php checked( in_array( $snippet->id, $shared_snippets ) ); ?>>
				<?php _e( 'Allow this snippet to be activated on individual sites on the network', 'code-snippets' ); ?>
			</label></td>
		</tr>

		<?php
	}

	/**
	 * Retrieve the first error in a snippet's code
	 *
	 * @param $snippet_id
	 *
	 * @return array|bool
	 */
	private function get_snippet_error( $snippet_id ) {

		if ( ! intval( $snippet_id ) ) {
			return false;
		}

		$snippet = get_snippet( intval( $snippet_id ) );

		if ( '' === $snippet->code ) {
			return false;
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

		$result = $_REQUEST['result'];

		if ( 'code-error' === $result ) {

			if ( isset( $_REQUEST['id'] ) && $error = $this->get_snippet_error( $_REQUEST['id'] ) ) {

				printf(
					'<div id="message" class="error fade"><p>%s</p><p><strong>%s</strong></p></div>',
					sprintf( __( 'The snippet has been deactivated due to an error on line %d:', 'code-snippets' ), $error['line'] ),
					$error['message']
				);

			} else {
				echo '<div id="message" class="error fade"><p>', __( 'The snippet has been deactivated due to an error in the code.', 'code-snippets' ), '</p></div>';
			}

			return;
		}

		if ( 'save-error' === $result ) {
			echo '<div id="message" class="error fade"><p>', __( 'An error occurred when saving the snippet.', 'code-snippets' ), '</p></div>';
			return;
		}

		$messages = array(
			'added' => __( 'Snippet <strong>added</strong>.', 'code-snippets' ),
			'updated' => __( 'Snippet <strong>updated</strong>.', 'code-snippets' ),
			'added-and-activated' => __( 'Snippet <strong>added</strong> and <strong>activated</strong>.', 'code-snippets' ),
			'updated-and-activated' => __( 'Snippet <strong>updated</strong> and <strong>activated</strong>.', 'code-snippets' ),
			'updated-and-deactivated' => __( 'Snippet <strong>updated</strong> and <strong>deactivated</strong>.', 'code-snippets' ),
		);

		if ( isset( $messages[ $result ] ) ) {
			echo '<div id="message" class="updated fade"><p>', $messages[ $result ], '</p></div>';
		}
	}

	/**
	 * Enqueue the Tag It library
	 */
	function enqueue_tagit() {
		$tagit_version = '2.0';
		$url = plugin_dir_url( CODE_SNIPPETS_FILE );

		/* Tag It UI */
		wp_enqueue_script(
			'code-snippets-tag-it',
			$url . 'js/min/tag-it.js',
			array(
				'jquery-ui-core',
				'jquery-ui-widget',
				'jquery-ui-position',
				'jquery-ui-autocomplete',
				'jquery-effects-blind',
				'jquery-effects-highlight',
			),
			$tagit_version
		);

		wp_enqueue_style(
			'code-snippets-tag-it',
			$url . 'css/min/tagit.css',
			false, $tagit_version
		);
	}

	/**
	 * Remove the old CodeMirror version used by the Debug Bar Console plugin
	 * that is messing up the snippet editor
	 */
	function remove_debug_bar_codemirror() {

		/* Try to discern if we are on the single snippet page as best as we can at this early time */
		if ( ! is_admin() || 'admin.php' !== $GLOBALS['pagenow'] ) {
			return;
		}

		if ( ! isset( $_GET['page'] ) || code_snippets()->get_menu_slug( 'edit' ) !== $_GET['page'] && code_snippets()->get_menu_slug( 'settings' ) ) {
			return;
		}

		remove_action( 'debug_bar_enqueue_scripts', 'debug_bar_console_scripts' );
	}
}
