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

		add_action( 'admin_init', array( $this, 'remove_incompatible_codemirror' ) );
	}

	/**
	 * Register the admin menu
	 */
	public function register() {

		/* Add New Snippet menu */
		$this->add_menu(
			code_snippets_get_menu_slug( 'add' ),
			__( 'Add New', 'code-snippets' ),
			__( 'Add New Snippet', 'code-snippets' )
		);

		/* Add edit menu if we are currently editing a snippet */
		if ( isset( $_REQUEST['page'] ) && code_snippets_get_menu_slug( 'edit' ) === $_REQUEST['page'] ) {
			parent::register();
		}
	}

	/**
	 * Executed when the menu is loaded
	 */
	public function load() {
		parent::load();

		/* Don't allow visiting the edit snippet page without a valid ID */
		if ( code_snippets_get_menu_slug( 'edit' ) === $_REQUEST['page'] ) {
			if ( ! isset( $_REQUEST['id'] ) || 0 == $_REQUEST['id'] ) {
				wp_redirect( code_snippets_get_menu_url( 'add' ) );
				exit;
			}
		}

		/* Load the contextual help tabs */
		code_snippets_load_edit_help();

		/* Enqueue the code editor and other scripts and styles */
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

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

		$this->save_posted_snippet();
	}

	/**
	 * Save the posted snippet to the database
	 * @uses wp_redirect() to pass the results to the page
	 * @uses save_snippet() to save the snippet to the database
	 */
	private function save_posted_snippet() {

		/* Make sure the nonce validates before we do any snippet ops */
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'save_snippet' ) ) {
			return;
		}

		/* Save the snippet if one has been submitted */
		if ( isset( $_POST['save_snippet'] ) || isset( $_POST['save_snippet_activate'] ) || isset( $_POST['save_snippet_deactivate'] ) ) {

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

			/* Save the snippet to the database */
			$snippet_id = save_snippet( $snippet );

			/* Update the shared network snippets if necessary */
			if ( $snippet_id && get_current_screen()->in_admin( 'network' ) ) {
				$shared_snippets = get_site_option( 'shared_network_snippets', array() );

				if ( isset( $_POST['snippet_sharing'] ) && 'on' === $_POST['snippet_sharing'] ) {

					/* Add the snippet ID to the array if it isn't already */
					if ( ! in_array( $snippet_id, $shared_snippets ) ) {
						$shared_snippets[] = $snippet_id;
						update_site_option( 'shared_network_snippets', array_values( $shared_snippets ) );
					}
				} elseif ( in_array( $snippet_id, $shared_snippets ) ) {
					/* Remove the snippet ID from the array */
					$shared_snippets = array_diff( $shared_snippets, array( $snippet_id ) );
					update_site_option( 'shared_network_snippets', array_values( $shared_snippets ) );
				}
			}

			/* If the saved snippet ID is invalid, display an error message */
			if ( ! $snippet_id || $snippet_id < 1 ) {
				/* An error occurred */
				wp_redirect( add_query_arg( 'result', 'error', code_snippets_get_menu_url( 'add' ) ) );
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
				code_snippets_get_menu_url( 'edit' )
			) );
			exit;
		}

		/* Delete the snippet if the button was clicked */
		elseif ( isset( $_POST['snippet_id'], $_POST['delete_snippet'] ) ) {
			delete_snippet( $_POST['snippet_id'] );
			wp_redirect( add_query_arg( 'result', 'delete', code_snippets_get_menu_url( 'manage' ) ) );
			exit;
		}

		/* Export the snippet if the button was clicked */
		elseif ( isset( $_POST['snippet_id'], $_POST['export_snippet'] ) ) {
			export_snippets( $_POST['snippet_id'] );
		}
	}

	/**
	 * Add a description editor to the single snippet page
	 * @param Snippet $snippet The snippet being used for this page
	 */
	function render_description_editor( Snippet $snippet ) {
		$settings = code_snippets_get_settings();
		$settings = $settings['description_editor'];
		$media_buttons = $settings['media_buttons'];

		echo '<label for="snippet_description"><h3>';
		$heading = __( 'Description', 'code-snippets' );
		echo $media_buttons ? $heading : "<div>$heading</div>";
		echo '</h3></label>';

		remove_editor_styles(); // stop custom theme styling interfering with the editor

		wp_editor(
			$snippet->description,
			'description',
			apply_filters( 'code_snippets/admin/description_editor_settings', array(
				'textarea_name' => 'snippet_description',
				'textarea_rows' => $settings['rows'],
				'teeny' => ! $settings['use_full_mce'],
				'media_buttons' => $media_buttons,
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
			placeholder="<?php esc_html_e( 'Enter a list of tags; separated by commas', 'code-snippets' ); ?>" value="<?php echo $snippet->tags_list; ?>" />

		<script type="text/javascript">
		jQuery('#snippet_tags').tagit({
			availableTags: ['<?php echo implode( "','", get_all_snippet_tags() ); ?>'],
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
			__( 'Only run in adminstration area', 'code-snippets' ),
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

	/*
	 * Print the status and error messages
	 */
	protected function print_messages() {

		/* Check if an error exists, and if so, build the message */
		$error = $this->get_result_message(
			array( 'error' => __( 'An error occurred when saving the snippet.', 'code-snippets' ) ),
			'result', 'error'
		);

		/* Output the error message if it exists, otherwise try to output a result message */
		if ( $error ) {
			echo $error;
		} else {
			echo $this->get_result_message(
				array(
					'added' => __( 'Snippet <strong>added</strong>.', 'code-snippets' ),
					'updated' => __( 'Snippet <strong>updated</strong>.', 'code-snippets' ),
					'added-and-activated' => __( 'Snippet <strong>added</strong> and <strong>activated</strong>.', 'code-snippets' ),
					'updated-and-activated' => __( 'Snippet <strong>updated</strong> and <strong>activated</strong>.', 'code-snippets' ),
					'updated-and-deactivated' => __( 'Snippet <strong>updated</strong> and <strong>deactivated</strong>.', 'code-snippets' ),
				)
			);
		}
	}

	/**
	 * Registers and loads the code editor's assets
	 *
	 * @uses wp_enqueue_style() to add the stylesheets to the queue
	 * @uses wp_enqueue_script() to add the scripts to the queue
	 */
	function enqueue_assets() {
		$tagit_version = '2.0';
		$url = plugin_dir_url( CODE_SNIPPETS_FILE );

		/* Enqueue CodeMirror */
		code_snippets_enqueue_codemirror();

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
	function remove_incompatible_codemirror() {
		global $pagenow;

		/* Try to discern if we are on the single snippet page as best as we can at this early time */
		is_admin() && 'admin.php' === $pagenow && isset( $_GET['page'] ) && code_snippets_get_menu_slug( 'edit' ) === $_GET['page']

		/* Remove the action and stop all Debug Bar Console scripts */
		&& remove_action( 'debug_bar_enqueue_scripts', 'debug_bar_console_scripts' );
	}
}
