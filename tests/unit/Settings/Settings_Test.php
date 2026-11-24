<?php
/**
 * Tests for reading and saving the plugin settings.
 *
 * @package Code_Snippets
 */

namespace Code_Snippets\Settings;

use Code_Snippets\UnitTestCase;

/**
 * Settings read back after a save show the values that were saved.
 *
 * @group settings
 */
class Settings_Test extends UnitTestCase {

	/**
	 * Save settings as the settings form submits them.
	 *
	 * @param array<string, array<string, string>> $input Posted settings.
	 *
	 * @return void
	 */
	private function submit( array $input ): void {
		register_setting( OPTION_GROUP, OPTION_NAME, [ 'sanitize_callback' => __NAMESPACE__ . '\\sanitize_settings' ] );
		update_option( OPTION_NAME, $input );
	}

	/**
	 * A checkbox reads back as saved, whichever way it was changed.
	 *
	 * @return void
	 */
	public function test_saved_checkbox_reads_back_as_saved(): void {
		add_option( OPTION_NAME, Settings_Fields::get_default_values() );
		get_settings_values();

		$this->submit( [ 'general' => [ 'activate_by_default' => 'on' ] ] );
		$this->assertTrue( get_setting( 'general', 'activate_by_default' ) );

		$this->submit( [ 'general' => [] ] );
		$this->assertFalse( get_setting( 'general', 'activate_by_default' ) );

		$this->submit( [ 'general' => [ 'activate_by_default' => 'on' ] ] );
		$this->assertTrue( get_setting( 'general', 'activate_by_default' ) );
	}
}
