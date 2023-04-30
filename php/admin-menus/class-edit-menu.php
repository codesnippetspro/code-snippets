<?php

namespace Code_Snippets;

use Code_Snippets\REST_API\Snippets_REST_Controller;
use function Code_Snippets\Settings\get_setting;

/**
 * This class handles the add/edit menu
 */
class Edit_Menu extends Admin_Menu {

	/**
	 * The snippet object currently being edited
	 *
	 * @var Snippet
	 * @see Edit_Menu::load_snippet_data()
	 */
	protected $snippet = null;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			'edit',
			_x( 'Edit Snippet', 'menu label', 'code-snippets' ),
			__( 'Edit Snippet', 'code-snippets' )
		);
	}

	/**
	 * Register action and filter hooks
	 */
	public function run() {
		parent::run();
		$this->remove_debug_bar_codemirror();
	}

	/**
	 * Register the admin menu
	 *
	 * @return void
	 */
	public function register() {
		parent::register();

		// Only preserve the edit menu if we are currently editing a snippet.
		if ( ! isset( $_REQUEST['page'] ) || $_REQUEST['page'] !== $this->slug ) {
			remove_submenu_page( $this->base_slug, $this->slug );
		}

		// Add New Snippet menu.
		$this->add_menu(
			code_snippets()->get_menu_slug( 'add' ),
			_x( 'Add New', 'menu label', 'code-snippets' ),
			__( 'Add New Snippet', 'code-snippets' )
		);
	}

	/**
	 * Executed when the menu is loaded
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
		if ( $screen->base === $edit_hook && ( empty( $_REQUEST['id'] ) || 0 === $this->snippet->id || null === $this->snippet->id ) &&
		     ! isset( $_REQUEST['preview'] ) ) {
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
		printf(
			'<div id="edit-snippet-form-container">%s</div>',
			esc_html__( 'Loading edit page…', 'code-snippets' )
		);
	}

	/**
	 * Load the data for the snippet currently being edited.
	 */
	public function load_snippet_data() {
		$edit_id = isset( $_REQUEST['id'] ) ? absint( $_REQUEST['id'] ) : 0;

		$this->snippet = get_snippet( $edit_id );

		if ( 0 === $edit_id && isset( $_GET['type'] ) && sanitize_key( $_GET['type'] ) !== $this->snippet->type ) {
			$type = sanitize_key( $_GET['type'] );

			$default_scopes = [
				'php'  => 'global',
				'css'  => 'site-css',
				'html' => 'content',
				'js'   => 'site-head-js',
				'cond' => 'condition',
			];

			if ( isset( $default_scopes[ $type ] ) ) {
				$this->snippet->scope = $default_scopes[ $type ];
			}
		}

		$this->snippet = apply_filters( 'code_snippets/admin/load_snippet_data', $this->snippet );
	}

	/**
	 * Enqueue assets for the edit menu
	 */
	public function enqueue_assets() {
		$plugin = code_snippets();
		$rtl = is_rtl() ? '-rtl' : '';

		$settings = Settings\get_settings_values();
		$tags_enabled = $settings['general']['enable_tags'];
		$desc_enabled = $settings['general']['enable_description'];

		enqueue_code_editor( $this->snippet->type );

		$css_deps = [
			'code-editor',
			'wp-components',
		];

		$js_deps = [
			'code-snippets-code-editor',
			'react',
			'react-dom',
			'wp-url',
			'wp-i18n',
			'wp-api-fetch',
			'wp-components',
		];

		wp_enqueue_style(
			'code-snippets-edit',
			plugins_url( "dist/edit$rtl.css", $plugin->file ),
			$css_deps,
			$plugin->version
		);

		wp_enqueue_script(
			'code-snippets-edit-menu',
			plugins_url( 'dist/edit.js', $plugin->file ),
			$js_deps,
			$plugin->version,
			true
		);

		if ( $desc_enabled ) {
			remove_editor_styles();
			wp_enqueue_editor();
		}

		wp_localize_script(
			'code-snippets-edit-menu',
			'CODE_SNIPPETS_EDIT',
			[
				'isLicensed'        => false,
				'snippet'           => $this->snippet->get_fields(),
				'restAPI'           => [
					'base'     => esc_url_raw( rest_url() ),
					'snippets' => esc_url_raw( rest_url( Snippets_REST_Controller::get_base_route() ) ),
					'nonce'    => wp_create_nonce( 'wp_rest' ),
				],
				'addNewUrl'         => $plugin->get_menu_url( 'add' ),
				'pageTitleActions'  => $plugin->is_compact_menu() ? $this->page_title_action_links( [ 'manage', 'import', 'settings' ] ) : [],
				'isPreview'         => isset( $_REQUEST['preview'] ),
				'activateByDefault' => get_setting( 'general', 'activate_by_default' ),
				'editorTheme'       => get_setting( 'editor', 'theme' ),
				'extraSaveButtons'  => apply_filters( 'code_snippets/extra_save_buttons', true ),
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
		// Try to discern if we are on the single snippet page as good as we can at this early time.
		$is_codemirror_page =
			is_admin() && 'admin.php' === $GLOBALS['pagenow'] && isset( $_GET['page'] ) && (
				code_snippets()->get_menu_slug( 'edit' ) === $_GET['page'] ||
				code_snippets()->get_menu_slug( 'settings' ) === $_GET['page']
			);

		if ( $is_codemirror_page ) {
			remove_action( 'debug_bar_enqueue_scripts', 'debug_bar_console_scripts' );
		}
	}
}
