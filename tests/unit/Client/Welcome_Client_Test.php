<?php

namespace Code_Snippets\Client;

use Code_Snippets\UnitTestCase;
use WP_Filesystem_Direct;
use const Code_Snippets\PLUGIN_FILE;

/**
 * Tests for reading the welcome screen data.
 *
 * @group welcome
 */
class Welcome_Client_Test extends UnitTestCase {

	/**
	 * A release heading with a status label in place of a date is read with that label.
	 *
	 * @return void
	 */
	public function test_release_without_a_date_keeps_its_label(): void {
		update_option( 'date_format', 'Y-m-d' );

		$changelog = Welcome_Client::parse_changelog_contents(
			"# Changelog\n\n## [9.1.0] (UPCOMING)\n\n### Fixed\n* Second fix.\n\n## [9.0.0] (2026-11-05)\n\n### Added\n* First feature.\n"
		);

		$this->assertSame( [ '9.1.0', '9.0.0' ], array_column( $changelog, 'version' ) );
		$this->assertSame( [ 'UPCOMING', '2026-11-05' ], array_column( $changelog, 'date' ) );
	}

	/**
	 * A partial feed is read without padding: a missing banner date is read as no limit rather than the current
	 * time, a value that is not a string is read as empty, and short or missing lists hold only the items the feed
	 * provides.
	 *
	 * @return void
	 */
	public function test_banner_reads_promotion_fields_and_missing_dates(): void {
		delete_transient( 'code_snippets_welcome_data' );

		$feed = [
			'banner' => [
				'key'          => 'BF 2026',
				'end_datetime' => '2026-12-01T00:00',
				'text_free'    => '40% off Pro yearly and lifetime plans',
				'text_pro'     => [ 'not', 'a', 'string' ],
				'label'        => 'Black Friday',
				'headline_free' => 'Short headline',
			],
			'features' => [
				[ 'title' => 'Only feature' ],
			],
		];

		$respond = static function () use ( $feed ) {
			return [
				'headers'  => [],
				'body'     => wp_json_encode( $feed ),
				'response' => [
					'code'    => 200,
					'message' => 'OK',
				],
				'cookies'  => [],
			];
		};

		add_filter( 'pre_http_request', $respond );
		$client = new Welcome_Client();
		$banner = $client->get_banner();
		remove_filter( 'pre_http_request', $respond );
		delete_transient( 'code_snippets_welcome_data' );

		$this->assertSame( 'bf2026', $banner['key'] );
		$this->assertSame( '40% off Pro yearly and lifetime plans', $banner['text_free'] );
		$this->assertSame( '', $banner['text_pro'] );
		$this->assertSame( 'Black Friday', $banner['label'] );
		$this->assertSame( 'Short headline', $banner['headline_free'] );
		$this->assertSame( '', $banner['secondary_free'] );
		$this->assertNull( $banner['start_datetime'] );
		$this->assertSame( '2026-12-01 00:00', $banner['end_datetime']->format( 'Y-m-d H:i' ) );
		$this->assertSame( [ 'Only feature' ], array_column( $client->get_features(), 'title' ) );
		$this->assertSame( [], $client->get_partners() );
	}

	/**
	 * Stored welcome data with a banner that is not a list is read as having no banner.
	 *
	 * @return void
	 */
	public function test_damaged_stored_banner_is_read_as_no_banner(): void {
		set_transient( 'code_snippets_welcome_data', [ 'banner' => 'damaged' ] );

		$banner = ( new Welcome_Client() )->get_banner();
		delete_transient( 'code_snippets_welcome_data' );

		$this->assertSame( [], $banner );
	}

	/**
	 * Damaged parts of the stored welcome data are read as empty, and damaged banner fields as empty values.
	 *
	 * @return void
	 */
	public function test_damaged_stored_welcome_data_is_read_as_empty(): void {
		set_transient(
			'code_snippets_welcome_data',
			[
				'hero-item' => 'damaged',
				'features'  => 'damaged',
				'partners'  => 3,
				'changelog' => 'damaged',
				'banner'    => [
					'key'            => [ 'not', 'a', 'key' ],
					'headline_free'  => [ 'not text' ],
					'text_free'      => 'Shown text',
					'start_datetime' => 'not a date',
				],
			]
		);

		$client = new Welcome_Client();
		$banner = $client->get_banner();
		delete_transient( 'code_snippets_welcome_data' );

		$this->assertSame( [], $client->get_hero_item() );
		$this->assertSame( [], $client->get_features() );
		$this->assertSame( [], $client->get_partners() );
		$this->assertSame( [], $client->get_changelog() );
		$this->assertSame( '', $banner['key'] );
		$this->assertSame( '', $banner['headline_free'] );
		$this->assertSame( 'Shown text', $banner['text_free'] );
		$this->assertNull( $banner['start_datetime'] );
	}
}
