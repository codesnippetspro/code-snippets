<?php

namespace Code_Snippets\Client;

use Code_Snippets\UnitTestCase;
use WP_Error;

/**
 * Tests for the documentation index client.
 *
 * @group search
 */
class Docs_Client_Test extends UnitTestCase {

	/**
	 * URLs requested during a test.
	 *
	 * @var string[]
	 */
	private array $requested_urls = [];

	/**
	 * Whether requests to the documentation site should fail.
	 *
	 * @var bool
	 */
	private bool $should_fail = false;

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->requested_urls = [];
		$this->should_fail = false;
		Docs_Client::clear_cache();

		add_filter( 'pre_http_request', [ $this, 'mock_request' ], 10, 3 );
	}

	/**
	 * Tear down after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'pre_http_request', [ $this, 'mock_request' ] );
		Docs_Client::clear_cache();

		parent::tear_down();
	}

	/**
	 * Intercept requests to the documentation site.
	 *
	 * @param mixed  $preempt     Existing preempted value.
	 * @param array  $parsed_args Parsed HTTP request arguments.
	 * @param string $url         Requested URL.
	 *
	 * @return mixed
	 */
	public function mock_request( $preempt, array $parsed_args, string $url ) {
		if ( false === strpos( $url, 'codesnippets.pro/wp-content/uploads/cs_docs/cs-docs-index.json' ) ) {
			return $preempt;
		}

		$this->requested_urls[] = $url;

		if ( $this->should_fail ) {
			return new WP_Error( 'http_request_failed', 'Could not connect.' );
		}

		$body = [
			'generated' => 1790892144,
			'articles'  => [
				[
					'title'    => 'Recovering with <em>safe mode</em>',
					'url'      => 'https://codesnippets.pro/doc/safe-mode/',
					'excerpt'  => '<p>What to do when a snippet <strong>breaks</strong> your site.</p>',
					'category' => [ 'Emergency Fixes', 'FAQ' ],
				],
				[
					'title'    => 'Hosted elsewhere',
					'url'      => 'https://example.com/doc/elsewhere/',
					'excerpt'  => '',
					'category' => [],
				],
				[
					'title'   => '',
					'url'     => 'https://codesnippets.pro/doc/untitled/',
					'excerpt' => 'No title.',
				],
				'not an article',
			],
		];

		return [
			'headers'  => [],
			'body'     => wp_json_encode( $body ),
			'response' => [
				'code'    => 200,
				'message' => 'OK',
			],
			'cookies'  => [],
		];
	}

	/**
	 * Articles are reduced to plain text, and those that are untitled or hosted elsewhere are dropped.
	 *
	 * @return void
	 */
	public function test_builds_a_plain_text_index() {
		$articles = ( new Docs_Client() )->get_articles();

		$this->assertSame(
			[
				[
					'id'       => substr( md5( 'https://codesnippets.pro/doc/safe-mode/' ), 0, 12 ),
					'title'    => 'Recovering with safe mode',
					'url'      => 'https://codesnippets.pro/doc/safe-mode/',
					'text'     => 'What to do when a snippet breaks your site.',
					'category' => 'Emergency Fixes',
				],
			],
			$articles
		);
	}

	/**
	 * A response that is not an index yields no articles.
	 *
	 * @return void
	 */
	public function test_ignores_a_malformed_index() {
		remove_filter( 'pre_http_request', [ $this, 'mock_request' ] );

		$respond_with_list = fn() => [
			'headers'  => [],
			'body'     => wp_json_encode( [ 'articles' => 'none' ] ),
			'response' => [
				'code'    => 200,
				'message' => 'OK',
			],
			'cookies'  => [],
		];

		add_filter( 'pre_http_request', $respond_with_list );
		$articles = ( new Docs_Client() )->get_articles();
		remove_filter( 'pre_http_request', $respond_with_list );

		$this->assertSame( [], $articles );
	}

	/**
	 * A fetched index is reused instead of being requested again.
	 *
	 * @return void
	 */
	public function test_caches_the_index() {
		$client = new Docs_Client();
		$client->get_articles();
		$requests = count( $this->requested_urls );

		$this->assertCount( 1, $client->get_articles() );
		$this->assertCount( $requests, $this->requested_urls );
	}

	/**
	 * A failed fetch yields no articles, and is not retried straight away.
	 *
	 * @return void
	 */
	public function test_failure_yields_no_articles_and_backs_off() {
		$this->should_fail = true;
		$client = new Docs_Client();

		$this->assertSame( [], $client->get_articles() );
		$this->assertCount( 1, $this->requested_urls );

		$this->should_fail = false;

		$this->assertSame( [], $client->get_articles() );
		$this->assertCount( 1, $this->requested_urls );
	}
}
