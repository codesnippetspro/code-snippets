<?php

namespace Code_Snippets\Admin\Menus;

use Code_Snippets\Model\Snippet;
use Code_Snippets\UnitTestCase;
use WP_UnitTest_Factory;
use function Code_Snippets\code_snippets;
use function Code_Snippets\get_snippets;
use function Code_Snippets\save_snippet;

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
	 * Localized data retains the exact Task 2 keys.
	 *
	 * @return void
	 */
	public function test_enqueue_localizes_task_two_manage_data(): void {
		$filter = static fn() => PHP_INT_MAX;
		add_filter( 'code_snippets/manage/inline_snippets_limit', $filter );

		$this->enqueue_assets();
		$localized = $this->get_localized_data();

		remove_filter( 'code_snippets/manage/inline_snippets_limit', $filter );

		$this->assertSame(
			[
				'hasNetworkCap',
				'hiddenColumns',
				'truncateRowValues',
				'snippetsPerPage',
				'cloudSearchPerPage',
				'isSafeModeActive',
				'bulkDownloadNonce',
				'supportsZipDownloads',
				'editorTheme',
				'snippetsList',
			],
			array_keys( $localized )
		);
		$this->assertArrayNotHasKey( 'typeCounts', $localized );
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
	 * The inline snippets threshold can be disabled.
	 *
	 * @return void
	 */
	public function test_enqueue_applies_disabled_inline_snippets_limit(): void {
		add_filter( 'code_snippets/manage/inline_snippets_limit', '__return_zero' );

		$this->enqueue_assets();
		$localized = $this->get_localized_data();

		remove_filter( 'code_snippets/manage/inline_snippets_limit', '__return_zero' );

		$this->assertArrayNotHasKey( 'snippetsList', $localized );
	}

	/**
	 * Snippet lists at or below the configured limit are localized inline.
	 *
	 * @dataProvider provide_inline_limit_offsets
	 *
	 * @param int $limit_offset Number added to the current snippet count.
	 *
	 * @return void
	 */
	public function test_enqueue_localizes_snippets_within_inline_limit( int $limit_offset ): void {
		save_snippet( new Snippet( [ 'name' => 'Inline Snippet Fixture' ] ) );
		$inline_limit = count( get_snippets() ) + $limit_offset;
		$filter = static fn() => $inline_limit;
		add_filter( 'code_snippets/manage/inline_snippets_limit', $filter );

		$this->enqueue_assets();
		$localized = $this->get_localized_data();

		remove_filter( 'code_snippets/manage/inline_snippets_limit', $filter );

		$this->assertArrayHasKey( 'snippetsList', $localized );
	}

	/**
	 * Provide offsets that place the snippet count at or below the inline limit.
	 *
	 * @return array<string,array{int}>
	 */
	public static function provide_inline_limit_offsets(): array {
		return [
			'at the limit' => [ 0 ],
			'below the limit' => [ 1 ],
		];
	}

	/**
	 * Snippet lists above the configured limit are omitted.
	 *
	 * @return void
	 */
	public function test_enqueue_omits_snippets_above_inline_limit(): void {
		save_snippet( new Snippet( [ 'name' => 'Oversized Inline Snippet Fixture' ] ) );
		$inline_limit = count( get_snippets() ) - 1;
		$filter = static fn() => $inline_limit;
		add_filter( 'code_snippets/manage/inline_snippets_limit', $filter );

		$this->enqueue_assets();
		$localized = $this->get_localized_data();

		remove_filter( 'code_snippets/manage/inline_snippets_limit', $filter );

		$this->assertArrayNotHasKey( 'snippetsList', $localized );
	}

	/**
	 * Enqueue assets through the extracted service.
	 *
	 * @return void
	 */
	private function enqueue_assets(): void {
		$assets = new Manage_Menu_Assets( new Manage_Menu_Screen_Options() );
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
