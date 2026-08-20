<?php

namespace Code_Snippets\REST_API\Preferences;

/**
 * Controller for reading and updating the saved view preferences for Insights charts.
 *
 * @package Code_Snippets
 */
final class Insights_View_Rest_Controller extends Preference_REST_Controller {

	/**
	 * Current API version.
	 */
	public const VERSION = 1;

	/**
	 * The base of this controller's route.
	 */
	public const BASE_ROUTE = 'insights-chart-views';

	/**
	 * The key used to identify this preference in the REST API.
	 */
	protected const PREFERENCE_KEY = 'views';

	/**
	 * The name of the option used to store Insights chart view preferences.
	 */
	public const OPTION_NAME = 'code_snippets_insights_preferences';

	/**
	 * Insights charts with independently configurable views.
	 */
	public const CHART_KEYS = [ 'type', 'activation', 'location' ];

	/**
	 * Valid Insights chart view values.
	 */
	public const CHART_VIEWS = [ 'pie', 'bar' ];

	/**
	 * The Insights chart views shown when no preference has been saved.
	 */
	public const DEFAULT_VIEWS = [
		'type'       => 'bar',
		'activation' => 'pie',
		'location'   => 'bar',
	];

	/**
	 * Retrieve the Insights chart views, normalizing missing or invalid values.
	 *
	 * @return array<string, string>
	 */
	public static function get_insights_chart_views(): array {
		$views = get_option( self::OPTION_NAME );

		if ( ! is_array( $views ) ) {
			return self::DEFAULT_VIEWS;
		}

		return array_reduce(
			self::CHART_KEYS,
			static function ( array $normalized, string $key ) use ( $views ): array {
				$view = $views[ $key ] ?? self::DEFAULT_VIEWS[ $key ];
				$normalized[ $key ] = in_array( $view, self::CHART_VIEWS, true )
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
		return self::get_insights_chart_views();
	}

	/**
	 * Get the schema for the update request argument.
	 *
	 * @return array The schema for the update request argument.
	 */
	protected function get_update_request_schema(): array {
		return [
			'description'       => esc_html__( 'Pie or bar view for each Insights chart.', 'code-snippets' ),
			'type'              => 'object',
			'required'          => true,
			'validate_callback' => [ $this, 'validate_insights_chart_views' ],
		];
	}

	/**
	 * Validate a complete Insights chart view preference map.
	 *
	 * @param mixed $views Candidate preference map.
	 *
	 * @return bool
	 */
	public function validate_insights_chart_views( $views ): bool {
		if ( ! is_array( $views ) || count( self::CHART_KEYS ) !== count( $views ) ) {
			return false;
		}

		foreach ( self::CHART_KEYS as $key ) {
			if ( ! array_key_exists( $key, $views ) ) {
				return false;
			}

			$view = $views[ $key ];

			if ( ! in_array( $view, self::CHART_VIEWS, true ) ) {
				return false;
			}
		}

		return true;
	}
}
