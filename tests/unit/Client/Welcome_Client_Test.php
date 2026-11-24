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
}
