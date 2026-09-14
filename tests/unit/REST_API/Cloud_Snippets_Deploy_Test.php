<?php

namespace Code_Snippets\REST_API;

use Code_Snippets\UnitTestCase;
use WP_REST_Request;
use function Code_Snippets\get_snippet;
use function Code_Snippets\get_snippet_by_cloud_id;
use function Code_Snippets\get_snippets;

/**
 * Tests for the cloud-app deploy (deploy_item) endpoint on the snippets REST controller.
 *
 * Proves the cloud -> site deploy route is registered and authorizes on a
 * valid site token alone, with no logged-in WP user (server-to-server push).
 *
 * @group rest-api
 */
class Cloud_Snippets_Deploy_Test extends UnitTestCase {

	/**
	 * REST API namespace and base route.
	 *
	 * @var string
	 */
	protected string $endpoint = '/code-snippets/v1/cloud/snippets';

	/**
	 * The live connection's local_token as it was before this test mutated it,
	 * so it can be restored in tear_down() without leaking state (empty or
	 * otherwise) into other test files that share the same live connection.
	 *
	 * @var string
	 */
	private string $original_local_token = '';

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->original_local_token = $this->get_connection_token();

		wp_set_current_user( 0 );
		add_filter( 'pre_http_request', [ $this, 'mock_codevault_request' ], 10, 3 );
	}

	/**
	 * Tear down after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'pre_http_request', [ $this, 'mock_codevault_request' ] );

		// Restore the shared live connection's local_token so it doesn't leak
		// into other test files that depend on its original value.
		$this->seed_connection_token( $this->original_local_token );

		parent::tear_down();
	}

	/**
	 * Read the active cloud connection's local token.
	 *
	 * @return string
	 */
	private function get_connection_token(): string {
		$plugin = \Code_Snippets\code_snippets();

		$property = new \ReflectionProperty( $plugin, 'cloud_connection' );
		$property->setAccessible( true );

		return $property->getValue( $plugin )->get_local_token();
	}

	/**
	 * Short-circuit the outbound "all codevault snippets" request that
	 * `deploy_item()` triggers indirectly via `Cloud_Library_Controller::add_cloud_link()`,
	 * so posting a deploy in these tests doesn't depend on real network access.
	 *
	 * @param mixed  $preempt     Existing preempted value.
	 * @param array  $parsed_args Parsed HTTP request arguments.
	 * @param string $url         Requested URL.
	 *
	 * @return mixed
	 */
	public function mock_codevault_request( $preempt, array $parsed_args, string $url ) {
		if ( false === strpos( $url, 'private/allsnippets' ) ) {
			return $preempt;
		}

		return [
			'headers'  => [],
			'body'     => wp_json_encode(
				[
					'data' => [],
					'meta' => [
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
	 * Seed a real, non-empty local_token directly onto the live connection
	 * via reflection. The live connection's settings were loaded (empty) at
	 * bootstrap and won't pick up a changed option, so this sets it directly
	 * on the shared instance the routes actually authenticate against.
	 *
	 * @param string $token Token value to set.
	 *
	 * @return string The token that was set.
	 */
	private function seed_connection_token( string $token = 'test-site-token-abc123' ): string {
		$plugin = \Code_Snippets\code_snippets();
		$conn_prop = new \ReflectionProperty( $plugin, 'cloud_connection' );
		$conn_prop->setAccessible( true );
		$connection = $conn_prop->getValue( $plugin );

		$settings_prop = new \ReflectionProperty( $connection, 'settings' );
		$settings_prop->setAccessible( true );
		$settings = $settings_prop->getValue( $connection );
		$settings['local_token'] = $token;
		$settings_prop->setValue( $connection, $settings );

		return $token;
	}

	/**
	 * Build and dispatch a deploy POST request with the given snippet payload
	 * and (optionally) a token header.
	 *
	 * @param array       $snippet_data Snippet payload (cloud snippet shape).
	 * @param string|null $token        Access-Control token to send, or null to omit.
	 *
	 * @return \WP_REST_Response
	 */
	private function do_deploy_request( array $snippet_data, ?string $token ) {
		$request = new WP_REST_Request( 'POST', $this->endpoint );

		if ( null !== $token ) {
			$request->add_header( 'Access-Control', $token );
		}

		// deploy_item() reads $request->get_body() as a JSON array whose
		// first element is itself a JSON-encoded snippet object.
		$request->set_body( wp_json_encode( [ wp_json_encode( $snippet_data ) ] ) );

		return rest_do_request( $request );
	}

	/**
	 * Base valid snippet payload, as the cloud app would send it.
	 *
	 * @param string|int $id Cloud snippet id.
	 *
	 * @return array
	 */
	private function make_snippet_payload( $id ): array {
		return [
			'id'          => $id,
			'name'        => 'Deployed Snippet',
			'description' => 'Deployed from cloud',
			'code'        => '<?php echo "deployed"; ?>',
			'scope'       => 'global',
			'created'     => '2026-03-10 12:00:00',
			'revision'    => 1,
		];
	}

	/**
	 * A valid token with no logged-in user can deploy a snippet, which is
	 * created locally, linked as non-owner, with the right cloud_id.
	 *
	 * @return void
	 */
	public function test_deploy_with_valid_token_and_no_user_creates_snippet(): void {
		$response = $this->do_deploy_request( $this->make_snippet_payload( 601 ), $this->seed_connection_token() );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertIsInt( $data['snippet_id'] );
		$this->assertGreaterThan( 0, $data['snippet_id'] );
		$this->assertArrayHasKey( 'active', $data );
		$this->assertSame( 'Snippet deployed', $data['message'] );

		$snippets = get_snippets();
		$matches = array_values(
			array_filter(
				$snippets,
				static function ( $snippet ) {
					return 0 === strpos( (string) $snippet->cloud_id, '601' );
				}
			)
		);

		$this->assertCount( 1, $matches );
		$deployed = $matches[0];

		// save_snippet() strips the opening/closing PHP tags from PHP-scoped
		// code before storing them (they're re-added at execution time).
		$this->assertSame( 'Deployed Snippet', $deployed->name );
		$this->assertSame( ' echo "deployed"; ', $deployed->code );
		$this->assertSame( 'global', $deployed->scope );
		$this->assertFalse( $deployed->is_cloud_owner );

		// The response reports the exact local snippet id and activation state.
		$this->assertSame( (int) $deployed->id, $data['snippet_id'] );
		$this->assertSame( (bool) $deployed->active, $data['active'] );
	}

	/**
	 * Re-posting the same cloud_id updates the existing local snippet
	 * instead of creating a duplicate row.
	 *
	 * @return void
	 */
	public function test_deploy_same_cloud_id_twice_updates_not_duplicates(): void {
		$token = $this->seed_connection_token();

		$first = $this->do_deploy_request( $this->make_snippet_payload( 602 ), $token );
		$this->assertSame( 200, $first->get_status() );

		$payload = $this->make_snippet_payload( 602 );
		$payload['name'] = 'Deployed Snippet Updated';
		$payload['code'] = '<?php echo "updated"; ?>';

		$second = $this->do_deploy_request( $payload, $token );
		$this->assertSame( 200, $second->get_status() );

		$snippets = get_snippets();
		$matches = array_values(
			array_filter(
				$snippets,
				static function ( $snippet ) {
					return 0 === strpos( (string) $snippet->cloud_id, '602' );
				}
			)
		);

		$this->assertCount( 1, $matches, 'Re-posting the same cloud_id must not create a duplicate row.' );
		$this->assertSame( 'Deployed Snippet Updated', $matches[0]->name );
		$this->assertSame( ' echo "updated"; ', $matches[0]->code );
	}

	/**
	 * `active:true` in the payload yields a locally-active snippet; omitting
	 * it yields an inactive one.
	 *
	 * @return void
	 */
	public function test_deploy_active_flag_controls_local_active_state(): void {
		$token = $this->seed_connection_token();

		$active_payload = $this->make_snippet_payload( 603 );
		$active_payload['active'] = true;
		$active_response = $this->do_deploy_request( $active_payload, $token );
		$this->assertSame( 200, $active_response->get_status() );

		$active_snippet = get_snippet_by_cloud_id( '603_0' );
		$this->assertNotNull( $active_snippet );
		$this->assertTrue( $active_snippet->active );

		$inactive_payload = $this->make_snippet_payload( 604 );
		$inactive_response = $this->do_deploy_request( $inactive_payload, $token );
		$this->assertSame( 200, $inactive_response->get_status() );

		$inactive_snippet = get_snippet_by_cloud_id( '604_0' );
		$this->assertNotNull( $inactive_snippet );
		$this->assertFalse( $inactive_snippet->active );
	}

	/**
	 * A `conditions` array creates one scope=condition sibling snippet and
	 * sets the deployed snippet's condition_id. A flat rule list is wrapped
	 * as a single group.
	 *
	 * @return void
	 */
	public function test_deploy_with_conditions_creates_sibling_and_links_condition_id(): void {
		$token = $this->seed_connection_token();

		$before_condition_snippets = count(
			array_filter(
				get_snippets(),
				static function ( $snippet ) {
					return 'condition' === $snippet->scope;
				}
			)
		);

		$payload = $this->make_snippet_payload( 605 );
		$payload['conditions'] = [
			[
				'subject' => 'page',
				'operator' => 'is',
				'value' => 'home',
			],
		];
		$payload['condition_name'] = 'Home page only';

		$response = $this->do_deploy_request( $payload, $token );
		$this->assertSame( 200, $response->get_status() );

		$snippets = get_snippets();

		$condition_snippets = array_values(
			array_filter(
				$snippets,
				static function ( $snippet ) {
					return 'condition' === $snippet->scope;
				}
			)
		);
		$this->assertCount( $before_condition_snippets + 1, $condition_snippets );

		$condition_snippet = end( $condition_snippets );
		$this->assertSame( 'Home page only', $condition_snippet->name );

		// The flat rule list [{subject:...}] must be wrapped as a single group [[...]].
		$decoded_conditions = json_decode( $condition_snippet->code, true );
		$this->assertIsArray( $decoded_conditions );
		$this->assertCount( 1, $decoded_conditions );
		$this->assertIsArray( $decoded_conditions[0] );
		$this->assertSame( 'page', $decoded_conditions[0][0]['subject'] );

		// Rules must be stored in the canonical shape read by the condition
		// editor and evaluator: tested values under an `object` list, not the
		// cloud payload's `value` key (which nothing in the plugin reads).
		$this->assertSame( 'is', $decoded_conditions[0][0]['operator'] );
		$this->assertSame( [ 'home' ], $decoded_conditions[0][0]['object'] );
		$this->assertArrayNotHasKey( 'value', $decoded_conditions[0][0] );

		$deployed = get_snippet_by_cloud_id( '605_0' );
		$this->assertNotNull( $deployed );
		$this->assertSame( (int) $condition_snippet->id, (int) $deployed->condition_id );
	}

	/**
	 * Cloud rules arrive with values under `value` (scalar or list) while some
	 * builds already send `object` lists. All shapes must be normalised to
	 * `object` arrays, and unknown rule keys must not be persisted.
	 *
	 * @return void
	 */
	public function test_deploy_conditions_normalise_value_shapes_to_object_arrays(): void {
		$token = $this->seed_connection_token();

		$payload = $this->make_snippet_payload( 606 );
		$payload['conditions'] = [
			[
				[
					'subject'  => 'page',
					'operator' => 'in',
					'value'    => [ 'home', 'about' ],
				],
				[
					'subject'  => 'user_role',
					'operator' => 'is',
					'object'   => [ 'editor' ],
				],
			],
			[
				[
					'subject'  => 'date',
					'operator' => 'before',
					'value'    => '2026-01-01',
					'extra'    => 'should-not-persist',
				],
			],
		];
		$payload['condition_name'] = 'Shape test condition';

		$response = $this->do_deploy_request( $payload, $token );
		$this->assertSame( 200, $response->get_status() );

		$deployed = get_snippet_by_cloud_id( '606_0' );
		$this->assertNotNull( $deployed );
		$this->assertGreaterThan( 0, (int) $deployed->condition_id );

		$condition_snippet = get_snippet( (int) $deployed->condition_id );
		$this->assertNotNull( $condition_snippet );
		$decoded = json_decode( $condition_snippet->code, true );

		$this->assertSame( [ 'home', 'about' ], $decoded[0][0]['object'] );
		$this->assertSame( [ 'editor' ], $decoded[0][1]['object'] );
		$this->assertSame( [ '2026-01-01' ], $decoded[1][0]['object'] );
		$this->assertArrayNotHasKey( 'value', $decoded[0][0] );
		$this->assertArrayNotHasKey( 'value', $decoded[1][0] );
		$this->assertArrayNotHasKey( 'extra', $decoded[1][0] );
	}

	/**
	 * Count the condition-scope snippets currently stored.
	 *
	 * @return int
	 */
	private function count_condition_snippets(): int {
		return count(
			array_filter(
				get_snippets(),
				static function ( $snippet ) {
					return 'condition' === $snippet->scope;
				}
			)
		);
	}

	/**
	 * A payload carrying conditions plus an optional shared condition_key.
	 *
	 * @param string|int  $id            Cloud snippet id.
	 * @param string|null $condition_key Shared per-deploy key, or null to omit.
	 *
	 * @return array
	 */
	private function make_conditional_payload( $id, ?string $condition_key ): array {
		$payload = $this->make_snippet_payload( $id );
		$payload['conditions'] = [
			[
				'subject'  => 'page',
				'operator' => 'is',
				'value'    => 'home',
			],
		];
		$payload['condition_name'] = 'Shared deploy condition';

		if ( null !== $condition_key ) {
			$payload['condition_key'] = $condition_key;
		}

		return $payload;
	}

	/**
	 * When a stable condition_key is present, every snippet in the deploy shares
	 * ONE condition: the first request creates it, later requests reuse it, and
	 * all deployed snippets link to the same condition_id.
	 *
	 * @return void
	 */
	public function test_deploy_with_condition_key_shares_one_condition(): void {
		$token = $this->seed_connection_token();
		$before = $this->count_condition_snippets();
		$key = 'b1e7c0de-1111-4a2b-8c3d-000000000001';

		$first = $this->do_deploy_request( $this->make_conditional_payload( 701, $key ), $token );
		$second = $this->do_deploy_request( $this->make_conditional_payload( 702, $key ), $token );

		$this->assertSame( 200, $first->get_status() );
		$this->assertSame( 200, $second->get_status() );

		// Exactly one new condition for the whole deploy, not one per snippet.
		$this->assertSame( $before + 1, $this->count_condition_snippets() );

		$deployed_a = get_snippet_by_cloud_id( '701_0' );
		$deployed_b = get_snippet_by_cloud_id( '702_0' );
		$this->assertNotNull( $deployed_a );
		$this->assertNotNull( $deployed_b );
		$this->assertGreaterThan( 0, (int) $deployed_a->condition_id );

		// Both deployed snippets link to the same single shared condition.
		$this->assertSame( (int) $deployed_a->condition_id, (int) $deployed_b->condition_id );
	}

	/**
	 * A second deploy that reuses an existing condition_key must NOT create a
	 * duplicate condition, even across separate requests.
	 *
	 * @return void
	 */
	public function test_deploy_reuses_condition_for_existing_key(): void {
		$token = $this->seed_connection_token();
		$key = 'c0ffee00-2222-4a2b-8c3d-000000000002';

		$this->do_deploy_request( $this->make_conditional_payload( 703, $key ), $token );
		$after_first = $this->count_condition_snippets();

		$this->do_deploy_request( $this->make_conditional_payload( 704, $key ), $token );

		$this->assertSame( $after_first, $this->count_condition_snippets(), 'Reusing a condition_key must not create another condition.' );
	}

	/**
	 * Backward compatibility: without a condition_key (older cloud builds), each
	 * snippet still gets its OWN condition, preserving prior behaviour.
	 *
	 * @return void
	 */
	public function test_deploy_without_condition_key_creates_per_snippet_condition(): void {
		$token = $this->seed_connection_token();
		$before = $this->count_condition_snippets();

		$first = $this->do_deploy_request( $this->make_conditional_payload( 711, null ), $token );
		$second = $this->do_deploy_request( $this->make_conditional_payload( 712, null ), $token );

		$this->assertSame( 200, $first->get_status() );
		$this->assertSame( 200, $second->get_status() );

		// One condition per snippet, as before the shared-condition change.
		$this->assertSame( $before + 2, $this->count_condition_snippets() );

		$deployed_a = get_snippet_by_cloud_id( '711_0' );
		$deployed_b = get_snippet_by_cloud_id( '712_0' );
		$this->assertNotSame( (int) $deployed_a->condition_id, (int) $deployed_b->condition_id );
	}

	/**
	 * A POST with no logged-in user and a wrong/absent token is rejected.
	 *
	 * @return void
	 */
	public function test_deploy_with_invalid_token_and_no_user_is_rejected(): void {
		$response = $this->do_deploy_request( $this->make_snippet_payload( 606 ), 'not-the-right-token' );

		$this->assertContains( $response->get_status(), [ 401, 403 ] );

		// The rejection carries a display-safe message for the cloud app.
		$data = $response->get_data();
		$message = is_array( $data ) ? ( $data['message'] ?? '' ) : ( $data->message ?? '' );
		$this->assertSame( 'Invalid site token', $message );

		$this->assertNull( get_snippet_by_cloud_id( '606_0' ) );
	}

	/**
	 * A POST with no logged-in user and no token at all is rejected.
	 *
	 * @return void
	 */
	public function test_deploy_with_no_token_and_no_user_is_rejected(): void {
		$response = $this->do_deploy_request( $this->make_snippet_payload( 607 ), null );

		$this->assertContains( $response->get_status(), [ 401, 403 ] );

		$this->assertNull( get_snippet_by_cloud_id( '607_0' ) );
	}

	/**
	 * A never-connected site (empty local_token), no logged-in user, and a
	 * present-but-empty Access-Control header must be rejected outright, not
	 * fail open. Nothing gets deployed.
	 *
	 * @return void
	 */
	public function test_deploy_with_empty_token_and_empty_header_is_rejected(): void {
		$this->seed_connection_token( '' );

		$response = $this->do_deploy_request( $this->make_snippet_payload( 608 ), '' );

		$this->assertContains( $response->get_status(), [ 401, 403 ] );

		$this->assertNull( get_snippet_by_cloud_id( '608_0' ) );
	}

	/**
	 * A token-authorized POST with a malformed body (not the expected
	 * `[json-encoded-snippet]` shape) returns 422 with a display-safe body and
	 * creates no snippet, instead of a fatal/warning from an unguarded access.
	 *
	 * @return void
	 */
	public function test_deploy_with_malformed_body_is_rejected(): void {
		$token = $this->seed_connection_token();
		$before = count( get_snippets() );

		$request = new WP_REST_Request( 'POST', $this->endpoint );
		$request->add_header( 'Access-Control', $token );
		$request->set_body( '{}' );

		$response = rest_do_request( $request );

		$this->assertSame( 422, $response->get_status() );
		$data = $response->get_data();
		$this->assertFalse( $data['success'] );
		$this->assertNotEmpty( $data['message'] );
		$this->assertCount( $before, get_snippets(), 'A malformed body must not create a snippet.' );
	}

	/**
	 * A token-authorized POST whose body is valid JSON but not the expected
	 * array shape (e.g. a bare string) also returns 422 and creates nothing.
	 *
	 * @return void
	 */
	public function test_deploy_with_invalid_json_body_is_rejected(): void {
		$token = $this->seed_connection_token();

		$request = new WP_REST_Request( 'POST', $this->endpoint );
		$request->add_header( 'Access-Control', $token );
		$request->set_body( 'not json' );

		$response = rest_do_request( $request );

		$this->assertSame( 422, $response->get_status() );
		$this->assertFalse( $response->get_data()['success'] );
	}

	/**
	 * A token-authorized POST that is missing the snippet code returns 422 with
	 * a specific, display-safe "Missing snippet code" message and creates nothing.
	 *
	 * @return void
	 */
	public function test_deploy_with_missing_code_is_rejected(): void {
		$token = $this->seed_connection_token();
		$before = count( get_snippets() );

		$payload = $this->make_snippet_payload( 609 );
		$payload['code'] = '';

		$response = $this->do_deploy_request( $payload, $token );

		$this->assertSame( 422, $response->get_status() );
		$data = $response->get_data();
		$this->assertFalse( $data['success'] );
		$this->assertSame( 'Missing snippet code', $data['message'] );
		$this->assertCount( $before, get_snippets(), 'A payload missing code must not create a snippet.' );
	}

	/**
	 * An unexpected error while persisting the snippet is reported as a 500 with
	 * a safe body rather than surfacing a raw fatal. Forced by throwing from a
	 * hook that fires during save.
	 *
	 * @return void
	 */
	public function test_deploy_unexpected_error_returns_500(): void {
		$token = $this->seed_connection_token();

		$thrower = static function () {
			throw new \RuntimeException( 'boom' );
		};
		add_action( 'code_snippets/create_snippet', $thrower );
		add_action( 'code_snippets/update_snippet', $thrower );

		try {
			$response = $this->do_deploy_request( $this->make_snippet_payload( 610 ), $token );
		} finally {
			remove_action( 'code_snippets/create_snippet', $thrower );
			remove_action( 'code_snippets/update_snippet', $thrower );
		}

		$this->assertSame( 500, $response->get_status() );
		$data = $response->get_data();
		$this->assertFalse( $data['success'] );
		$this->assertNotEmpty( $data['message'] );
	}
}
