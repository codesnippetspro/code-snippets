<?php

namespace Code_Snippets\Admin\Menus;

use Code_Snippets\UnitTestCase;
use WP_UnitTest_Factory;
use function Code_Snippets\code_snippets;

/**
 * Tests for manage menu Screen Options.
 */
class Manage_Menu_Screen_Options_Test extends UnitTestCase {

	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	protected static int $admin_user_id;

	/**
	 * Set up fixtures before any tests run.
	 *
	 * @param WP_UnitTest_Factory $factory Factory object.
	 *
	 * @return void
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$admin_user_id = $factory->user->create( [ 'role' => 'administrator' ] );
	}

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		wp_set_current_user( self::$admin_user_id );
		set_current_screen( 'toplevel_page_' . code_snippets()->get_menu_slug() );
		delete_user_option( self::$admin_user_id, 'snippets_table_truncate_row_values' );
		unset(
			$_POST['wp_screen_options'],
			$_POST['screenoptionnonce'],
			$_POST['snippets_table_truncate_row_values'],
			$_REQUEST['page'],
			$_REQUEST['subpage']
		);
	}

	/**
	 * Screen columns retain existing values and add the snippet fields.
	 *
	 * @return void
	 */
	public function test_get_columns_adds_snippet_columns(): void {
		$options = new Manage_Menu_Screen_Options();

		$columns = $options->get_columns( [ 'existing' => 'Existing' ] );

		$this->assertSame( 'Existing', $columns['existing'] );
		$this->assertSame( 'Columns', $columns['_title'] );
		$this->assertSame( 'Description', $columns['desc'] );
		$this->assertSame( 'Modified', $columns['date'] );
	}

	/**
	 * The manage screen renders a truncation toggle.
	 *
	 * @return void
	 */
	public function test_render_adds_truncation_toggle(): void {
		$options = new Manage_Menu_Screen_Options();

		$output = $options->render( '' );

		$this->assertStringContainsString( 'snippets-table-truncate-row-values', $output );
		$this->assertStringContainsString( 'Truncate long snippet names and descriptions', $output );
	}

	/**
	 * The Community Cloud view does not render snippet table controls.
	 *
	 * @return void
	 */
	public function test_render_skips_truncation_toggle_on_cloud_community_view(): void {
		$_REQUEST['subpage'] = 'cloud-community';
		$options = new Manage_Menu_Screen_Options();

		$this->assertSame( '', $options->render( '' ) );
		$this->assertTrue( $options->is_cloud_community_view() );
		$this->assertFalse( $options->is_manage_table_view() );
	}

	/**
	 * The truncation preference is saved from the Screen Options form.
	 *
	 * @return void
	 */
	public function test_save_truncation_preference_updates_user_option(): void {
		$_REQUEST['page'] = code_snippets()->get_menu_slug();
		$_POST['wp_screen_options'] = [
			'option' => 'snippets_per_page',
			'value'  => '20',
		];
		$_POST['screenoptionnonce'] = wp_create_nonce( 'screen-options-nonce' );
		$options = new Manage_Menu_Screen_Options();

		$options->save_truncation_preference();
		$this->assertFalse( (bool) get_user_option( 'snippets_table_truncate_row_values', self::$admin_user_id ) );

		$_POST['snippets_table_truncate_row_values'] = '1';
		$options->save_truncation_preference();
		$this->assertTrue( (bool) get_user_option( 'snippets_table_truncate_row_values', self::$admin_user_id ) );
	}

	/**
	 * The Community Cloud screen does not overwrite the truncation preference.
	 *
	 * @return void
	 */
	public function test_save_truncation_preference_ignores_cloud_community_view(): void {
		update_user_option( self::$admin_user_id, 'snippets_table_truncate_row_values', 1 );
		$_REQUEST['page'] = code_snippets()->get_menu_slug();
		$_REQUEST['subpage'] = 'cloud-community';
		$_POST['wp_screen_options'] = [
			'option' => 'snippets_per_page',
			'value'  => '20',
		];
		$_POST['screenoptionnonce'] = wp_create_nonce( 'screen-options-nonce' );

		( new Manage_Menu_Screen_Options() )->save_truncation_preference();

		$this->assertTrue( (bool) get_user_option( 'snippets_table_truncate_row_values', self::$admin_user_id ) );
	}

	/**
	 * Only the snippets per-page option is saved by the filter.
	 *
	 * @return void
	 */
	public function test_save_per_page_option_limits_the_filter_to_snippets(): void {
		$options = new Manage_Menu_Screen_Options();

		$this->assertSame( '25', $options->save_per_page_option( false, 'snippets_per_page', '25' ) );
		$this->assertFalse( $options->save_per_page_option( false, 'other_option', '25' ) );
	}
}
