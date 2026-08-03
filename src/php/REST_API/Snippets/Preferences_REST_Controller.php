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
	 * The name of the option used to store Insights chart view preferences.
	 */
	public const INSIGHTS_CHART_VIEWS_OPTION = 'code_snippets_insights_chart_views';

	/**
	 * Insights charts with independently configurable views.
	 */
	public const INSIGHTS_CHART_KEYS = [ 'type', 'activation', 'location' ];

	/**
	 * Valid Insights chart view values.
	 */
	public const INSIGHTS_CHART_VIEWS = [ 'pie', 'bar' ];

	/**
	 * The Insights chart views shown when no preference has been saved.
	 */
	public const DEFAULT_INSIGHTS_CHART_VIEWS = [
		'type'       => 'bar',
		'activation' => 'pie',
		'location'   => 'bar',
	];

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
	 * Retrieve the Insights chart views, normalizing missing or invalid values.
	 *
	 * @return array<string, string>
	 */
	public static function get_insights_chart_views(): array {
		$views = get_option( self::INSIGHTS_CHART_VIEWS_OPTION, self::DEFAULT_INSIGHTS_CHART_VIEWS );

		if ( ! is_array( $views ) ) {
			return self::DEFAULT_INSIGHTS_CHART_VIEWS;
		}

		return array_reduce(
			self::INSIGHTS_CHART_KEYS,
			static function ( array $normalized, string $key ) use ( $views ): array {
				$view = $views[ $key ] ?? self::DEFAULT_INSIGHTS_CHART_VIEWS[ $key ];
				$normalized[ $key ] = in_array( $view, self::INSIGHTS_CHART_VIEWS, true )
					? $view
					: self::DEFAULT_INSIGHTS_CHART_VIEWS[ $key ];

				return $normalized;
			},
			[]
		);
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
			self::BASE_ROUTE . '/insights-chart-views',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_insights_chart_views_callback' ],
					'permission_callback' => [ $this, 'permission_callback' ],
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_insights_chart_views_callback' ],
					'permission_callback' => [ $this, 'permission_callback' ],
					'args'                => [
						'views' => [
							'description'       => esc_html__( 'Pie or bar view for each Insights chart.', 'code-snippets' ),
							'type'              => 'object',
							'required'          => true,
							'validate_callback' => [ $this, 'validate_insights_chart_views' ],
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
	 * Validate a complete Insights chart view preference map.
	 *
	 * @param mixed $views Candidate preference map.
	 *
	 * @return bool
	 */
	public function validate_insights_chart_views( $views ): bool {
		if ( ! is_array( $views ) || count( self::INSIGHTS_CHART_KEYS ) !== count( $views ) ) {
			return false;
		}

		foreach ( self::INSIGHTS_CHART_KEYS as $key ) {
			if ( ! array_key_exists( $key, $views ) ) {
				return false;
			}

			$view = $views[ $key ];

			if ( ! in_array( $view, self::INSIGHTS_CHART_VIEWS, true ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Retrieve the current Insights chart view preferences.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_insights_chart_views_callback( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response( [ 'views' => self::get_insights_chart_views() ] );
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
	 * Update the Insights chart view preferences.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function update_insights_chart_views_callback( WP_REST_Request $request ): WP_REST_Response {
		$views = $request->get_param( 'views' );

		update_option( self::INSIGHTS_CHART_VIEWS_OPTION, $views );

		return new WP_REST_Response( [ 'views' => $views ] );
	}
}
