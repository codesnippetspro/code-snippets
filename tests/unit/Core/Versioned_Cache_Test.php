<?php

namespace Code_Snippets\Core;

use Code_Snippets\UnitTestCase;
use function Code_Snippets\flush_cache_group;
use function Code_Snippets\flush_versioned_cache_groups;
use const Code_Snippets\CACHE_GROUP;
use const Code_Snippets\CACHE_GROUP_BASE;
use const Code_Snippets\PLUGIN_VERSION;

/**
 * Tests for version-scoped cache groups.
 *
 * @group cache
 */
class Versioned_Cache_Test extends UnitTestCase {

	/**
	 * The cache group carries the plugin version.
	 *
	 * Snippet objects are cached, and the Snippet class moved namespace in
	 * 3.10. A version that cannot resolve the stored class fatals on
	 * unserialize, so two versions must never share a group.
	 *
	 * @return void
	 */
	public function test_cache_group_is_scoped_to_the_plugin_version(): void {
		$this->assertSame( CACHE_GROUP_BASE . '_' . PLUGIN_VERSION, CACHE_GROUP );
		$this->assertStringContainsString( PLUGIN_VERSION, CACHE_GROUP );
	}

	/**
	 * A different version reads a different group, so cannot see this data.
	 *
	 * @return void
	 */
	public function test_another_version_does_not_share_cached_data(): void {
		wp_cache_set( 'shared_key', 'written by this version', CACHE_GROUP );

		$other_group = CACHE_GROUP_BASE . '_0.0.1';

		$this->assertSame( 'written by this version', wp_cache_get( 'shared_key', CACHE_GROUP ) );
		$this->assertFalse( wp_cache_get( 'shared_key', $other_group ) );
	}

	/**
	 * Flushing a group is safe even when the backend cannot do it.
	 *
	 * @return void
	 */
	public function test_flushing_a_group_never_errors(): void {
		wp_cache_set( 'transient_key', 'value', CACHE_GROUP );

		$this->assertIsBool( flush_cache_group( CACHE_GROUP ) );
	}

	/**
	 * Flushing on a version change clears the previous version's group.
	 *
	 * @return void
	 */
	public function test_version_change_clears_the_previous_group(): void {
		$previous_group = CACHE_GROUP_BASE . '_3.9.6';

		wp_cache_set( 'stale', 'left by the older version', $previous_group );
		wp_cache_set( 'current', 'ours', CACHE_GROUP );

		flush_versioned_cache_groups( '3.9.6' );

		if ( function_exists( 'wp_cache_supports' ) && wp_cache_supports( 'flush_group' ) ) {
			$this->assertFalse( wp_cache_get( 'stale', $previous_group ) );
		} else {
			$this->markTestSkipped( 'Object cache does not support group flushing.' );
		}
	}

	/**
	 * An empty previous version is tolerated, as on a fresh install.
	 *
	 * @return void
	 */
	public function test_empty_previous_version_is_tolerated(): void {
		flush_versioned_cache_groups( '' );

		$this->assertTrue( true );
	}
}
