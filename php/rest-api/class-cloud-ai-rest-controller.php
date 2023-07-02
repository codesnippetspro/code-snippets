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
	private $fs_plugin_data;
	private $freemius_licence;

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->local_token = get_setting( 'cloud', 'cloud_token' );
		$fs_account_data = get_option('fs_accounts');
		
		// TODO: check if fs_account_data is not empty before accessing named properties
		$this->fs_plugin_data = $fs_account_data['plugins']['code-snippets'];
		$this->freemius_licence = $fs_account_data['all_licenses'][$this->fs_plugin_data->id][0]->secret_key;
	}


    /**
	 * Register REST routes.
	 */
	public function register_routes() {
        $route_base = '/cloudai';

        register_rest_route(
			$this->namespace,
			$route_base . '/prompt',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'cloud_ai_prompt' ],
					'permission_callback' => [ $this, 'cloud_api_check' ],
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
					'permission_callback' => [ $this, 'cloud_api_check' ],
					'args'                => $this->get_endpoint_args_for_item_schema( true ),
				],
				'schema' => [ $this, 'get_item_schema' ],
			]
		);

		register_rest_route(
			$this->namespace,
			$route_base . '/check',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'cloud_ai_check' ],
					// 'permission_callback' => [ $this, 'cloud_api_check' ],
					// 'permission_callback' => '__return_true',
					'args'                => $this->get_endpoint_args_for_item_schema( true ),
				],
				// 'schema' => [ $this, 'get_item_schema' ],
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
		
		$cloud_ai = new Cloud_AI();
		//Construct success response
		$response = [
			'status'  => 'success',
			'message' => [
				'local_token' => $this->local_token,
				'fs_plugin_data' => $this->fs_plugin_data,
				'freemius_licence' => $this->freemius_licence,
				'cloud_ai' => $cloud_ai->prompt('create a snippet that will add a new admin notice to the dashboard with the message "Sup bro! this AI is working"'),
			],
		];

		//Return the response
		return rest_ensure_response( $response );
	}
}