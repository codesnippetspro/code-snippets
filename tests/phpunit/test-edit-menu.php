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
	 * The edit submenu item remains registered so the page stays directly accessible.
	 *
	 * @return void
	 */
	public function test_register_keeps_edit_submenu_item(): void {
		$menu = new Edit_Menu();
		$menu->register();

		$submenu = $GLOBALS['submenu'][ code_snippets()->get_menu_slug() ] ?? [];
		$submenu_slugs = array_column( $submenu, 2 );

		$this->assertContains( code_snippets()->get_menu_slug( 'edit' ), $submenu_slugs );
		$this->assertContains( code_snippets()->get_menu_slug( 'add' ), $submenu_slugs );
	}

	/**
	 * The edit menu no longer injects inline JavaScript in the admin footer.
	 *
	 * @return void
	 */
	public function test_edit_menu_does_not_use_footer_inline_script(): void {
		$menu = new Edit_Menu();

		$this->assertFalse( has_action( 'admin_print_footer_scripts', [ $menu, 'disable_menu_link' ] ) );
		$this->assertFalse( has_action( 'network_admin_print_footer_scripts', [ $menu, 'disable_menu_link' ] ) );
	}
}
