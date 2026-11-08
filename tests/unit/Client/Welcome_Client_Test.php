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
	 * Changelog placed in the plugin directory, found before any changelog further up.
	 *
	 * @return string
	 */
	private function changelog_path(): string {
		return plugin_dir_path( PLUGIN_FILE ) . 'CHANGELOG.md';
	}

	/**
	 * Remove the test changelog after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		if ( file_exists( $this->changelog_path() ) ) {
			wp_delete_file( $this->changelog_path() );
		}

		parent::tear_down();
	}

	/**
	 * A release heading with a status label in place of a date is read with that label.
	 *
	 * @return void
	 */
	public function test_release_without_a_date_keeps_its_label(): void {
		update_option( 'date_format', 'Y-m-d' );

		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';

		( new WP_Filesystem_Direct( null ) )->put_contents(
			$this->changelog_path(),
			"# Changelog\n\n## [9.1.0] (UPCOMING)\n\n### Fixed\n* Second fix.\n\n## [9.0.0] (2026-11-05)\n\n### Added\n* First feature.\n"
		);

		$changelog = Welcome_Client::read_changelog();

		$this->assertSame( [ '9.1.0', '9.0.0' ], array_column( $changelog, 'version' ) );
		$this->assertSame( [ 'UPCOMING', '2026-11-05' ], array_column( $changelog, 'date' ) );
	}
}
