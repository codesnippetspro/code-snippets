<?php
/**
 * For some reason, WordPress requires that TinyMCE translations be hosted in an external file. So that's what this is.
 *
 * @package Code_Snippets
 *
 * @var array<string, string|mixed> $strings
 */

namespace Code_Snippets;

use _WP_Editors;

$strings = [
	'insert_content_menu'  => __( 'Content Snippet', 'code-snippets' ),
	'insert_content_title' => __( 'Insert Content Snippet', 'code-snippets' ),
	'snippet_label'        => __( 'Snippet', 'code-snippets' ),
	'php_att_label'        => __( 'Run PHP code', 'code-snippets' ),
	'format_att_label'     => __( 'Apply formatting', 'code-snippets' ),
	'shortcodes_att_label' => __( 'Enable shortcodes', 'code-snippets' ),

	'insert_source_menu'      => __( 'Snippet Source Code', 'code-snippets' ),
	'insert_source_title'     => __( 'Insert Snippet Source', 'code-snippets' ),
	'show_line_numbers_label' => __( 'Show line numbers', 'code-snippets' ),
];

$strings = array_map( 'esc_js', $strings );

$snippets = get_snippets();

$strings['all_snippets'] = [];
$strings['content_snippets'] = [];

foreach ( $snippets as $snippet ) {
	if ( 'content' === $snippet->scope ) {
		$strings['content_snippets'][ $snippet->id ] = $snippet->display_name;
	}

	$strings['all_snippets'][ $snippet->id ] = sprintf(
		'%s (%s)',
		$snippet->display_name,
		strtoupper( $snippet->type )
	);
}

asort( $strings['all_snippets'], SORT_STRING | SORT_FLAG_CASE );
asort( $strings['content_snippets'], SORT_STRING | SORT_FLAG_CASE );

$strings = [ _WP_Editors::$mce_locale => [ 'code_snippets' => $strings ] ];
/** $strings is used by outer file. @noinspection PhpUnusedLocalVariableInspection */
$strings = 'tinyMCE.addI18n(' . wp_json_encode( $strings ) . ');';
