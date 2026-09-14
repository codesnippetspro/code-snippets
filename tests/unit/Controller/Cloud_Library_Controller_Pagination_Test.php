<?php

namespace Code_Snippets\Controller;

use Code_Snippets\Model\Authenticated_Cloud_Connection;
use Code_Snippets\UnitTestCase;
use ReflectionProperty;

/**
 * Tests that cached codevault data is only reused when it covers the slice of
 * the vault being asked for.
 *
 * @group cloud
 */
class Cloud_Library_Controller_Pagination_Test extends UnitTestCase {

	/**
	 * Query strings of the codevault requests made during a test, in order.
	 *
	 * @var array<int, string>
	 */
	private array $codevault_queries = [];

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->codevault_queries = [];
		delete_transient( 'cs_codevault_snippets' );
		add_filter( 'pre_http_request', [ $this, 'answer_codevault_request' ], 10, 3 );
	}

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'pre_http_request', [ $this, 'answer_codevault_request' ] );
		delete_transient( 'cs_codevault_snippets' );
		parent::tear_down();
	}

	/**
	 * Record and answer codevault requests, passing anything else through.
	 *
	 * @param mixed  $preempt     Existing preempted value.
	 * @param array  $parsed_args Parsed HTTP request arguments.
	 * @param string $url         Requested URL.
	 *
	 * @return mixed
	 */
	public function answer_codevault_request( $preempt, array $parsed_args, string $url ) {
		if ( false === strpos( $url, 'private/allsnippets' ) ) {
			return $preempt;
		}

		$this->codevault_queries[] = (string) wp_parse_url( $url, PHP_URL_QUERY );

		return [
			'headers'  => [],
			'body'     => wp_json_encode(
				[
					'snippets' => [],
					'meta'     => [
						'total'       => 0,
						'total_pages' => 0,
						'page'        => 1,
					],
				]
			),
			'response' => [
				'code'    => 200,
				'message' => 'OK',
			],
			'cookies'  => [],
		];
	}

	/**
	 * Build a library controller over a verified connection.
	 *
	 * @return Cloud_Library_Controller
	 */
	private function make_controller(): Cloud_Library_Controller {
		$connection = new Authenticated_Cloud_Connection();

		$property = new ReflectionProperty( $connection, 'settings' );
		$property->setAccessible( true );
		$property->setValue(
			$connection,
			array_replace(
				$property->getValue( $connection ),
				[
					'cloud_token'    => 'test-cloud-token',
					'token_verified' => true,
					'local_token'    => 'test-site-token',
				]
			)
		);

		return new Cloud_Library_Controller( $connection );
	}

	/**
	 * Asking for the same page at a different size must refetch: the cached data
	 * covers a different slice of the vault even though the page number matches.
	 *
	 * @return void
	 */
	public function test_changing_per_page_refetches_the_page(): void {
		$controller = $this->make_controller();

		$controller->get_codevault_snippets( 1, 20 );
		$requests_after_first = count( $this->codevault_queries );

		$controller->get_codevault_snippets( 1, 50 );

		$this->assertGreaterThan(
			$requests_after_first,
			count( $this->codevault_queries ),
			'A changed page size should invalidate the cached codevault page.'
		);
		$this->assertStringContainsString( 'per_page=50', end( $this->codevault_queries ) );
	}

	/**
	 * Repeating the same page and size serves from cache rather than refetching.
	 *
	 * @return void
	 */
	public function test_repeating_the_same_request_uses_the_cache(): void {
		$controller = $this->make_controller();

		$controller->get_codevault_snippets( 1, 20 );
		$requests_after_first = count( $this->codevault_queries );

		$controller->get_codevault_snippets( 1, 20 );

		$this->assertSame(
			$requests_after_first,
			count( $this->codevault_queries ),
			'An identical request should be served from the cached codevault data.'
		);
	}

	/**
	 * An over-large size is clamped before the comparison, so a caller repeating
	 * it is not forced into a refetch on every call.
	 *
	 * @return void
	 */
	public function test_repeating_an_over_large_per_page_uses_the_cache(): void {
		$controller = $this->make_controller();

		$controller->get_codevault_snippets( 1, 5000 );
		$requests_after_first = count( $this->codevault_queries );

		$controller->get_codevault_snippets( 1, 5000 );

		$this->assertSame(
			$requests_after_first,
			count( $this->codevault_queries ),
			'A clamped page size should still match the cached data on the next call.'
		);
	}
}
