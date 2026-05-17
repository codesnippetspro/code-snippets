<?php

namespace Code_Snippets\REST_API\Cloud;

use Code_Snippets\Client\Cloud_API;
use Code_Snippets\REST_API\REST_Controller;
use WP_REST_Request;

/**
 * Base class for REST API controllers that interface with the Code Snippets Cloud API.
 *
 * @package Code_Snippets
 */
abstract class Cloud_REST_Controller extends REST_Controller {

	/**
	 * Instance of Cloud API class.
	 *
	 * @var Cloud_API
	 */
	protected Cloud_API $cloud_api;

	/**
	 * Class constructor.
	 *
	 * @param Cloud_API $cloud_api Cloud API instance.
	 */
	public function __construct( Cloud_API $cloud_api ) {
		parent::__construct();
		$this->cloud_api = $cloud_api;
	}

	/**
	 * Check the request from Cloud API is valid
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool
	 */
	public function permission_callback( WP_REST_Request $request ): bool {
		return parent::permission_callback( $request ) &&
		       $request->get_header( 'Access-Control' ) === $this->cloud_api->get_local_token();
	}
}
