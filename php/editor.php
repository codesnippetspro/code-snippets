<?php

/**
 * Get the attributes for the code editor
 *
 * @param  array        $override_atts Pass an array of attributes to override the saved ones
 * @param  bool         $json_encode   Encode the data as JSON
 * @return array|string                Array if $json_encode is false, JSON string if it is true
 */
function code_snippets_get_editor_atts( $override_atts, $json_encode ) {
	$settings = code_snippets_get_settings();
	$settings = $settings['editor'];

	$fields = code_snippets_get_settings_fields();
	$fields = $fields['editor'];

	$saved_atts = array(
		'matchBrackets' => true,
	);

	foreach ( $fields as $field_id => $field ) {
		$saved_atts[ $field['codemirror'] ] = $settings[ $field_id ];
	}

	$atts = wp_parse_args( $override_atts, $saved_atts );
	$atts = apply_filters( 'code_snippets_codemirror_atts', $atts );

	if ( $json_encode ) {

		/* JSON_UNESCAPED_SLASHES was added in PHP 5.4 */
		if ( version_compare( phpversion(), '5.4.0', '>=' ) ) {
			$atts = json_encode( $atts, JSON_UNESCAPED_SLASHES );
		} else {
			/* Use a fallback for < 5.4 */
			$atts = str_replace( '\\/', '/', json_encode( $atts ) );
		}
	}

	return $atts;
}

/**
 * Registers and loads the CodeMirror library
 *
 * @uses wp_enqueue_style() to add the stylesheets to the queue
 * @uses wp_enqueue_script() to add the scripts to the queue
 */
function code_snippets_enqueue_codemirror() {
	$codemirror_version = '5.40.2';
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
}

/**
 * Retrieve a list of the available CodeMirror themes
 * @return array the available themes
 */
function code_snippets_get_available_themes() {
	static $themes = null;

	if ( ! is_null( $themes ) ) {
		return $themes;
	}

	$themes = array();
	$themes_dir = plugin_dir_path( CODE_SNIPPETS_FILE ) . 'css/min/cmthemes/';
	$theme_files = glob( $themes_dir . '*.css' );

	foreach ( $theme_files as $i => $theme ) {
		$theme = str_replace( $themes_dir, '', $theme );
		$theme = str_replace( '.css', '', $theme );
		$themes[] = $theme;
	}

	return $themes;
}
