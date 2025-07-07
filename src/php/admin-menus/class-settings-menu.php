<?php

namespace Code_Snippets;

use const Code_Snippets\Settings\CACHE_KEY;
use const Code_Snippets\Settings\OPTION_GROUP;
use const Code_Snippets\Settings\OPTION_NAME;

/**
 * This class handles the settings admin menu
 *
 * @since   2.4.0
 * @package Code_Snippets
 */
class Settings_Menu extends Admin_Menu {

	/**
	 * Settings page name as registered with the Settings API.
	 */
	const SETTINGS_PAGE = 'code-snippets';

	/**
	 * Constructor
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

		if ( isset( $_GET['confirm-authorise-cloud'], $_GET['code'], $_GET['state'] ) ) {
			$auth_code = sanitize_text_field( wp_unslash( $_GET['code'] ) );
			$state = sanitize_text_field( wp_unslash( $_GET['state'] ) );

			$result = code_snippets()->cloud_api->decode_auth_code( $state, $auth_code );

			wp_safe_redirect(
				add_query_arg(
					'connect_cloud_result',
					is_null( $result ) ? 'ok' : 'fail',
					code_snippets()->get_menu_url( 'manage' )
				)
			);
			exit;
		}

		if ( isset( $_GET['connect-authorise-cloud'], $_GET['_wpnonce'] ) ) {
			$api = code_snippets()->cloud_api;

			if ( $api->verify_action_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) ) ) {
				$api->init_cloud_connection();
			} else {
				wp_die( '', 401 );
			}
		}

		if ( isset( $_GET['reset-cloud'], $_GET['_wpnonce'] ) ) {
			$api = code_snippets()->cloud_api;

			if ( $api->verify_action_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) ) ) {
				code_snippets()->cloud_api->remove_sync();

				add_settings_error(
					OPTION_NAME,
					'snippets_cloud_sync_disconnected',
					__( 'This site has been successfully disconnected from Code Snippets Cloud.', 'code-snippets' ),
					'updated'
				);
			} else {
				wp_die( '', 401 );
			}
		}

		if ( is_network_admin() ) {
			if ( Settings\are_settings_unified() ) {
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
		$plugin = code_snippets();

		Settings\enqueue_editor_preview_assets();

		wp_enqueue_style(
			'code-snippets-settings',
			plugins_url( 'dist/settings.css', $plugin->file ),
			[ 'code-editor' ],
			$plugin->version
		);
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

		$active_tab = isset( $_REQUEST['section'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['section'] ) ) : $default_section;
		return isset( $sections[ $active_tab ] ) ? $active_tab : $default_section;
	}

	/**
	 * Render the admin screen
	 */
	public function render() {
		$update_url = is_network_admin() ? add_query_arg( 'update_site_option', true ) : admin_url( 'options.php' );
		$current_section = $this->get_current_section();

		?>
		<div class="code-snippets-settings wrap" data-active-tab="<?php echo esc_attr( $current_section ); ?>">
			<h1>
				<?php
				esc_html_e( 'Settings', 'code-snippets' );

				if ( code_snippets()->is_compact_menu() ) {
					$actions = [
						_x( 'Manage', 'snippets', 'code-snippets' ) => code_snippets()->get_menu_url(),
						_x( 'Add New', 'snippet', 'code-snippets' ) => code_snippets()->get_menu_url( 'add' ),
						_X( 'Import', 'snippets', 'code-snippets' ) => code_snippets()->get_menu_url( 'import' ),
					];

					foreach ( $actions as $label => $url ) {
						printf(
							'<a href="%s" class="page-title-action">%s</a>',
							esc_url( $url ),
							esc_html( $label )
						);
					}
				}
				?>
			</h1>

			<?php

			if ( isset( $_GET['connect_cloud_result'] ) ) {
				$success = 'ok' === sanitize_key( wp_unslash( $_GET['connect_cloud_result'] ) );
				printf( '<div class="notice notice-%s settings-error is-dismissible"><p><strong>', $success ? 'success' : 'error' );

				echo $success ?
					esc_html__( 'Successfully connected this site to Code Snippets Cloud.', 'code-snippets' ) :
					esc_html__( 'Failed to connect this site to Code Snippets Cloud. Please try again.', 'code-snippets' );

				echo '</strong></p></div>';
				$_SERVER['REQUEST_URI'] = remove_query_arg( 'connect_cloud_result' );
			}
			?>


			<?php settings_errors( OPTION_NAME ); ?>

			<form action="<?php echo esc_url( $update_url ); ?>" method="post">
				<input type="hidden" name="section" value="<?php echo esc_attr( $current_section ); ?>">
				<?php

				settings_fields( OPTION_GROUP );
				$this->do_settings_tabs();
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
	 * Output snippet settings in tabs
	 */
	protected function do_settings_tabs() {
		$sections = $this->get_sections();
		$active_tab = $this->get_current_section();

		echo '<h2 class="nav-tab-wrapper" id="settings-sections-tabs">';

		foreach ( $sections as $section ) {
			printf(
				'<a class="nav-tab%s" data-section="%s" href="%s">%s</a>',
				esc_attr( $active_tab ) === $section['id'] ? ' nav-tab-active' : '',
				esc_attr( $section['id'] ),
				esc_url( add_query_arg( 'section', $section['id'] ) ),
				esc_html( $section['title'] )
			);
		}

		echo '</h2>';

		foreach ( $sections as $section ) {
			if ( 'license' === $section['id'] ) {
				continue;
			}

			if ( $section['title'] ) {
				printf(
					'<h2 id="%s-settings" class="settings-section-title">%s</h2>' . "\n",
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

	/**
	 * Empty implementation for print_messages.
	 *
	 * @return void
	 */
	protected function print_messages() {
		// none required.
	}
}
