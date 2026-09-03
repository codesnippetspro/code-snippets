<?php

namespace Code_Snippets\Core;

use Code_Snippets\UnitTestCase;
use Composer\Autoload\ClassLoader;

/**
 * Tests for the Composer autoloader namespace cleanup performed in load.php.
 */
class Autoloader_Prefixes_Test extends UnitTestCase {

	/**
	 * Retrieve the plugin's registered Composer autoloader, if there is one.
	 *
	 * @return ClassLoader|null
	 */
	private function get_plugin_autoloader(): ?ClassLoader {
		$registered = spl_autoload_functions();

		foreach ( is_array( $registered ) ? $registered : [] as $callback ) {
			if ( is_array( $callback ) && $callback[0] instanceof ClassLoader ) {
				$prefixes = $callback[0]->getPrefixesPsr4();

				if ( isset( $prefixes['Code_Snippets\\'] ) ) {
					return $callback[0];
				}
			}
		}

		return null;
	}

	/**
	 * Vendor packages are prefixed by Imposter, but Composer still registers the
	 * original namespace against the same directory. Left in place, our autoloader
	 * answers for the unprefixed name, includes the prefixed file a second time,
	 * and PHP raises "cannot declare interface, because the name is already in use"
	 * on any site running another plugin that bundles the same library.
	 *
	 * @return void
	 */
	public function test_no_unprefixed_vendor_namespace_remains_registered(): void {
		$autoloader = $this->get_plugin_autoloader();

		if ( ! $autoloader ) {
			$this->markTestSkipped( 'The plugin Composer autoloader is not registered in this environment.' );
		}

		$vendor_prefix = 'Code_Snippets\\Vendor\\';
		$prefixes      = $autoloader->getPrefixesPsr4();
		$leftovers     = [];

		foreach ( array_keys( $prefixes ) as $namespace ) {
			if ( 0 !== strpos( $namespace, $vendor_prefix ) &&
			     isset( $prefixes[ $vendor_prefix . $namespace ] ) &&
			     ! empty( $prefixes[ $namespace ] ) ) {
				$leftovers[] = $namespace;
			}
		}

		$this->assertSame(
			[],
			$leftovers,
			'Unprefixed vendor namespaces are still registered: ' . implode( ', ', $leftovers )
		);
	}
}
