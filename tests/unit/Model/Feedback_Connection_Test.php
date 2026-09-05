<?php
/**
 * Tests for the feedback reporting connection.
 *
 * @package Code_Snippets
 */

namespace Code_Snippets\Model;

use Code_Snippets\UnitTestCase;

/**
 * Requests are signed with a per-site secret, and a secret is only ever stored once it
 * matches the shape the cloud issues.
 *
 * @group feedback
 */
class Feedback_Connection_Test extends UnitTestCase {

	/**
	 * Connection under test.
	 *
	 * @var Feedback_Connection
	 */
	private Feedback_Connection $connection;

	/**
	 * A credential pair of the shape the cloud issues.
	 *
	 * @var array<string, mixed>
	 */
	private array $credentials;

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->connection = new Feedback_Connection();
		$this->credentials = [
			'public_id' => str_repeat( 'a', 20 ),
			'secret'    => str_repeat( 'b', 40 ),
			'offset'    => 0,
		];
	}

	/**
	 * Remove what a test stored or registered.
	 *
	 * @return void
	 */
	public function tear_down() {
		delete_option( Feedback_Connection::CREDENTIALS_OPTION );
		remove_all_filters( 'code_snippets_feedback_endpoint_url' );
		remove_all_filters( 'code_snippets_feedback_key' );

		parent::tear_down();
	}

	/**
	 * The reporting endpoint hangs off the cloud API URL the rest of the plugin uses.
	 *
	 * @return void
	 */
	public function test_endpoint_url_is_built_from_the_cloud_api_url(): void {
		$this->assertSame(
			$this->connection->get_api_url() . '/beta-reports',
			$this->connection->get_endpoint_url()
		);

		$this->assertSame(
			$this->connection->get_api_url() . '/beta-reports/register',
			$this->connection->get_endpoint_url( 'register' )
		);
	}

	/**
	 * Sites pointed at another cloud host can redirect reports with the endpoint filter.
	 *
	 * @return void
	 */
	public function test_endpoint_url_is_filterable(): void {
		add_filter( 'code_snippets_feedback_endpoint_url', static fn() => 'https://example.com/reports' );

		$this->assertSame( 'https://example.com/reports', $this->connection->get_endpoint_url() );
	}

	/**
	 * The same request always signs the same way, and any change to it does not.
	 *
	 * @return void
	 */
	public function test_signature_covers_the_method_uri_and_body(): void {
		$first = $this->connection->get_signature_headers( $this->credentials, 'post', '/api/v1/beta-reports', '{}' );
		$second = $this->connection->get_signature_headers( $this->credentials, 'POST', '/api/v1/beta-reports', '{}' );
		$other_body = $this->connection->get_signature_headers( $this->credentials, 'POST', '/api/v1/beta-reports', '{"a":1}' );
		$other_uri = $this->connection->get_signature_headers( $this->credentials, 'POST', '/api/v1/beta-reports/search', '{}' );
		$other_method = $this->connection->get_signature_headers( $this->credentials, 'GET', '/api/v1/beta-reports', '{}' );

		$this->assertSame( $first['X-CS-Signature'], $second['X-CS-Signature'] );
		$this->assertNotSame( $first['X-CS-Signature'], $other_body['X-CS-Signature'] );
		$this->assertNotSame( $first['X-CS-Signature'], $other_uri['X-CS-Signature'] );
		$this->assertNotSame( $first['X-CS-Signature'], $other_method['X-CS-Signature'] );
		$this->assertSame( $this->credentials['public_id'], $first['X-CS-Site-Id'] );
	}

	/**
	 * A site whose clock disagrees with the cloud signs with the corrected time.
	 *
	 * @return void
	 */
	public function test_signature_timestamp_includes_the_stored_offset(): void {
		$this->credentials['offset'] = 500;

		$timestamp = (int) $this->connection->get_signature_headers( $this->credentials, 'GET', '/x', '' )['X-CS-Timestamp'];

		$this->assertGreaterThanOrEqual( time() + 495, $timestamp );
		$this->assertLessThanOrEqual( time() + 505, $timestamp );
	}

	/**
	 * Anything that is not a credential pair of the issued shape is refused.
	 *
	 * @return void
	 */
	public function test_malformed_credentials_are_rejected(): void {
		$short_id = [
			'public_id' => 'short',
			'secret'    => str_repeat( 'b', 40 ),
		];

		$bad_secret = [
			'public_id' => str_repeat( 'a', 20 ),
			'secret'    => 'not-alphanumeric!',
		];

		$this->assertTrue( $this->connection->is_valid_credentials( $this->credentials ) );
		$this->assertFalse( $this->connection->is_valid_credentials( [] ) );
		$this->assertFalse( $this->connection->is_valid_credentials( $short_id ) );
		$this->assertFalse( $this->connection->is_valid_credentials( $bad_secret ) );
		$this->assertFalse( $this->connection->is_valid_credentials( [ 'public_id' => str_repeat( 'a', 20 ) ] ) );
	}

	/**
	 * Credentials survive a round trip, and are gone once deleted.
	 *
	 * @return void
	 */
	public function test_credentials_round_trip(): void {
		$this->credentials['offset'] = 3;

		$this->connection->save_credentials( $this->credentials );
		$this->assertSame( $this->credentials, $this->connection->get_credentials() );

		$this->connection->delete_credentials();
		$this->assertSame( [], $this->connection->get_credentials() );
	}

	/**
	 * The secret is not worth loading on every request, so it is not autoloaded.
	 *
	 * @return void
	 */
	public function test_credentials_are_not_autoloaded(): void {
		$this->connection->save_credentials( $this->credentials );

		wp_cache_delete( 'alloptions', 'options' );

		$this->assertArrayNotHasKey( Feedback_Connection::CREDENTIALS_OPTION, wp_load_alloptions() );
	}

	/**
	 * A signature covers the query string as well as the path.
	 *
	 * @return void
	 */
	public function test_request_uri_keeps_the_query_string(): void {
		$this->assertSame(
			'/api/v1/beta-reports/search?q=hello',
			Feedback_Connection::get_request_uri( 'https://example.com/api/v1/beta-reports/search?q=hello' )
		);

		$this->assertSame(
			'/api/v1/beta-reports',
			Feedback_Connection::get_request_uri( 'https://example.com/api/v1/beta-reports' )
		);
	}

	/**
	 * Requests identify the programme, the site and the edition running.
	 *
	 * @return void
	 */
	public function test_request_headers_carry_the_programme_key_and_edition(): void {
		$headers = $this->connection->get_request_headers();

		$this->assertSame( 'Bearer ' . $this->connection->get_key(), $headers['Authorization'] );
		$this->assertContains( $headers['X-CS-Edition'], [ 'free', 'pro' ] );
		$this->assertSame( site_url(), $headers['X-CS-Site'] );
		$this->assertSame( 'application/json', $headers['Accept'] );
	}

	/**
	 * The programme key can be replaced without editing the plugin.
	 *
	 * @return void
	 */
	public function test_programme_key_is_filterable(): void {
		add_filter( 'code_snippets_feedback_key', static fn() => 'csb_replacement' );

		$this->assertSame( 'csb_replacement', $this->connection->get_key() );
	}
}
