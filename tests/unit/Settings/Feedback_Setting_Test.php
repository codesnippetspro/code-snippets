<?php
/**
 * Tests for the feedback reporter setting.
 *
 * @package Code_Snippets
 */

namespace Code_Snippets\Settings;

use Code_Snippets\UnitTestCase;

/**
 * The setting that gates the feedback reporter is off until someone turns it on.
 *
 * @group settings
 */
class Feedback_Setting_Test extends UnitTestCase {

	/**
	 * The reporter is opt-in, so existing installs gain nothing on upgrade.
	 *
	 * @return void
	 */
	public function test_feedback_reporter_setting_defaults_to_disabled(): void {
		$defaults = Settings_Fields::get_default_values();

		$this->assertArrayHasKey( 'enable_feedback_reporter', $defaults['general'] );
		$this->assertFalse( $defaults['general']['enable_feedback_reporter'] );
	}

	/**
	 * The field is drawn under Advanced while its value stays in the general section.
	 *
	 * @return void
	 */
	public function test_feedback_reporter_setting_appears_on_the_advanced_tab(): void {
		$contents = Settings_Layout::get_tab_contents();

		$this->assertContains( [ 'general', 'enable_feedback_reporter' ], $contents['advanced'] );
	}

	/**
	 * The checkbox is the consent gate, so it carries a description of what gets sent.
	 *
	 * @return void
	 */
	public function test_feedback_reporter_field_is_a_checkbox_with_a_disclosure(): void {
		$fields = Settings_Fields::get_field_definitions();

		$this->assertArrayHasKey( 'enable_feedback_reporter', $fields['general'] );

		$field = $fields['general']['enable_feedback_reporter'];

		$this->assertSame( 'checkbox', $field['type'] );
		$this->assertNotEmpty( $field['name'] );
		$this->assertNotEmpty( $field['label'] );
		$this->assertNotEmpty( $field['desc'] );
	}
}
