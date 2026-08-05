<?php

namespace Code_Snippets\Admin\Menus;

use Code_Snippets\Admin\Contextual_Help;
use Code_Snippets\Model\Snippet;
use WP_Screen;
use function Code_Snippets\code_snippets;
use function Code_Snippets\get_all_snippet_tags;
use function Code_Snippets\get_snippet;
use function Code_Snippets\Settings\get_setting;
use function Code_Snippets\Settings\get_settings_values;
use function Code_Snippets\Utils\enqueue_code_editor;
use const Code_Snippets\PLUGIN_FILE;
use const Code_Snippets\PLUGIN_VERSION;

/**
 * This class handles the add/edit menu.
 */
class Edit_Menu extends Admin_Menu {

	/**
	 * Handle for JavaScript asset file.
	 */
	private const JS_HANDLE = 'code-snippets-edit-menu';

	/**
	 * Handle for CSS asset file.
	 */
	private const CSS_HANDLE = 'code-snippets-edit';

	/**
	 * The snippet object currently being edited
	 *
	 * @var Snippet|null
	 * @see Edit_Menu::load_snippet_data()
	 */
	protected ?Snippet $snippet = null;

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct(
			'edit',
			_x( 'Edit Snippet', 'menu label', 'code-snippets' ),
			__( 'Edit Snippet', 'code-snippets' )
		);

		add_action( 'current_screen', array( $this, 'maybe_hide_menu_item' ) );

		$this->remove_debug_bar_codemirror();
	}

	/**
	 * Register the admin menu
	 *
	 * @return void
	 */
	public function register() {
		parent::register();

		// Create New Snippet menu.
		$this->add_menu(
			code_snippets()->get_menu_slug( 'add' ),
			_x( 'Add New', 'menu label', 'code-snippets' ),
			__( 'Create New Snippet', 'code-snippets' )
		);
	}

	/**
	 * Retrieve every hookname registered by this menu, including the separate
	 * "Add New" page, so screen-based checks recognise both editor views.
	 *
	 * @return string[]
	 */
	public function get_hooknames(): array {
		return [
			$this->get_hookname(),
			get_plugin_page_hookname( code_snippets()->get_menu_slug( 'add' ), $this->base_slug ),
		];
	}

	/**
	 * Hide the static Edit Snippet menu item unless a specific snippet is being edited.
	 *
	 * @param WP_Screen $screen Current admin screen.
	 *
	 * @return void
	 */
	public function maybe_hide_menu_item( WP_Screen $screen ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin menu context.
		$current_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$edit_hook  = get_plugin_page_hookname( $this->slug, $this->base_slug );
		$edit_hook .= $screen->in_admin( 'network' ) ? '-network' : '';

		if ( ( $screen->id === $edit_hook || $screen->base === $edit_hook ) && 0 < $current_id ) {
			return;
		}

		remove_submenu_page( $this->base_slug, $this->slug );
	}

	/**
	 * Executed when the menu is loaded.
	 *
	 * @return void
	 */
	public function load() {
		parent::load();

		$this->load_snippet_data();
		$this->ensure_correct_page();

		$contextual_help = new Contextual_Help( 'edit' );
		$contextual_help->load();
	}

	/**
	 * Disallow vising the Edit Snippet page without a valid ID.
	 *
	 * @return void
	 */
	protected function ensure_correct_page() {
		$screen = get_current_screen();
		$edit_hook = get_plugin_page_hookname( $this->slug, $this->base_slug );
		$edit_hook .= $screen->in_admin( 'network' ) ? '-network' : '';

		// Disallow visiting the edit snippet page without a valid ID.
		if (
			$screen->base === $edit_hook
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			&& ( empty( $_REQUEST['id'] ) || 0 === $this->snippet->id || null === $this->snippet->id )
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			&& ! isset( $_REQUEST['preview'] )
		) {
			wp_safe_redirect( code_snippets()->get_menu_url( 'add' ) );
			exit;
		}
	}

	/**
	 * Render the edit menu interface.
	 *
	 * @return void
	 */
	public function render() {
		echo '<div id="edit-snippet-container" class="wrap"></div>';
	}

	/**
	 * Load the data for the snippet currently being edited.
	 */
	public function load_snippet_data() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$edit_id = isset( $_REQUEST['id'] ) ? absint( $_REQUEST['id'] ) : 0;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$edit_type = isset( $_REQUEST['type'] ) ? sanitize_key( wp_unslash( $_REQUEST['type'] ) ) : '';

		$this->snippet = get_snippet( $edit_id );

		if ( 0 === $edit_id && $edit_type !== $this->snippet->type ) {
			$default_scopes = [
				'php'  => 'global',
				'css'  => 'site-css',
				'html' => 'content',
				'js'   => 'site-head-js',
				'cond' => 'condition',
			];

			if ( isset( $default_scopes[ $edit_type ] ) ) {
				$this->snippet->scope = $default_scopes[ $edit_type ];
			}
		}

		$this->snippet = apply_filters( 'code_snippets/admin/load_snippet_data', $this->snippet );
	}

	/**
	 * Enqueue assets for the edit menu
	 *
	 * @return void
	 */
	public function enqueue_assets() {

		$settings = get_settings_values();
		$tags_enabled = $settings['general']['enable_tags'];
		$desc_enabled = $settings['general']['enable_description'];

		enqueue_code_editor( $this->snippet->type );

		$plugin_dir = plugin_dir_path( PLUGIN_FILE );
		$css_version = filemtime( $plugin_dir . 'dist/edit.css' );
		$js_version = filemtime( $plugin_dir . 'dist/edit.js' );

		wp_enqueue_style(
			self::CSS_HANDLE,
			plugins_url( 'dist/edit.css', PLUGIN_FILE ),
			[
				'code-editor',
				'wp-components',
			],
			false !== $css_version ? $css_version : PLUGIN_VERSION
		);

		wp_enqueue_script(
			self::JS_HANDLE,
			plugins_url( 'dist/edit.js', PLUGIN_FILE ),
			[ 'code-snippets-code-editor' ] + self::$script_deps,
			false !== $js_version ? $js_version : PLUGIN_VERSION,
			[ 'in_footer' => true ]
		);

		wp_set_script_translations( self::JS_HANDLE, 'code-snippets' );

		if ( $desc_enabled ) {
			remove_editor_styles();
			wp_enqueue_editor();
		}

		code_snippets()->localize_script( self::JS_HANDLE );

		wp_localize_script(
			self::JS_HANDLE,
			'CODE_SNIPPETS_EDIT',
			[
				'snippet'           => $this->snippet->get_fields(),
				'activateByDefault' => get_setting( 'general', 'activate_by_default' ),
				'editorTheme'       => get_setting( 'editor', 'theme' ),
				'enableDownloads'   => apply_filters( 'code_snippets/enable_downloads', true ),
				'enableDescription' => $desc_enabled,
				'tagOptions'        => apply_filters(
					'code_snippets/tag_editor_options',
					[
						'enabled'       => $tags_enabled,
						'allowSpaces'   => true,
						'availableTags' => $tags_enabled ? get_all_snippet_tags() : [],
					]
				),
				'descEditorOptions' => [
					'rows' => $settings['general']['visual_editor_rows'],
				],
			]
		);
	}

	/**
	 * Remove the old CodeMirror version used by the Debug Bar Console plugin that is messing up the snippet editor.
	 */
	public function remove_debug_bar_codemirror() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		// Try to discern if we are on the single snippet page as good as we can at this early time.
		$is_codemirror_page =
			is_admin() && 'admin.php' === $GLOBALS['pagenow'] && $current_page && (
				code_snippets()->get_menu_slug( 'edit' ) === $current_page ||
				code_snippets()->get_menu_slug( 'settings' ) === $current_page
			);

		if ( $is_codemirror_page ) {
			remove_action( 'debug_bar_enqueue_scripts', 'debug_bar_console_scripts' );
		}
	}
}
