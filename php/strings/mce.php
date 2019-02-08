<?php

/**
 * For some reason, WordPress requires that TinyMCE translations be hosted in an external file.
 * So that's what this is
 * @package Code_Snippets
 */

$strings = [
	'insert_content_menu'  => __( 'Content Snippet', 'code-snippets' ),
	'insert_content_title' => __( 'Insert Content Snippet', 'code-snippets' ),
	'snippet_id_label'     => __( 'Snippet ID', 'code-snippets' ),
	'php_att_label'        => __( 'Evaluate PHP code', 'code-snippets' ),
	'format_att_label'     => __( 'Apply formatting', 'code-snippets' ),
	'shortcodes_att_label' => __( 'Evaluate shortcodes', 'code-snippets' ),

	'insert_source_menu'      => __( 'Snippet Source Code', 'code-snippets' ),
	'insert_source_title'     => __( 'Insert Snippet Source', 'code-snippets' ),
	'show_line_numbers_label' => __( 'Show line numbers', 'code-snippets' ),

];

$strings = array_map( 'esc_js', $strings );
$strings = [ _WP_Editors::$mce_locale => [ 'code_snippets' => $strings ] ];
$strings = 'tinyMCE.addI18n(' . json_encode( $strings ) . ');';
