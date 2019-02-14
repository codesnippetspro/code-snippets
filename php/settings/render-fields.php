<?php

namespace Code_Snippets\Settings;

/**
 * This file handles rendering the settings fields
 *
 * @since 2.0.0
 * @package Code_Snippets
 */

/**
 * Render a checkbox field for a setting
 *
 * @since 2.0.0
 *
 * @param array $atts The setting field's attributes
 */
function render_checkbox_field( $atts ) {
	$saved_value = get_setting( $atts['section'], $atts['id'] );
	$input_name = sprintf( 'code_snippets_settings[%s][%s]', $atts['section'], $atts['id'] );

	$checkbox = sprintf(
		'<input type="checkbox" name="%s"%s>',
		esc_attr( $input_name ),
		checked( $saved_value, true, false )
	);

	// Output the checkbox field, optionally with label
	$kses = [ 'input' => [ 'type' => [], 'name' => [], 'checked' => [] ] ];
	if ( isset( $atts['label'] ) ) {
		printf( '<label for="%s">%s %s</label>', esc_attr( $input_name ), wp_kses( $checkbox, $kses ), wp_kses_post( $atts['label'] ) );
	} else {
		echo wp_kses( $checkbox, $kses );
	}

	// Add field description if it is set
	if ( ! empty( $atts['desc'] ) ) {
		echo '<p class="description">' . wp_kses_post( $atts['desc'] ) . '</p>';
	}
}

/**
 * Render a number select field for an editor setting
 *
 * @since 2.0.0
 *
 * @param array $atts The setting field's attributes
 */
function render_number_field( $atts ) {

	printf(
		'<input type="number" name="code_snippets_settings[%s][%s]" value="%s"',
		esc_attr( $atts['section'] ),
		esc_attr( $atts['id'] ),
		esc_attr( get_setting( $atts['section'], $atts['id'] ) )
	);

	if ( isset( $atts['min'] ) ) {
		printf( ' min="%d"', intval( $atts['min'] ) );
	}

	if ( isset( $atts['max'] ) ) {
		printf( ' max="%d"', intval( $atts['max'] ) );
	}

	echo '>';

	if ( ! empty( $atts['label'] ) ) {
		echo ' ' . wp_kses_post( $atts['label'] );
	}

	// Add field description if it is set
	if ( ! empty( $atts['desc'] ) ) {
		echo '<p class="description">', wp_kses_post( $atts['desc'] ), '</p>';
	}
}
