<?php
/**
 * Runs one step of deleting the plugin through WordPress, in a process of its own.
 *
 * WordPress deletes an inactive plugin by running its uninstall.php without loading the plugin, which a PHPUnit test
 * cannot reproduce in its own process, where the plugin is always loaded. This script loads WordPress against tables
 * of its own, so the test suite's tables are left alone.
 *
 * Usage: php uninstall-runner.php <install|activate|uninstall|cleanup> <tests directory> <content directory> [no-group-flush]
 *
 * With no-group-flush, the object cache reports that it cannot flush whole groups, so the uninstaller deletes the
 * plugin's cache keys one by one instead.
 *
 * @package Code_Snippets
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
 * phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
 * phpcs:disable WordPress.DB.DirectDatabaseQuery
 * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
 * phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
 * phpcs:disable WordPress.WP.AlternativeFunctions
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
 */

[ , $step, $tests_dir, $content_dir ] = $argv;
$no_group_flush = 'no-group-flush' === ( $argv[4] ?? '' );

const RUNNER_TABLE_PREFIX = 'csuninst_';
const RUNNER_PLUGIN = 'src/code-snippets.php';

if ( 'install' === $step ) {
	define( 'WP_INSTALLING', true );
}

// The plugin is found where it is in the repository, as src/code-snippets.php.
define( 'WP_PLUGIN_DIR', dirname( __DIR__ ) );
define( 'WP_CONTENT_DIR', $content_dir );
define( 'DISABLE_WP_CRON', true );

require $tests_dir . '/wp-tests-config.php';

$table_prefix = RUNNER_TABLE_PREFIX;

$_SERVER['HTTP_HOST'] = WP_TESTS_DOMAIN;
$_SERVER['SERVER_NAME'] = WP_TESTS_DOMAIN;
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['PHP_SELF'] = '/index.php';

require ABSPATH . 'wp-settings.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

/**
 * Remove every table this script created.
 *
 * @return void
 */
function runner_drop_tables() {
	global $wpdb;

	$tables = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( RUNNER_TABLE_PREFIX ) . '%' ) );

	foreach ( $tables as $table ) {
		$wpdb->query( "DROP TABLE IF EXISTS `$table`" );
	}
}

switch ( $step ) {
	case 'install':
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		runner_drop_tables();
		wp_install( 'Uninstall Test', 'admin', WP_TESTS_EMAIL, false, '', 'password' );
		break;

	case 'activate':
		$result = activate_plugin( RUNNER_PLUGIN );

		if ( is_wp_error( $result ) ) {
			echo $result->get_error_message();
			exit( 1 );
		}

		Code_Snippets\code_snippets()->db->create_or_upgrade_tables();
		update_option( 'code_snippets_settings', [ 'general' => [ 'complete_uninstall' => true ] ] );
		wp_mkdir_p( WP_CONTENT_DIR . '/code-snippets' );
		file_put_contents( WP_CONTENT_DIR . '/code-snippets/index.php', '<?php' );

		deactivate_plugins( RUNNER_PLUGIN );
		break;

	case 'uninstall':
		if ( function_exists( 'Code_Snippets\code_snippets' ) ) {
			echo 'The plugin was loaded before it was uninstalled.';
			exit( 1 );
		}

		if ( $no_group_flush ) {
			add_filter( 'code_snippets/pre_flush_cache_group', '__return_false' );
		}

		uninstall_plugin( RUNNER_PLUGIN );

		echo wp_json_encode(
			[
				'table'    => $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'snippets' ) ),
				'settings' => get_option( 'code_snippets_settings' ),
				'version'  => get_option( 'code_snippets_version' ),
				'files'    => is_dir( WP_CONTENT_DIR . '/code-snippets' ),
			]
		);
		break;

	case 'cleanup':
		runner_drop_tables();
		break;
}
