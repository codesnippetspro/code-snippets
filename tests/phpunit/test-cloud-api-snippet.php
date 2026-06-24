<?php

namespace Code_Snippets\Tests;

use Code_Snippets\Client\Cloud_Snippets_Client;
use Code_Snippets\Model\Basic_Cloud_Connection;
use Code_Snippets\Model\Cloud_Snippet;
use WP_Error;

/**
 * Tests for single-snippet and revision fetches, which use a different response
 * envelope than search (`snippet` / `snippet_revision`, not `data`/`snippets`).
 *
 * @group cloud
 */
class Cloud_API_Snippet_Test extends TestCase {

	/**
	 * API instance.
	 *
	 * @var Cloud_Snippets_Client
	 */
	private Cloud_Snippets_Client $api;

	/**
	 * Response to return from the mock HTTP filter.
	 *
	 * @var array|WP_Error|null
	 */
	private $mock_response = null;

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->mock_response = null;
		$this->api = new Cloud_Snippets_Client( new Basic_Cloud_Connection() );

		add_filter( 'pre_http_request', [ $this, 'mock_request' ], 10, 3 );
	}

	/**
	 * Tear down after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'pre_http_request', [ $this, 'mock_request' ], 10 );

		parent::tear_down();
	}

	/**
	 * Intercept getsnippet / getsnippetrevision requests and return the mock response.
	 *
	 * @param mixed  $preempt     Short-circuit value.
	 * @param array  $parsed_args Request arguments.
	 * @param string $url         Request URL.
	 *
	 * @return array|WP_Error|mixed
	 */
	public function mock_request( $preempt, array $parsed_args, string $url ) {
		if ( false === strpos( $url, 'public/getsnippet' ) ) {
			return $preempt;
		}

		return null !== $this->mock_response ? $this->mock_response : $preempt;
	}

	/**
	 * Wrap a JSON body in a mock HTTP response array.
	 *
	 * @param array $body Response body to encode.
	 *
	 * @return array
	 */
	private function json_response( array $body ): array {
		return [
			'body'     => wp_json_encode( $body ),
			'response' => [ 'code' => 200 ],
		];
	}

	/**
	 * A single snippet response (`{ snippet: {...}, success: true }`) is parsed into a Cloud_Snippet.
	 *
	 * @return void
	 */
	public function test_get_single_snippet_parses_snippet(): void {
		$this->mock_response = $this->json_response(
			[
				'snippet' => [
					'id'   => 5,
					'name' => 'Test Snippet',
					'code' => '<?php echo 1;',
				],
				'success' => true,
			]
		);

		$result = $this->api->get_cloud_snippet( 5 );

		$this->assertInstanceOf( Cloud_Snippet::class, $result );
		$this->assertSame( 'Test Snippet', $result->name );
	}

	/**
	 * A failed/empty single-snippet request returns an empty Cloud_Snippet without erroring.
	 *
	 * @return void
	 */
	public function test_get_single_snippet_handles_empty_response(): void {
		$this->mock_response = new WP_Error( 'http_request_failed', 'down' );

		$result = $this->api->get_cloud_snippet( 5 );

		$this->assertInstanceOf( Cloud_Snippet::class, $result );
		$this->assertSame( '', $result->name );
	}

	/**
	 * A revision response (`{ snippet_revision: N }`) returns the revision string.
	 *
	 * @return void
	 */
	public function test_get_cloud_snippet_revision_returns_revision(): void {
		$this->mock_response = $this->json_response( [ 'snippet_revision' => 7 ] );

		$this->assertSame( '7', $this->api->get_cloud_snippet_revision( '5' ) );
	}

	/**
	 * A failed/empty revision request returns null without erroring.
	 *
	 * @return void
	 */
	public function test_get_cloud_snippet_revision_handles_empty_response(): void {
		$this->mock_response = new WP_Error( 'http_request_failed', 'down' );

		$this->assertNull( $this->api->get_cloud_snippet_revision( '5' ) );
	}
}
