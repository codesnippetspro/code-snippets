<?php

namespace Code_Snippets\Client;

use Code_Snippets\Model\Basic_Cloud_Connection;
use Code_Snippets\Settings\Version_Switch;
use Code_Snippets\UnitTestCase;
use WP_Error;

/**
 * Tests that outbound requests state how long they are prepared to wait.
 *
 * These are all made while an admin screen is rendering, so a request left to
 * WordPress's default holds the screen for as long as that default allows, and
 * a slow or unreachable service is felt by every administrator on the site.
 * Each call site names its own limit instead.
 *
 * @group http
 */
class Remote_Request_Timeouts_Test extends UnitTestCase {

	/**
	 * Timeouts seen on requests made during a test.
	 *
	 * @var array<int, mixed>
	 */
	private array $timeouts = [];

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->timeouts = [];
		delete_transient( 'code_snippets_welcome_data' );
		delete_transient( 'code_snippets_available_versions' );

		add_filter( 'pre_http_request', [ $this, 'capture_request' ], 10, 2 );
	}

	/**
	 * Tear down after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'pre_http_request', [ $this, 'capture_request' ], 10 );
		delete_transient( 'code_snippets_welcome_data' );
		delete_transient( 'code_snippets_available_versions' );

		parent::tear_down();
	}

	/**
	 * Record each request's timeout and answer it without leaving the machine.
	 *
	 * Answers with a failure, so each caller takes its own error path and none
	 * of them parse a response body. What is under test is the request that went
	 * out, not what any of them make of what comes back.
	 *
	 * @param mixed $preempt     Short-circuit value, from an earlier callback.
	 * @param array $parsed_args Request arguments, with defaults already applied.
	 *
	 * @return WP_Error Canned failure.
	 */
	public function capture_request( $preempt, $parsed_args ) {
		$this->timeouts[] = $parsed_args['timeout'] ?? null;

		return new WP_Error( 'http_request_failed', 'Refused by the test.' );
	}

	/**
	 * Assert every request made during the test asked for a given limit.
	 *
	 * @param int    $expected Limit the call site should have set, in seconds.
	 * @param string $subject  What was being fetched, for the failure message.
	 *
	 * @return void
	 */
	private function assert_requests_waited_for( int $expected, string $subject ): void {
		$this->assertNotEmpty( $this->timeouts, "fetching $subject should have made a request" );

		foreach ( $this->timeouts as $timeout ) {
			$this->assertEquals(
				$expected,
				$timeout,
				"fetching $subject should state how long it waits; without one it falls back to "
				. 'WordPress\'s default and holds the admin screen open for that long instead'
			);
		}
	}

	/**
	 * The welcome document gives up soonest, as no screen depends on it.
	 *
	 * @return void
	 */
	public function test_welcome_data_request_is_bounded(): void {
		new Welcome_Client();

		$this->assert_requests_waited_for( 3, 'the welcome document' );
	}

	/**
	 * A single cloud snippet states its own limit.
	 *
	 * @return void
	 */
	public function test_cloud_snippet_request_is_bounded(): void {
		( new Cloud_Public_Client( new Basic_Cloud_Connection() ) )->get_cloud_snippet( 1 );

		$this->assert_requests_waited_for( 10, 'a cloud snippet' );
	}

	/**
	 * A cloud snippet's revision states its own limit.
	 *
	 * @return void
	 */
	public function test_cloud_snippet_revision_request_is_bounded(): void {
		( new Cloud_Public_Client( new Basic_Cloud_Connection() ) )->get_cloud_snippet_revision( '1' );

		$this->assert_requests_waited_for( 10, "a cloud snippet's revision" );
	}

	/**
	 * Featured cloud snippets state their own limit.
	 *
	 * @return void
	 */
	public function test_featured_snippets_request_is_bounded(): void {
		( new Cloud_Public_Client( new Basic_Cloud_Connection() ) )->get_featured_snippets( 1, 10, [] );

		$this->assert_requests_waited_for( 10, 'featured cloud snippets' );
	}

	/**
	 * Searching the cloud is allowed the longest, as the reader is waiting on it.
	 *
	 * @return void
	 */
	public function test_cloud_search_request_is_bounded(): void {
		( new Cloud_Public_Client( new Basic_Cloud_Connection() ) )
			->fetch_search_results( 'search', 'term', 1, 10, [] );

		$this->assert_requests_waited_for( 15, 'cloud search results' );
	}

	/**
	 * The list of installable versions states its own limit.
	 *
	 * @return void
	 */
	public function test_available_versions_request_is_bounded(): void {
		Version_Switch::get_available_versions();

		$this->assert_requests_waited_for( 10, 'the list of available versions' );
	}
}
