<?php

namespace Code_Snippets\Admin\Menus;

use Code_Snippets\AdminUnitTestCase;
use Code_Snippets\UnitTestCase;
use WP_UnitTest_Factory;
use function Code_Snippets\code_snippets;

/**
 * Tests for the edit menu registration.
 *
 * @group admin-menu
 */
class Edit_Menu_Test extends AdminUnitTestCase {

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		set_current_screen( 'toplevel_page_' . code_snippets()->get_menu_slug() );
		unset( $GLOBALS['submenu'][ code_snippets()->get_menu_slug() ] );
		unset( $_GET['id'] );
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
	 * Hide the edit submenu item outside the snippet edit screen.
	 *
	 * @return void
	 */
	public function test_maybe_hide_menu_item_removes_edit_submenu_outside_edit_screen(): void {
		$menu = new Edit_Menu();
		$menu->register();

		$menu->maybe_hide_menu_item( get_current_screen() );

		$submenu = $GLOBALS['submenu'][ code_snippets()->get_menu_slug() ] ?? [];
		$submenu_slugs = array_column( $submenu, 2 );

		$this->assertNotContains( code_snippets()->get_menu_slug( 'edit' ), $submenu_slugs );
		$this->assertContains( code_snippets()->get_menu_slug( 'add' ), $submenu_slugs );
	}

	/**
	 * Keep the edit submenu item visible while editing a specific snippet.
	 *
	 * @return void
	 */
	public function test_maybe_hide_menu_item_keeps_edit_submenu_on_edit_screen(): void {
		$menu = new Edit_Menu();
		$menu->register();

		$screen = get_current_screen();
		$hook = get_plugin_page_hookname(
			code_snippets()->get_menu_slug( 'edit' ),
			code_snippets()->get_menu_slug()
		);

		$screen->id = $hook;
		$screen->base = $hook;
		$_GET['id'] = '11';

		$menu->maybe_hide_menu_item( $screen );

		$submenu = $GLOBALS['submenu'][ code_snippets()->get_menu_slug() ] ?? [];
		$submenu_slugs = array_column( $submenu, 2 );

		$this->assertContains( code_snippets()->get_menu_slug( 'edit' ), $submenu_slugs );
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

	/**
	 * The edit menu hides its submenu item using the current_screen hook.
	 *
	 * @return void
	 */
	public function test_edit_menu_uses_current_screen_to_hide_menu_item(): void {
		$menu = new Edit_Menu();

		$this->assertNotFalse( has_action( 'current_screen', [ $menu, 'maybe_hide_menu_item' ] ) );
	}
}
