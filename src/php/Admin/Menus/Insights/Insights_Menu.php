<?php

namespace Code_Snippets\Admin\Menus\Insights;

use Code_Snippets\Admin\Menus\Admin_Menu;
use function Code_Snippets\code_snippets;
use const Code_Snippets\PLUGIN_FILE;
use const Code_Snippets\PLUGIN_VERSION;

/**
 * Provides the Insights admin menu.
 */
class Insights_Menu extends Admin_Menu {

	/**
	 * Handle for the Insights script and stylesheet.
	 */
	private const ASSET_HANDLE = 'code-snippets-insights';

	/**
	 * Class constructor.
	 */
	public function __construct() {
		parent::__construct(
			'insights',
			_x( 'Insights', 'menu label', 'code-snippets' ),
			__( 'Snippet Insights', 'code-snippets' )
		);
	}

	/**
	 * Render the Insights interface.
	 *
	 * @return void
	 */
	public function render() {
		echo '<div id="code-snippets-insights-container" class="wrap"></div>';
	}

	/**
	 * Enqueue Insights assets and localized runtime data.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		wp_enqueue_style(
			self::ASSET_HANDLE,
			plugins_url( 'dist/insights.css', PLUGIN_FILE ),
			self::$style_deps,
			PLUGIN_VERSION
		);

		wp_enqueue_script(
			self::ASSET_HANDLE,
			plugins_url( 'dist/insights.js', PLUGIN_FILE ),
			self::$script_deps,
			PLUGIN_VERSION,
			true
		);

		wp_set_script_translations( self::ASSET_HANDLE, 'code-snippets' );
		code_snippets()->localize_script( self::ASSET_HANDLE );
		wp_localize_script( self::ASSET_HANDLE, 'CODE_SNIPPETS_INSIGHTS', ( new Insights_Summary() )->get() );
	}
}
