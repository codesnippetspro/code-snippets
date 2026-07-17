<?php

namespace Code_Snippets\Admin\Menus;

use Code_Snippets\UnitTestCase;

/**
 * Tests for manage menu registration support.
 */
class Manage_Menu_Registration_Test extends UnitTestCase {

	/**
	 * The menu icon stylesheet is registered through the helper.
	 *
	 * @return void
	 */
	public function test_enqueue_menu_css_enqueues_menu_icon_stylesheet(): void {
		wp_dequeue_style( 'code-snippets-menu' );

		( new Manage_Menu_Registration() )->enqueue_menu_css();

		$this->assertTrue( wp_style_is( 'code-snippets-menu', 'enqueued' ) );
	}
}
