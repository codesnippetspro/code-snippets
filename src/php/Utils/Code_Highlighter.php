<?php

namespace Code_Snippets\Utils;

use const Code_Snippets\PLUGIN_FILE;
use const Code_Snippets\PLUGIN_VERSION;

/**
 * This class handles the integration of the PrismJS syntax highlighter.
 *
 * @package Code_Snippets
 */
class Code_Highlighter {

	/**
	 * Handle to use for the PrismJS library.
	 */
	public const PRISM_HANDLE = 'code-snippets-prism';

	/**
	 * Enqueue the styles and scripts for the Prism syntax highlighter.
	 *
	 * @return void
	 */
	public static function register_prism_assets() {
		wp_register_script(
			self::PRISM_HANDLE,
			plugins_url( 'dist/prism.js', PLUGIN_FILE ),
			[],
			PLUGIN_VERSION,
			[ 'in_footer' => true ]
		);

		wp_register_style(
			self::PRISM_HANDLE,
			plugins_url( 'dist/prism.css', PLUGIN_FILE ),
			[],
			PLUGIN_VERSION
		);
	}


	/**
	 * Enqueue all available Prism themes.
	 *
	 * @return void
	 */
	public static function enqueue_all_prism_themes() {
		self::register_prism_assets();

		wp_enqueue_style( self::PRISM_HANDLE );
		wp_enqueue_script( self::PRISM_HANDLE );
	}
}
