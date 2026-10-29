<?php

namespace Code_Snippets\REST_API\Import;

use Code_Snippets\AdminUnitTestCase;
use WP_REST_Request;
use WP_REST_Response;
use WP_UnitTest_Factory;
use function Code_Snippets\get_snippets;

/**
 * Tests for parsing uploaded snippet export files.
 *
 * @group rest-api
 * @group import
 */
class File_Import_REST_Controller_Test extends AdminUnitTestCase {

	/**
	 * Export files written during a test.
	 *
	 * @var string[]
	 */
	private array $files = [];

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
	 * Remove the export files.
	 *
	 * @return void
	 */
	public function tear_down() {
		array_map( 'unlink', array_filter( $this->files, 'file_exists' ) );
		parent::tear_down();
	}

	/**
	 * Parse an export file holding the given snippets.
	 *
	 * @param array<int, array<string, mixed>> $snippets Snippets in the export.
	 *
	 * @return WP_REST_Response
	 */
	private function parse_export( array $snippets ): WP_REST_Response {
		$path = wp_tempnam( 'export.code-snippets.json' );
		file_put_contents( $path, wp_json_encode( [ 'snippets' => $snippets ] ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$this->files[] = $path;

		$request = new WP_REST_Request( 'POST', '/code-snippets/v1/import/file-upload/parse' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$request->set_file_params(
			[
				'files' => [
					'name'     => 'export.code-snippets.json',
					'type'     => 'application/json',
					'tmp_name' => $path,
					'error'    => UPLOAD_ERR_OK,
					'size'     => filesize( $path ),
				],
			]
		);

		return rest_do_request( $request );
	}

	/**
	 * A snippet that had a condition is flagged, as the condition is not imported.
	 *
	 * @return void
	 */
	public function test_snippet_with_a_condition_is_flagged(): void {
		$response = $this->parse_export(
			[
				[
					'name'         => 'Limited snippet',
					'code'         => '// limited',
					'condition_id' => 12,
				],
				[
					'name' => 'Unlimited snippet',
					'code' => '// unlimited',
				],
			]
		);

		$warnings = $response->get_data()['warnings'] ?? [];

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 1, $warnings );
		$this->assertStringContainsString( 'Limited snippet', $warnings[0] );
	}

	/**
	 * Snippets without conditions import without warnings.
	 *
	 * @return void
	 */
	public function test_snippets_without_conditions_are_not_flagged(): void {
		$response = $this->parse_export(
			[
				[
					'name' => 'Plain snippet',
					'code' => '// plain',
				],
			]
		);

		$this->assertArrayNotHasKey( 'warnings', $response->get_data() );
	}

	/**
	 * Import the given snippets.
	 *
	 * @param array<string, mixed> $params Request parameters.
	 *
	 * @return WP_REST_Response
	 */
	private function import( array $params ): WP_REST_Response {
		$request = new WP_REST_Request( 'POST', '/code-snippets/v1/import/file-upload/import' );

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		return rest_do_request( $request );
	}

	/**
	 * Skip the current test when not running on multisite.
	 *
	 * @return void
	 */
	private function require_multisite(): void {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Network scope only exists on multisite installs.' );
		}
	}

	/**
	 * A site administrator can import into the site table.
	 *
	 * @return void
	 */
	public function test_site_admin_can_import_site_snippets(): void {
		$response = $this->import(
            [
				'snippets' => [
					[
						'name' => 'Site import',
						'code' => '// site',
					],
				],
			]
        );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 1, $response->get_data()['imported'] );
	}

	/**
	 * A site administrator without the network capability cannot import into the network table.
	 *
	 * @return void
	 */
	public function test_site_admin_cannot_import_network_snippets(): void {
		$this->require_multisite();

		$response = $this->import(
			[
				'network'  => true,
				'snippets' => [
					[
						'name' => 'Network import',
						'code' => '// network',
					],
				],
			]
		);

		$this->assertSame( 403, $response->get_status() );
		$this->assertEmpty( get_snippets( [], true ) );
	}

	/**
	 * A site administrator without the network capability cannot import shared network snippets.
	 *
	 * @return void
	 */
	public function test_site_admin_cannot_import_shared_network_snippets(): void {
		$this->require_multisite();

		$response = $this->import(
			[
				'snippets' => [
					[
						'name'           => 'Shared import',
						'code'           => '// shared',
						'shared_network' => true,
					],
				],
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
		$this->require_multisite();
		wp_set_current_user( self::$super_admin_id );

		$response = $this->import(
			[
				'network'  => true,
				'snippets' => [
					[
						'name' => 'Network import',
						'code' => '// network',
					],
				],
			]
		);

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 1, get_snippets( [], true ) );
	}
}
