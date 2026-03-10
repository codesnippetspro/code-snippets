<?php

namespace Code_Snippets\Tests;

use Code_Snippets\Admin\Menus\Edit_Menu;
use function Code_Snippets\code_snippets;

/**
 * Tests for the edit menu registration.
 *
 * @group admin-menu
 */
class Edit_Menu_Test extends TestCase {

	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	protected static int $admin_user_id;

	/**
	 * Set up fixtures before any tests run.
	 *
	 * @param mixed $factory Factory object.
	 *
	 * @return void
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$admin_user_id = $factory->user->create(
			[
				'role' => 'administrator',
			]
		);
	}

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		wp_set_current_user( self::$admin_user_id );
		unset( $GLOBALS['submenu'][ code_snippets()->get_menu_slug() ] );
	}

	/**
	 * The edit submenu item is removed after registration.
	 *
	 * @return void
	 */
	public function test_register_hides_edit_submenu_item(): void {
		$menu = new Edit_Menu();
		$menu->register();

		$submenu = $GLOBALS['submenu'][ code_snippets()->get_menu_slug() ] ?? [];
		$submenu_slugs = array_column( $submenu, 2 );

		$this->assertNotContains( code_snippets()->get_menu_slug( 'edit' ), $submenu_slugs );
		$this->assertContains( code_snippets()->get_menu_slug( 'add' ), $submenu_slugs );
	}
}
