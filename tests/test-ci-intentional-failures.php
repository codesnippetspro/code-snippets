<?php

namespace Code_Snippets\Tests;

/**
 * Intentionally failing tests to validate CI reporting.
 */
class CI_Intentional_Failures_Test extends TestCase {

	public function test_intentional_failure_all_versions() {
		$this->fail( 'CI demo: intentional failure on all PHP versions.' );
	}

	public function test_intentional_failure_php_8_3_and_8_4_only() {
		if ( PHP_VERSION_ID >= 80300 && PHP_VERSION_ID < 80500 ) {
			$this->fail( 'CI demo: intentional failure on PHP 8.3 and 8.4 only.' );
		}

		$this->assertTrue( true );
	}
}
