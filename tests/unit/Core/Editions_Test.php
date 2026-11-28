<?php

namespace Code_Snippets\Core;

use Code_Snippets\UnitTestCase;

/**
 * Tests for finding out which editions of the plugin are installed.
 *
 * @group uninstall
 */
class Editions_Test extends UnitTestCase {

	/**
	 * Plugin directories created by a test.
	 *
	 * @var string[]
	 */
	private array $plugin_dirs = [];

	/**
	 * Remove the plugins a test installed.
	 *
	 * @return void
	 */
	public function tear_down() {
		array_map( [ $this, 'remove_plugin' ], $this->plugin_dirs );
		$this->plugin_dirs = [];

		parent::tear_down();
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
	 * Another edition is installed when two editions are.
	 *
	 * @return void
	 */
	public function test_two_installed_editions_include_another_edition(): void {
		$this->install_editions( 2 );

		$this->assertTrue( Editions::is_other_edition_installed() );
	}

	/**
	 * A single installed edition is the last one.
	 *
	 * @return void
	 */
	public function test_single_installed_edition_is_the_last_one(): void {
		$this->install_editions( 1 );

		$this->assertFalse( Editions::is_other_edition_installed() );
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

		$this->assertFalse( Editions::is_other_edition_installed() );
	}
}
