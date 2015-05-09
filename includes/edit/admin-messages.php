<?php

/**
 * Status and error messages for the single snippet page
 *
 * @package    Code_Snippets
 * @subpackage Admin_Messages
 */

$_f = '<div id="message" class="%2$s fade"><p>%1$s</p></div>';

if ( isset( $_REQUEST['invalid'] ) && $_REQUEST['invalid'] ) :

	printf( $_f, __( 'An error occurred when saving the snippet.', 'code-snippets' ), 'error' );

elseif ( isset( $_REQUEST['activated'], $_REQUEST['updated'] ) && $_REQUEST['activated'] && $_REQUEST['updated'] ) :

	printf( $_f, __( 'Snippet <strong>updated</strong> and <strong>activated</strong>.', 'code-snippets' ), 'updated' );

elseif ( isset( $_REQUEST['activated'], $_REQUEST['added'] ) && $_REQUEST['activated'] && $_REQUEST['added'] ) :

	printf( $_f, __( 'Snippet <strong>added</strong> and <strong>activated</strong>.', 'code-snippets' ), 'updated' );

elseif ( isset( $_REQUEST['deactivated'], $_REQUEST['updated'] ) && $_REQUEST['deactivated'] && $_REQUEST['updated'] ) :

	printf( $_f, __( 'Snippet <strong>updated</strong> and <strong>deactivated</strong>.', 'code-snippets' ), 'updated' );

elseif ( isset( $_REQUEST['updated'] ) && $_REQUEST['updated'] ) :

	printf( $_f, __( 'Snippet <strong>updated</strong>.', 'code-snippets' ), 'updated' );

elseif ( isset( $_REQUEST['added'] ) && $_REQUEST['added'] ) :

	printf( $_f, __( 'Snippet <strong>added</strong>.', 'code-snippets' ), 'updated' );

endif;
