<?php

namespace Code_Snippets;

use WP_UnitTestCase;

/**
 * Retrieve the configured directory where WordPress PHPUnit tests are stored.
 *
 * @return string
 */
function _get_tests_dir(): string {
	switch ( true ) {
		case (bool) getenv( 'WP_TESTS_DIR' ):
			return getenv( 'WP_TESTS_DIR' );

		case (bool) getenv( 'WP_PHPUNIT__DIR' ):
			return getenv( 'WP_PHPUNIT__DIR' );

		case file_exists( dirname( __DIR__ ) . '/.wp-tests-lib/includes/functions.php' ):
			return dirname( __DIR__ ) . '/.wp-tests-lib';

		case file_exists( '/tmp/wordpress-tests-lib/includes/functions.php' ):
			return '/tmp/wordpress-tests-lib';

		default:
			return rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
	}
}

$_tests_dir = _get_tests_dir();

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	die( "WordPress test suite not found. Run: bash tests/install-wp-tests.sh <db-name> <db-user> <db-pass>\n" );
}

require_once $_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	function () {
		require dirname( __DIR__ ) . '/src/code-snippets.php';
	}
);

require $_tests_dir . '/includes/bootstrap.php';
require __DIR__ . '/unit/UnitTestCase.php';
require __DIR__ . '/unit/AdminUnitTestCase.php';
