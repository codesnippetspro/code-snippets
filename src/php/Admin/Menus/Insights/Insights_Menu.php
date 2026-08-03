<?php

namespace Code_Snippets\Admin\Menus\Insights;

use Code_Snippets\Admin\Menus\Admin_Menu;

/**
 * Provides the Insights admin menu.
 */
class Insights_Menu extends Admin_Menu {

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
	 * Enqueue the Insights page assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		( new Insights_Menu_Assets() )->enqueue( self::$script_deps, self::$style_deps );
	}
}
