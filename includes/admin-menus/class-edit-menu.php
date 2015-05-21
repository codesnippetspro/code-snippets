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

		add_action( 'code_snippets/admin/single', array( $this, 'render_scope_setting' ), 5 );
		add_action( 'code_snippets/admin/single', array( $this, 'render_description_editor' ), 9 );
		add_action( 'code_snippets/admin/single', array( $this, 'render_tags_editor' ) );
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

		code_snippets_load_edit_help();

		/* Enqueue the code editor and other scripts and styles */
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ), 9 );

		/* Don't allow visiting the edit snippet page without a valid ID */
		if ( code_snippets_get_menu_slug( 'edit' ) === $_REQUEST['page'] ) {
			if ( ! isset( $_REQUEST['id'] ) || 0 == $_REQUEST['id'] ) {
				wp_redirect( code_snippets_get_menu_url( 'add' ) );
				exit;
			}
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
			$snippet_id = save_snippet( stripslashes_deep( $_POST ) );

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
	 * Render the snippet scope setting
	 * @param object $snippet the snippet currently being edited
	 */
	function render_scope_setting( $snippet ) {

		if ( ! code_snippets_get_setting( 'general', 'snippet_scope_enabled' ) ) {
			return;
		}

		$scopes = array(
			__( 'Run snippet everywhere', 'code-snippets' ),
			__( 'Only run in adminstration area', 'code-snippets' ),
			__( 'Only run on site front-end', 'code-snippets' ),
		);

		echo '<div class="snippet-scope">';
		printf( '<label for="snippet_scope"><h3>%s</h3></label>', __( 'Scope', 'code-snippets' ) );

		foreach ( $scopes as $scope => $label ) {
			printf( '<div><input type="radio" name="snippet_scope" value="%s"', $scope );
			checked( $scope, $snippet->scope );
			echo "> $label</div>";
		}

		echo '</div>';
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
					'updated' =>  __( 'Snippet <strong>updated</strong>.', 'code-snippets' ),
					'added-and-activated' => __( 'Snippet <strong>added</strong> and <strong>activated</strong>.', 'code-snippets' ),
					'updated-and-activated' => __( 'Snippet <strong>updated</strong> and <strong>activated</strong>.', 'code-snippets' ),
					'updated-and-deactivated' => __( 'Snippet <strong>updated</strong> and <strong>deactivated</strong>.', 'code-snippets' ),
				)
			);
		}
	}
}
