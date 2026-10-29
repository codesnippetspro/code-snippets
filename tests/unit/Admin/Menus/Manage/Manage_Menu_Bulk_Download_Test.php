<?php

namespace Code_Snippets\Admin\Menus\Manage;

use Code_Snippets\Model\Snippet;
use Code_Snippets\UnitTestCase;
use WP_Error;
use function Code_Snippets\save_snippet;

/**
 * Tests for manage menu bulk downloads.
 */
class Manage_Menu_Bulk_Download_Test extends UnitTestCase {

	/**
	 * Malformed request data does not resolve any snippets.
	 *
	 * @return void
	 */
	public function test_resolve_snippets_ignores_malformed_payload(): void {
		$bulk_download = new Manage_Menu_Bulk_Download();

		$this->assertSame( [], $bulk_download->resolve_snippets( '{invalid' ) );
	}

	/**
	 * Valid local snippet identifiers resolve to snippet models.
	 *
	 * @return void
	 */
	public function test_resolve_snippets_returns_requested_local_snippets(): void {
		$snippet = save_snippet( new Snippet( [ 'name' => 'Bulk Download Fixture' ] ) );
		$this->assertInstanceOf( Snippet::class, $snippet );

		$payload = wp_json_encode( [ [ 'id' => $snippet->id ] ] );
		$result = ( new Manage_Menu_Bulk_Download() )->resolve_snippets( $payload );

		$this->assertCount( 1, $result );
		$this->assertSame( $snippet->id, $result[0]->id );
	}

	/**
	 * Subsite admins cannot resolve network snippets for download.
	 *
	 * @return void
	 */
	public function test_resolve_snippets_requires_network_cap(): void {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Network snippet downloads only apply on multisite.' );
		}

		$snippet = save_snippet(
			new Snippet(
				[
					'name'    => 'Network Download Fixture',
					'code'    => '<?php echo "network";',
					'scope'   => 'global',
					'network' => true,
				]
			)
		);

		$payload = wp_json_encode(
			[
				[
					'id'      => $snippet->id,
					'network' => true,
				],
			]
		);

		$result = ( new Manage_Menu_Bulk_Download() )->resolve_snippets( $payload );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'code_snippets_forbidden_network_download', $result->get_error_code() );
	}
}
