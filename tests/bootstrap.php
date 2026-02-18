<?php

namespace Code_Snippets\Tests;

use WP_UnitTestCase;

$_tests_dir = false;

switch ( true ) {
	case (bool) getenv( 'WP_TESTS_DIR' ):
		$_tests_dir = getenv( 'WP_TESTS_DIR' );
		break;

	case (bool) getenv( 'WP_DEVELOP_DIR' ):
		$_tests_dir = getenv( 'WP_DEVELOP_DIR' ) . '/tests/phpunit';
		break;

	case (bool) getenv( 'WP_PHPUNIT__DIR' ):
		$_tests_dir = getenv( 'WP_PHPUNIT__DIR' );
		break;

	case file_exists( dirname( __DIR__ ) . '/.wp-tests-lib/includes/functions.php' ):
		$_tests_dir = dirname( __DIR__ ) . '/.wp-tests-lib';
		break;

	case file_exists( '/tmp/wordpress-tests-lib/includes/functions.php' ):
		$_tests_dir = '/tmp/wordpress-tests-lib';
		break;

	default:
		$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
		break;
}

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

/**
 * Base test case for all Code Snippets tests.
 */
class TestCase extends WP_UnitTestCase {}
