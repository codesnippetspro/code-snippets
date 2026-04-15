<?php

namespace Code_Snippets\UnifiedSnippets\REST;

use Code_Snippets\REST_API\REST_Controller;
use Code_Snippets\UnifiedSnippets\Scan_Results_Store;
use Code_Snippets\UnifiedSnippets\Scanner_Base;
use Code_Snippets\UnifiedSnippets\Scanner_Registry;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST API controller for site scanning operations.
 *
 * Endpoints:
 *   POST /scan          — Run all available scanners (or a subset).
 *   POST /scan/{id}     — Run a single scanner by ID.
 *   GET  /scan/results  — Retrieve the most recent scan results.
 *   GET  /scan/scanners — List registered scanners and their status.
 *   GET  /scan/changes  — Detect changes between current and previous scan.
 *   DELETE /scan/results — Clear stored scan results.
 *
 * @package Code_Snippets
 */
class Scan_REST_Controller extends REST_Controller {

	public const VERSION = 1;

	public const BASE_ROUTE = 'scan';

	/**
	 * @var Scanner_Registry
	 */
	private Scanner_Registry $registry;

	/**
	 * @var Scan_Results_Store
	 */
	private Scan_Results_Store $store;

	/**
	 * Class constructor.
	 *
	 * @param Scanner_Registry   $registry Scanner registry instance.
	 * @param Scan_Results_Store $store    Scan results store instance.
	 */
	public function __construct( Scanner_Registry $registry, Scan_Results_Store $store ) {
		$this->registry = $registry;
		$this->store    = $store;

		parent::__construct();
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . static::BASE_ROUTE,
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'run_scan' ],
				'permission_callback' => [ $this, 'permission_callback' ],
				'args'                => [
					'scanners' => [
						'type'        => 'array',
						'required'    => false,
						'description' => 'Optional list of scanner IDs to run. Omit to run all available.',
						'items'       => [ 'type' => 'string' ],
					],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . static::BASE_ROUTE . '/(?P<scanner_id>[a-z0-9-]+)',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'run_single_scanner' ],
				'permission_callback' => [ $this, 'permission_callback' ],
				'args'                => [
					'scanner_id' => [
						'type'     => 'string',
						'required' => true,
					],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . static::BASE_ROUTE . '/results',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_results' ],
					'permission_callback' => [ $this, 'permission_callback' ],
					'args'                => [
						'source_type' => [
							'type'     => 'string',
							'required' => false,
						],
						'scanner_id'  => [
							'type'     => 'string',
							'required' => false,
						],
					],
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'clear_results' ],
					'permission_callback' => [ $this, 'permission_callback' ],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . static::BASE_ROUTE . '/scanners',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'list_scanners' ],
				'permission_callback' => [ $this, 'permission_callback' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . static::BASE_ROUTE . '/changes',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_changes' ],
				'permission_callback' => [ $this, 'permission_callback' ],
			]
		);
	}

	/**
	 * Run all (or selected) available scanners.
	 *
	 * @param WP_REST_Request $request The REST request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function run_scan( WP_REST_Request $request ) {
		$requested_ids = $request->get_param( 'scanners' );
		$available     = $this->registry->get_available();
		$to_run        = [];

		if ( $requested_ids ) {
			foreach ( $requested_ids as $id ) {
				if ( isset( $available[ $id ] ) ) {
					$to_run[ $id ] = $available[ $id ];
				}
			}

			if ( empty( $to_run ) ) {
				return new WP_Error(
					'no_valid_scanners',
					'None of the requested scanners are available.',
					[ 'status' => 400 ]
				);
			}
		} else {
			$to_run = $available;
		}

		$all_snippets = [];
		$scanner_ids  = [];
		$errors       = [];

		foreach ( $to_run as $scanner ) {
			try {
				$snippets      = $scanner->scan();
				$all_snippets  = array_merge( $all_snippets, $snippets );
				$scanner_ids[] = $scanner->get_id();
			} catch ( \Throwable $e ) {
				$errors[ $scanner->get_id() ] = $e->getMessage();
			}
		}

		$this->store->save( $all_snippets, $scanner_ids );

		$response = [
			'scan_date'   => gmdate( 'c' ),
			'scanners'    => $scanner_ids,
			'total_count' => count( $all_snippets ),
		];

		if ( $errors ) {
			$response['errors'] = $errors;
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Run a single scanner and merge its results into the existing scan.
	 *
	 * @param WP_REST_Request $request The REST request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function run_single_scanner( WP_REST_Request $request ) {
		$scanner_id = $request->get_param( 'scanner_id' );
		$scanner    = $this->registry->get( $scanner_id );

		if ( ! $scanner ) {
			return new WP_Error(
				'scanner_not_found',
				sprintf( 'Scanner "%s" is not registered.', $scanner_id ),
				[ 'status' => 404 ]
			);
		}

		if ( ! $scanner->is_available() ) {
			return new WP_Error(
				'scanner_unavailable',
				sprintf( 'Scanner "%s" is not available in the current environment.', $scanner_id ),
				[ 'status' => 400 ]
			);
		}

		try {
			$snippets = $scanner->scan();
		} catch ( \Throwable $e ) {
			return new WP_Error(
				'scan_failed',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}

		$this->store->merge_scanner_results( $scanner_id, $snippets );

		return rest_ensure_response( [
			'scanner_id' => $scanner_id,
			'count'      => count( $snippets ),
		] );
	}

	/**
	 * Retrieve stored scan results with optional filtering.
	 *
	 * @param WP_REST_Request $request The REST request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_results( WP_REST_Request $request ): WP_REST_Response {
		$source_type = $request->get_param( 'source_type' );
		$scanner_id  = $request->get_param( 'scanner_id' );

		if ( $source_type ) {
			$snippets = $this->store->get_by_source_type( $source_type );
		} elseif ( $scanner_id ) {
			$snippets = $this->store->get_by_scanner( $scanner_id );
		} else {
			$snippets = $this->store->get_all();
		}

		$metadata = $this->store->get_metadata();

		return rest_ensure_response( [
			'scan_date'   => $metadata['scan_date'],
			'scanners'    => $metadata['scanners'],
			'total_count' => $metadata['total_count'],
			'count'       => count( $snippets ),
			'snippets'    => array_map(
				static fn( $s ) => $s->to_array(),
				array_values( $snippets )
			),
		] );
	}

	/**
	 * List all registered scanners and their availability.
	 *
	 * @return WP_REST_Response
	 */
	public function list_scanners(): WP_REST_Response {
		return rest_ensure_response( [
			'scanners' => $this->registry->get_scanner_info(),
		] );
	}

	/**
	 * Detect changes between the current and previous scans.
	 *
	 * @return WP_REST_Response
	 */
	public function get_changes(): WP_REST_Response {
		$changes = $this->store->detect_changes();

		$serialize = static fn( array $items ) => array_map(
			static fn( $s ) => $s->to_array(),
			$items
		);

		return rest_ensure_response( [
			'new'      => $serialize( $changes['new'] ),
			'modified' => $serialize( $changes['modified'] ),
			'removed'  => $serialize( $changes['removed'] ),
		] );
	}

	/**
	 * Clear all stored scan results.
	 *
	 * @return WP_REST_Response
	 */
	public function clear_results(): WP_REST_Response {
		$this->store->clear();

		return rest_ensure_response( [ 'cleared' => true ] );
	}
}
