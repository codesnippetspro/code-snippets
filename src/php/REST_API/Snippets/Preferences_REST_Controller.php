<?php

namespace Code_Snippets\REST_API\Snippets;

use Code_Snippets\REST_API\REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function Code_Snippets\code_snippets;

/**
 * Controller for reading and updating plugin-wide interface preferences,
 * such as whether snippet lists display as a table or a grid of cards.
 *
 * @package Code_Snippets
 */
final class Preferences_REST_Controller extends REST_Controller {

	/**
	 * Current API version.
	 */
	public const VERSION = 1;

	/**
	 * The base of this controller's route.
	 */
	public const BASE_ROUTE = 'preferences';

	/**
	 * The name of the option used to store the snippet view preference.
	 */
	public const SNIPPET_VIEW_OPTION = 'code_snippets_snippet_view';

	/**
	 * Valid snippet view values.
	 */
	public const SNIPPET_VIEWS = [ 'card', 'table' ];

	/**
	 * The snippet view shown when no preference has been saved.
	 */
	public const DEFAULT_SNIPPET_VIEW = 'table';

	/**
	 * The name of the option recording which feature demos have been watched.
	 */
	public const DEMOS_SEEN_OPTION = 'code_snippets_demos_seen';

	/**
	 * Feature demos whose completion is recorded.
	 */
	public const DEMOS = [ 'ai-agent', 'blueprints' ];

	/**
	 * Retrieve the current snippet view preference, falling back to the
	 * default when the stored value is missing or invalid.
	 *
	 * @return string Either 'card' or 'table'.
	 */
	public static function get_snippet_view(): string {
		$view = get_option( self::SNIPPET_VIEW_OPTION, self::DEFAULT_SNIPPET_VIEW );

		return in_array( $view, self::SNIPPET_VIEWS, true )
			? $view
			: self::DEFAULT_SNIPPET_VIEW;
	}

	/**
	 * Retrieve the feature demos that have been watched through to the end.
	 *
	 * @return string[]
	 */
	public static function get_demos_seen(): array {
		$seen = get_option( self::DEMOS_SEEN_OPTION, [] );

		return is_array( $seen ) ? array_values( array_intersect( self::DEMOS, $seen ) ) : [];
	}

	/**
	 * Forget which feature demos have been watched, so their toolbar tabs
	 * announce themselves as new again.
	 *
	 * @return void
	 */
	public static function reset_demos_seen(): void {
		delete_option( self::DEMOS_SEEN_OPTION );
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			self::BASE_ROUTE . '/snippet-view',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_snippet_view_callback' ],
					'permission_callback' => [ $this, 'permission_callback' ],
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_snippet_view_callback' ],
					'permission_callback' => [ $this, 'permission_callback' ],
					'args'                => [
						'view' => [
							'description' => esc_html__( 'Whether snippet lists display as a grid of cards or a table.', 'code-snippets' ),
							'type'        => 'string',
							'enum'        => self::SNIPPET_VIEWS,
							'required'    => true,
						],
					],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			self::BASE_ROUTE . '/demos-seen',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_demos_seen_callback' ],
					'permission_callback' => [ $this, 'permission_callback' ],
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_demos_seen_callback' ],
					'permission_callback' => [ $this, 'permission_callback' ],
					'args'                => [
						'demo' => [
							'description' => esc_html__( 'Identifier of the feature demo that has been watched.', 'code-snippets' ),
							'type'        => 'string',
							'enum'        => self::DEMOS,
							'required'    => true,
						],
					],
				],
			]
		);
	}

	/**
	 * Determine whether the request has permission to manage preferences.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool
	 */
	public function permission_callback( WP_REST_Request $request ): bool {
		return code_snippets()->current_user_can();
	}

	/**
	 * Retrieve the current snippet view preference.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_snippet_view_callback( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response( [ 'view' => self::get_snippet_view() ] );
	}

	/**
	 * Update the snippet view preference.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function update_snippet_view_callback( WP_REST_Request $request ): WP_REST_Response {
		$view = $request->get_param( 'view' );

		update_option( self::SNIPPET_VIEW_OPTION, $view );

		return new WP_REST_Response( [ 'view' => $view ] );
	}

	/**
	 * Retrieve the feature demos that have been watched.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_demos_seen_callback( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response( [ 'demos' => self::get_demos_seen() ] );
	}

	/**
	 * Record that a feature demo has been watched through to the end.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function update_demos_seen_callback( WP_REST_Request $request ): WP_REST_Response {
		$demos = self::get_demos_seen();
		$demos[] = $request->get_param( 'demo' );

		$demos = array_values( array_intersect( self::DEMOS, array_unique( $demos ) ) );
		update_option( self::DEMOS_SEEN_OPTION, $demos );

		return new WP_REST_Response( [ 'demos' => $demos ] );
	}
}
