<?php

namespace Code_Snippets\REST_API\Preferences;

/**
 * Controller for reading and updating the saved view preferences for Stats charts.
 *
 * @package Code_Snippets
 */
final class Stats_View_Rest_Controller extends Preference_REST_Controller {

	/**
	 * Current API version.
	 */
	public const VERSION = 1;

	/**
	 * The base of this controller's route.
	 */
	public const BASE_ROUTE = 'stats-chart-views';

	/**
	 * The key used to identify this preference in the REST API.
	 */
	protected const PREFERENCE_KEY = 'views';

	/**
	 * The name of the option used to store Stats chart view preferences.
	 */
	public const OPTION_NAME = 'code_snippets_stats';

	/**
	 * Stats charts with independently configurable views.
	 */
	public const CHART_KEYS = [ 'type', 'activation', 'conditions', 'location', 'tags' ];

	/**
	 * Valid Stats chart view values.
	 */
	public const CHART_VIEWS = [
		'type'       => [ 'pie', 'bar' ],
		'activation' => [ 'pie', 'bar' ],
		'conditions' => [ 'pie', 'bar' ],
		'location'   => [ 'pie', 'bar' ],
		'tags'       => [ 'bar', 'cloud' ],
	];

	/**
	 * The Stats chart views shown when no preference has been saved.
	 */
	public const DEFAULT_VIEWS = [
		'type'       => 'bar',
		'activation' => 'pie',
		'conditions' => 'pie',
		'location'   => 'bar',
		'tags'       => 'bar',
	];

	/**
	 * Retrieve the Stats chart views, normalizing missing or invalid values.
	 *
	 * @return array<string, string>
	 */
	public static function get_stats_chart_views(): array {
		$views = get_option( self::OPTION_NAME );

		if ( ! is_array( $views ) ) {
			return self::DEFAULT_VIEWS;
		}

		return array_reduce(
			self::CHART_KEYS,
			static function ( array $normalized, string $key ) use ( $views ): array {
				$view = $views[ $key ] ?? self::DEFAULT_VIEWS[ $key ];
				$normalized[ $key ] = in_array( $view, self::CHART_VIEWS[ $key ], true )
					? $view
					: self::DEFAULT_VIEWS[ $key ];

				return $normalized;
			},
			[]
		);
	}

	/**
	 * Retrieve the stored preference value, falling back to the default when the
	 * stored value is missing or invalid.
	 *
	 * @return array
	 */
	protected function get_option_value(): array {
		return self::get_stats_chart_views();
	}

	/**
	 * Get the schema for the update request argument.
	 *
	 * @return array The schema for the update request argument.
	 */
	protected function get_update_request_schema(): array {
		return [
			'description'       => esc_html__( 'Supported chart-specific view for each Stats chart; tags also supports the cloud view.', 'code-snippets' ),
			'type'              => 'object',
			'required'          => true,
			'validate_callback' => [ $this, 'validate_stats_chart_views' ],
		];
	}

	/**
	 * Validate a complete Stats chart view preference map.
	 *
	 * @param mixed $views Candidate preference map.
	 *
	 * @return bool
	 */
	public function validate_stats_chart_views( $views ): bool {
		if ( ! is_array( $views ) || count( self::CHART_KEYS ) !== count( $views ) ) {
			return false;
		}

		foreach ( self::CHART_KEYS as $key ) {
			if ( ! array_key_exists( $key, $views ) ) {
				return false;
			}

			$view = $views[ $key ];

			if ( ! in_array( $view, self::CHART_VIEWS[ $key ], true ) ) {
				return false;
			}
		}

		return true;
	}
}
