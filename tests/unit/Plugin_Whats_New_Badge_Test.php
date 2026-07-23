<?php

namespace Code_Snippets;

use Code_Snippets\Admin\Whats_New_Badge;
use WP_UnitTest_Factory;
use const Code_Snippets\PLUGIN_VERSION;

/**
 * Tests the What's New badge script bootstrap.
 */
class Plugin_Whats_New_Badge_Test extends UnitTestCase {

	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	protected static int $admin_user_id;

	/**
	 * Create the administrator fixture.
	 *
	 * @param WP_UnitTest_Factory $factory Factory object.
	 *
	 * @return void
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$admin_user_id = $factory->user->create( [ 'role' => 'administrator' ] );
	}

	/**
	 * The shared script bootstrap exposes the unseen flag.
	 *
	 * @return void
	 */
	public function test_localized_bootstrap_flag(): void {
		wp_set_current_user( self::$admin_user_id );
		update_user_meta( self::$admin_user_id, Whats_New_Badge::USER_META_KEY, '1.0.0' );
		$this->assertStringContainsString( '"whatsNewUnseen":"1"', $this->get_localized_data() );

		update_user_meta( self::$admin_user_id, Whats_New_Badge::USER_META_KEY, PLUGIN_VERSION );
		$this->assertStringContainsString( '"whatsNewUnseen":""', $this->get_localized_data() );
	}

	/**
	 * Retrieve the localized script data.
	 *
	 * @return string
	 */
	private function get_localized_data(): string {
		$handle = wp_unique_id( 'whats-new-badge-' );
		wp_register_script( $handle, '', [], PLUGIN_VERSION, true );
		code_snippets()->localize_script( $handle );
		$data = wp_scripts()->get_data( $handle, 'data' );

		$this->assertIsString( $data );
		return $data;
	}
}
