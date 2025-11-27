<?php

namespace Code_Snippets\Tests;

use WP_UnitTestCase;

// Determine the tests directory
if ( false !== getenv( 'WP_TESTS_DIR' ) ) {
	$_tests_dir = getenv( 'WP_TESTS_DIR' );
} elseif ( false !== getenv( 'WP_DEVELOP_DIR' ) ) {
	$_tests_dir = getenv( 'WP_DEVELOP_DIR' ) . '/tests/phpunit';
} elseif ( false !== getenv( 'WP_PHPUNIT__DIR' ) ) {
	$_tests_dir = getenv( 'WP_PHPUNIT__DIR' );
} elseif ( file_exists( '/tmp/wordpress-tests-lib/includes/functions.php' ) ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
} else {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php\n";
	echo "Please run: bash tests/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version]\n";
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	$plugin_dir = dirname( __DIR__ );
	require $plugin_dir . '/src/code-snippets.php';
}

tests_add_filter( 'muplugins_loaded', __NAMESPACE__ . '\\_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

/**
 * Base test case for all Code Snippets tests.
 */
class TestCase extends WP_UnitTestCase {
	// Put convenience methods here

}
