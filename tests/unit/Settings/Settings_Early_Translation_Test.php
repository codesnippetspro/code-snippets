<?php

namespace Code_Snippets\Settings;

use Code_Snippets\UnitTestCase;
use WP_Query;
use const Code_Snippets\CACHE_GROUP;

/**
 * Tests that reading a setting does not translate anything.
 *
 * Settings are read from hooks the plugin has no say over the timing of. The
 * shortcode handler reads one on the_posts, which fires for any post query,
 * including one another plugin or a snippet runs while plugins are still
 * loading. WordPress reports a translation requested that early as an error,
 * and with debug display on the notice is printed ahead of the headers.
 *
 * Each test runs in its own process. The field definitions are built once and
 * kept, so a read later in a shared process says nothing about a cold one.
 *
 * @group settings
 */
class Settings_Early_Translation_Test extends UnitTestCase {

	/**
	 * Strings translated in the plugin's text domain during a test.
	 *
	 * @var string[]
	 */
	private array $translated = [];

	/**
	 * Record a translation requested in the plugin's text domain.
	 *
	 * @param string $translation Translated text.
	 * @param string $text        Text to translate.
	 * @param string $domain      Text domain.
	 *
	 * @return string
	 */
	public function record_translation( $translation, $text, $domain ) {
		if ( 'code-snippets' === $domain ) {
			$this->translated[] = $text;
		}

		return $translation;
	}

	/**
	 * Start recording, with the cached settings cleared so the read is a cold one.
	 *
	 * @return void
	 */
	private function start_recording(): void {
		wp_cache_delete( CACHE_KEY, CACHE_GROUP );
		$this->translated = [];

		add_filter( 'gettext', [ $this, 'record_translation' ], 10, 3 );
	}

	/**
	 * A setting's value can be read without translating anything.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_reading_a_setting_translates_nothing(): void {
		$this->start_recording();

		get_setting( 'general', 'disable_prism' );

		$this->assertSame(
			[],
			$this->translated,
			'reading a setting should not translate anything, as settings are read before translations may load'
		);
	}

	/**
	 * A post query handled by the shortcode filter translates nothing.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_a_post_query_translates_nothing(): void {
		$post_id = $this->factory()->post->create( [ 'post_content' => 'No shortcodes in here.' ] );

		$this->start_recording();

		$query = new WP_Query( [ 'p' => $post_id ] );

		$this->assertCount( 1, $query->posts, 'the query should return a post, or the filter has nothing to handle' );
		$this->assertSame(
			[],
			$this->translated,
			'a post query should not translate anything, as one can run while plugins are still loading'
		);
	}
}
