<?php
/**
 * Tests for the environment capture attached to feedback reports.
 *
 * @package Code_Snippets
 */

namespace Code_Snippets\Utils;

use Code_Snippets\UnitTestCase;

/**
 * Reports carry enough of the environment to reproduce a problem, and the summary shown
 * to the reporter beforehand names everything that leaves the site.
 *
 * @group feedback
 */
class System_Info_Test extends UnitTestCase {

	/**
	 * Remove what a test registered.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_all_filters( 'code_snippets_feedback_system_info' );

		parent::tear_down();
	}

	/**
	 * Every key the cloud expects is present and describes the running environment.
	 *
	 * @return void
	 */
	public function test_system_info_reports_the_running_environment(): void {
		$info = System_Info::get_system_info();

		$expected_keys = [
			'plugin_version',
			'edition',
			'wordpress_version',
			'php_version',
			'database',
			'active_theme',
			'active_plugins',
			'plugin_count',
			'multisite',
			'locale',
			'wp_debug',
			'wp_memory_limit',
			'php_memory_limit',
			'max_execution_time',
			'server_software',
			'site_url',
		];

		foreach ( $expected_keys as $key ) {
			$this->assertArrayHasKey( $key, $info );
		}

		$this->assertSame( PHP_VERSION, $info['php_version'] );
		$this->assertSame( get_locale(), $info['locale'] );
		$this->assertSame( site_url(), $info['site_url'] );
		$this->assertIsArray( $info['active_plugins'] );
		$this->assertCount( $info['plugin_count'], $info['active_plugins'] );
	}

	/**
	 * The edition names the plugin the cloud already knows about.
	 *
	 * @return void
	 */
	public function test_edition_is_reported_as_free_or_pro(): void {
		$this->assertContains( System_Info::get_system_info()['edition'], [ 'free', 'pro' ] );
	}

	/**
	 * The reporter is shown a short summary, and it withholds nothing that the summary omits.
	 *
	 * @return void
	 */
	public function test_summary_lists_the_disclosed_values(): void {
		$summary = System_Info::get_summary( System_Info::get_system_info() );

		$this->assertCount( 6, $summary );
		$this->assertNotEmpty( $summary[ __( 'Code Snippets', 'code-snippets' ) ] );
		$this->assertNotEmpty( $summary[ __( 'WordPress', 'code-snippets' ) ] );
		$this->assertSame( PHP_VERSION, $summary[ __( 'PHP', 'code-snippets' ) ] );
	}

	/**
	 * Sites can amend what is collected before it is sent.
	 *
	 * @return void
	 */
	public function test_system_info_is_filterable(): void {
		add_filter(
			'code_snippets_feedback_system_info',
			static function ( array $info ): array {
				$info['locale'] = 'xx_XX';
				return $info;
			}
		);

		$this->assertSame( 'xx_XX', System_Info::get_system_info()['locale'] );
	}
}
