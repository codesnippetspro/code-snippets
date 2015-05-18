<?php

/**
 * This class holds the settings fields array
 * @since 2.1.0
 */
class Code_Snippets_Settings {

	private static $fields = array();
	private static $defaults = array();

	/**
	 * Retrieve the defined fields
	 * @return array
	 */
	public static function get_fields() {
		if ( empty( self::$fields ) ) {
			self::set_fields();
		}

		return self::$fields;
	}

	/**
	 * Retrieve the default values of the fields
	 * @return array the default field values, keyed by ID
	 */
	public static function get_defaults() {
		if ( empty( self::$defaults ) ) {
			self::set_defaults();
		}

		return self::$defaults;
	}

	/**
	 * Loop through the settings fields and extract the default values
	 */
	private static function set_defaults() {

		foreach ( self::get_fields() as $section_id => $section_fields ) {
			self::$defaults[ $section_id ] = wp_list_pluck( $section_fields, 'default', 'id' );
		}
	}

	private static function set_fields() {

		self::$fields['general'] = array(
			array(
				'id' => 'activate_by_default',
				'name' => __( 'Activate by Default', 'code-snippets' ),
				'type' => 'checkbox',
				'label' => __( "Make the 'Save and Activate' button the default action when saving a snippet.", 'code-snippets' ),
				'default' => false,
			),

			array(
				'id' => 'snippet_scope_enabled',
				'name' => __( 'Enable Scope Selector', 'code-snippets' ),
				'type' => 'checkbox',
				'label' => __( 'Enable the scope selector when editing a snippet', 'code-snippets' ),
				'default' => true,
			),
		);

		/* Description Editor settings section */
		self::$fields['description_editor'] = array(

			array(
				'id' => 'rows',
				'name' => __( 'Row Height', 'code-snippets' ),
				'type' => 'number',
				'label' => __( 'rows', 'code-snippets' ),
				'default' => 5,
				'min' => 0,
			),

			array(
				'id' => 'use_full_mce',
				'name' => __( 'Use Full Editor', 'code-snippets' ),
				'type' => 'checkbox',
				'label' => __( 'Enable all features of the visual editor', 'code-snippets' ),
				'default' => false,
			),

			array(
				'id' => 'media_buttons',
				'name' => __( 'Media Buttons', 'code-snippets' ),
				'type' => 'checkbox',
				'label' => __( 'Enable the add media buttons', 'code-snippets' ),
				'default' => false,
			),
		);

		/* Code Editor settings section */

		self::$fields['editor'] = array(
			array(
				'id' => 'theme',
				'name' => __( 'Theme', 'code-snippets' ),
				'type' => 'codemirror_theme_select',
				'default' => 'default',
				'codemirror' => 'theme',
			),

			array(
				'id' => 'indent_with_tabs',
				'name' => __( 'Indent With Tabs', 'code-snippets' ),
				'type' => 'checkbox',
				'label' => __( 'Use hard tabs (not spaces) for indentation.', 'code-snippets' ),
				'default' => true,
				'codemirror' => 'indentWithTabs',
			),

			array(
				'id' => 'tab_size',
				'name' => __( 'Tab Size', 'code-snippets' ),
				'type' => 'number',
				'desc' => __( 'The width of a tab character.', 'code-snippets' ),
				'default' => 4,
				'codemirror' => 'tabSize',
				'min' => 0,
			),

			array(
				'id' => 'indent_unit',
				'name' => __( 'Indent Unit', 'code-snippets' ),
				'type' => 'number',
				'desc' => __( 'How many spaces a block should be indented.', 'code-snippets' ),
				'default' => 2,
				'codemirror' => 'indentUnit',
				'min' => 0,
			),

			array(
				'id' => 'wrap_lines',
				'name' => __( 'Wrap Lines', 'code-snippets' ),
				'type' => 'checkbox',
				'label' => __( 'Whether the editor should scroll or wrap for long lines.', 'code-snippets' ),
				'default' => true,
				'codemirror' => 'lineWrapping',
			),

			array(
				'id' => 'line_numbers',
				'name' => __( 'Line Numbers', 'code-snippets' ),
				'type' => 'checkbox',
				'label' => __( 'Show line numbers to the left of the editor.', 'code-snippets' ),
				'default' => true,
				'codemirror' => 'lineNumbers',
			),

			array(
				'id' => 'auto_close_brackets',
				'name' => __( 'Auto Close Brackets', 'code-snippets' ),
				'type' => 'checkbox',
				'label' => __( 'Auto-close brackets and quotes when typed.', 'code-snippets' ),
				'default' => true,
				'codemirror' => 'autoCloseBrackets',
			),

			array(
				'id' => 'highlight_selection_matches',
				'name' => __( 'Highlight Selection Matches', 'code-snippets' ),
				'label' => __( 'Highlight all instances of a currently selected word.', 'code-snippets' ),
				'type' => 'checkbox',
				'default' => true,
				'codemirror' => 'highlightSelectionMatches',
			),
		);

		self::$fields = apply_filters( 'code_snippets_settings_fields', self::$fields );
	}
}
