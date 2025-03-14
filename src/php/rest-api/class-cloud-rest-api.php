<?php

namespace Code_Snippets\REST_API;

use Code_Snippets\Cloud\Cloud_API;
use Code_Snippets\Cloud\Cloud_GPT_API;
use Code_Snippets\Snippet;
use WP_Error;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use Code_Snippets\Cloud\Cloud_Link;
use function Code_Snippets\code_snippets;
use function Code_Snippets\save_snippet;
use const Code_Snippets\REST_API_NAMESPACE;


/**
 * Allows two-way sync with Code Snippets Cloud API.
 *
 * @since   3.4.0
 * @package Code_Snippets
 */
final class Cloud_REST_API {

	/**
	 * Current API version.
	 */
	const VERSION = 1;

	/**
	 * The base route for these API endpoints.
	 */
	const BASE_ROUTE = 'cloud';

	/**
	 * Instance of Cloud API class.
	 *
	 * @var Cloud_API
	 */
	private Cloud_API $cloud_api;

	/**
	 * Class constructor.
	 *
	 * @param Cloud_API $cloud_api Cloud API instance.
	 */
	public function __construct( Cloud_API $cloud_api ) {
		$this->cloud_api = $cloud_api;
	}

	/**
	 * Retrieve this controller's REST API base path, including namespace.
	 *
	 * @return string
	 */
	public static function get_base_route(): string {
		return REST_API_NAMESPACE . self::VERSION . '/' . self::BASE_ROUTE;
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes() {
		$namespace = REST_API_NAMESPACE . self::VERSION;

		register_rest_route(
			$namespace,
			self::BASE_ROUTE . '/snippet',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_item_from_cloud' ],
					'permission_callback' => [ $this, 'cloud_permission_check' ],
					'args'                => rest_get_endpoint_args_for_schema( $this->get_cloud_snippet_schema() ),
				],
				'schema' => [ $this, 'get_cloud_snippet_schema' ],
			]
		);

		register_rest_route(
			$namespace,
			self::BASE_ROUTE,
			[
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'remove_sync' ],
					'permission_callback' => [ $this, 'cloud_permission_check' ],
					'args'                => [],
				],
			]
		);

		register_rest_route(
			$namespace,
			self::BASE_ROUTE . '/ai/prompt',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'cloud_ai_prompt' ],
					'permission_callback' => [ $this, 'cloud_permission_check' ],
					'args'                => [
						'prompt' => [
							'description' => esc_html__( 'Prompt to use when generating snippet.', 'code-snippets' ),
							'required'    => true,
							'type'        => 'string',
						],
						'type'   => [
							'description' => esc_html__( 'Language type to use when generating code.', 'code-snippets' ),
							'required'    => true,
							'type'        => 'string',
							'enum'        => Cloud_GPT_API::VALID_PROMPT_TYPES,
						],
					],
				],
			]
		);

		register_rest_route(
			$namespace,
			self::BASE_ROUTE . '/ai/explain',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'cloud_ai_explain' ],
					'permission_callback' => [ $this, 'cloud_permission_check' ],
					'args'                => [
						'code'  => [
							'description' => esc_html__( 'Snippet code to use for generating an explanation.', 'code-snippets' ),
							'required'    => true,
							'type'        => 'string',
						],
						'field' => [
							'description' => esc_html__( 'Snippet field to target when generating explanation.', 'code-snippets' ),
							'required'    => true,
							'type'        => 'string',
							'enum'        => Cloud_GPT_API::VALID_EXPLAIN_FIELDS,
						],
					],
				],
			]
		);
	}

	/**
	 * Check the request from Cloud API is valid
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return boolean
	 */
	public function cloud_permission_check( WP_REST_Request $request ): bool {
		return $request->get_header( 'Access-Control' ) === $this->cloud_api->get_local_token();
	}

	/**
	 * Get the schema for a cloud snippet request body.
	 *
	 * @return array
	 */
	public function get_cloud_snippet_schema(): array {
		static $schema = null;

		if ( ! is_null( $schema ) ) {
			return $schema;
		}

		$schema = [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'cloud snippet',
			'type'       => 'object',
			'properties' => [
				'id'          => [
					'description' => esc_html__( 'Cloud snippet identifier.', 'code-snippets' ),
					'type'        => 'string',
				],
				'name'        => [
					'description' => esc_html__( 'Title of cloud snippet.', 'code-snippets' ),
					'type'        => 'string',
				],
				'description' => [
					'description' => esc_html__( 'Descriptive text associated with snippet.', 'code-snippets' ),
					'type'        => 'string',
				],
				'code'        => [
					'description' => esc_html__( 'Executable snippet code.', 'code-snippets' ),
					'type'        => 'string',
				],
				'scope'       => [
					'description' => esc_html__( 'Context in which the snippet is executable.', 'code-snippets' ),
					'type'        => 'string',
				],
				'created'     => [
					'description' => esc_html__( 'Date and time when the snippet was last created, in ISO format.', 'code-snippets' ),
					'type'        => 'string',
				],
				'revision'    => [
					'description' => esc_html__( 'Snippet revision number.', 'code-snippets' ),
					'type'        => 'integer',
				],
			],
		];

		return $schema;
	}

	/**
	 * Create one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item_from_cloud( WP_REST_Request $request ) {
		$body = json_decode( $request->get_body() );
		$snippet_data = json_decode( $body[0], true );

		$snippet = new Snippet();

		$snippet->name = $snippet_data['name'];
		$snippet->desc = $snippet_data['description'];
		$snippet->code = $snippet_data['code'];
		$snippet->scope = $snippet_data['scope'];
		$snippet->modified = $snippet_data['created'];
		$snippet->revision = $snippet_data['revision'] ?? 1;
		$snippet->cloud_id = $snippet_data['id'] . '_0'; // Set to not owner.
		$snippet->shared_network = false;
		$snippet->network = false;
		$snippet->active = false;

		$result = save_snippet( $snippet );

		if ( ! $result ) {
			return new WP_Error(
				'rest_cannot_create',
				__( 'The snippet could not be created.', 'code-snippets' ),
				[ 'status' => 500 ]
			);
		}

		$link = new Cloud_Link();
		$link->local_id = $snippet_data['id'];
		$link->cloud_id = $snippet->cloud_id;
		$link->is_owner = false;
		$link->in_codevault = false;
		$link->update_available = false;

		code_snippets()->cloud_api->add_cloud_link( $link );

		$response = [
			'status'  => 'success',
			'message' => __( 'Snippet created', 'code-snippets' ),
		];

		return rest_ensure_response( $response );
	}

	/**
	 * Remove sync.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function remove_sync() {
		code_snippets()->cloud_api->remove_sync();

		return rest_ensure_response(
			[
				'status'  => 'success',
				'message' => __( 'Sync has been revoked.', 'code-snippets' ),
			]
		);
	}

	/**
	 * Get the response from Cloud AI API /prompt endpoint
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function cloud_ai_prompt( WP_REST_Request $request ) {
		$prompt = $request->get_param( 'prompt' );
		$type = $request->get_param( 'type' );

		$cloud_ai_api = new Cloud_GPT_API( $this->cloud_api );
		$result = $cloud_ai_api->prompt( $prompt, $type );

		return is_wp_error( $result ) ?
			$result :
			rest_ensure_response(
				[
					'status'  => 'success',
					'message' => $result,
				]
			);
	}

	/**
	 * Get the response from Cloud AI API /explain endpoint.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function cloud_ai_explain( WP_REST_Request $request ) {
		$code = $request->get_param( 'code' );
		$field = $request->get_param( 'field' );

		$cloud_ai_api = new Cloud_GPT_API( $this->cloud_api );
		$result = $cloud_ai_api->explain( $code, $field );

		return is_wp_error( $result ) ?
			$result :
			rest_ensure_response(
				[
					'status'  => 'success',
					'message' => $result,
				]
			);
	}
}
