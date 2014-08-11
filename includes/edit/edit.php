<?php

/**
 * Functions to handle the single snippet menu
 *
 * @package    Code_Snippets
 * @subpackage Administration
 */

/**
 * Fetch the admin menu slug for a snippets menu
 * @param integer $id The snippet
 * @return string The URL to the edit snippet page for that snippet
 */
function get_snippet_edit_url( $snippet_id ) {
	return add_query_arg(
		'id', absint( $snippet_id ),
		code_snippets_get_menu_url( 'edit' )
	);
}

/**
 * Register the single snippet admin menu
 *
 * @since 2.0
 * @access private
 * @uses add_submenu_page() To register a sub-menu
 */
function code_snippets_add_single_menu() {

	/* Add New Snippet menu */
	$add_hook = add_submenu_page(
		code_snippets_get_menu_slug(),
		__( 'Add New Snippet', 'code-snippets' ),
		__( 'Add New', 'code-snippets' ),
		get_snippets_cap(),
		code_snippets_get_menu_slug( 'add' ),
		'code_snippets_render_single_menu'
	);

	add_action( 'load-' . $add_hook, 'code_snippets_load_single_menu' );

	/* Check if we are currently editing a snippet */
	if ( isset( $_REQUEST['page'] ) && code_snippets_get_menu_slug( 'edit' ) === $_REQUEST['page'] ) {

		$edit_hook = add_submenu_page(
			code_snippets_get_menu_slug(),
			__( 'Edit Snippet', 'code-snippets' ),
			__( 'Edit', 'code-snippets' ),
			get_snippets_cap(),
			code_snippets_get_menu_slug( 'edit' ),
			'code_snippets_render_single_menu'
		);

		add_action( 'load-' . $edit_hook, 'code_snippets_load_single_menu' );
	}

}

add_action( 'admin_menu', 'code_snippets_add_single_menu', 5 );
add_action( 'network_admin_menu', 'code_snippets_add_single_menu', 5 );

/**
 * Displays the single snippet menu
 *
 * @since 2.0
 */
function code_snippets_render_single_menu() {
	require plugin_dir_path( __FILE__ ) . 'admin-messages.php';
	require plugin_dir_path( __FILE__ ) . 'admin.php';
}

/**
 * Loads the help tabs for the Edit Snippets page
 *
 * @since 1.0
 * @access private
 * @uses wp_redirect To pass the results to the page
 */
function code_snippets_load_single_menu() {

	/* Make sure the user has permission to be here */
	if ( ! current_user_can( get_snippets_cap() ) ) {
		wp_die( __( 'You are not access this page.', 'code-snippets' ) );
	}

	/* Create the snippet tables if they don't exist */
	create_code_snippets_tables( true, true );

	/* Load the screen help tabs */
	require plugin_dir_path( __FILE__ ) . 'admin-help.php';

	/* Enqueue the code editor and other scripts and styles */
	add_filter( 'admin_enqueue_scripts', 'code_snippets_enqueue_codemirror' );

	/* Don't allow visiting the edit snippet page without a valid ID */
	if ( code_snippets_get_menu_slug( 'edit' ) === $_REQUEST['page'] ) {
		if ( ! isset( $_REQUEST['id'] ) || 0 == $_REQUEST['id'] ) {
			wp_redirect( code_snippets_get_menu_url( 'add' ) );
			exit;
		}
	}

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

		/* Strip old status query vars from URL */
		$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'added', 'updated', 'activated', 'deactivated', 'invalid' ) );

		/* Build the status message and redirect */

		if ( $result && isset( $_POST['save_snippet_activate'] ) ) {
			/* Snippet was activated addition to saving*/
			$_SERVER['REQUEST_URI'] = add_query_arg( 'activated', true );
		}
		elseif ( $result && isset( $_POST['save_snippet_deactivate'] ) ) {
			/* Snippet was deactivated addition to saving*/
			$_SERVER['REQUEST_URI'] = add_query_arg( 'deactivated', true );
		}

		if ( ! $result || $result < 1 ) {
			/* An error occurred */
			wp_redirect( add_query_arg( 'invalid', true ) );
		}
		elseif ( isset( $_POST['snippet_id'] ) ) {
			/* Existing snippet was updated */
			wp_redirect( add_query_arg(	array( 'id' => $result, 'updated' => true ) ) );
		}
		else {
			/* New snippet was added */
			wp_redirect( add_query_arg(
				array( 'id' => $result, 'added' => true ),
				code_snippets_get_menu_url( 'edit' )
			) );
		}
	}

	/* Delete the snippet if the button was clicked */
	elseif ( isset( $_POST['snippet_id'], $_POST['delete_snippet'] ) ) {
		delete_snippet( $_POST['snippet_id'] );
		wp_redirect( add_query_arg( 'delete', true, $this->manage_url ) );
	}

	/* Export the snippet if the button was clicked */
	elseif ( isset( $_POST['snippet_id'], $_POST['export_snippet'] ) ) {
		export_snippet( $_POST['snippet_id'] );
	}
}

/**
 * Add a description editor to the single snippet page
 *
 * @since 1.7
 * @access private
 * @param object $snippet The snippet being used for this page
 */
function code_snippets_description_editor_box( $snippet ) {

	?>

	<label for="snippet_description">
		<h3><div><?php _e( 'Description', 'code-snippets' ); ?></div></h3>
	</label>

	<?php

	remove_editor_styles(); // stop custom theme styling interfering with the editor

	wp_editor(
		$snippet->description,
		'description',
		apply_filters( 'code_snippets/admin/description_editor_settings', array(
			'textarea_name' => 'snippet_description',
			'textarea_rows' => 10,
			'teeny' => true,
			'media_buttons' => false,
		) )
	);
}

add_action( 'code_snippets/admin/single', 'code_snippets_description_editor_box', 5 );

/**
* Output the interface for editing snippet tags
* @since 2.0
* @param object $snippet The snippet currently being edited
*/
function code_snippets_tags_editor( $snippet ) {
?>
	<label for="snippet_tags" style="cursor: auto;">
		<h3><?php esc_html_e( 'Tags', 'code-snippets' ); ?></h3>
	</label>

	<input type="text" id="snippet_tags" name="snippet_tags" style="width: 100%;"
		placeholder="Enter a list of tags; separated by commas" value="<?php echo implode( ', ', $snippet->tags ); ?>" />

	<script type="text/javascript">
	jQuery('#snippet_tags').tagit({
		availableTags: ['<?php echo implode( "','", get_all_snippet_tags() ); ?>'],
		allowSpaces: true,
		removeConfirmation: true
	});
	</script>
<?php
}

add_action( 'code_snippets/admin/single', 'code_snippets_tags_editor' );

/**
 * Registers and loads the code editor's assets
 *
 * @since 1.7
 * @access private
 *
 * @uses wp_register_script()
 * @uses wp_register_style()
 * @uses wp_enqueue_script() To add the scripts to the queue
 * @uses wp_enqueue_style() To add the stylesheets to the queue
 *
 * @param string $hook The current page hook, to be compared with the single snippet page hook
 */
function code_snippets_enqueue_codemirror() {

	/* Remove other CodeMirror styles */
	wp_deregister_style( 'codemirror' );
	wp_deregister_style( 'wpeditor' );

	/* CodeMirror */

	$codemirror_version = '4.4';
	$codemirror_url     = plugins_url( 'vendor/codemirror/', CODE_SNIPPETS_FILE );

	wp_enqueue_style(
		'code-snippets-codemirror',
		$codemirror_url . 'lib/codemirror.css',
		false,
		$codemirror_version
	);

	wp_enqueue_script(
		'code-snippets-codemirror',
		$codemirror_url . 'lib/codemirror.js',
		false,
		$codemirror_version
	);

	/* CodeMirror Modes */

	wp_enqueue_script(
		'code-snippets-codemirror-mode-clike',
		$codemirror_url . 'mode/clike/clike.js',
		array( 'code-snippets-codemirror' ),
		$codemirror_version
	);

	wp_enqueue_script(
		'code-snippets-codemirror-mode-php',
		$codemirror_url . 'mode/php/php.js',
		array( 'code-snippets-codemirror', 'code-snippets-codemirror-mode-clike' ),
		$codemirror_version
	);


	/* CodeMirror Addons */

	wp_enqueue_script(
		'code-snippets-codemirror-addon-searchcursor',
		$codemirror_url . 'addon/search/searchcursor.js',
		array( 'code-snippets-codemirror' ),
		$codemirror_version
	);

	wp_enqueue_script(
		'code-snippets-codemirror-addon-search',
		$codemirror_url . 'addon/search/search.js',
		array( 'code-snippets-codemirror', 'code-snippets-codemirror-addon-searchcursor' ),
		$codemirror_version
	);

	wp_enqueue_script(
		'code-snippets-codemirror-addon-matchbrackets',
		$codemirror_url . 'addon/edit/matchbrackets.js',
		array( 'code-snippets-codemirror' ),
		$codemirror_version
	);

	wp_enqueue_script(
		'code-snippets-codemirror-addon-closebrackets',
		$codemirror_url . 'addon/edit/closebrackets.js',
		array( 'code-snippets-codemirror' ),
		$codemirror_version
	);

	/* Plugin Assets */

	wp_enqueue_style(
		'code-snippets-edit',
		plugins_url( 'css/min/edit-snippet.css', CODE_SNIPPETS_FILE ),
		false,
		CODE_SNIPPETS_VERSION
	);

	/* CodeMirror Theme */

	$settings = get_option( 'code_snippets_settings' );
	$theme = $settings['editor']['theme'];

	if ( 'default' !== $theme ) {

		wp_enqueue_style(
			'code-snippets-codemirror-theme-' . $theme,
			$codemirror_url . "theme/$theme.css",
			array( 'code-snippets-codemirror' ),
			$codemirror_version
		);
	}

	/* Tag It UI */

	$tagit_version = '2.0';

	wp_enqueue_script(
		'code-snippets-tag-it',
		plugins_url( 'css/vendor/tag-it.min.js', CODE_SNIPPETS_FILE ),
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
		'code-snippets-tagit',
		plugins_url( 'css/vendor/jquery.tagit.css', CODE_SNIPPETS_FILE ),
		false,
		$tagit_version
	);

	wp_enqueue_style(
		'code-snippets-tagit-zendesk-ui',
		plugins_url( 'css/vendor/tagit.ui-zendesk.css', CODE_SNIPPETS_FILE ),
		array( 'code-snippets-tagit' ),
		$tagit_version
	);

}
