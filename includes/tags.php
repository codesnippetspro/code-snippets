<?php

/**
 * Load the functions for handling the administration interface
 *
 * @package Code_Snippets
 * @subpackage Tags
 */

/**
* Add a tags column to the snippets table
* @since 2.0
*/
function code_snippets_tags_add_table_column( $columns ) {
	$columns['tags'] = __( 'Tags', 'code-snippets' );
	return $columns;
}

add_filter( 'code_snippets/list_table/columns', 'code_snippets_tags_add_table_column' );

/**
* Output the content of the table column
* This function is used once for each row
* @since 2.0
* @param object $snippet
*/
function code_snippets_tags_table_column( $snippet ) {

	if ( ! empty( $snippet->tags ) ) {

		foreach ( $snippet->tags as $tag ) {
			$out[] = sprintf( '<a href="%s">%s</a>',
				add_query_arg( 'tag', esc_attr( $tag ) ),
				esc_html( $tag )
			);
		}
		echo join( ', ', $out );
	} else {
		echo '&#8212;';
	}
}

add_action( 'code_snippets/list_table/column_tags', 'code_snippets_tags_table_column' );

/**
* Adds the 'tag' query var as a required form field so it is preserved over form submissions
* @since 2.0
*/
function code_snippets_tags_add_form_field( $vars, $context ) {

	if ( 'filter_controls' !== $context ) {
		$vars[] = 'tag';
	}

	return $vars;
}

add_filter( 'code_snippets/list_table/required_form_fields', 'code_snippets_tags_add_form_field', 10, 2 );

/**
* Filter the snippets based on the tag filter
* @since 2.0
*/
function code_snippets_tags_filter_snippets( $snippets ) {

	if ( isset( $_POST['tag'] ) ) {

		if ( ! empty( $_POST['tag'] ) )
			wp_redirect( add_query_arg( 'tag', $_POST['tag'] ) );
		else
			wp_redirect( remove_query_arg( 'tag' ) );
	}

	if ( ! empty( $_GET['tag'] ) ) {
		$snippets = array_filter( $snippets, 'code_snippets_tags__filter_snippets_callback' );
	}

	return $snippets;
}

add_filter( 'code_snippets/list_table/get_snippets', 'code_snippets_tags_filter_snippets' );

/**
* Used by the code_snippets_tags_filter_snippets() function
* @ignore
*/
function code_snippets_tags__filter_snippets_callback( $snippet ) {

	$tags = explode( ',', $_GET['tag'] );

	foreach ( $tags as $tag ) {
		if ( in_array( $tag, $snippet->tags ) ) {
			return true;
		}
	}
}

/**
* Adds the tag filter to the search notice
* @since 2.0
*/
function code_snippets_tags_search_notice() {

	if ( ! empty( $_GET['tag'] ) ) {
		return sprintf ( __(' in tag &#8220;%s&#8221;', 'code-snippets' ), $_GET['tag'] );
	}
}

add_filter( 'code_snippets/list_table/search_notice', 'code_snippets_tags_search_notice' );

/**
* Display a dropdown of all of the used tags for filtering items
* @since 2.0
*/
function code_snippets_tags_dropdown() {
	global $wpdb;

	$tags = code_snippets_get_current_tags();
	$query = isset( $_GET['tag'] ) ? $_GET['tag'] : '';

	if ( ! count( $tags ) )
		return;

	echo '<select name="tag">';

	printf ( "<option %s value=''>%s</option>\n",
		selected( $query, '', false ),
		__( 'Show all tags', 'code-snippets' )
	);

	foreach ( $tags as $tag ) {

		printf( "<option %s value='%s'>%s</option>\n",
			selected( $query, $tag, false ),
			esc_attr( $tag ),
			$tag
		);
	}

	echo '</select>';
}

add_action( 'code_snippets/list_table/filter_controls', 'code_snippets_tags_dropdown' );

/**
* Gets all of the used tags from the database
* @since 2.0
*/
function code_snippets_get_all_tags() {
	global $wpdb;

	/* Grab all tags from the database */
	$tags = array();
	$table = get_snippets_table_name();
	$all_tags = $wpdb->get_col( "SELECT tags FROM $table" );

	/* Merge all tags into a single array */
	foreach ( $all_tags as $snippet_tags ) {
		$snippet_tags = maybe_unserialize( $snippet_tags );
		$snippet_tags = code_snippets_tags_build_array( $snippet_tags );
		$tags = array_merge( $snippet_tags, $tags );
	}

	/* Remove duplicate tags */
	return array_values( array_unique( $tags, SORT_REGULAR ) );
}

/**
* Gets the tags of the snippets currently being viewed in the table
* @since 2.0
*/
function code_snippets_get_current_tags() {
	global $snippets, $status;

	/* If we're not viewing a snippets table, get all used tags instead */
	if ( ! isset( $snippets, $status ) ) {
		return code_snippets_get_all_tags();
	}

	$tags = array();

	/* Merge all tags into a single array */
	foreach ( $snippets[ $status ] as $snippet ) {
		$tags = array_merge( $snippet->tags, $tags );
	}

	/* Remove duplicate tags */
	return array_values( array_unique( $tags, SORT_REGULAR ) );
}

/**
 * Make sure that the tags are a valid array
 * @since 2.0
 *
 * @param mixed $tags The tags to convert into an array
 * @return array The converted tags
 */
function code_snippets_tags_build_array( $tags ) {

	/* If there are no tags set, create a default empty array */
	if ( empty( $tags ) ) {
		$tags = array();
	}

	/* If the tags are set as a string, convert them into an array */
	elseif ( is_string( $tags ) ) {
		$tags = str_replace( ', ', ',', $tags );
		$tags = explode( ',', $tags );
	}

	/* If we still don't have an array, just convert whatever we do have into one */
	if ( ! is_array( $tags ) ) {
		$tags = (array) $tags;
	}

	return $tags;
}

/**
 * Escape the tag data for insertion into the database
 * @since 2.0
 * @param  object $snippet The snippet data to be escaped
 * @return object          The escaped snippet data
 */
function code_snippets_tags_escape_snippet_data( $snippet ) {
	$snippet->tags = code_snippets_tags_build_array( $snippet->tags );
	$snippet->tags = implode( ', ', $snippet->tags );
	return $snippet;
}

add_filter( 'code_snippets/escape_snippet_data', 'code_snippets_tags_escape_snippet_data' );

/**
* Unescape the tag data after retrieval from the database,
* ready for use
*
* @since 2.0
* @param object $snippet The snippet data to be unescaped
* @return object The unescaped snippet data
*/
function code_snippets_tags_unescape_snippet_data( $snippet ) {
	$snippet->tags = maybe_unserialize( $snippet->tags );
	$snippet->tags = code_snippets_tags_build_array( $snippet->tags );
	return $snippet;
}

add_filter( 'code_snippets/unescape_snippet_data', 'code_snippets_tags_unescape_snippet_data' );

/**
* Create an empty array for the tags
* when building an empty snippet object
* @since 2.0
* @param object $snippet The default snippet data, without default tags
* @return object The default snippet data, with default tags
*/
function code_snippets_tags_build_default_snippet( $snippet ) {
	$snippet->tags = array();
	return $snippet;
}

add_filter( 'code_snippets/build_default_snippet', 'code_snippets_tags_build_default_snippet' );

/**
* Convert snippet array keys into a
* valid snippet object
* @since 2.0
* @param object $snippet The snippet object to add data to
* @param array $data The data to insert into the array
* @return object The snippet object with added data
*/
function code_snippets_tags_build_snippet_object( $snippet, $data ) {

	if ( isset( $data['tags'] ) ) {
		$snippet->tags = $data['tags'];
	}

	elseif ( isset( $data['snippet_tags'] ) ) {
		$snippet->tags = $data['snippet_tags'];
	}

	return $snippet;
}

add_filter( 'code_snippets/build_snippet_object', 'build_snippet_object', 10, 2 );


/**
* Enqueue the tag-it scripts and styles on the edit/add new snippet page
* @since 2.0
* @param string $hook The current page hook
*/
function code_snippets_tags_enqueue_scripts( $hook ) {

	if ( $hook !== code_snippets_get_menu_hook( 'edit' )
		&& $hook !== code_snippets_get_menu_hook( 'add' ) ) {
		return;
	}

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

add_action( 'admin_enqueue_scripts', 'code_snippets_tags_enqueue_scripts' );

/**
* Output the interface for editing snippet tags
* @since 2.0
* @param object $snippet The snippet currently being edited
*/
function code_snippets_tags_admin( $snippet ) {
?>
	<label for="snippet_tags" style="cursor: auto;">
		<h3><?php esc_html_e( 'Tags', 'code-snippets' ); ?></h3>
	</label>

	<input type="text" id="snippet_tags" name="snippet_tags" style="width: 100%;" placeholder="Enter a list of tags; separated by commas" value="<?php echo implode( ', ', $snippet->tags ); ?>" />

	<script type="text/javascript">
	jQuery('#snippet_tags').tagit({
		availableTags: ['<?php echo implode( "','", code_snippets_get_all_tags() ); ?>'],
		allowSpaces: true,
		removeConfirmation: true
	});
	</script>

<?php
}

add_action( 'code_snippets/admin/single', 'code_snippets_tags_admin' );
