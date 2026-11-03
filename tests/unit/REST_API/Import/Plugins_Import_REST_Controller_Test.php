<?php

namespace Code_Snippets\REST_API\Import;

use Code_Snippets\AdminUnitTestCase;
use WP_REST_Request;
use WP_REST_Response;
use WP_UnitTest_Factory;

/**
 * Tests for permission checks on the plugin importer routes.
 *
 * @group rest-api
 * @group import
 */
class Plugins_Import_REST_Controller_Test extends AdminUnitTestCase {

	/**
	 * Super administrator user ID.
	 *
	 * @var int
	 */
	protected static int $super_admin_id;

	/**
	 * Create the fixture users.
	 *
	 * @param WP_UnitTest_Factory $factory Factory object.
	 *
	 * @return void
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::$super_admin_id = $factory->user->create( [ 'role' => 'administrator' ] );

		if ( is_multisite() ) {
			grant_super_admin( self::$super_admin_id );
		}
	}

	/**
	 * Request an import from the Insert PHP Code Snippet importer.
	 *
	 * @param array<string, mixed> $params Request parameters.
	 *
	 * @return WP_REST_Response
	 */
	private function import( array $params ): WP_REST_Response {
		$request = new WP_REST_Request( 'POST', '/code-snippets/v1/import/plugins/insert-php-code-snippet/import' );

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		return rest_do_request( $request );
	}

	/**
	 * A site administrator can import into the site table.
	 *
	 * @return void
	 */
	public function test_site_admin_can_import_site_snippets(): void {
		$response = $this->import( [ 'ids' => [] ] );

		$this->assertSame( 200, $response->get_status() );
	}

	/**
	 * A site administrator without the network capability cannot import into the network table.
	 *
	 * @return void
	 */
	public function test_site_admin_cannot_import_network_snippets(): void {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Network scope only exists on multisite installs.' );
		}

		$response = $this->import(
			[
				'ids'     => [],
				'network' => true,
			]
		);

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * A network administrator can import into the network table.
	 *
	 * @return void
	 */
	public function test_super_admin_can_import_network_snippets(): void {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Network scope only exists on multisite installs.' );
		}

		wp_set_current_user( self::$super_admin_id );

		$response = $this->import(
			[
				'ids'     => [],
				'network' => true,
			]
		);

		$this->assertSame( 200, $response->get_status() );
	}
}
