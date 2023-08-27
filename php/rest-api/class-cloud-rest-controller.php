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
class Cloud_REST_Controller extends Snippets_REST_Controller {
	
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
			$route . '/cloudcreate',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_item_from_cloud' ],
					'permission_callback' => [ $this, 'cloud_api_check' ],
					'args'                => $this->get_endpoint_args_for_item_schema( true ),
				],
				'schema' => [ $this, 'get_item_schema' ],
			]
		);

		register_rest_route(
			$this->namespace,
			$route . '/removesync',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'remove_sync' ],
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
	public function create_item_from_cloud( $request ) {
		//Create an empty array to store the snippet
		$snippet_to_store = [];
		//Process the request body
		$body = json_decode( $request->get_body() );
		//Get the first item in the array
		$body = reset($body);
		//Convert to Array
		$body = json_decode( $body, true );

		//Set up Snippet to store
		$snippet_to_store['id'] 			=  0;
		$snippet_to_store['name'] 			=  $body['name'];
		$snippet_to_store['desc'] 			=  $body['description'];
		$snippet_to_store['code'] 			=  $body['code'];
		$snippet_to_store['scope']	 		=  $body['scope'];
		$snippet_to_store['active'] 		=  false;
		$snippet_to_store['network'] 		=  false;
		$snippet_to_store['modified'] 		=  $body['created'];
		$snippet_to_store['revision'] 		=  $body['revision'] ?? 1;
		$snippet_to_store['priority'] 		=  10;
		$snippet_to_store['cloud_id'] 		=  $body['id'].'_0'; //Set to not owner
		$snippet_to_store['shared_network'] =  false;
		
		//Create the snippet
		$snippet = $this->create_item( $snippet_to_store );

		//Check if snippet was created.
		if ( is_wp_error( $snippet ) ) {
			//Grab the error message and return WP Error Response
			$error = $snippet->get_error_message();
			return new WP_Error( 'snippet_not_created', $error, [ 'status' => 500 ] );
		}		

		//Create a link between the local snippet and the cloud snippet
		$link 					= new Cloud_Link();
		$link->local_id 		= $snippet->data['id'];
		$link->cloud_id 		= $snippet_to_store['id'];
		$link->is_owner 		= false; //Set false by default
		$link->in_codevault 	= false ; //Set false by default
		$link->update_available = false; //Set false by default

		//Add the link to the cloud
		code_snippets()->cloud_api->add_map_link( $link );
		
		//Construct success response
		$response = [
			'status'  => 'success',
			'message' => __( 'Snippet created', 'code-snippets' ),
		];

		//Return the response
		return rest_ensure_response( $response );
	}

	/**
	 * Remove Sync
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function remove_sync( $request ) {
		//Get the settings and set the cloud token and local token to empty strings and change the token_verified to false
		$settings = get_option( 'code_snippets_settings' );
		$settings['cloud']['cloud_token'] = '';
		$settings['cloud']['local_token'] = '';
		$settings['cloud']['token_verified'] = 'false';

		//Update the settings
		update_option( 'code_snippets_settings', $settings );

		//Construct success response
		$response = [
			'status'  => 'success',
			'message' => __( 'Sync has been revoked', 'code-snippets' ),
		];

		//Return the response
		return rest_ensure_response( $response );
	}

}