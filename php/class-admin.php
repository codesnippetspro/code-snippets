<?php

namespace Code_Snippets;

/**
 * Functions specific to the administration interface
 *
 * @package Code_Snippets
 */
class Admin {

	/**
	 * Admin_Menu class instances
	 *
	 * @var array
	 */
	public $menus = array();

	/**
	 * Class constructor
	 */
	public function __construct() {
		if ( is_admin() ) {
			$this->run();
		}
	}

	/**
	 * Initialise classes
	 */
	public function load_classes() {
		$this->menus['manage'] = new Manage_Menu();
		$this->menus['edit'] = new Edit_Menu();
		$this->menus['import'] = new Import_Menu();

		if ( is_network_admin() === Settings\are_settings_unified() ) {
			$this->menus['settings'] = new Settings_Menu();
		}

		foreach ( $this->menus as $menu ) {
			$menu->run();
		}
	}

	/**
	 * Register action and filter hooks
	 */
	public function run() {
		add_action( 'init', array( $this, 'load_classes' ), 11 );

		add_filter( 'mu_menu_items', array( $this, 'mu_menu_items' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( PLUGIN_FILE ), array( $this, 'plugin_settings_link' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_meta_links' ), 10, 2 );
		add_filter( 'debug_information', array( $this, 'debug_information' ) );
		add_action( 'code_snippets/admin/manage', array( $this, 'survey_message' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_menu_icon' ) );
		add_action( 'admin_notices', array( $this, 'license_warning_notice' ) );
		add_action( 'network_admin_notices', array( $this, 'license_warning_notice' ) );

		if ( isset( $_POST['save_snippet'] ) && $_POST['save_snippet'] ) {
			add_action( 'code_snippets/allow_execute_snippet', array( $this, 'prevent_exec_on_save' ), 10, 3 );
		}
	}

	/**
	 * Allow super admins to control site admin access to
	 * snippet admin menus
	 *
	 * Adds a checkbox to the *Settings > Network Settings*
	 * network admin menu
	 *
	 * @param array $menu_items Current mu menu items.
	 *
	 * @return array Modified mu menu items.
	 * @since 1.7.1
	 *
	 */
	public function mu_menu_items( $menu_items ) {
		$menu_items['snippets'] = __( 'Snippets', 'code-snippets' );
		$menu_items['snippets_settings'] = __( 'Snippets &raquo; Settings', 'code-snippets' );

		return $menu_items;
	}

	/**
	 * Load the stylesheet for the admin menu icon
	 */
	public function load_admin_menu_icon() {
		wp_enqueue_style(
			'menu-icon-snippets',
			plugins_url( 'css/min/menu-icon.css', code_snippets()->file ),
			array(), code_snippets()->version
		);
	}

	/**
	 * Prevent the snippet currently being saved from being executed
	 * so that it is not run twice (once normally, once when validated)
	 *
	 * @param bool   $exec       Whether the snippet will be executed.
	 * @param int    $exec_id    ID of the snippet being executed.
	 * @param string $table_name Name of the database table the snippet is stored in.
	 *
	 * @return bool Whether the snippet will be executed.
	 */
	public function prevent_exec_on_save( $exec, $exec_id, $table_name ) {

		if ( ! isset( $_POST['save_snippet'], $_POST['snippet_id'] ) ) {
			return $exec;
		}

		if ( code_snippets()->db->get_table_name() !== $table_name ) {
			return $exec;
		}

		$id = intval( $_POST['snippet_id'] );

		if ( $id === $exec_id ) {
			return false;
		}

		return $exec;
	}

	/**
	 * Adds a link pointing to the Manage Snippets page
	 *
	 * @param array $links Existing plugin action links.
	 *
	 * @return array Modified plugin action links
	 * @since 2.0.0
	 *
	 */
	public function plugin_settings_link( $links ) {
		$format = '<a href="%1$s" title="%2$s">%3$s</a>';

		array_unshift( $links, sprintf(
			$format,
			code_snippets()->get_menu_url( 'settings' ),
			__( 'Change plugin settings', 'code-snippets' ),
			__( 'Settings', 'code-snippets' )
		) );

		array_unshift( $links, sprintf(
			$format,
			code_snippets()->get_menu_url(),
			__( 'Manage your existing snippets', 'code-snippets' ),
			__( 'Snippets', 'code-snippets' )
		) );

		return $links;
	}

	/**
	 * Adds extra links related to the plugin
	 *
	 * @param array  $links Existing plugin info links.
	 * @param string $file  The plugin the links are for.
	 *
	 * @return array The modified plugin info links.
	 * @since 2.0.0
	 *
	 */
	public function plugin_meta_links( $links, $file ) {

		/* We only want to affect the Code Snippets plugin listing */
		if ( plugin_basename( PLUGIN_FILE ) !== $file ) {
			return $links;
		}

		$format = '<a href="%1$s" title="%2$s" target="_blank">%3$s</a>';

		/* array_merge appends the links to the end */

		return array_merge( $links, array(
			sprintf( $format,
				'https://codesnippets.pro/about/',
				__( 'Find out more about Code Snippets', 'code-snippets' ),
				__( 'About', 'code-snippets' )
			),
			sprintf( $format,
				'https://codesnippets.pro/support/',
				__( 'Find out how to get support with Code Snippets', 'code-snippets' ),
				__( 'Support', 'code-snippets' )
			),
		) );
	}

	/**
	 * Add a warning message to admin pages while there is not a valid license.
	 */
	public function license_warning_notice() {
		$dismiss_key = 'dismiss_code_snippets_license_notice';
		$status = code_snippets()->licensing->license;
		$expiry = strtotime( code_snippets()->licensing->expires );

		// only display a message if the license is not valid and not close to expiring.
		if ( 'valid' === $status && ( ! $expiry || ! ( time() + DAY_IN_SECONDS * 14 > $expiry ) ) ) {
			return;
		}

		// if the notice has been dismissed, then stop here.
		if ( get_transient( $dismiss_key ) === $status ) {
			return;
		}

		// check if we have just dismissed a notice.
		if ( isset( $_GET[ $dismiss_key ] ) ) {
			check_admin_referer( $dismiss_key );
			set_transient( $dismiss_key, $status, MONTH_IN_SECONDS );
			return;
		}

		// output the notice.
		echo '<div class="notice notice-warning is-dismissible code-snippets-license-warning"><p>';
		$button = esc_html__( 'Update License', 'code-snippets' );

		// if the license is valid, then show an 'expiring soon' warning.
		if ( 'valid' === $status || 'expired' === $status ) {
			$days_left = round( ( $expiry - time() ) / DAY_IN_SECONDS );

			if ( 'valid' === $status && $days_left > 0 ) {
				/* translators: %d: number of days */
				$text = _n( 'Your Code Snippets Pro license will expire in %d day. ', 'Your Code Snippets Pro license will expire in %d days. ', $days_left, 'code-snippets' );
				echo esc_html( sprintf( $text, $days_left ) );
			} else {
				esc_html_e( 'Your Code Snippets Pro license has expired. ', 'code-snippets' );
			}

		} else {
			esc_html_e( 'This site is missing a valid Code Snippets Pro license. ', 'code-snippets' );
			$button = esc_html__( 'Add License', 'code-snippets' );
		}

		esc_html_e( 'Pro features will not function without an active license key. ', 'code-snippets' );

		// show a button to go to license settings if the current user has access.
		if ( current_user_can( is_multisite() ? code_snippets()->get_network_cap_name() : code_snippets()->get_cap_name() ) ) {
			$settings_url = code_snippets()->get_menu_url( 'settings', 'network' );

			printf(
				'<a href="%s" class="button button-secondary button-small">%s</a>',
				esc_url( add_query_arg( 'section', 'license', $settings_url ) ),
				$button
			);
		}

		printf(
			'<a class="notice-dismiss" href="%s" style="text-decoration: none;">' .
			'<span class="screen-reader-text">%s</span></a></p></div>',
			esc_url( wp_nonce_url( add_query_arg( $dismiss_key, true ), $dismiss_key ) ),
			esc_html__( 'Dismiss', 'code-snippets' )
		);
	}

	/**
	 * Add Code Snippets information to Site Health information.
	 *
	 * @param array $info The Site Health information.
	 *
	 * @return array The updated Site Health information.
	 * @author sc0ttkclark
	 *
	 */
	public function debug_information( $info ) {
		$fields = array();

		// fetch all active snippets.
		$args = array( 'active_only' => true, 'limit' => 100 );
		$snippet_objects = get_snippets( array(), null, $args );

		// build the debug information from snippet data.
		foreach ( $snippet_objects as $snippet ) {
			$values = [ $snippet->scope_name ];
			$debug = [];

			if ( $snippet->name ) {
				$debug[] = 'name: ' . $snippet->name;
			}

			$debug[] = 'scope: ' . $snippet->scope;

			if ( $snippet->modified ) {
				/* translators: %s: formatted last modified date */
				$values[] = sprintf( __( 'Last modified %s', 'code-snippets' ), $snippet->format_modified( false ) );
				$debug[] = 'modified: ' . $snippet->modified;
			}

			if ( $snippet->tags ) {
				$values[] = $snippet->tags_list;
				$debug[] = 'tags: [' . $snippet->tags_list . ']';
			}

			$fields[ 'snippet-' . $snippet->id ] = [
				'label' => $snippet->display_name,
				'value' => implode( "\n | ", $values ),
				'debug' => implode( ', ', $debug ),
			];
		}

		$snippets_info = array(
			'label'      => __( 'Active Snippets', 'code-snippets' ),
			'show_count' => true,
			'fields'     => $fields,
		);

		// attempt to insert the new section right after the Inactive Plugins section.
		$index = array_search( 'wp-plugins-inactive', array_keys( $info ), true );

		if ( false === $index ) {
			$info['code-snippets'] = $snippets_info;
		} else {
			$info = array_merge(
				array_slice( $info, 0, $index + 1 ),
				[ 'code-snippets' => $snippets_info ],
				array_slice( $info, $index + 1 )
			);
		}

		return $info;
	}

	/**
	 * Print a notice inviting people to participate in the Code Snippets Survey
	 *
	 * @return void
	 * @since  1.9
	 */
	public function survey_message() {
		global $current_user;

		$key = 'ignore_code_snippets_survey_message';

		/* Bail now if the user has dismissed the message */
		if ( get_user_meta( $current_user->ID, $key ) ) {
			return;
		} elseif ( isset( $_GET[ $key ], $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], $key ) ) {
			add_user_meta( $current_user->ID, $key, true, true );

			return;
		}

		?>

		<br />

		<div class="updated code-snippets-survey-message">
			<p>

				<?php echo wp_kses( __( "<strong>Have feedback on Code Snippets?</strong> Please take the time to answer a short survey on how you use this plugin and what you'd like to see changed or added in the future.", 'code-snippets' ), [ 'strong' => [] ] ); ?>

				<a href="https://codesnippets.pro/survey/" class="button secondary"
				   target="_blank" style="margin: auto .5em;">
					<?php esc_html_e( 'Take the survey now', 'code-snippets' ); ?>
				</a>

				<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( $key, true ), $key ) ); ?>">
					<?php esc_html_e( 'Dismiss', 'code-snippets' ); ?>
				</a>

			</p>
		</div>

		<?php
	}
}
