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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ), 9 );

		/* Register action hooks */
		add_action( 'code_snippets/admin/single', array( $this, 'render_description_editor' ), 9 );
		add_action( 'code_snippets/admin/single', array( $this, 'render_tags_editor' ) );

		if ( code_snippets_get_setting( 'general', 'snippet_scope_enabled' ) ) {
			add_action( 'code_snippets/admin/single/settings', array( $this, 'render_scope_setting' ) );
		}

		if ( get_current_screen()->is_network ) {
			add_action( 'code_snippets/admin/single/settings', array( $this, 'render_multisite_sharing_setting' ) );
		}

		$this->save_posted_snippet();
	}

	/**
	 * Save the posted snippet to the database
	 * @access private
	 * @uses wp_redirect() to pass the results to the page
	 */
	private function save_posted_snippet() {

		/* Make sure the nonce validates before we do any snippet ops */
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'save_snippet' ) ) {
			return;
		}

		/* Save the snippet if one has been submitted */
		if ( isset( $_POST['save_snippet'] ) || isset( $_POST['save_snippet_activate'] ) || isset( $_POST['save_snippet_deactivate'] ) ) {

			/* Activate or deactivate the snippet before saving if we clicked the button */
			if ( isset( $_POST['save_snippet_activate'] ) ) {
				$_POST['snippet_active'] = 1;
			} elseif ( isset( $_POST['save_snippet_deactivate'] ) ) {
				$_POST['snippet_active'] = 0;
			}

			/* Save the snippet to the database */
			$result = save_snippet( stripslashes_deep( $_POST ) );

			/* Update the shared network snippets if necessary */
			if ( $screen->is_network && $snippet_id && $snippet_id > 0 ) {
				$shared_snippets = get_site_option( 'shared_network_snippets', array() );

				if ( isset( $_POST['snippet_sharing'] ) && 'on' === $_POST['snippet_sharing'] ) {

					/* Add the snippet ID to the array if it isn't already */
					if ( ! in_array( $snippet_id, $shared_snippets ) ) {
						$shared_snippets[] = $snippet_id;
					}
				} else {
					/* Remove the snippet ID from the array */
					$shared_snippets = array_diff( $shared_snippets, array( $$snippet_id ) );
				}
				update_site_option( 'shared_network_snippets', array_values( $shared_snippets ) );
			}

			/* Build the status message and redirect */
			$query_args = array();

			if ( $result && isset( $_POST['save_snippet_activate'] ) ) {
				/* Snippet was activated addition to saving*/
				$query_args['activated'] = true;
			}
			elseif ( $result && isset( $_POST['save_snippet_deactivate'] ) ) {
				/* Snippet was deactivated addition to saving*/
				$query_args['deactivated'] = true;
			}

			if ( ! $result || $result < 1 ) {
				/* An error occurred */
				$query_args['invalid'] = true;
			}
			elseif ( isset( $_POST['snippet_id'] ) ) {
				/* Existing snippet was updated */
				$query_args['id'] = $result;
				$query_args['updated'] = true;
			}
			else {
				/* New snippet was added */
				$query_args['id'] = $result;
				$query_args['added'] = true;
			}

			/* Redirect to edit snippet page */
			wp_redirect( add_query_arg( $query_args, code_snippets_get_menu_url( 'edit' ) ) );
		}

		/* Delete the snippet if the button was clicked */
		elseif ( isset( $_POST['snippet_id'], $_POST['delete_snippet'] ) ) {
			delete_snippet( $_POST['snippet_id'] );
			wp_redirect( add_query_arg( 'delete', true, code_snippets_get_menu_url( 'manage' ) ) );
		}

		/* Export the snippet if the button was clicked */
		elseif ( isset( $_POST['snippet_id'], $_POST['export_snippet'] ) ) {
			export_snippets( $_POST['snippet_id'] );
		}
	}

	/**
	 * Add a description editor to the single snippet page
	 * @access private
	 * @param object $snippet The snippet being used for this page
	 */
	function render_description_editor( $snippet ) {
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
	* @param object $snippet the snippet currently being edited
	*/
	function render_tags_editor( $snippet ) {
		?>
		<label for="snippet_tags" style="cursor: auto;">
			<h3><?php esc_html_e( 'Tags', 'code-snippets' ); ?></h3>
		</label>

		<input type="text" id="snippet_tags" name="snippet_tags" style="width: 100%;"
			placeholder="<?php esc_html_e( 'Enter a list of tags; separated by commas', 'code-snippets' ); ?>" value="<?php echo implode( ', ', $snippet->tags ); ?>" />

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
	 * @param object $snippet the snippet currently being edited
	 */
	function render_scope_setting( $snippet ) {
		$scopes = array(
			__( 'Run snippet everywhere', 'code-snippets' ),
			__( 'Only run in adminstration area', 'code-snippets' ),
			__( 'Only run on site front-end', 'code-snippets' ),
		);

		echo '<tr class="snippet-scope">';
		echo '<th scope="row">' . __( 'Scope', 'code-snippets' ) . '</th><td>';

		foreach ( $scopes as $scope => $label ) {
			printf( '<div><input type="radio" name="snippet_scope" value="%s"', $scope );
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
	 * Registers and loads the code editor's assets
	 * @access private
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
	 * Print the status and error messages
	 */
	protected function print_messages() {
		if ( isset( $_REQUEST['invalid'] ) && $_REQUEST['invalid'] ) {
			printf( $format, __( 'An error occurred when saving the snippet.', 'code-snippets' ), 'error' );
		} elseif ( isset( $_REQUEST['activated'], $_REQUEST['updated'] ) && $_REQUEST['activated'] && $_REQUEST['updated'] ) {
			printf( $format, __( 'Snippet <strong>updated</strong> and <strong>activated</strong>.', 'code-snippets' ), 'updated' );
		} elseif ( isset( $_REQUEST['activated'], $_REQUEST['added'] ) && $_REQUEST['activated'] && $_REQUEST['added'] ) {
			printf( $format, __( 'Snippet <strong>added</strong> and <strong>activated</strong>.', 'code-snippets' ), 'updated' );
		} elseif ( isset( $_REQUEST['deactivated'], $_REQUEST['updated'] ) && $_REQUEST['deactivated'] && $_REQUEST['updated'] ) {
			printf( $format, __( 'Snippet <strong>updated</strong> and <strong>deactivated</strong>.', 'code-snippets' ), 'updated' );
		} elseif ( isset( $_REQUEST['updated'] ) && $_REQUEST['updated'] ) {
			printf( $format, __( 'Snippet <strong>updated</strong>.', 'code-snippets' ), 'updated' );
		} elseif ( isset( $_REQUEST['added'] ) && $_REQUEST['added'] ) {
			printf( $format, __( 'Snippet <strong>added</strong>.', 'code-snippets' ), 'updated' );
		}
	}
}
