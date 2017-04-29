<?php

/**
 * This file handles the editor preview setting
 *
 * @since 2.0
 * @package Code_Snippets
 */

/**
 * Load the CSS and JavaScript for the editor preview field
 *
 * @param string $hook The current page hook
 */
function code_snippets_editor_settings_preview_assets( $hook ) {

	/* Only load on the settings page */
	if ( code_snippets()->get_menu_hook( 'settings' ) !== $hook ) {
		return;
	}

	/* Enqueue scripts for the editor preview */
	code_snippets_enqueue_codemirror();

	/* Enqueue ALL themes */
	$themes = code_snippets_get_available_themes();

	foreach ( $themes as $theme ) {

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
	$themes_dir = plugin_dir_path( CODE_SNIPPETS_FILE ) . 'css/min/cmthemes/';
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

/**
 * Render the editor preview setting
 */
function code_snippets_settings_editor_preview() {

	$example_content = "
function example_custom_admin_footer_text( \$text ) {
	return 'Thank you for visiting <a href=\"' . get_home_url() . '\">' . get_bloginfo( 'name' ) . '</a>.';
}

add_filter( 'admin_footer_text', 'example_custom_admin_footer_text' );

";

	$atts = array(
		'mode' => 'text/x-php',
		'value' => $example_content,
	);

	?>

	<div style="max-width: 800px" id="code_snippets_editor_preview"></div>

	<script>
	(function () {
		'use strict';

		// Load CodeMirror
		var atts = [];
		atts = <?php echo code_snippets_get_editor_atts( $atts, true ); ?>;
		atts['viewportMargin'] = Infinity;

		var editor = CodeMirror(document.getElementById('code_snippets_editor_preview'), atts);

		// Dynamically change editor settings
		<?php

		/* Retrieve editor settings */
		$fields = code_snippets_get_settings_fields();
		$fields = $fields['editor'];

		foreach ( $fields as $setting => $field ) {

			/* Only output settings which have a CodeMirror attribute */
			if ( empty( $field['codemirror'] ) ) {
				continue;
			}

			$att_name = addslashes( $field['codemirror'] );

			switch ( $field['type'] ) {

				case 'codemirror_theme_select':	?>

		document.querySelector('select[name="code_snippets_settings[editor][<?php echo $setting; ?>]"]').onchange = function () {
			editor.setOption('<?php echo $att_name; ?>', this.options[this.selectedIndex].value);
		};

					<?php break;
				case 'checkbox': ?>

		document.querySelector('input[name="code_snippets_settings[editor][<?php echo $setting; ?>]"]').onchange = function () {
			editor.setOption('<?php echo $att_name; ?>', this.checked);
		};

					<?php break;
				case 'number': ?>

		document.querySelector('input[name="code_snippets_settings[editor][<?php echo $setting; ?>]"]').onchange = function () {
			editor.setOption( '<?php echo $att_name; ?>', this.value);
		};

					<?php break;
			}
		}

		?>
	}());
	</script>

	<?php
}
