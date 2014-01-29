<?php

/**
 * This file handles the code snippets settings admin menu
 * @package Code_Snippets
 */

/**
 * Register the setting sub-menu
 *
 * @since 2.0
 * @access private
 *
 * @uses add_submenu_page() To register a sub-menu
 */
function code_snippets_add_settings_menu() {

	add_submenu_page(
		code_snippets_get_menu_slug(),
		__( 'Snippets Settings', 'code-snippets' ),
		__( 'Settings', 'code-snippets' ),
		get_snippets_cap(),
		code_snippets_get_menu_slug( 'settings' ),
		'code_snippets_render_settings_menu'
	);
}

add_action( 'admin_menu', 'code_snippets_add_settings_menu' );
add_action( 'network_admin_menu', 'code_snippets_add_settings_menu' );

function code_snippets_settings_init() {
	register_setting( 'code-snippets', 'code_snippets_settings', 'code_snippets_settings_validate' );

	add_settings_section(
		'code-snippets-editor',
		__( 'Code Editor', 'code-snippets' ),
		'__return_empty_string',
		'code-snippets'
	);

	add_settings_field(
		'code_snippets_codemirror_theme',
		__( 'Theme', 'code-snippets' ),
		'code_snippets_codemirror_theme_select_field',
		'code-snippets',
		'code-snippets-editor'
	);

	add_settings_field(
		'code_snippets_intent_with_tabs',
		__( 'Indent With Tabs', 'code-snippets' ),
		'code_snippets_setting_indent_with_tabs',
		'code-snippets',
		'code-snippets-editor'
	);

	add_settings_field(
		'code_snippets_editor_wrap_lines',
		__( 'Wrap Lines', 'code-snippets' ),
		'code_snippets_setting_editor_wrap_lines',
		'code-snippets',
		'code-snippets-editor'
	);

	if ( ! get_option( 'code_snippets_settings' ) ) {
		add_option(
			'code_snippets_settings',
			array(
				'editor' => array(
					'indent_with_tabs' => true,
					'theme'            => 'default',
					'wrap_lines'       => true,
				),
			)
		);
	}
}

add_action( 'admin_init', 'code_snippets_settings_init' );

function code_snippets_setting_editor_wrap_lines() {
	$options = get_option( 'code_snippets_settings' );

	echo '<input type="checkbox" name="code_snippets_settings[editor][wrap_lines]"' .
		checked( $options['editor']['wrap_lines'], true, false ) . '>';
}

function code_snippets_setting_indent_with_tabs() {
	$options = get_option( 'code_snippets_settings' );

	echo '<input type="checkbox" name="code_snippets_settings[editor][indent_with_tabs]"' .
		checked( $options['editor']['indent_with_tabs'], true, false ) . '>';
}

function code_snippets_codemirror_theme_select_field() {
	$options = get_option( 'code_snippets_settings' );

	echo '<select id="code_snippets_setting_editor_theme" name="code_snippets_settings[editor][theme]" />';

	/* Fetch all theme CSS files */
	$themes_dir = plugin_dir_path( CODE_SNIPPETS_FILE ) . 'vendor/codemirror/theme/';
	$themes = glob( $themes_dir . '*.css' );

	foreach ( $themes as $theme ) {

		/* Extract theme name from path */
		$theme = str_replace( $themes_dir, '', $theme );
		$theme = str_replace( '.css', '', $theme );

		if ( 'ambiance-mobile' === $theme ) {
			continue;
		}

		printf (
			'<option value="%1$s"%2$s>%1$s</option>',
			$theme,
			selected( $theme, $options['editor']['theme'], false )
		);
	}

	echo '</select>';
}

function code_snippets_settings_validate( $input ) {

	$output['editor']['indent_with_tabs'] = ( 'on' === $input['editor']['indent_with_tabs'] );
	$output['editor']['wrap_lines'] = ( 'on' === $input['editor']['wrap_lines'] );
	$output['editor']['theme'] = $input['editor']['theme'];

	/* Add an updated message */
	add_settings_error(
		'code-snippets-settings-notices',
		'settings-saved',
		__( 'Settings saved.', 'code-snippets' ),
		'updated'
	);

	return $output;
}

/**
 * Displays the settings menu
 *
 * @since 2.0
 */
function code_snippets_render_settings_menu() {
	?>
	<div class="wrap">

		<?php screen_icon(); ?>
		<h2><?php esc_html_e( 'Settings', 'code-snippets' ); ?></h2>

		<?php settings_errors( 'code-snippets-settings-notices' ); ?>

		<form action="options.php" method="post">
			<?php settings_fields( 'code-snippets' ); ?>
			<table class="form-table">
				<?php do_settings_sections( 'code-snippets' ); ?>
			</table>
			<?php submit_button(); ?>
		</form>

		<?php var_dump( get_option( 'code_snippets_settings' ) ); ?>

	</div>
	<?php
}
