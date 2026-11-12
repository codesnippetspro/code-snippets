<?php

namespace Code_Snippets\Client;

use Code_Snippets\UnitTestCase;

/**
 * Tests for welcome news items.
 */
class Welcome_Client_Test extends UnitTestCase {

	/**
	 * All remote news items remain available to the welcome screen.
	 *
	 * @return void
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
		$reflection = new \ReflectionClass( Welcome_Client::class );
		$client = $reflection->newInstanceWithoutConstructor();
		$parser = $reflection->getMethod( 'parse_hero_item' );
		$parser->setAccessible( true );
		$data = $reflection->getProperty( 'welcome_data' );
		$data->setAccessible( true );
		$data->setValue( $client, [ 'hero-item' => $parser->invoke( $client, $items ) ] );

		$this->assertSame( $items, $client->get_hero_items() );
		$this->assertSame( $items[0], $client->get_hero_item() );
	}

	/**
	 * Previously cached single items remain readable.
	 *
	 * @return void
	 */
	public function test_reads_legacy_cached_hero_item() {
		$item = [
			'name' => 'Cached',
			'follow_url' => '',
			'image_url' => '',
		];
		$reflection = new \ReflectionClass( Welcome_Client::class );
		$client = $reflection->newInstanceWithoutConstructor();
		$data = $reflection->getProperty( 'welcome_data' );
		$data->setAccessible( true );
		$data->setValue( $client, [ 'hero-item' => $item ] );

		$this->assertSame( [ $item ], $client->get_hero_items() );
	}
}
