<?php

namespace Code_Snippets\Admin\Menus\Insights;

use function Code_Snippets\code_snippets;
use const Code_Snippets\PLUGIN_FILE;
use const Code_Snippets\PLUGIN_VERSION;

/**
 * Enqueues assets and builds localized data for the Insights menu.
 */
class Insights_Menu_Assets {

	/**
	 * Handle for the Insights JavaScript asset.
	 */
	private const JS_HANDLE = 'code-snippets-insights';

	/**
	 * Handle for the Insights stylesheet.
	 */
	private const CSS_HANDLE = 'code-snippets-insights';

	/**
	 * Build an aggregate of saved snippets for the Insights screen.
	 *
	 * @return array{
	 *     active: int,
	 *     inactive: int,
	 *     typeCounts: array<string, array{label: string, count: int}>,
	 *     locationCounts: array<string, array{label: string, count: int}>,
	 *     tagCounts: array<string, array{label: string, count: int}>
	 * }
	 */
	public function get_summary(): array {
		return ( new Insights_Summary() )->get();
	}

	/**
	 * Enqueue Insights assets and localized runtime data.
	 *
	 * @param string[] $script_dependencies JavaScript dependencies.
	 * @param string[] $style_dependencies  Stylesheet dependencies.
	 *
	 * @return void
	 */
	public function enqueue( array $script_dependencies, array $style_dependencies ): void {
		wp_enqueue_style(
			self::CSS_HANDLE,
			plugins_url( 'dist/insights.css', PLUGIN_FILE ),
			$style_dependencies,
			PLUGIN_VERSION
		);

		wp_enqueue_script(
			self::JS_HANDLE,
			plugins_url( 'dist/insights.js', PLUGIN_FILE ),
			$script_dependencies,
			PLUGIN_VERSION,
			true
		);

		wp_set_script_translations( self::JS_HANDLE, 'code-snippets' );
		code_snippets()->localize_script( self::JS_HANDLE );
		wp_localize_script( self::JS_HANDLE, 'CODE_SNIPPETS_INSIGHTS', $this->get_summary() );
	}
}
