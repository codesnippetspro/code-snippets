<?php

namespace Code_Snippets\Admin\Menus;

use Code_Snippets\Admin\Contextual_Help;
use Code_Snippets\Model\Snippet;
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

		$this->remove_debug_bar_codemirror();
	}

	/**
	 * Register the admin menu
	 *
	 * @return void
	 */
	public function register() {
		// The page itself is always registered outside the menu, so nothing that
		// reads the menu during `admin_menu` — menu editors and role managers
		// among them — ever sees it. Registering it in the menu and removing it
		// afterwards is why a page that never renders in the menu could still end
		// up in someone's saved menu.
		$this->register_without_menu_item();

		$snippet_id = $this->get_requested_snippet_id();

		if ( $snippet_id ) {
			$this->add_current_snippet_menu_item( $snippet_id );
		}

		// Create New Snippet menu.
		$this->add_menu(
			code_snippets()->get_menu_slug( 'add' ),
			_x( 'Add New', 'menu label', 'code-snippets' ),
			__( 'Create New Snippet', 'code-snippets' )
		);
	}

	/**
	 * The snippet this request is editing, if any.
	 *
	 * Read from the request rather than the current screen, because this runs
	 * during `admin_menu`, before the screen is known.
	 *
	 * @return int Snippet ID, or 0 when not editing a specific snippet.
	 */
	private function get_requested_snippet_id(): int {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only routing parameter.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		if ( $page !== $this->slug ) {
			return 0;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only routing parameter.
		return isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	}

	/**
	 * Register the edit page without placing it in the admin menu.
	 *
	 * @return void
	 */
	private function register_without_menu_item(): void {
		$hook = add_submenu_page(
			'',
			$this->title,
			$this->label,
			code_snippets()->get_cap(),
			$this->slug,
			array( $this, 'render' )
		);

		if ( $hook ) {
			add_action( 'load-' . $hook, array( $this, 'load' ) );
		}
	}

	/**
	 * Mark the snippet being edited in the menu.
	 *
	 * Added as a plain link rather than a second registration of the page: a
	 * submenu slug doubles as the identifier WordPress checks permissions
	 * against, so carrying the ID in the slug would make `page=edit-snippet`
	 * match nothing and the screen would refuse to load.
	 *
	 * @param int $snippet_id Snippet being edited.
	 *
	 * @return void
	 */
	private function add_current_snippet_menu_item( int $snippet_id ): void {
		add_submenu_page(
			$this->base_slug,
			$this->title,
			$this->label,
			code_snippets()->get_cap(),
			add_query_arg(
				[
					'page' => $this->slug,
					'id'   => $snippet_id,
				],
				'admin.php'
			),
			'',
			1
		);
	}

	/**
	 * Retrieve the hookname of the edit page.
	 *
	 * The page is registered without a parent, so WordPress files it under
	 * "admin_page_" rather than under the Snippets menu; deriving it from the
	 * menu slug would name a screen that does not exist.
	 *
	 * @return string
	 */
	public function get_hookname(): string {
		return get_plugin_page_hookname( $this->slug, '' );
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
		$screen    = get_current_screen();
		$edit_hook = $this->get_hookname() . ( $screen->in_admin( 'network' ) ? '-network' : '' );

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

		wp_enqueue_style(
			self::CSS_HANDLE,
			plugins_url( 'dist/edit.css', PLUGIN_FILE ),
			[
				'code-editor',
				'wp-components',
			],
			PLUGIN_VERSION
		);

		wp_enqueue_script(
			self::JS_HANDLE,
			plugins_url( 'dist/edit.js', PLUGIN_FILE ),
			array_merge( [ 'code-snippets-code-editor' ], self::$script_deps ),
			PLUGIN_VERSION,
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
