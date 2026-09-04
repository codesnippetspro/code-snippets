<?php

namespace Code_Snippets\Settings;

use function Code_Snippets\code_snippets;

/**
 * Plugin settings fields definitions.
 *
 * This class defines the settings fields and their default values for the Code Snippets plugin.
 * It provides methods to retrieve the settings fields and default values.
 *
 * @package Code_Snippets\Settings
 */
class Settings_Fields {

	/**
	 * Instance of this class.
	 *
	 * @var Settings_Fields
	 */
	private static Settings_Fields $instance;

	/**
	 * The settings fields definitions.
	 *
	 * @var array<string, array<string, array>>
	 */
	private array $fields;

	/**
	 * The default settings values.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $defaults;

	/**
	 * Constructor.
	 *
	 * Initializes the settings fields and default values.
	 */
	public function __construct() {
		$this->init_fields();
		$this->init_defaults();
	}

	/**
	 * Retrieve the instance of this class.
	 *
	 * @return Settings_Fields
	 */
	private static function get_instance(): Settings_Fields {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Retrieve the default setting values
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_default_values(): array {
		return self::get_instance()->defaults;
	}

	/**
	 * Retrieve the settings fields.
	 *
	 * @return array<string, array<string, array>>
	 */
	public static function get_field_definitions(): array {
		return self::get_instance()->fields;
	}

	/**
	 * Initialise default settings values.
	 *
	 * @return void
	 */
	private function init_defaults() {
		$this->defaults = [
			'general'        => [
				'activate_by_default'     => true,
				'enable_tags'             => true,
				'enable_description'      => true,
				'visual_editor_rows'      => 5,
				'list_order'              => 'priority-asc',
				'disable_prism'           => false,
				'hide_upgrade_menu'       => false,
				'complete_uninstall'      => false,
				'enable_flat_files'       => false,
				'enable_admin_bar'        => true,
				'admin_bar_snippet_limit' => 20,
			],
			'editor'         => [
				'indent_with_tabs'            => true,
				'tab_size'                    => 4,
				'indent_unit'                 => 4,
				'font_size'                   => 14,
				'wrap_lines'                  => true,
				'code_folding'                => true,
				'line_numbers'                => true,
				'auto_close_brackets'         => true,
				'highlight_selection_matches' => true,
				'highlight_active_line'       => true,
				'keymap'                      => 'default',
				'theme'                       => 'default',
			],
			'version-switch' => [
				'selected_version' => '',
			],
		];

		$this->defaults = apply_filters( 'code_snippets_settings_defaults', $this->defaults );
	}

	/**
	 * Initialise the settings fields values.
	 *
	 * @return void
	 */
	private function init_fields() {
		$this->fields = [];

		$this->fields['debug'] = [
			'database_update'       => [
				'name'  => __( 'Database Table Upgrade', 'code-snippets' ),
				'type'  => 'action',
				'label' => __( 'Upgrade Database Table', 'code-snippets' ),
				'desc'  => __( 'Use this button to manually upgrade the Code Snippets database table. This action will only affect the snippets table and should be used only when necessary.', 'code-snippets' ),
			],
			'reset_caches'          => [
				'name' => __( 'Reset Caches', 'code-snippets' ),
				'type' => 'action',
				'desc' => __( 'Use this button to manually clear snippets caches. Worth doing before switching to an older version of the plugin, if your site uses a persistent object cache.', 'code-snippets' ),
			],
		];

		$this->fields['version-switch'] = [
			'version_switcher' => [
				'name'            => __( 'Switch Version', 'code-snippets' ),
				'type'            => 'callback',
				'render_callback' => [ '\\Code_Snippets\\Settings\\Version_Switch', 'render_version_switch_field' ],
			],
			'refresh_versions' => [
				'name'            => __( 'Refresh Versions', 'code-snippets' ),
				'type'            => 'callback',
				'render_callback' => [ '\\Code_Snippets\\Settings\\Version_Switch', 'render_refresh_versions_field' ],
			],
			'version_warning'  => [
				'name'            => '',
				'type'            => 'callback',
				'render_callback' => [ '\\Code_Snippets\\Settings\\Version_Switch', 'render_version_switch_warning' ],
			],
		];

		$this->fields['general'] = [
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
			$this->fields['general']['hide_upgrade_menu'] = [
				'name'  => __( 'Hide Upgrade Notices', 'code-snippets' ),
				'type'  => 'checkbox',
				'label' => __( 'Hide notices inviting you to upgrade to Code Snippets Pro.', 'code-snippets' ),
			];
		}

		if ( ! is_multisite() || is_main_site() ) {
			$this->fields['general']['complete_uninstall'] = [
				'name'  => __( 'Complete Uninstall', 'code-snippets' ),
				'type'  => 'checkbox',
				'label' => __( 'When the plugin is deleted from the Plugins menu, also delete all snippets and plugin settings.', 'code-snippets' ),
			];
		}

		$this->fields['general']['enable_admin_bar'] = [
			'name'  => __( 'Enable Admin Bar Menu', 'code-snippets' ),
			'type'  => 'checkbox',
			'label' => __( 'Show a Snippets menu in the admin bar for quick access to snippets.', 'code-snippets' ),
		];

		$this->fields['general']['admin_bar_snippet_limit'] = [
			'name'  => __( 'Admin Bar Snippets Per Page', 'code-snippets' ),
			'type'  => 'number',
			'desc'  => __( 'Number of snippets to show in the admin bar Active/Inactive menus before paginating.', 'code-snippets' ),
			'label' => __( 'snippets', 'code-snippets' ),
			'min'   => 1,
			'max'   => 100,
			'show_if' => [
				'section' => 'general',
				'field'   => 'enable_admin_bar',
				'value'   => true,
			],
		];

		$this->fields['editor'] = [
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
			'font_size'                   => [
				'name'       => __( 'Font Size', 'code-snippets' ),
				'type'       => 'number',
				'label'      => _x( 'px', 'unit', 'code-snippets' ),
				'codemirror' => 'fontSize',
				'min'        => 8,
				'max'        => 28,
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
				'options'    => Editor_Preview::get_editor_theme_list(),
				'codemirror' => 'theme',
			],
		];

		$this->fields = apply_filters( 'code_snippets_settings_fields', $this->fields );
	}
}
