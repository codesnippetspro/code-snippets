<?php
/**
 * Runs one step of installing, updating or deleting the plugin through WordPress, in a process of its own.
 *
 * WordPress deletes an inactive plugin by running its uninstall.php without loading the plugin, and replaces its files
 * on an update between requests. A PHPUnit test cannot reproduce either in its own process, where the plugin is always
 * loaded. This script loads WordPress against tables of its own, so the test suite's tables are left alone.
 *
 * Usage: php plugin-lifecycle-runner.php <step> <tests directory> <content directory> [name=value ...]
 *
 * Options:
 * - plugins: directory holding the plugin as code-snippets/, instead of the repository's src/ directory.
 * - package: plugin zip used by the install-plugin, overwrite and update steps.
 * - cache: no-group-flush to have the object cache report that it cannot flush whole groups.
 *
 * Every step ends by printing the state of the plugin's data as JSON on the last line.
 *
 * @package Code_Snippets
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
 * phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
 * phpcs:disable WordPress.DB.DirectDatabaseQuery
 * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
 * phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
 * phpcs:disable WordPress.WP.AlternativeFunctions
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
 */

[ , $step, $tests_dir, $content_dir ] = $argv;

$options = [];

foreach ( array_slice( $argv, 4 ) as $option ) {
	[ $name, $value ] = array_pad( explode( '=', $option, 2 ), 2, '' );
	$options[ $name ] = $value;
}

const RUNNER_TABLE_PREFIX = 'csuninst_';
const RUNNER_DIR_NAMES_OPTION = 'code_snippets_flat_files_dirs';

if ( 'install-wp' === $step ) {
	define( 'WP_INSTALLING', true );
}

if ( empty( $options['plugins'] ) ) {
	// The plugin is found where it is in the repository, as src/code-snippets.php.
	define( 'WP_PLUGIN_DIR', dirname( __DIR__ ) );
	define( 'RUNNER_PLUGIN', 'src/code-snippets.php' );
} else {
	define( 'WP_PLUGIN_DIR', $options['plugins'] );
	define( 'RUNNER_PLUGIN', 'code-snippets/code-snippets.php' );
}

define( 'WP_CONTENT_DIR', $content_dir );
define( 'DISABLE_WP_CRON', true );
define( 'FS_METHOD', 'direct' );

require $tests_dir . '/wp-tests-config.php';

$table_prefix = RUNNER_TABLE_PREFIX;

$_SERVER['HTTP_HOST'] = WP_TESTS_DOMAIN;
$_SERVER['SERVER_NAME'] = WP_TESTS_DOMAIN;
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['PHP_SELF'] = '/index.php';

require ABSPATH . 'wp-settings.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/misc.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

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

/**
 * Stop with a message and a failing exit code.
 *
 * @param string $message Reason for stopping.
 *
 * @return void
 */
function runner_fail( string $message ) {
	echo $message;
	exit( 1 );
}

/**
 * Turn an option on in the plugin's saved settings.
 *
 * @param string $setting Setting in the general section.
 *
 * @return void
 */
function runner_enable_setting( string $setting ) {
	$settings = get_option( 'code_snippets_settings', [] );
	$settings['general'][ $setting ] = true;
	update_option( 'code_snippets_settings', $settings );
}

/**
 * Describe the plugin's data as it is now.
 *
 * @return array<string, mixed>
 */
function runner_state(): array {
	global $wpdb;

	$table = $wpdb->prefix . 'snippets';
	$has_table = (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	$plugin_file = WP_PLUGIN_DIR . '/' . RUNNER_PLUGIN;

	return [
		'active'    => is_plugin_active( RUNNER_PLUGIN ),
		'files'     => file_exists( $plugin_file ) ? get_file_data( $plugin_file, [ 'Version' => 'Version' ] )['Version'] : null,
		'table'     => $has_table,
		'snippets'  => $has_table ? $wpdb->get_col( "SELECT name FROM $table ORDER BY id" ) : [],
		'version'   => get_option( 'code_snippets_version' ),
		'settings'  => get_option( 'code_snippets_settings' ),
		'dir_names' => get_option( RUNNER_DIR_NAMES_OPTION ),
		'flat_dir'  => is_dir( WP_CONTENT_DIR . '/code-snippets' ),
	];
}

$upgrader = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );

switch ( $step ) {
	case 'install-wp':
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		runner_drop_tables();
		wp_install( 'Uninstall Test', 'admin', WP_TESTS_EMAIL, false, '', 'password' );
		break;

	case 'install-plugin':
		if ( true !== $upgrader->install( $options['package'] ) ) {
			runner_fail( 'Installing the plugin failed: ' . implode( ' ', $upgrader->skin->get_upgrade_messages() ) );
		}
		break;

	case 'activate':
		$result = activate_plugin( RUNNER_PLUGIN );

		if ( is_wp_error( $result ) ) {
			runner_fail( $result->get_error_message() );
		}
		break;

	case 'request':
		// Loading WordPress with the plugin active is the request; the plugin upgrades itself on plugins_loaded.
		break;

	case 'add-data':
		Code_Snippets\save_snippet(
			new Code_Snippets\Model\Snippet(
				[
					'name' => 'Snippet added by the test',
					'code' => 'return;',
				]
			)
		);

		update_option( RUNNER_DIR_NAMES_OPTION, [ $wpdb->prefix . 'snippets' => 'directory' ] );
		wp_mkdir_p( WP_CONTENT_DIR . '/code-snippets' );
		file_put_contents( WP_CONTENT_DIR . '/code-snippets/index.php', '<?php' );
		runner_enable_setting( 'complete_uninstall' );
		break;

	case 'from-version':
		// Record the data as written by an earlier version, so the next request upgrades it.
		update_option( 'code_snippets_version', $options['version'] );
		break;

	case 'overwrite':
		// Plugins > Add New > Upload, choosing to replace the installed version.
		if ( true !== $upgrader->install( $options['package'], [ 'overwrite_package' => true ] ) ) {
			runner_fail( 'Replacing the plugin failed: ' . implode( ' ', $upgrader->skin->get_upgrade_messages() ) );
		}
		break;

	case 'update':
		// An update offered by WordPress.org.
		$updates = (object) [
			'last_checked' => time(),
			'checked'      => [ RUNNER_PLUGIN => '0' ],
			'response'     => [
				RUNNER_PLUGIN => (object) [
					'slug'        => 'code-snippets',
					'plugin'      => RUNNER_PLUGIN,
					'new_version' => '99',
					'package'     => $options['package'],
				],
			],
		];

		set_site_transient( 'update_plugins', $updates );

		if ( true !== $upgrader->upgrade( RUNNER_PLUGIN, [ 'clear_update_cache' => false ] ) ) {
			runner_fail( 'Updating the plugin failed: ' . implode( ' ', $upgrader->skin->get_upgrade_messages() ) );
		}
		break;

	case 'deactivate':
		deactivate_plugins( RUNNER_PLUGIN );
		break;

	case 'uninstall':
		if ( function_exists( 'Code_Snippets\code_snippets' ) ) {
			runner_fail( 'The plugin was loaded before it was uninstalled.' );
		}

		if ( 'no-group-flush' === ( $options['cache'] ?? '' ) ) {
			add_filter( 'code_snippets/pre_flush_cache_group', '__return_false' );
		}

		uninstall_plugin( RUNNER_PLUGIN );
		break;

	case 'cleanup':
		runner_drop_tables();
		exit( 0 );

	default:
		runner_fail( "Unknown step: $step" );
}

echo "\n", wp_json_encode( runner_state() );
