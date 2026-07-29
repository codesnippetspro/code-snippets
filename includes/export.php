<?php
/**
 * Exports seleted snippets as a Code Snippets (.xml) export file.
 *
 * @package Code Snippets
 * @since Code Snippets 1.3
 */

if( ! function_exists( 'cs_export') ) :
 
function cs_export( $ids, $table ) {

	global $wpdb;
	
	if( ! isset( $table ) )
		$table = apply_filters( 'cs_table', $wpdb->prefix . 'snippets' );
		
	if( ! is_array( $ids ) ) {
		$ids = array( $ids );
	}
	
	$sitename = sanitize_key( get_bloginfo( 'name' ) );
	
	$filename = 'code-snippets.' . $sitename;
	
	if( count( $ids ) < 2 ) {
		$entry = $wpdb->get_row( "select * from $table where id=" . intval( $ids ) );
		$filename = sanitize_title( $entry->name, 'snippet' ) . '.code-snippets';
	}
	
	$filename = apply_filters( 'cs_export_filename', $filename );

	header( 'Content-Disposition: attachment; filename='.$filename.'.xml;' );
	header( 'Content-Type: text/xml; charset=utf-8' );
	
	echo '<?xml version="1.0"?>' . "\n";
	echo '<snippets sitename="' . $sitename . '">';
	
	foreach( $ids as $id ) {
		
		$id = intval( mysql_real_escape_string( $id ) );
		
		if( ! $id > 0 ) continue; // skip this one if we don't have a valid ID
		
		$snippet = $wpdb->get_row( "select * from $table where id=$id" );
		
		echo "\n\t" . '<snippet>';
		echo "\n\t\t" . "<name>$snippet->name</name>";
		echo "\n\t\t" . "<description>$snippet->description</description>";
		echo "\n\t\t" . "<code>$snippet->code</code>";
		echo "\n\t" . '</snippet>';
	}
	
	echo "\n</snippets>";
	exit;
}

endif; // function exists check