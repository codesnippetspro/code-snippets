<?php

/**
 * Get the attributes for the code editor
 * @param array $override_atts Pass an array of attributes to override the saved ones
 * @param boolean $json_encode Encode the data as JSON
 * @return array|string Array if $json_encode is false, JSON string if it is true
 */
function code_snippets_get_editor_atts( $override_atts, $json_encode ) {
	$settings = code_snippets_get_settings();
	$settings = $settings['editor'];

	$fields = code_snippets_get_settings_fields();
	$fields = $fields['editor'];

	$saved_atts = array(
		'matchBrackets' => true,
	);

	foreach ( $fields as $field ) {
		$saved_atts[ $field['codemirror'] ] = $settings[ $field['id'] ];

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
