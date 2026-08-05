<?php

namespace Code_Snippets\Admin\Menus\Manage;

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
	 * Handle for JavaScript asset file.
	 */
	private const JS_HANDLE = 'code-snippets-manage-menu';

	/**
	 * Handle for CSS asset file.
	 */
	private const CSS_HANDLE = 'code-snippets-manage';

	/**
	 * Manage menu Screen Options.
	 *
	 * @var Manage_Menu_Screen_Options
	 */
	private Manage_Menu_Screen_Options $screen_options;

	/**
	 * Class constructor.
	 *
	 * @param Manage_Menu_Screen_Options $screen_options Manage menu Screen Options.
	 */
	public function __construct( Manage_Menu_Screen_Options $screen_options ) {
		$this->screen_options = $screen_options;
	}

	/**
	 * Count non-trashed snippets by type.
	 *
	 * @return array<string, int> Map of type name to snippet count, including 'all'.
	 */
	private function get_snippet_type_counts(): array {
		$counts = [ 'all' => 0 ];

		foreach ( get_snippets() as $snippet ) {
			if ( $snippet->trashed ) {
				continue;
			}

			$counts[ $snippet->type ] = ( $counts[ $snippet->type ] ?? 0 ) + 1;
			++$counts['all'];
		}

		return $counts;
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
		$plugin_dir = plugin_dir_path( PLUGIN_FILE );
		$css_version = filemtime( $plugin_dir . 'dist/manage.css' );
		$js_version  = filemtime( $plugin_dir . 'dist/manage.js' );
		$css_version = false !== $css_version ? $css_version : PLUGIN_VERSION;
		$js_version  = false !== $js_version ? $js_version : PLUGIN_VERSION;

		wp_enqueue_style(
			self::CSS_HANDLE,
			plugins_url( 'dist/manage.css', PLUGIN_FILE ),
			$style_dependencies,
			$css_version
		);

		wp_enqueue_script(
			self::JS_HANDLE,
			plugins_url( 'dist/manage.js', PLUGIN_FILE ),
			$script_dependencies,
			$js_version,
			[ 'in_footer' => true ]
		);

		enqueue_code_preview_editor( 'php' );
		wp_set_script_translations( self::JS_HANDLE, 'code-snippets' );
		code_snippets()->localize_script( self::JS_HANDLE );

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
			'typeCounts'           => $this->get_snippet_type_counts(),
		];

		if ( $this->screen_options->is_manage_table_view() ) {
			$snippets = get_snippets();
			$inline_limit = max( 0, intval( apply_filters( 'code_snippets/manage/inline_snippets_limit', 100 ) ) );

			// Full code bodies make this payload grow with the size of the snippet library.
			if ( 0 < $inline_limit && count( $snippets ) <= $inline_limit ) {
				$localized['snippetsList'] = array_map(
					fn( $snippet ) => $snippet->get_fields(),
					$snippets
				);
			}
		}

		wp_localize_script( self::JS_HANDLE, 'CODE_SNIPPETS_MANAGE', $localized );
	}
}
