<?php

namespace Code_Snippets\Admin\Menus;

use Code_Snippets\Integration\Evaluate_Functions;
use function Code_Snippets\code_snippets;
use function Code_Snippets\get_snippets;
use function Code_Snippets\Settings\get_setting;
use function Code_Snippets\Utils\enqueue_code_preview_editor;
use const Code_Snippets\PLUGIN_FILE;
use const Code_Snippets\PLUGIN_VERSION;

/**
 * Enqueues assets and builds localized data for the manage menu.
 */
class Manage_Menu_Assets {

	/**
	 * Manage menu Screen Options.
	 *
	 * @var Manage_Menu_Screen_Options
	 */
	private Manage_Menu_Screen_Options $screen_options;

	/**
	 * Snippet type counter.
	 *
	 * @var Snippet_Type_Counter
	 */
	private Snippet_Type_Counter $type_counter;

	/**
	 * Class constructor.
	 *
	 * @param Manage_Menu_Screen_Options $screen_options Manage menu Screen Options.
	 * @param Snippet_Type_Counter       $type_counter   Snippet type counter.
	 */
	public function __construct(
		Manage_Menu_Screen_Options $screen_options,
		Snippet_Type_Counter $type_counter
	) {
		$this->screen_options = $screen_options;
		$this->type_counter = $type_counter;
	}

	/**
	 * Enqueue manage assets and localize their runtime data.
	 *
	 * @param string[] $script_dependencies JavaScript dependencies.
	 * @param string[] $style_dependencies  CSS dependencies.
	 *
	 * @return void
	 */
	public function enqueue( array $script_dependencies, array $style_dependencies ): void {
		wp_enqueue_style(
			Manage_Menu::CSS_HANDLE,
			plugins_url( 'dist/manage.css', PLUGIN_FILE ),
			$style_dependencies,
			PLUGIN_VERSION
		);

		wp_enqueue_script(
			Manage_Menu::JS_HANDLE,
			plugins_url( 'dist/manage.js', PLUGIN_FILE ),
			$script_dependencies,
			PLUGIN_VERSION,
			[ 'in_footer' => true ]
		);

		enqueue_code_preview_editor( 'php' );
		wp_set_script_translations( Manage_Menu::JS_HANDLE, 'code-snippets' );
		code_snippets()->localize_script( Manage_Menu::JS_HANDLE );

		$localized = [
			'hasNetworkCap'        => current_user_can( code_snippets()->get_network_cap_name() ),
			'hiddenColumns'        => $this->screen_options->get_hidden_columns(),
			'truncateRowValues'    => (int) $this->screen_options->should_truncate_rows(),
			'snippetsPerPage'      => Manage_Menu::get_snippets_per_page(),
			'cloudSearchPerPage'   => Manage_Menu::get_cloud_search_per_page(),
			'isSafeModeActive'     => Evaluate_Functions::is_safe_mode_active(),
			'bulkDownloadNonce'    => wp_create_nonce( 'code_snippets_bulk_download' ),
			'supportsZipDownloads' => class_exists( 'ZipArchive' ),
			'editorTheme'          => get_setting( 'editor', 'theme' ),
			'typeCounts'           => $this->type_counter->count(),
		];

		if ( $this->screen_options->is_manage_table_view() ) {
			$snippets = get_snippets();
			$inline_limit = max( 0, intval( apply_filters( 'code_snippets/manage/inline_snippets_limit', 100 ) ) );

			// Full code bodies make this payload grow with the size of the snippet library.
			if ( 0 < $inline_limit && count( $snippets ) <= $inline_limit ) {
				$localized['snippetsList'] = array_map(
					function ( $snippet ) {
						return $snippet->get_fields();
					},
					$snippets
				);
			}
		}

		wp_localize_script( Manage_Menu::JS_HANDLE, 'CODE_SNIPPETS_MANAGE', $localized );
	}
}
