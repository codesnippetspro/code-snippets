<?php

/**
 * Functions to handle the single snippet menu
 *
 * @package    Code_Snippets
 * @subpackage Administration
 */

/**
 * Register the single snippet admin menu
 *
 * @since 2.0
 * @access private
 * @uses add_submenu_page() To register a sub-menu
 */
function code_snippets_add_single_menu() {

	/* Check if we are currently editing a snippet */
	$editing = ( isset( $_REQUEST['page'], $_REQUEST['edit'] ) && 'snippet' === $_REQUEST['page'] );

	$hook = add_submenu_page(
		'snippets',
		$editing ? __( 'Edit Snippet', 'code-snippets' ) : __( 'Add New Snippet', 'code-snippets' ),
		$editing ? __( 'Edit', 'code-snippets' ) : __( 'Add New', 'code-snippets' ),
		get_snippets_cap(),
		'snippet',
		'code_snippets_render_single_menu'
	);

	add_action( 'load-' . $hook, 'code_snippets_load_single_menu' );
}

add_action( 'admin_menu', 'code_snippets_add_single_menu', 5 );
add_action( 'network_admin_menu', 'code_snippets_add_single_menu', 5 );

/**
 * Displays the single snippet menu
 *
 * @since 2.0
 */
function code_snippets_render_single_menu() {
	require plugin_dir_path( __FILE__ ) . 'messages/single.php';
	require plugin_dir_path( __FILE__ ) . 'views/single.php';
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
	require plugin_dir_path( __FILE__ ) . 'help/single.php';

	/* Enqueue the code editor and other scripts and styles */
	add_filter( 'admin_enqueue_scripts', 'code_snippets_single_menu_assets' );

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
			wp_redirect( add_query_arg(	array( 'edit' => $result, 'updated' => true ) ) );
		}
		else {
			/* New snippet was added */
			wp_redirect( add_query_arg( array( 'edit' => $result, 'added' => true ) ) );
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
function code_snippets_single_menu_assets( $hook ) {
	global $code_snippets;

	/* If we're not on the right admin page, bail early */
	if ( $hook !== get_plugin_page_hookname( 'snippet', 'snippets' ) ) {
		return;
	}

	/* Remove other CodeMirror styles */
	wp_deregister_style( 'codemirror' );
	wp_deregister_style( 'wpeditor' );

	/* CodeMirror */

	$codemirror_version = '3.20';
	$codemirror_url     = plugins_url( 'vendor/codemirror/', $code_snippets->file );

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

	/* Plugin Assets */

	wp_enqueue_style(
		'code-snippets-admin-single',
		plugins_url( 'css/min/admin-single.css', $code_snippets->file ),
		false,
		$code_snippets->version
	);

	wp_enqueue_script(
		'code-snippets-admin-single',
		plugins_url( 'js/admin-single.js', $code_snippets->file ),
		array( 'code-snippets-codemirror' ),
		$code_snippets->version,
		true // Load in footer
	);
}
