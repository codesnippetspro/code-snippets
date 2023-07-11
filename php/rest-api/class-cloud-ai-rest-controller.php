<?php

namespace Code_Snippets\REST_API;

use WP_Error;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use Code_Snippets\Cloud\Cloud_AI;
use function Code_Snippets\Settings\get_setting;


/**
 * Allows two way sync with Code Snippets Cloud API 
 *
 * @since   3.4.0
 * @package Code_Snippets
 */
class CloudAI_REST_Controller extends Snippets_REST_Controller {
	
	/**
	 * Locally  Token
	 *
	 * @var string
	 */
	private $local_token;
	private $cloud_ai_api;

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->local_token = get_setting( 'cloud', 'cloud_token' );
		$this->cloud_ai_api = new Cloud_AI();
	}


    /**
	 * Register REST routes.
	 */
	public function register_routes() {
        $route_base = '/cloud-ai';

        register_rest_route(
			$this->namespace,
			$route_base . '/prompt',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'cloud_ai_prompt' ],
					'permission_callback' => [ $this, 'cloud_api_auth' ],
					'args'                => $this->get_endpoint_args_for_item_schema( true ),
				],
				'schema' => [ $this, 'get_item_schema' ],
			]
		);

		register_rest_route(
			$this->namespace,
			$route_base . '/explain',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'cloud_ai_explain' ],
					'permission_callback' => [ $this, 'cloud_api_auth' ],
					'args'                => $this->get_endpoint_args_for_item_schema( true ),
				],
				'schema' => [ $this, 'get_item_schema' ],
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
	public function cloud_api_auth( $request ): bool {
		
        //Get Cloud Token from Authorization Header Bearer
		$cloud_token = $request->get_header('access-control');
        $cs_cloud_api_nonce = $request->get_param('nonce') ?? '';

        if ( $cloud_token === $this->local_token && wp_verify_nonce( $cs_cloud_api_nonce, 'cs_cloud_ai_api' )) {
            return true;
		}

		return false;
	}

	/**
	 * Get the response from Cloud AI API /prompt endpoint
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function cloud_ai_prompt( $request ) {
		
		$prompt = $request->get_param('prompt') ?? '';
		
		if ( empty( $prompt ) ) {
			return new WP_Error( 'rest_empty_prompt', __( 'Prompt cannot be empty', 'code-snippets' ), [ 'status' => 400 ] );
		}

		$api_response = $this->cloud_ai_api->prompt($prompt);
		$response = [
			'status'  => 'success',
			'message' => $api_response,
		];

		return rest_ensure_response( $response );
	}

	/**
	 * Get the response from Cloud AI API /explain endpoint
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function cloud_ai_explain( $request ) {

		$prompt = $request->get_param('prompt') ?? '';
		
		if ( empty( $prompt ) ) {
			return new WP_Error( 'rest_empty_prompt', __( 'Prompt cannot be empty', 'code-snippets' ), [ 'status' => 400 ] );
		}

		$api_response = $this->cloud_ai_api->explain($prompt);
		$response = [
			'status'  => 'success',
			'message' => $api_response,
			// 'key' => wp_create_nonce( 'cs_cloud_ai_api' ),
		];

		return rest_ensure_response( $response );
	}


}