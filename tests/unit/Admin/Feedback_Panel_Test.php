<?php
/**
 * Tests for the feedback reporter panel.
 *
 * @package Code_Snippets
 */

namespace Code_Snippets\Admin;

use Code_Snippets\UnitTestCase;
use function Code_Snippets\Settings\update_setting;

/**
 * The reporter stays out of the way until it is switched on, and then only appears on this
 * plugin's own screens for people allowed to manage snippets.
 *
 * @group feedback
 */
class Feedback_Panel_Test extends UnitTestCase {

	/**
	 * Panel under test.
	 *
	 * @var Feedback_Panel
	 */
	private Feedback_Panel $panel;

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->panel = new Feedback_Panel();
	}

	/**
	 * Remove what a test set.
	 *
	 * @return void
	 */
	public function tear_down() {
		update_setting( 'general', Feedback_Panel::SETTING_FIELD, false );
		remove_all_filters( 'code_snippets_feedback_badge_label' );
		delete_transient( Feedback_Panel::SUMMARY_TRANSIENT );
		unset( $_GET['page'] );
		wp_set_current_user( 0 );

		parent::tear_down();
	}

	/**
	 * Place the request on a Code Snippets screen.
	 *
	 * @return void
	 */
	private function visit_snippets_screen(): void {
		$_GET['page'] = 'snippets-settings';
		set_current_screen( 'snippets_page_snippets-settings' );
	}

	/**
	 * Sign in as somebody allowed to manage snippets.
	 *
	 * @return void
	 */
	private function log_in_as_administrator(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * Nothing is switched on by an upgrade.
	 *
	 * @return void
	 */
	public function test_is_disabled_by_default(): void {
		$this->assertFalse( Feedback_Panel::is_enabled() );
	}

	/**
	 * The reporter is absent while the setting is off.
	 *
	 * @return void
	 */
	public function test_does_not_render_while_the_setting_is_off(): void {
		$this->log_in_as_administrator();
		$this->visit_snippets_screen();

		$this->assertFalse( $this->panel->should_render() );
	}

	/**
	 * Somebody who cannot manage snippets is not offered the reporter.
	 *
	 * @return void
	 */
	public function test_does_not_render_without_the_capability(): void {
		update_setting( 'general', Feedback_Panel::SETTING_FIELD, true );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );
		$this->visit_snippets_screen();

		$this->assertFalse( $this->panel->should_render() );
	}

	/**
	 * The reporter belongs to this plugin's screens, not to the whole admin.
	 *
	 * @return void
	 */
	public function test_does_not_render_outside_code_snippets_screens(): void {
		update_setting( 'general', Feedback_Panel::SETTING_FIELD, true );
		$this->log_in_as_administrator();
		set_current_screen( 'edit-post' );

		$this->assertFalse( $this->panel->should_render() );
	}

	/**
	 * With the setting on, an administrator sees the reporter on a snippets screen.
	 *
	 * @return void
	 */
	public function test_renders_on_a_code_snippets_screen_when_enabled(): void {
		update_setting( 'general', Feedback_Panel::SETTING_FIELD, true );
		$this->log_in_as_administrator();
		$this->visit_snippets_screen();

		$this->assertTrue( $this->panel->should_render() );
	}

	/**
	 * The container the panel mounts into carries the identifier the script looks for.
	 *
	 * @return void
	 */
	public function test_container_markup_carries_the_expected_id(): void {
		update_setting( 'general', Feedback_Panel::SETTING_FIELD, true );
		$this->log_in_as_administrator();
		$this->visit_snippets_screen();

		ob_start();
		$this->panel->render_container();
		$markup = ob_get_clean();

		$this->assertStringContainsString( 'id="' . Feedback_Panel::CONTAINER_ID . '"', $markup );
	}

	/**
	 * Nothing is printed on a screen the reporter does not belong on.
	 *
	 * @return void
	 */
	public function test_container_is_not_printed_when_it_should_not_render(): void {
		$this->log_in_as_administrator();
		set_current_screen( 'edit-post' );

		ob_start();
		$this->panel->render_container();

		$this->assertSame( '', ob_get_clean() );
	}

	/**
	 * Reading every plugin header is too much work to repeat on each page load, so the
	 * summary the panel shows is collected once and reused.
	 *
	 * @return void
	 */
	public function test_the_environment_summary_is_only_collected_once(): void {
		update_setting( 'general', Feedback_Panel::SETTING_FIELD, true );
		$this->log_in_as_administrator();
		$this->visit_snippets_screen();

		$collected = 0;

		add_filter(
			'code_snippets_feedback_system_info',
			static function ( array $info ) use ( &$collected ): array {
				++$collected;
				return $info;
			}
		);

		$this->panel->enqueue_assets();
		$this->panel->enqueue_assets();

		remove_all_filters( 'code_snippets_feedback_system_info' );

		$this->assertSame( 1, $collected );
		$this->assertIsArray( get_transient( Feedback_Panel::SUMMARY_TRANSIENT ) );
	}

	/**
	 * A released build is not labelled as a test build.
	 *
	 * @return void
	 */
	public function test_badge_label_is_empty_on_a_stable_version(): void {
		$this->assertSame( '', Feedback_Panel::get_badge_label( '4.1.0' ) );
	}

	/**
	 * A pre-release build says so, so a report can be read against the right build.
	 *
	 * @return void
	 */
	public function test_badge_label_names_a_prerelease_version(): void {
		$this->assertSame( 'Beta 4.0.0-beta.1', Feedback_Panel::get_badge_label( '4.0.0-beta.1' ) );
		$this->assertSame( 'Alpha 4.0.0-alpha.2', Feedback_Panel::get_badge_label( '4.0.0-alpha.2' ) );
		$this->assertSame( 'RC 4.0.0-rc.1', Feedback_Panel::get_badge_label( '4.0.0-rc.1' ) );
	}

	/**
	 * The badge wording can be replaced without editing the plugin.
	 *
	 * @return void
	 */
	public function test_badge_label_is_filterable(): void {
		add_filter( 'code_snippets_feedback_badge_label', static fn() => 'Preview' );

		$this->assertSame( 'Preview', Feedback_Panel::get_badge_label( '4.1.0' ) );
	}
}
