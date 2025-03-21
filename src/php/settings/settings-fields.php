<?php
/**
 * Manages the settings field definitions.
 *
 * @package    Code_Snippets
 * @subpackage Settings
 */

namespace Code_Snippets\Settings;

use function Code_Snippets\code_snippets;

/**
 * Retrieve the default setting values
 *
 * @return array<string, array<string, mixed>>
 */
function get_default_settings(): array {
	static $defaults;

	if ( isset( $defaults ) ) {
		return $defaults;
	}

	$defaults = [
		'general' => [
			'activate_by_default' => true,
			'enable_tags'         => true,
			'enable_description'  => true,
			'visual_editor_rows'  => 5,
			'list_order'          => 'priority-asc',
			'disable_prism'       => false,
			'hide_upgrade_menu'   => false,
			'complete_uninstall'  => false,
		],
		'editor'  => [
			'indent_with_tabs'            => true,
			'tab_size'                    => 4,
			'indent_unit'                 => 4,
			'wrap_lines'                  => true,
			'code_folding'                => true,
			'line_numbers'                => true,
			'auto_close_brackets'         => true,
			'highlight_selection_matches' => true,
			'highlight_active_line'       => true,
			'keymap'                      => 'default',
			'theme'                       => 'default',
		],
	];

	$defaults = apply_filters( 'code_snippets_settings_defaults', $defaults );

	return $defaults;
}

/**
 * Retrieve the settings fields
 *
 * @return array<string, array<string, array>>
 */
function get_settings_fields(): array {
	static $fields;

	if ( isset( $fields ) ) {
		return $fields;
	}

	$fields = [];

	$fields['debug'] = [
		'database_update' => [
			'name'  => __( 'Database Table Upgrade', 'code-snippets' ),
			'type'  => 'action',
			'label' => __( 'Upgrade Database Table', 'code-snippets' ),
			'desc'  => __( 'Use this button to manually upgrade the Code Snippets database table. This action will only affect the snippets table and should be used only when necessary.', 'code-snippets' ),
		],
		'reset_caches'    => [
			'name' => __( 'Reset Caches', 'code-snippets' ),
			'type' => 'action',
			'desc' => __( 'Use this button to manually clear snippets caches.', 'code-snippets' ),
		],
	];

	$fields['general'] = [
		'activate_by_default' => [
			'name'  => __( 'Activate by Default', 'code-snippets' ),
			'type'  => 'checkbox',
			'label' => __( "Make the 'Save and Activate' button the default action when saving a snippet.", 'code-snippets' ),
		],
		'enable_tags'         => [
			'name'  => __( 'Enable Snippet Tags', 'code-snippets' ),
			'type'  => 'checkbox',
			'label' => __( 'Show snippet tags on admin pages.', 'code-snippets' ),
		],
		'enable_description'  => [
			'name'  => __( 'Enable Snippet Descriptions', 'code-snippets' ),
			'type'  => 'checkbox',
			'label' => __( 'Show snippet descriptions on admin pages.', 'code-snippets' ),
		],
		'visual_editor_rows'  => [
			'name'  => __( 'Description Editor Height', 'code-snippets' ),
			'type'  => 'number',
			'label' => _x( 'rows', 'unit', 'code-snippets' ),
			'min'   => 0,
		],
		'list_order'          => [
			'name'    => __( 'Snippets List Order', 'code-snippets' ),
			'type'    => 'select',
			'desc'    => __( 'Default way to order snippets on the All Snippets admin menu.', 'code-snippets' ),
			'options' => [
				'priority-asc'  => __( 'Priority', 'code-snippets' ),
				'name-asc'      => __( 'Name (A-Z)', 'code-snippets' ),
				'name-desc'     => __( 'Name (Z-A)', 'code-snippets' ),
				'modified-desc' => __( 'Modified (latest first)', 'code-snippets' ),
				'modified-asc'  => __( 'Modified (oldest first)', 'code-snippets' ),
			],
		],
		'disable_prism'       => [
			'name'  => __( 'Disable Syntax Highlighter', 'code-snippets' ),
			'type'  => 'checkbox',
			'label' => __( 'Disable syntax highlighting when displaying snippet code on the front-end.', 'code-snippets' ),
		],
	];

	if ( ! code_snippets()->licensing->is_licensed() ) {
		$fields['general']['hide_upgrade_menu'] = [
			'name'  => __( 'Hide Upgrade Menu', 'code-snippets' ),
			'type'  => 'checkbox',
			'label' => __( 'Hide the Upgrade button from the admin menu.', 'code-snippets' ),
		];
	}

	if ( ! is_multisite() || is_main_site() ) {
		$fields['general']['complete_uninstall'] = [
			'name'  => __( 'Complete Uninstall', 'code-snippets' ),
			'type'  => 'checkbox',
			'label' => __( 'When the plugin is deleted from the Plugins menu, also delete all snippets and plugin settings.', 'code-snippets' ),
		];
	}

	$fields['editor'] = [
		'indent_with_tabs'            => [
			'name'       => __( 'Indent With Tabs', 'code-snippets' ),
			'type'       => 'checkbox',
			'label'      => __( 'Use hard tabs instead of spaces for indentation.', 'code-snippets' ),
			'codemirror' => 'indentWithTabs',
		],
		'tab_size'                    => [
			'name'       => __( 'Tab Size', 'code-snippets' ),
			'type'       => 'number',
			'desc'       => __( 'The width of a tab character.', 'code-snippets' ),
			'label'      => _x( 'spaces', 'unit', 'code-snippets' ),
			'codemirror' => 'tabSize',
			'min'        => 0,
		],
		'indent_unit'                 => [
			'name'       => __( 'Indent Unit', 'code-snippets' ),
			'type'       => 'number',
			'desc'       => __( 'The number of spaces to indent a block.', 'code-snippets' ),
			'label'      => _x( 'spaces', 'unit', 'code-snippets' ),
			'codemirror' => 'indentUnit',
			'min'        => 0,
		],
		'wrap_lines'                  => [
			'name'       => __( 'Wrap Lines', 'code-snippets' ),
			'type'       => 'checkbox',
			'label'      => __( 'Soft-wrap long lines of code instead of horizontally scrolling.', 'code-snippets' ),
			'codemirror' => 'lineWrapping',
		],
		'code_folding'                => [
			'name'       => __( 'Code Folding', 'code-snippets' ),
			'type'       => 'checkbox',
			'label'      => __( 'Allow folding functions or other blocks into a single line.', 'code-snippets' ),
			'codemirror' => 'foldGutter',
		],
		'line_numbers'                => [
			'name'       => __( 'Line Numbers', 'code-snippets' ),
			'type'       => 'checkbox',
			'label'      => __( 'Show line numbers to the left of the editor.', 'code-snippets' ),
			'codemirror' => 'lineNumbers',
		],
		'auto_close_brackets'         => [
			'name'       => __( 'Auto Close Brackets', 'code-snippets' ),
			'type'       => 'checkbox',
			'label'      => __( 'Auto-close brackets and quotes when typed.', 'code-snippets' ),
			'codemirror' => 'autoCloseBrackets',
		],
		'highlight_selection_matches' => [
			'name'       => __( 'Highlight Selection Matches', 'code-snippets' ),
			'label'      => __( 'Highlight all instances of a currently selected word.', 'code-snippets' ),
			'type'       => 'checkbox',
			'codemirror' => 'highlightSelectionMatches',
		],
		'highlight_active_line'       => [
			'name'       => __( 'Highlight Active Line', 'code-snippets' ),
			'label'      => __( 'Highlight the line that is currently being edited.', 'code-snippets' ),
			'type'       => 'checkbox',
			'codemirror' => 'styleActiveLine',
		],
		'keymap'                      => [
			'name'       => __( 'Keymap', 'code-snippets' ),
			'type'       => 'select',
			'desc'       => __( 'The set of keyboard shortcuts to use in the code editor.', 'code-snippets' ),
			'options'    => [
				'default' => __( 'Default', 'code-snippets' ),
				'vim'     => __( 'Vim', 'code-snippets' ),
				'emacs'   => __( 'Emacs', 'code-snippets' ),
				'sublime' => __( 'Sublime Text', 'code-snippets' ),
			],
			'codemirror' => 'keyMap',
		],
		'theme'                       => [
			'name'       => __( 'Theme', 'code-snippets' ),
			'type'       => 'select',
			'options'    => get_editor_theme_list(),
			'codemirror' => 'theme',
		],
	];

	$fields = apply_filters( 'code_snippets_settings_fields', $fields );

	return $fields;
}
