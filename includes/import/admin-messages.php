<?php

/**
 * Status and error messages for the import snippets page
 *
 * @package    Code_Snippets
 * @subpackage Admin_Messages
 */

if ( isset( $_REQUEST['imported'] ) && 0 !== intval( $_REQUEST['imported'] ) ) {

	echo '<div id="message" class="updated fade"><p>';

	printf(
		_n(
			'Successfully imported <strong>%d</strong> snippet. <a href="%s">Have fun!</a>',
			'Successfully imported <strong>%d</strong> snippets. <a href="%s">Have fun!</a>',
			$_REQUEST['imported'],
			'code-snippets'
		),
		$_REQUEST['imported'],
		code_snippets_get_menu_url( 'manage' )
	);

	echo '</p></div>';
}
elseif ( isset( $_REQUEST['error'] ) && $_REQUEST['error'] ) {
	printf(
		'<div id="message" class="error fade"><p>%s</p></div>',
		__( 'An error occurred when processing the import file.', 'code-snippets' )
	);
}
