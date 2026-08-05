<?php

namespace Code_Snippets\Admin\Menus;

use Code_Snippets\Settings\Settings_Fields;
use function Code_Snippets\code_snippets;
use function Code_Snippets\Settings\are_settings_unified;
use function Code_Snippets\Utils\enqueue_code_editor;
use function Code_Snippets\Utils\get_editor_themes;
use const Code_Snippets\PLUGIN_FILE;
use const Code_Snippets\PLUGIN_VERSION;
use const Code_Snippets\Settings\OPTION_GROUP;
use const Code_Snippets\Settings\OPTION_NAME;
use const Code_Snippets\Settings\CACHE_KEY;

/**
 * This class handles the settings admin menu.
 *
 * @package Code_Snippets
 */
class Settings_Menu extends Admin_Menu {

	/**
	 * Settings page name as registered with the Settings API.
	 */
	public const SETTINGS_PAGE = 'code-snippets';

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'settings',
			_x( 'Settings', 'menu label', 'code-snippets' ),
			__( 'Snippets Settings', 'code-snippets' )
		);
	}

	/**
	 * Executed when the admin page is loaded
	 */
	public function load() {
		parent::load();

		if ( is_network_admin() ) {
			if ( are_settings_unified() ) {
				$this->update_network_options();
			} else {
				wp_safe_redirect( code_snippets()->get_menu_url( 'settings', 'admin' ) );
				exit;
			}
		}
	}

	/**
	 * Enqueue the stylesheet for the settings menu
	 */
	public function enqueue_assets() {
		$this->enqueue_codemirror();
		$handle      = 'code-snippets-settings';
		$plugin_dir  = plugin_dir_path( PLUGIN_FILE );
		$css_version = filemtime( $plugin_dir . 'dist/settings.css' );
		$js_version  = filemtime( $plugin_dir . 'dist/settings.js' );
		$css_version = false !== $css_version ? $css_version : PLUGIN_VERSION;
		$js_version  = false !== $js_version ? $js_version : PLUGIN_VERSION;

		wp_enqueue_style(
			$handle,
			plugins_url( 'dist/settings.css', PLUGIN_FILE ),
			self::$style_deps + [ 'code-editor' ],
			$css_version
		);

		wp_enqueue_script(
			$handle,
			plugins_url( 'dist/settings.js', PLUGIN_FILE ),
			self::$script_deps + [ 'code-snippets-code-editor' ],
			$js_version,
			true
		);

		wp_set_script_translations( $handle, 'code-snippets' );
		code_snippets()->localize_script( $handle );

		$this->add_codemirror_settings_script( $handle );

		// Provide configuration and simple i18n for the version switch JS module.
		wp_localize_script(
			$handle,
			'code_snippets_version_switch',
			[
				'ajaxurl'       => admin_url( 'admin-ajax.php' ),
				'nonce_switch'  => wp_create_nonce( 'code_snippets_version_switch' ),
				'nonce_refresh' => wp_create_nonce( 'code_snippets_refresh_versions' ),
			]
		);

		wp_localize_script(
			$handle,
			'__code_snippets_i18n',
			[
				'selectDifferent' => esc_html__( 'Please select a different version to switch to.', 'code-snippets' ),
				'switching'       => esc_html__( 'Switching…', 'code-snippets' ),
				'processing'      => esc_html__( 'Processing version switch. Please wait…', 'code-snippets' ),
				'error'           => esc_html__( 'An error occurred.', 'code-snippets' ),
				'errorSwitch'     => esc_html__( 'An error occurred while switching versions. Please try again.', 'code-snippets' ),
				'refreshing'      => esc_html__( 'Refreshing…', 'code-snippets' ),
				'refreshed'       => esc_html__( 'Refreshed!', 'code-snippets' ),
			]
		);
	}

	/**
	 * Enqueue the CodeMirror scripts and styles, including all themes.
	 *
	 * @return void
	 */
	protected function enqueue_codemirror() {
		enqueue_code_editor( 'php' );
		$themes = get_editor_themes();

		foreach ( $themes as $theme ) {
			wp_enqueue_style(
				'code-snippets-editor-theme-' . $theme,
				plugins_url( "dist/editor-themes/$theme.css", PLUGIN_FILE ),
				[ 'code-editor' ],
				PLUGIN_VERSION
			);
		}
	}

	/**
	 * Load the CodeMirror settings as an inline script variable.
	 *
	 * @param string $handle The handle of the script to which the settings will be added.
	 *
	 * @return void
	 */
	protected function add_codemirror_settings_script( string $handle ) {
		$field_definitions = Settings_Fields::get_field_definitions();
		$editor_fields = [];

		foreach ( $field_definitions['editor'] as $name => $field ) {
			if ( empty( $field['codemirror'] ) ) {
				continue;
			}

			$editor_fields[] = [
				'name'       => $name,
				'type'       => $field['type'],
				'codemirror' => addslashes( $field['codemirror'] ),
			];
		}

		// Pass the saved options to the external JavaScript file.
		$inline_script = 'var code_snippets_editor_settings = ' . wp_json_encode( $editor_fields ) . ';';

		wp_add_inline_script( $handle, $inline_script, 'before' );
	}

	/**
	 * Retrieve the list of settings sections.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_sections(): array {
		global $wp_settings_sections;

		if ( ! isset( $wp_settings_sections[ self::SETTINGS_PAGE ] ) ) {
			return array();
		}

		return (array) $wp_settings_sections[ self::SETTINGS_PAGE ];
	}

	/**
	 * Retrieve the name of the settings section currently being viewed.
	 *
	 * @param string $default_section Name of the default tab displayed.
	 *
	 * @return string
	 */
	public function get_current_section( string $default_section = 'general' ): string {
		$sections = $this->get_sections();

		if ( ! $sections ) {
			return $default_section;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Value is matched to registered sections.
		$active_tab = isset( $_REQUEST['section'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['section'] ) ) : $default_section;
		return isset( $sections[ $active_tab ] ) ? $active_tab : $default_section;
	}

	/**
	 * Render the admin screen
	 */
	public function render() {
		$this->render_navigation();

		$update_url = is_network_admin() ? add_query_arg( 'update_site_option', true ) : admin_url( 'options.php' );
		$current_section = $this->get_current_section();

		?>
		<div class="wrap code-snippets-settings" data-active-tab="<?php echo esc_attr( $current_section ); ?>">
			<?php $this->render_section_tabs(); ?>

			<div class="snippets-page-header">
				<h1><?php esc_html_e( 'Snippets Settings', 'code-snippets' ); ?></h1>
			</div>

			<?php
			if ( code_snippets()->is_compact_menu() ) {
				$actions = [
					_x( 'Manage', 'snippets', 'code-snippets' ) => code_snippets()->get_menu_url(),
					_x( 'Add New', 'snippet', 'code-snippets' ) => code_snippets()->get_menu_url( 'add' ),
					_x( 'Import', 'snippets', 'code-snippets' ) => code_snippets()->get_menu_url( 'import' ),
				];

				echo '<p class="settings-page-actions">';

				foreach ( $actions as $label => $url ) {
					printf(
						'<a href="%s" class="page-title-action">%s</a>',
						esc_url( $url ),
						esc_html( $label )
					);
				}

				echo '</p>';
			}
			?>

			<hr class="wp-header-end" />

			<?php settings_errors( OPTION_NAME ); ?>

			<form action="<?php echo esc_url( $update_url ); ?>" method="post">
				<input type="hidden" name="section" value="<?php echo esc_attr( $current_section ); ?>">
				<?php

				settings_fields( OPTION_GROUP );
				$this->render_settings_sections();
				?>
				<p class="submit">
					<?php
					submit_button( null, 'primary', 'submit', false );

					submit_button(
						__( 'Reset to Default', 'code-snippets' ),
						'secondary',
						sprintf( '%s[%s]', OPTION_NAME, 'reset_settings' ),
						false
					);
					?>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Output the settings section subnavigation bar, styled to match the
	 * subnavigation on the snippet management pages, followed by the slot
	 * that adopts the Screen Options and Help tabs.
	 */
	protected function render_section_tabs() {
		$sections = $this->get_sections();
		$active_tab = $this->get_current_section();

		echo '<nav class="snippet-type-nav settings-type-nav" id="settings-sections-tabs" aria-label="' . esc_attr__( 'Settings tabs', 'code-snippets' ) . '"><ul>';

		foreach ( $sections as $section ) {
			printf(
				'<li><a class="snippet-type-link%s" data-section="%s" href="%s"><span>%s</span></a></li>',
				esc_attr( $active_tab ) === $section['id'] ? ' active-type' : '',
				esc_attr( $section['id'] ),
				esc_url( add_query_arg( 'section', $section['id'] ) ),
				esc_html( $section['title'] )
			);
		}

		echo '</ul></nav>';
		echo '<div id="snippets-screen-meta-slot" class="snippets-screen-meta-slot"></div>';
	}

	/**
	 * Output snippet settings sections.
	 */
	protected function render_settings_sections() {
		$sections = $this->get_sections();

		foreach ( $sections as $section ) {
			if ( 'license' === $section['id'] ) {
				continue;
			}

			if ( $section['title'] ) {
				printf(
					'<h3 id="%s-settings" class="settings-section-title">%s</h3>' . "\n",
					esc_attr( $section['id'] ),
					esc_html( $section['title'] )
				);
			}

			if ( $section['callback'] ) {
				call_user_func( $section['callback'], $section );
			}

			printf( '<div class="settings-section %s-settings"><table class="form-table">', esc_attr( $section['id'] ) );

			do_settings_fields( self::SETTINGS_PAGE, $section['id'] );
			echo '</table></div>';
		}
	}

	/**
	 * Fill in for the Settings API in the Network Admin
	 */
	public function update_network_options() {

		// Ensure the settings have been saved.
		if ( empty( $_GET['update_site_option'] ) || empty( $_POST[ OPTION_NAME ] ) ) {
			return;
		}

		check_admin_referer( 'code-snippets-options' );

		// Retrieve the saved options and save them to the database.
		$value = map_deep( wp_unslash( $_POST[ OPTION_NAME ] ), 'sanitize_key' );
		update_site_option( OPTION_NAME, $value );
		wp_cache_delete( CACHE_KEY );

		// Add an updated notice.
		if ( ! count( get_settings_errors() ) ) {
			add_settings_error( 'general', 'settings_updated', __( 'Settings saved.', 'code-snippets' ), 'updated' );
		}

		set_transient( 'settings_errors', get_settings_errors(), 30 );

		// Redirect back to the settings menu.
		$redirect = add_query_arg( 'settings-updated', 'true', remove_query_arg( 'update_site_option', wp_get_referer() ) );
		wp_safe_redirect( esc_url_raw( $redirect ) );
		exit;
	}
}
