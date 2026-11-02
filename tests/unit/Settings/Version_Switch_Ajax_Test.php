<?php

namespace Code_Snippets\Settings;

use WP_Ajax_UnitTestCase;
use WPAjaxDieContinueException;
use WP_Error;

require_once __DIR__ . '/Recording_Version_Source.php';

/**
 * Tests for the version switcher's AJAX handlers.
 *
 * @group version-switch
 * @group ajax
 */
class Version_Switch_Ajax_Test extends WP_Ajax_UnitTestCase {

	/**
	 * Source registered on the seam filter for the duration of a test.
	 *
	 * @var Recording_Version_Source
	 */
	private Recording_Version_Source $source;

	/**
	 * Callback supplying the recording source.
	 *
	 * @var callable
	 */
	private $supply_source;

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->source = new Recording_Version_Source();
		$source = $this->source;
		$this->supply_source = function () use ( $source ) {
			return $source;
		};

		add_filter( 'code_snippets_version_source', $this->supply_source );
		Version_Switch::init();
		Version_Switch::clear_version_caches();

		$this->_setRole( 'administrator' );
	}

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		Version_Switch::clear_version_caches();
		remove_filter( 'code_snippets_version_source', $this->supply_source );

		parent::tear_down();
	}

	/**
	 * Run the refresh handler and decode the JSON it sent.
	 *
	 * @return array<string, mixed>
	 */
	private function refresh_versions(): array {
		$_POST['nonce'] = wp_create_nonce( 'code_snippets_refresh_versions' );

		try {
			$this->_handleAjax( 'code_snippets_refresh_versions' );
		} catch ( WPAjaxDieContinueException $exception ) {
			unset( $exception );
		}

		return json_decode( $this->_last_response, true );
	}

	/**
	 * A refresh whose request fails reports the failure instead of success.
	 *
	 * @return void
	 */
	public function test_failed_refresh_is_reported_as_an_error(): void {
		$this->source->catalogue = new WP_Error( 'version_source_request_error', 'The version service is down.' );

		$response = $this->refresh_versions();

		$this->assertFalse( $response['success'] );
		$this->assertSame( 'The version service is down.', $response['data']['message'] );
	}

	/**
	 * A refresh that fetches a catalogue reports success.
	 *
	 * @return void
	 */
	public function test_successful_refresh_is_reported_as_success(): void {
		$response = $this->refresh_versions();

		$this->assertTrue( $response['success'] );
	}
}
