<?php

function code_snippets_editor_settings_preview_assets( $hook ) {

	/* Only load on the settings page */
	if ( code_snippets_get_menu_hook( 'settings' ) !== $hook ) {
		return;
	}

	/* Enqueue scripts for the editor preview */
	code_snippets_enqueue_codemirror();

	/* Enqueue ALL themes */
	$themes_dir = plugin_dir_path( CODE_SNIPPETS_FILE ) . 'vendor/codemirror/theme/';
	$themes = glob( $themes_dir . '*.css' );

	foreach ( $themes as $theme ) {
		$theme = str_replace( $themes_dir, '', $theme );
		$theme = str_replace( '.css', '', $theme );

		wp_enqueue_style(
			'code-snippets-codemirror-theme-' . $theme,
			plugins_url( "css/min/cmthemes/$theme.css", CODE_SNIPPETS_FILE ),
			array( 'code-snippets-codemirror' )
		);
	}

	wp_enqueue_script( 'jquery' );
}

add_action( 'admin_enqueue_scripts', 'code_snippets_editor_settings_preview_assets' );

/**
 * Render a theme select field
 */
function code_snippets_codemirror_theme_select_field( $atts ) {

	$saved_value = code_snippets_get_setting( $atts['section'], $atts['id'] );

	echo '<select name="code_snippets_settings[editor][theme]">';
	echo '<option value="default"' . selected( 'default', $saved_value, false ) . '>Default</option>';

	/* Fetch all theme CSS files */
	$themes_dir = plugin_dir_path( CODE_SNIPPETS_FILE ) . 'vendor/codemirror/theme/';
	$themes = glob( $themes_dir . '*.css' );

	/* Print dropdown entry for each theme */
	foreach ( $themes as $theme ) {

		/* Extract theme name from path */
		$theme = str_replace( $themes_dir, '', $theme );
		$theme = str_replace( '.css', '', $theme );

		/* Skip mobile themes */
		if ( 'ambiance-mobile' === $theme ) {
			continue;
		}
		printf(
			'<option value="%s"%s>%s</option>',
			$theme,
			selected( $theme, $saved_value, false ),
			ucwords( str_replace( '-', ' ', $theme ) )
		);
	}

	echo '</select>';
}

function code_snippets_settings_editor_preview() {

	$example_content = "
function example_custom_admin_footer_text( \$text ) {
	return 'Thank you for visiting <a href=\"' . get_home_url() . '\">' . get_bloginfo( 'name' ) . '</a>.';
}

add_filter( 'admin_footer_text', 'example_custom_admin_footer_text' );";

	$atts = array(
		'mode' => 'text/x-php',
		'value' => $example_content,
	);

	?>

	<div style="max-width: 800px" id="code_snippets_editor_preview"></div>

	<script>
	(function( $ ) {
		'use strict';

		$(function() {

			// Load CodeMirror
			var atts = <?php echo code_snippets_get_editor_atts( $atts, true ); ?>;
			var editor = CodeMirror(document.getElementById('code_snippets_editor_preview'), atts);

			// Dynamically change editor settings

			<?php

			$fields = code_snippets_get_settings_fields();
			$fields = $fields['editor'];

			$types = wp_list_pluck( $fields, 'type', 'id' );
			$codemirror_atts = wp_list_pluck( $fields, 'codemirror', 'id' );

			foreach ( $codemirror_atts as $setting => $att_name ) {

				switch ( $types[ $setting ] ) {

					case 'codemirror_theme_select':	?>

			$( 'select[name="code_snippets_settings[editor][<?php echo $setting; ?>]"]' ).change( function () {
				editor.setOption( '<?php echo $att_name; ?>', $(this).val() );
			} );

						<?php break;
					case 'checkbox': ?>

			$( 'input[name="code_snippets_settings[editor][<?php echo $setting; ?>]"]' ).change( function () {
				editor.setOption( '<?php echo $att_name; ?>', $(this).is(':checked') );
			} );

						<?php break;
					case 'number': ?>

			$( 'input[name="code_snippets_settings[editor][<?php echo $setting; ?>]"]' ).change( function () {
				editor.setOption( '<?php echo $att_name; ?>', $(this).val() );
			} );

						<?php break;
				}
			}
		?>

		});

	}(jQuery));
	</script>

	<?php
}
