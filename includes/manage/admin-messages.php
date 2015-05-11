<?php

/**
 * Status and error messages for the manage snippets page
 *
 * @package Code_Snippets
 * @subpackage Manage
 */

$_f = '<div id="message" class="%2$s fade"><p>%1$s</p></div>';

if ( defined( 'CODE_SNIPPETS_SAFE_MODE' ) && CODE_SNIPPETS_SAFE_MODE ) :

	printf( $_f, __( '<strong>Warning:</strong> Safe mode is active and snippets will not execute! Remove the <code>CODE_SNIPPETS_SAFE_MODE</code> constant from <code>wp-config.php</code> to turn off safe mode. <a href="https://github.com/sheabunge/code-snippets/wiki/Safe-Mode" target="_blank">Help</a>', 'code-snippets' ), 'error' );

endif;

if ( isset( $_REQUEST['activate'] ) ) :

	printf( $_f, __( 'Snippet <strong>activated</strong>.', 'code-snippets' ), 'updated' );

elseif ( isset( $_REQUEST['activate-multi'] ) ) :

	printf( $_f, __( 'Selected snippets <strong>activated</strong>.', 'code-snippets' ), 'updated' );

elseif ( isset( $_REQUEST['deactivate'] ) ) :

	printf( $_f, __( 'Snippet <strong>deactivated</strong>.', 'code-snippets' ), 'updated' );

elseif ( isset( $_REQUEST['deactivate-multi'] ) ) :

	printf( $_f, __( 'Selected snippets <strong>deactivated</strong>.', 'code-snippets' ), 'updated' );

elseif ( isset( $_REQUEST['delete'] ) ) :

	printf( $_f, __( 'Snippet <strong>deleted</strong>.', 'code-snippets' ), 'updated' );

elseif ( isset( $_REQUEST['delete-multi'] ) ) :

	printf( $_f, __( 'Selected snippets <strong>deleted</strong>.', 'code-snippets' ), 'updated' );

endif;
