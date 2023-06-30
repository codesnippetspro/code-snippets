<?php

namespace Code_Snippets\REST_API;

use WP_Error;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Controller;
use Code_Snippets\Snippet;
use Code_Snippets\Cloud\Cloud_Link;
use function Code_Snippets\code_snippets;
use function Code_Snippets\Settings\get_setting;


/**
 * Allows two way sync with Code Snippets Cloud API 
 *
 * @since   3.4.0
 * @package Code_Snippets
 */
class CloudAI_REST_Controller extends WP_REST_Controller {
	
	/**
	 * Locally  Token
	 *
	 * @var string
	 */
	private $local_token;

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->local_token = get_setting( 'cloud', 'local_token' );
	}


    /**
	 * Register REST routes.
	 */
	public function register_routes() {
        $route = '/' . $this->rest_base;

        register_rest_route(
			$this->namespace,
			$route . '/prompt',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'cloud_ai_prompt' ],
					// 'permission_callback' => [ $this, 'cloud_api_check' ],
					'args'                => $this->get_endpoint_args_for_item_schema( true ),
				],
				'schema' => [ $this, 'get_item_schema' ],
			]
		);

		register_rest_route(
			$this->namespace,
			$route . '/explain',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'cloud_ai_explain' ],
					// 'permission_callback' => [ $this, 'cloud_api_check' ],
					'args'                => $this->get_endpoint_args_for_item_schema( true ),
				],
				'schema' => [ $this, 'get_item_schema' ],
			]
		);

		register_rest_route(
			$this->namespace,
			$route . '/check',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'cloud_ai_check' ],
					// 'permission_callback' => [ $this, 'cloud_api_check' ],
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
	public function cloud_api_check( $request ): bool {
		
        //Get Cloud Token from Authorization Header Bearer
		$cloud_token = $request->get_header('access-control');
        
        if ( $cloud_token === $this->local_token ) {
            return true;
		}

		return false;
	}

	/**
	 * Create one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function cloud_ai_prompt( $request ) {
		
		
		//Construct success response
		$response = [
			'status'  => 'success',
			'message' => __( 'Snippet created', 'code-snippets' ),
		];

		//Return the response
		return rest_ensure_response( $response );
	}

	/**
	 * Create one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function cloud_ai_explain( $request ) {
		
		
		//Construct success response
		$response = [
			'status'  => 'success',
			'message' => __( 'Snippet created', 'code-snippets' ),
		];

		//Return the response
		return rest_ensure_response( $response );
	}

	/**
	 * Create one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function cloud_ai_check( $request ) {
		
		
		//Construct success response
		$response = [
			'status'  => 'success',
			'message' => __( 'Snippet created', 'code-snippets' ),
		];

		//Return the response
		return rest_ensure_response( $response );
	}
}