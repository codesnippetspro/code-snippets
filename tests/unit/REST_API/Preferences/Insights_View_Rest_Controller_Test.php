<?php

namespace Code_Snippets\REST_API\Preferences;

use Code_Snippets\AdminUnitTestCase;
use WP_REST_Request;
use WP_REST_Response;
use WP_UnitTest_Factory;

/**
 * Tests for the interface preferences REST API endpoint.
 *
 * Verifies that the snippet view preference (cards or table) defaults to
 * the table, persists through the plugin-wide option, rejects invalid values,
 * and is only writable by users with snippet capabilities.
 *
 * @group rest-api
 */
class Insights_View_Rest_Controller_Test extends AdminUnitTestCase {

	/**
	 * REST API endpoint for the Insights chart view preferences.
	 *
	 * @var string
	 */
	protected string $endpoint = '/code-snippets/v1/preferences/insights-chart-views';
	/**
	 * Editor user ID (no snippet capabilities).
	 *
	 * @var int
	 */
	protected static int $editor_id;

	/**
	 * Set up fixture users before any tests run.
	 *
	 * @param WP_UnitTest_Factory $factory Factory object.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );
		self::$editor_id = $factory->user->create( [ 'role' => 'editor' ] );

		if ( is_multisite() ) {
			grant_super_admin( self::get_user_id() );
		}
	}

	/**
	 * Set up before each test.
	 */
	public function set_up() {
		parent::set_up();

		delete_option( Insights_View_Rest_Controller::OPTION_NAME );
	}

	/**
	 * Dispatch a REST request and return the raw response object.
	 *
	 * @param string               $method HTTP method.
	 * @param array<string, mixed> $params Request parameters.
	 *
	 * @return WP_REST_Response
	 */
	protected function dispatch( string $method, array $params = [] ): WP_REST_Response {
		$request = new WP_REST_Request( $method, $this->endpoint );

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		return rest_do_request( $request );
	}

	/**
	 * With no saved preference, Insights uses its intended default chart views.
	 */
	public function test_insights_chart_views_default_to_the_expected_mix() {
		$response = $this->dispatch( 'GET' );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame(
			[ 'views' => Insights_View_Rest_Controller::DEFAULT_VIEWS ],
			$response->get_data()
		);
	}

	/**
	 * Updating every Insights chart view persists the complete preference map.
	 */
	public function test_insights_chart_views_update_persists() {
		$views = [
			'type'       => 'pie',
			'activation' => 'bar',
			'location'   => 'pie',
		];
		$response = $this->dispatch( 'POST', [ 'views' => $views ] );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( [ 'views' => $views ], $response->get_data() );
		$this->assertSame( $views, get_option( Insights_View_Rest_Controller::OPTION_NAME ) );
		$this->assertSame( $views, Insights_View_Rest_Controller::get_insights_chart_views() );
	}

	/**
	 * Missing chart keys and invalid view names cannot replace the saved preference.
	 */
	public function test_invalid_insights_chart_views_are_rejected() {
		$response = $this->dispatch(
			'POST',
			[
				'views' => [
					'type'       => 'pie',
					'activation' => 'donut',
				],
			]
		);

		$this->assertSame( 400, $response->get_status() );
		$this->assertFalse( get_option( Insights_View_Rest_Controller::OPTION_NAME ) );
	}

	/**
	 * Partially saved preferences are normalized without losing the supported defaults.
	 */
	public function test_incomplete_stored_insights_chart_views_are_normalized() {
		update_option( Insights_View_Rest_Controller::OPTION_NAME, [ 'type' => 'pie' ] );

		$this->assertSame(
			[
				'type'       => 'pie',
				'activation' => 'pie',
				'location'   => 'bar',
			],
			Insights_View_Rest_Controller::get_insights_chart_views()
		);
	}

	/**
	 * Users without snippet capabilities cannot read or write the preference.
	 */
	public function test_editor_is_blocked() {
		wp_set_current_user( self::$editor_id );

		$response = $this->dispatch( 'GET' );
		$this->assertContains( $response->get_status(), [ 401, 403 ] );

		$response = $this->dispatch(
			'POST',
			[ 'views' => Insights_View_Rest_Controller::DEFAULT_VIEWS ]
		);
		$this->assertContains( $response->get_status(), [ 401, 403 ] );
		$this->assertFalse( get_option( Insights_View_Rest_Controller::OPTION_NAME ) );
	}
}
