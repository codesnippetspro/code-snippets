<?php

namespace Code_Snippets\Admin\Menus\Manage;

use Code_Snippets\Integration\Evaluate_Functions;
use Code_Snippets\Model\Snippet;
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
		wp_enqueue_style(
			self::CSS_HANDLE,
			plugins_url( 'dist/manage.css', PLUGIN_FILE ),
			$style_dependencies,
			PLUGIN_VERSION
		);

		wp_enqueue_script(
			self::JS_HANDLE,
			plugins_url( 'dist/manage.js', PLUGIN_FILE ),
			$script_dependencies,
			PLUGIN_VERSION,
			[ 'in_footer' => true ]
		);

		enqueue_code_preview_editor( 'php' );
		wp_set_script_translations( self::JS_HANDLE, 'code-snippets' );
		code_snippets()->localize_script( self::JS_HANDLE );

		$snippets_per_page = Manage_Menu::get_snippets_per_page();

		$localized = [
			'hasNetworkCap'        => current_user_can( code_snippets()->get_network_cap_name() ),
			'hiddenColumns'        => $this->screen_options->get_hidden_columns(),
			'truncateRowValues'    => (int) $this->screen_options->should_truncate_rows(),
			'snippetsPerPage'      => $snippets_per_page,
			'cloudSearchPerPage'   => Manage_Menu::get_cloud_search_per_page(),
			'isSafeModeActive'     => Evaluate_Functions::is_safe_mode_active(),
			'bulkDownloadNonce'    => wp_create_nonce( 'code_snippets_bulk_download' ),
			'runOnceNonce'         => wp_create_nonce( Manage_Menu::RUN_ONCE_NONCE ),
			'supportsZipDownloads' => class_exists( 'ZipArchive' ),
			'editorTheme'          => get_setting( 'editor', 'theme' ),
			'typeCounts'           => $this->get_snippet_type_counts(),
			'listOrder'            => get_setting( 'general', 'list_order' ),
		];

		if ( $this->screen_options->is_manage_table_view() ) {
			// Full code bodies make this payload grow with the size of the snippet library.
			$snippets = array_map(
				function ( Snippet $snippet ) {
					$fields = $snippet->get_fields();
					$fields['code'] = '';
					// Match the REST response: a UTC value with no offset is read
					// as local time by the browser.
					$fields['modified'] = $snippet->modified_iso;
					return $fields;
				},
				get_snippets()
			);

			$inline_limit = apply_filters( 'code_snippets/manage/inline_snippets_limit', min( $snippets_per_page, 100 ) );
			$inline_limit = max( 0, intval( $inline_limit ) );

			if ( $inline_limit > 0 ) {
				$localized['snippetsList'] = array_slice( $snippets, 0, $inline_limit );
			}
		}

		wp_localize_script( self::JS_HANDLE, 'CODE_SNIPPETS_MANAGE', $localized );
	}
}
