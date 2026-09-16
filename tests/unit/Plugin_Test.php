<?php

namespace Code_Snippets;

use Code_Snippets\Integration\Promotions\Other\Elementor_Editor;

/**
 * Tests for the main plugin class.
 */
class Plugin_Test extends UnitTestCase {

	/**
	 * Loading the plugin in the admin registers one Elementor promotion callback.
	 *
	 * @return void
	 */
	public function test_load_plugin_registers_elementor_promotion_once(): void {
		set_current_screen( 'dashboard' );

		$callbacks_before = $this->get_elementor_promotion_callbacks();

		$plugin = new Plugin();
		$plugin->load_plugin();

		$this->assertCount( count( $callbacks_before ) + 1, $this->get_elementor_promotion_callbacks() );
	}

	/**
	 * Retrieve the callbacks that initialize Elementor promotions.
	 *
	 * @return array
	 */
	private function get_elementor_promotion_callbacks(): array {
		global $wp_filter;

		$promotion_callbacks = [];

		foreach ( $wp_filter['elementor/init']->callbacks ?? [] as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$function = $callback['function'];

				if ( is_array( $function ) && $function[0] instanceof Elementor_Editor && 'promotion_in_custom_css_section' === $function[1] ) {
					$promotion_callbacks[] = $function;
				}
			}
		}

		return $promotion_callbacks;
	}
}
