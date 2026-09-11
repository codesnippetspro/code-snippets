<?php
/**
 * Tests for loading the code editor.
 *
 * @package Code_Snippets
 */

namespace Code_Snippets\Utils;

use Code_Snippets\UnitTestCase;
use function Code_Snippets\Settings\update_setting;

/**
 * The code editor is configured from the editor settings and loaded with only the assets it needs.
 *
 * @group editor
 */
class Editor_Test extends UnitTestCase {

	/**
	 * Remove what a test registered.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_all_filters( 'code_snippets_codemirror_atts' );

		wp_deregister_script( CODE_EDITOR_HANDLE );
		wp_deregister_script( 'code-snippets-manage-js' );
		wp_deregister_style( CODE_EDITOR_HANDLE );
		wp_deregister_style( 'code-snippets-editor-theme-monokai' );

		parent::tear_down();
	}

	/**
	 * Read the editor configuration printed before a script.
	 *
	 * @param string $handle Script handle.
	 *
	 * @return array<string, mixed>
	 */
	private function get_printed_config( string $handle ): array {
		$inline = implode( '', (array) wp_scripts()->get_data( $handle, 'before' ) );
		$this->assertMatchesRegularExpression( '/^var CODE_SNIPPETS_EDITOR = (.+);$/', $inline );

		preg_match( '/^var CODE_SNIPPETS_EDITOR = (.+);$/', $inline, $matches );
		return json_decode( $matches[1], true );
	}

	/**
	 * Saved settings reach the editor under the option names their fields declare, with their proper types.
	 *
	 * @return void
	 */
	public function test_settings_are_keyed_by_editor_option_name(): void {
		update_setting( 'editor', 'tab_size', '2' );
		update_setting( 'editor', 'line_numbers', false );
		update_setting( 'editor', 'keymap', 'vim' );

		$settings = get_code_editor_settings();

		$this->assertSame( 2, $settings['tabSize'] );
		$this->assertFalse( $settings['lineNumbers'] );
		$this->assertSame( 'vim', $settings['keyMap'] );
		$this->assertTrue( $settings['matchBrackets'] );
		$this->assertTrue( $settings['lint'] );
		$this->assertSame( 'ltr', $settings['direction'] );
		$this->assertTrue( $settings['indentationMarkers'] );
		$this->assertFalse( $settings['highlightTrailingWhitespace'] );
	}

	/**
	 * Attributes passed in, and then the filter, take precedence over saved settings.
	 *
	 * @return void
	 */
	public function test_extra_attributes_and_filter_override_saved_settings(): void {
		update_setting( 'editor', 'theme', 'monokai' );

		add_filter(
			'code_snippets_codemirror_atts',
			function ( array $atts ) {
				$atts['lint'] = false;
				return $atts;
			}
		);

		$settings = get_code_editor_settings( [ 'theme' => 'dracula' ] );

		$this->assertSame( 'dracula', $settings['theme'] );
		$this->assertFalse( $settings['lint'] );
	}

	/**
	 * The editor script is queued with its configuration, and the font size becomes a style rather than an option.
	 *
	 * @return void
	 */
	public function test_enqueue_code_editor_prints_configuration(): void {
		update_setting( 'editor', 'font_size', 16 );

		enqueue_code_editor();

		$this->assertTrue( wp_script_is( CODE_EDITOR_HANDLE ) );
		$this->assertContains( 'wp-hooks', wp_scripts()->registered[ CODE_EDITOR_HANDLE ]->deps );

		$config = $this->get_printed_config( CODE_EDITOR_HANDLE );
		$this->assertTrue( $config['enabled'] );
		$this->assertArrayNotHasKey( 'fontSize', $config['settings'] );

		$inline_styles = implode( '', (array) wp_styles()->get_data( CODE_EDITOR_HANDLE, 'after' ) );
		$this->assertStringContainsString( '.cm-editor { font-size: 16px; }', $inline_styles );
	}

	/**
	 * WordPress's bundled CodeMirror and its linters are no longer loaded.
	 *
	 * @return void
	 */
	public function test_enqueue_code_editor_does_not_load_wordpress_codemirror(): void {
		enqueue_code_editor();

		foreach ( [ 'wp-codemirror', 'code-editor', 'csslint', 'jshint', 'htmlhint' ] as $handle ) {
			$this->assertFalse( wp_script_is( $handle ), "$handle should not be enqueued." );
		}
	}

	/**
	 * Turning off syntax highlighting in the user profile is passed on to the editor.
	 *
	 * @return void
	 */
	public function test_profile_preference_disables_the_editor(): void {
		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		update_user_meta( $user_id, 'syntax_highlighting', 'false' );
		wp_set_current_user( $user_id );

		$this->assertFalse( is_code_editor_enabled() );

		enqueue_code_editor();
		$this->assertFalse( $this->get_printed_config( CODE_EDITOR_HANDLE )['enabled'] );
	}

	/**
	 * Previews attach the configuration to the script that shows them and load the chosen theme.
	 *
	 * @return void
	 */
	public function test_preview_editor_configures_the_given_script(): void {
		update_setting( 'editor', 'theme', 'monokai' );
		wp_register_script( 'code-snippets-manage-js', 'manage.js', [], '1.0', true );

		enqueue_code_preview_editor( 'code-snippets-manage-js' );

		$this->assertSame( 'monokai', $this->get_printed_config( 'code-snippets-manage-js' )['settings']['theme'] );
		$this->assertTrue( wp_style_is( 'code-snippets-editor-theme-monokai' ) );
	}

	/**
	 * The default theme needs no stylesheet of its own.
	 *
	 * @return void
	 */
	public function test_default_theme_loads_no_stylesheet(): void {
		update_setting( 'editor', 'theme', 'default' );

		enqueue_code_editor_theme();

		$this->assertFalse( wp_style_is( 'code-snippets-editor-theme-default' ) );
	}
}
