<?php

namespace Code_Snippets\Client;

use Code_Snippets\UnitTestCase;
use ReflectionClass;
use ReflectionException;

/**
 * Tests for reading the welcome screen data.
 *
 * @group welcome
 */
class Welcome_Client_Test extends UnitTestCase {

	/**
	 * All remote news items remain available to the welcome screen.
	 *
	 * @return void
	 * @throws ReflectionException
	 */
	public function test_preserves_all_hero_items() {
		$items = [
			[
				'name' => 'First',
				'follow_url' => 'https://example.com/first',
				'image_url' => 'https://example.com/first.png',
			],
			[
				'name' => 'Second',
				'follow_url' => 'https://example.com/second',
				'image_url' => 'https://example.com/second.png',
			],
		];
		$reflection = new ReflectionClass( Welcome_Client::class );
		$client = $reflection->newInstanceWithoutConstructor();
		$parser = $reflection->getMethod( 'parse_hero_item' );

		if ( version_compare( PHP_VERSION, '8.1.0', '<' ) ) {
			$parser->setAccessible( true );
		}

		$data = $reflection->getProperty( 'welcome_data' );

		if ( version_compare( PHP_VERSION, '8.1.0', '<' ) ) {
			$data->setAccessible( true );
		}

		$data->setValue( $client, [ 'hero-item' => $parser->invoke( $client, $items ) ] );

		$this->assertSame( $items, $client->get_hero_items() );
		$this->assertSame( $items[0], $client->get_hero_item() );
	}

	/**
	 * Previously cached single items remain readable.
	 *
	 * @return void
	 *
	 * @throws ReflectionException
	 */
	public function test_reads_legacy_cached_hero_item() {
		$item = [
			'name'       => 'Cached',
			'follow_url' => '',
			'image_url'  => '',
		];
		$reflection = new ReflectionClass( Welcome_Client::class );
		$client = $reflection->newInstanceWithoutConstructor();

		$data = $reflection->getProperty( 'welcome_data' );

		if ( version_compare( PHP_VERSION, '8.1.0', '<' ) ) {
			$data->setAccessible( true );
		}

		$data->setValue( $client, [ 'hero-item' => $item ] );

		$this->assertSame( [ $item ], $client->get_hero_items() );
	}

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
}
