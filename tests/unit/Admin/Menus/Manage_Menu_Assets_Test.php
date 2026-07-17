<?php

namespace Code_Snippets\Admin\Menus;

use Code_Snippets\Model\Snippet;
use Code_Snippets\UnitTestCase;
use WP_UnitTest_Factory;
use function Code_Snippets\code_snippets;
use function Code_Snippets\save_snippet;
use function Code_Snippets\trash_snippet;

/**
 * Tests for manage menu assets and localized data.
 */
class Manage_Menu_Assets_Test extends UnitTestCase {

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
		unset( $_REQUEST['subpage'] );
	}

	/**
	 * Hidden columns and table preferences are localized.
	 *
	 * @return void
	 */
	public function test_enqueue_localizes_manage_table_preferences(): void {
		$screen = get_current_screen();
		update_user_option( self::$admin_user_id, 'manage' . $screen->id . 'columnshidden', [ 'desc', 'date' ] );
		update_user_option( self::$admin_user_id, 'snippets_table_truncate_row_values', 0 );

		$this->enqueue_assets();
		$data = wp_scripts()->get_data( Manage_Menu::JS_HANDLE, 'data' );

		$this->assertIsString( $data );
		$this->assertStringContainsString( '"hiddenColumns":["desc","date"]', $data );
		$this->assertStringContainsString( '"truncateRowValues":"0"', $data );
		$this->assertStringContainsString( '"bulkDownloadNonce":"', $data );
		$this->assertStringContainsString( '"supportsZipDownloads":', $data );
	}

	/**
	 * Preview assets exclude the full editing dependencies.
	 *
	 * @return void
	 */
	public function test_enqueue_loads_only_preview_editor_dependencies(): void {
		foreach ( [ 'htmlhint', 'csslint', 'jshint', 'code-snippets-code-editor' ] as $handle ) {
			wp_dequeue_script( $handle );
		}

		$this->enqueue_assets();

		$this->assertTrue( wp_script_is( 'code-editor', 'enqueued' ) );
		$this->assertTrue( wp_style_is( 'code-editor', 'enqueued' ) );
		$this->assertFalse( wp_script_is( 'htmlhint', 'enqueued' ) );
		$this->assertFalse( wp_script_is( 'csslint', 'enqueued' ) );
		$this->assertFalse( wp_script_is( 'jshint', 'enqueued' ) );
		$this->assertFalse( wp_script_is( 'code-snippets-code-editor', 'enqueued' ) );
	}

	/**
	 * The inline snippets threshold remains filterable.
	 *
	 * @return void
	 */
	public function test_enqueue_applies_inline_snippets_limit_filter(): void {
		add_filter( 'code_snippets/manage/inline_snippets_limit', '__return_zero' );

		$this->enqueue_assets();
		$data = wp_scripts()->get_data( Manage_Menu::JS_HANDLE, 'data' );
		$localized_offset = is_string( $data ) ? strrpos( $data, 'var CODE_SNIPPETS_MANAGE = ' ) : false;

		remove_filter( 'code_snippets/manage/inline_snippets_limit', '__return_zero' );

		$this->assertIsString( $data );
		$this->assertNotFalse( $localized_offset );
		$this->assertStringNotContainsString( '"snippetsList":', substr( $data, $localized_offset ) );
	}

	/**
	 * Localized type counts retain their grouped, non-trashed shape.
	 *
	 * @return void
	 */
	public function test_enqueue_localizes_grouped_non_trashed_type_counts(): void {
		$counter = new Snippet_Type_Counter();
		$before = $counter->count();

		save_snippet( new Snippet( [ 'scope' => 'global' ] ) );
		save_snippet( new Snippet( [ 'scope' => 'admin' ] ) );
		save_snippet( new Snippet( [ 'scope' => 'content' ] ) );
		$trashed = save_snippet( new Snippet( [ 'scope' => 'site-css' ] ) );

		$this->assertInstanceOf( Snippet::class, $trashed );
		trash_snippet( $trashed->id );

		$this->enqueue_assets();
		$localized = $this->get_localized_data();

		$this->assertSame( ( $before['all'] ?? 0 ) + 3, $localized['typeCounts']['all'] );
		$this->assertSame( ( $before['php'] ?? 0 ) + 2, $localized['typeCounts']['php'] );
		$this->assertSame( ( $before['html'] ?? 0 ) + 1, $localized['typeCounts']['html'] );
		$this->assertSame( $before['css'] ?? 0, $localized['typeCounts']['css'] ?? 0 );
	}

	/**
	 * Enqueue assets through the extracted service.
	 *
	 * @return void
	 */
	private function enqueue_assets(): void {
		$assets = new Manage_Menu_Assets( new Manage_Menu_Screen_Options(), new Snippet_Type_Counter() );
		$assets->enqueue( Admin_Menu::$script_deps, Admin_Menu::$style_deps );
	}

	/**
	 * Decode the latest manage menu localization payload.
	 *
	 * @return array<string, mixed>
	 */
	private function get_localized_data(): array {
		$data = wp_scripts()->get_data( Manage_Menu::JS_HANDLE, 'data' );
		$prefix = 'var CODE_SNIPPETS_MANAGE = ';
		$offset = is_string( $data ) ? strrpos( $data, $prefix ) : false;

		$this->assertNotFalse( $offset );
		$json = substr( $data, $offset + strlen( $prefix ) );

		return json_decode( substr( $json, 0, strrpos( $json, ';' ) ), true );
	}
}
