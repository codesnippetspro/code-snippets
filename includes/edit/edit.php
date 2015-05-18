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
			__( 'Edit Snippet', 'code-snippets' ),
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
		wp_die( __( 'You are not authorized to access this page.', 'code-snippets' ) );
	}

	/* Create the snippet tables if they don't exist */
	create_code_snippets_tables();

	/* Load the screen help tabs */
	require plugin_dir_path( __FILE__ ) . 'admin-help.php';

	/* Enqueue the code editor and other scripts and styles */
	add_action( 'admin_enqueue_scripts', 'code_snippets_enqueue_codemirror', 9 );

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
 *
 * @since 1.7
 * @access private
 * @param object $snippet The snippet being used for this page
 */
function code_snippets_description_editor_box( $snippet ) {
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

add_action( 'code_snippets/admin/single', 'code_snippets_description_editor_box', 9 );

function code_snippets_snippet_scope_setting( $snippet ) {

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

add_action( 'code_snippets/admin/single', 'code_snippets_snippet_scope_setting', 5 );

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

add_action( 'code_snippets/admin/single', 'code_snippets_tags_editor' );

/**
 * Registers and loads the code editor's assets
 *
 * @since 1.7
 * @access private
 *
 * @uses wp_enqueue_script() To add the scripts to the queue
 * @uses wp_enqueue_style() To add the stylesheets to the queue
 */
function code_snippets_enqueue_codemirror() {
	$tagit_version = '2.0';
	$codemirror_version = '5.2';
	$url = plugin_dir_url( CODE_SNIPPETS_FILE );

	/* Remove other CodeMirror styles */
	wp_deregister_style( 'codemirror' );
	wp_deregister_style( 'wpeditor' );

	/* CodeMirror */
	wp_enqueue_style(
		'code-snippets-codemirror',
		$url . 'css/min/codemirror.css',
		false, $codemirror_version
	);

	wp_enqueue_script(
		'code-snippets-codemirror',
		$url . 'js/min/codemirror.js',
		false, $codemirror_version
	);

	/* CodeMirror Theme */
	$theme = code_snippets_get_setting( 'editor', 'theme' );

	if ( 'default' !== $theme ) {

		wp_enqueue_style(
			'code-snippets-codemirror-theme-' . $theme,
			$url . "css/min/cmthemes/$theme.css",
			array( 'code-snippets-codemirror' ),
			$codemirror_version
		);
	}

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
