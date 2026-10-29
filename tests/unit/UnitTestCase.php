<?php

namespace Code_Snippets;

use WP_UnitTestCase;

/**
 * Base test case for all Code Snippets tests.
 */
class UnitTestCase extends WP_UnitTestCase {

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		if ( is_multisite() ) {
			$menu_items = get_site_option( 'menu_items', [] );
			$menu_items['snippets'] = 1;
			update_site_option( 'menu_items', $menu_items );
		}
	}
}
