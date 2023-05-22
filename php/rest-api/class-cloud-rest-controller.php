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
		$cloud_token = $request->get_header('Authorization');
		$cloud_token = str_replace('Bearer ', '', $cloud_token);
        
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

		//Overwrite or set a few default params
		$request->set_param( 'active', false );
		$request->set_param( 'network', false );
		$request->set_param( 'shared_network', false );

		$snippet = $this->create_item( $request );

		//Check if snippet was created.
		if ( is_wp_error( $snippet ) ) {
			//Grab the error message and return WP Error Response
			$error = $snippet->get_error_message();
			return new WP_Error( 'snippet_not_created', $error, [ 'status' => 500 ] );
		}

		//Get Cloud ID from request object
		$cloud_id = $request->get_param('cloud_id');
		$is_owner = $request->get_param('is_owner');
		$in_codevault = $request->get_param('in_codevault');
		

		$link = new Cloud_Link();
		$link->local_id = $snippet->data['id'];;
		$link->cloud_id = $cloud_id;
		$link->is_owner = $is_owner ? true : false;
		$link->in_codevault = $in_codevault ? true : false ;
		$link->update_available = false; //Set false by default

		code_snippets()->cloud_api->add_map_link( $link );
		
		$response = [
			'status'  => 'success',
			'message' => __( 'Snippet created', 'code-snippets' ),
		];

		return rest_ensure_response( $response );
	}



}