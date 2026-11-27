<?php

namespace Code_Snippets\Core;

use Code_Snippets\Flat_Files\Snippet_Files;
use Code_Snippets\REST_API\Preferences\Stats_View_Rest_Controller;
use Code_Snippets\REST_API\Preferences\Snippet_View_REST_Controller;
use Code_Snippets\Settings\Version_Switch;
use Code_Snippets\UnitTestCase;
use function Code_Snippets\code_snippets;

/**
 * Tests for complete plugin uninstallation.
 */
class Uninstaller_Test extends UnitTestCase {

	/**
	 * Plugin directories created by a test.
	 *
	 * @var string[]
	 */
	private array $plugin_dirs = [];

	/**
	 * Restore the snippets table after testing complete uninstallation.
	 *
	 * @return void
	 */
	public function tear_down() {
		array_map( [ $this, 'remove_plugin' ], $this->plugin_dirs );
		$this->plugin_dirs = [];

		code_snippets()->db->create_or_upgrade_tables();
		Version_Switch::clear_version_caches();
		delete_option( Snippet_View_REST_Controller::OPTION_NAME );
		delete_option( Stats_View_Rest_Controller::OPTION_NAME );
		delete_option( 'code_snippets_settings' );

		parent::tear_down();
	}

	/**
	 * Complete uninstall removes saved Stats chart view preferences.
	 *
	 * @return void
	 */
	public function test_complete_uninstall_removes_stats_chart_view_preferences(): void {
		update_option(
			'code_snippets_settings',
			[
				'general' => [ 'complete_uninstall' => true ],
			]
		);

		update_option(
			Stats_View_Rest_Controller::OPTION_NAME,
			[
				'type'       => 'pie',
				'activation' => 'bar',
				'location'   => 'pie',
			]
		);

		( new Uninstaller() )->uninstall_plugin();

		$this->assertFalse( get_option( Stats_View_Rest_Controller::OPTION_NAME ) );
	}

	/**
	 * Complete uninstall removes the stored names of the snippet file directories.
	 *
	 * @return void
	 */
	public function test_complete_uninstall_removes_flat_file_directory_names(): void {
		update_option(
			'code_snippets_settings',
			[
				'general' => [ 'complete_uninstall' => true ],
			]
		);

		update_option( Snippet_Files::DIR_NAMES_OPTION, [ code_snippets()->db->table => 'directory' ] );

		( new Uninstaller() )->uninstall_plugin();

		$this->assertFalse( get_option( Snippet_Files::DIR_NAMES_OPTION ) );
	}

	/**
	 * Complete uninstall takes the version switcher's transients with it, whichever
	 * source cached them.
	 *
	 * @return void
	 */
	public function test_complete_uninstall_removes_version_switcher_transients(): void {
		update_option(
			'code_snippets_settings',
			[
				'general' => [ 'complete_uninstall' => true ],
			]
		);

		$cache_key = Version_Switch::get_source()->get_cache_key();

		set_transient(
			$cache_key,
			[
				'versions' => [],
				'floor'    => '',
			],
			HOUR_IN_SECONDS
		);
		set_transient( 'code_snippets_version_switch_progress', '3.9.2', HOUR_IN_SECONDS );
		set_transient( 'code_snippets_version_switch_error', 'version_source_request_error', HOUR_IN_SECONDS );

		( new Uninstaller() )->uninstall_plugin();

		$this->assertFalse( get_transient( $cache_key ) );
		$this->assertFalse( get_transient( 'code_snippets_version_switch_progress' ) );
		$this->assertFalse( get_transient( 'code_snippets_version_switch_error' ) );
	}

	/**
	 * Install a plugin consisting of a single main file with the given headers.
	 *
	 * @param string $dir         Plugin directory name.
	 * @param string $name        Plugin name.
	 * @param string $text_domain Plugin text domain.
	 *
	 * @return void
	 */
	private function install_plugin( string $dir, string $name, string $text_domain = 'code-snippets' ): void {
		wp_mkdir_p( WP_PLUGIN_DIR . "/$dir" );
		file_put_contents( WP_PLUGIN_DIR . "/$dir/code-snippets.php", "<?php\n/**\n * Plugin Name: $name\n * Text Domain: $text_domain\n */\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$this->plugin_dirs[] = $dir;
		wp_clean_plugins_cache( false );
	}

	/**
	 * Install copies of the plugin until the given number of editions is installed.
	 *
	 * The plugin under test may itself be in the plugins directory, depending on how the tests load it.
	 *
	 * @param int $count Number of editions to have installed.
	 *
	 * @return void
	 */
	private function install_editions( int $count ): void {
		$installed = array_filter(
			get_plugins(),
			static function ( array $plugin_data ): bool {
				return 'code-snippets' === $plugin_data['TextDomain'] && 0 === strpos( $plugin_data['Name'], 'Code Snippets' );
			}
		);

		for ( $edition = count( $installed ); $edition < $count; $edition++ ) {
			$this->install_plugin( "code-snippets-test-edition-$edition", 'Code Snippets' );
		}
	}

	/**
	 * Remove a plugin installed by install_plugin().
	 *
	 * @param string $dir Plugin directory name.
	 *
	 * @return void
	 */
	private function remove_plugin( string $dir ): void {
		if ( file_exists( WP_PLUGIN_DIR . "/$dir/code-snippets.php" ) ) {
			unlink( WP_PLUGIN_DIR . "/$dir/code-snippets.php" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
		}

		if ( is_dir( WP_PLUGIN_DIR . "/$dir" ) ) {
			rmdir( WP_PLUGIN_DIR . "/$dir" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
		}

		wp_clean_plugins_cache( false );
	}

	/**
	 * Turn on complete uninstall and record shared data that it would remove.
	 *
	 * @return void
	 */
	private function prepare_shared_data(): void {
		$settings = [ 'general' => [ 'complete_uninstall' => true ] ];
		update_option( 'code_snippets_settings', $settings );
		update_site_option( 'code_snippets_settings', $settings );
		update_option( 'code_snippets_version', '4.1.1' );
		update_site_option( 'recently_active_snippets', [ 1 => 1700000000 ] );
	}

	/**
	 * Report whether the shared data from prepare_shared_data() is still present.
	 *
	 * @return bool[]
	 */
	private function shared_data_state(): array {
		return [
			'settings'        => false !== get_option( 'code_snippets_settings' ),
			'version'         => false !== get_option( 'code_snippets_version' ),
			'recently_active' => false !== get_site_option( 'recently_active_snippets' ),
		];
	}

	/**
	 * Complete uninstall keeps the shared data while the other edition is still installed.
	 *
	 * @return void
	 */
	public function test_complete_uninstall_keeps_shared_data_while_another_edition_is_installed(): void {
		$this->install_editions( 2 );
		$this->prepare_shared_data();

		( new Uninstaller() )->uninstall_plugin();

		$this->assertSame(
			[
				'settings'        => true,
				'version'         => true,
				'recently_active' => true,
			],
			$this->shared_data_state()
		);
	}

	/**
	 * Complete uninstall removes the shared data when only one edition is installed.
	 *
	 * @return void
	 */
	public function test_complete_uninstall_removes_shared_data_for_the_last_edition(): void {
		$this->install_editions( 1 );
		$this->prepare_shared_data();

		( new Uninstaller() )->uninstall_plugin();

		$this->assertSame(
			[
				'settings'        => false,
				'version'         => false,
				'recently_active' => false,
			],
			$this->shared_data_state()
		);
	}

	/**
	 * Other plugins, and an edition deleted earlier in the same request, do not count as another edition.
	 *
	 * @return void
	 */
	public function test_only_installed_editions_count_as_another_edition(): void {
		$this->install_editions( 1 );
		$this->install_plugin( 'code-snippets-test-other-domain', 'Code Snippets Helper', 'other-plugin' );
		$this->install_plugin( 'code-snippets-test-other-name', 'Snippet Helper' );
		$this->install_plugin( 'code-snippets-test-pro', 'Code Snippets Pro' );

		// The plugin list stays cached after a bulk delete removes a plugin's files.
		get_plugins();
		unlink( WP_PLUGIN_DIR . '/code-snippets-test-pro/code-snippets.php' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
		$this->prepare_shared_data();

		( new Uninstaller() )->uninstall_plugin();

		$this->assertSame(
			[
				'settings'        => false,
				'version'         => false,
				'recently_active' => false,
			],
			$this->shared_data_state()
		);
	}
}
