<?php

namespace Code_Snippets\Admin\Menus;

use Code_Snippets\AdminUnitTestCase;
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
		// The hidden page's registration must be proven by each test, not inherited.
		unset( $GLOBALS['submenu'][ code_snippets()->get_menu_slug() ], $GLOBALS['submenu'][''] );
		unset( $_GET['id'] );
	}

	/**
	 * Reset request state between tests.
	 *
	 * @return void
	 */
	public function tear_down() {
		unset( $_GET['page'], $_GET['id'] );
		parent::tear_down();
	}

	/**
	 * Slugs currently listed under the Snippets menu.
	 *
	 * @return string[]
	 */
	private function get_snippets_submenu_slugs(): array {
		return array_column( $GLOBALS['submenu'][ code_snippets()->get_menu_slug() ] ?? [], 2 );
	}

	/**
	 * The edit page stays registered so it remains reachable by URL.
	 *
	 * @return void
	 */
	public function test_register_keeps_edit_page_reachable(): void {
		$menu = new Edit_Menu();
		$menu->register();

		$hidden_slugs = array_column( $GLOBALS['submenu'][''] ?? [], 2 );

		$this->assertContains( code_snippets()->get_menu_slug( 'edit' ), $hidden_slugs );
		$this->assertContains( code_snippets()->get_menu_slug( 'add' ), $this->get_snippets_submenu_slugs() );
	}

	/**
	 * The edit item is absent from the menu when no snippet is being edited.
	 *
	 * Anything reading the menu during `admin_menu` — menu editors and role
	 * managers among them — should never see it, so it must not be registered
	 * there and removed afterwards.
	 *
	 * @return void
	 */
	public function test_edit_item_absent_from_menu_when_not_editing(): void {
		$menu = new Edit_Menu();
		$menu->register();

		foreach ( $this->get_snippets_submenu_slugs() as $slug ) {
			$this->assertStringNotContainsString(
				code_snippets()->get_menu_slug( 'edit' ),
				$slug,
				'the edit page must not appear in the Snippets menu outside the edit screen'
			);
		}
	}

	/**
	 * While editing, the item appears and carries the snippet being edited.
	 *
	 * @return void
	 */
	public function test_edit_item_links_to_the_snippet_being_edited(): void {
		$_GET['page'] = code_snippets()->get_menu_slug( 'edit' );
		$_GET['id'] = '11';

		$menu = new Edit_Menu();
		$menu->register();

		$matching = array_filter(
			$this->get_snippets_submenu_slugs(),
			fn( $slug ) => false !== strpos( $slug, code_snippets()->get_menu_slug( 'edit' ) )
		);

		$this->assertCount( 1, $matching, 'the edit item should appear while editing a snippet' );
		$this->assertStringContainsString( 'id=11', reset( $matching ) );
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
	 * The hookname the menu reports is the one WordPress registered for the parentless page.
	 *
	 * @return void
	 */
	public function test_hookname_matches_the_registered_page(): void {
		$menu = new Edit_Menu();
		$menu->register();

		$registered = get_plugin_page_hookname( code_snippets()->get_menu_slug( 'edit' ), '' );

		$this->assertSame( $registered, $menu->get_hookname() );
		$this->assertContains( $registered, $menu->get_hooknames() );
		$this->assertNotFalse( has_action( 'load-' . $registered, [ $menu, 'load' ] ), 'the load hook is bound to the same name' );
	}
}
