<?php

namespace Code_Snippets;

use Freemius;
use Freemius_Exception;
use function fs_dynamic_init;

/**
 * Handles interfacing with the Freemius SDK and API.
 *
 * @package Code_Snippets
 */
class Licensing {

	/**
	 * Freemius product ID.
	 */
	private const PRODUCT_ID = 10565;

	/**
	 * Freemius public key.
	 */
	private const PUBLIC_KEY = 'pk_107ff34fc0b2a9700c150c1acf13a';

	/**
	 * Freemius SDK instance.
	 *
	 * @var Freemius
	 */
	private Freemius $sdk;

	/**
	 * Class constructor.
	 *
	 * @throws Freemius_Exception Freemius fails to initialise.
	 */
	public function __construct() {
		$this->enable_multisite_support();

		require_once dirname( CODE_SNIPPETS_FILE ) . '/vendor/freemius/wordpress-sdk/start.php';

		/**
		 * Check for constant defined in wp-config.php.
		 *
		 * @noinspection PhpUndefinedConstantInspection
		 */
		$secret_key = defined( 'CODE_SNIPPETS_SECRET_KEY' ) ? CODE_SNIPPETS_SECRET_KEY : null;

		$this->sdk = fs_dynamic_init(
			array(
				'id'                  => self::PRODUCT_ID,
				'slug'                => 'code-snippets',
				'premium_slug'        => 'code-snippets-pro',
				'type'                => 'plugin',
				'public_key'          => self::PUBLIC_KEY,
				'is_premium'          => true,
				'is_premium_only'     => true,
				'premium_suffix'      => 'Pro',
				'has_premium_version' => true,
				'has_addons'          => false,
				'has_paid_plans'      => true,
				'is_org_compliant'    => true,
				'has_affiliation'     => 'selected',
				'secret_key'          => $secret_key,
				'menu'                => array(
					'slug'        => code_snippets()->get_menu_slug(),
					'contact'     => false,
					'support'     => false,
					'pricing'     => false,
					'affiliation' => false,
					'network'     => true,
				),
			)
		);

		do_action( 'freemius_loaded' );
		$this->register_hooks();

		add_action( 'init', [ $this, 'override_strings' ] );
	}

	/**
	 * Create the necessary constant to enable multisite support within the Freemius SDK.
	 *
	 * @return void
	 */
	private function enable_multisite_support() {
		$constant_name = sprintf( 'WP_FS__PRODUCT_%d_MULTISITE', self::PRODUCT_ID );

		if ( ! defined( $constant_name ) ) {
			define( $constant_name, true );
		}
	}

	/**
	 * Determine whether the current site has an active license.
	 *
	 * @return bool
	 */
	public function is_licensed(): bool {
		return $this->sdk->can_use_premium_code();
	}

	/**
	 * Determine whether the current site has any license, including an expired license.
	 *
	 * @return bool
	 */
	public function was_licensed(): bool {
		return $this->sdk->has_any_license();
	}

	/**
	 * Retrieve the current license secret key.
	 *
	 * @return string|null
	 */
	public function get_license_key(): ?string {
		$license = $this->sdk->_get_license();
		return $license ? $license->secret_key : null;
	}

	/**
	 * Activate a license key.
	 *
	 * @param string $license_key The license key to activate.
	 * @param array  $options     Activation options (network, marketing, etc.).
	 * @return array Activation result with 'success' boolean and 'message' string.
	 */
	public function activate_license_key( string $license_key, array $options = [] ): array {
		$license_key = trim( $license_key );
		if ( empty( $license_key ) ) {
			return [
				'success' => false,
				'message' => 'License key cannot be empty.'
			];
		}

		$fs = $this->sdk;

		if ( $fs->can_use_premium_code() ) {
			return [
				'success' => false,
				'message' => 'Plugin is already licensed. Use this command to change the license key.',
				'type' => 'warning'
			];
		}

		$is_network = $options['network'] ?? false;
		$is_marketing_allowed = $options['marketing'] ?? false;

		$sites = $this->prepare_network_sites( $is_network );

		$result = $fs->opt_in(
			false, // email (will use current user)
			false, // first name
			false, // last name
			$license_key, // license key
			false, // is_uninstall
			false, // trial_plan_id
			false, // is_disconnected
			$is_marketing_allowed, // marketing allowed
			$sites, // sites for network activation
			false // redirect (false for CLI)
		);

		if ( is_object( $result ) && isset( $result->error ) ) {
			$error_message = $result->error->message ?? 'Unknown error occurred.';
			return [
				'success' => false,
				'message' => 'License activation failed: ' . $error_message
			];
		} elseif ( $result === false ) {
			return [
				'success' => false,
				'message' => 'License activation failed: Invalid response from server.'
			];
		} else {
			return [
				'success' => true,
				'message' => 'License activated successfully.'
			];
		}
	}

	/**
	 * Get detailed license status information.
	 *
	 * @return array License status information.
	 */
	public function get_license_status(): array {
		$fs = $this->sdk;
		$license = $this->get_license_object();

		$status_info = [
			'is_licensed' => $fs->can_use_premium_code() ? 'Yes' : 'No',
			'license_key' => 'Not available',
			'is_expired'  => 'N/A',
			'expires'     => 'N/A',
			'activations' => 'N/A',
		];

		if ( is_object( $license ) ) {
			$status_info = $this->format_license_status( $license );
		}

		return $status_info;
	}

	/**
	 * Register hooks with Freemius.
	 *
	 * @return void
	 */
	public function register_hooks() {
		$this->sdk->add_action( 'after_uninstall', [ $this, 'uninstall_hook' ] );
		$this->sdk->add_filter( 'is_submenu_visible', [ $this, 'is_submenu_visible' ], 10, 2 );
		$this->sdk->add_filter( 'plugin_icon', [ $this, 'plugin_icon' ] );
	}

	/**
	 * Get the relative path to the plugin icon.
	 *
	 * @return string
	 */
	public function plugin_icon(): string {
		return dirname( CODE_SNIPPETS_FILE ) . '/assets/icon.svg';
	}

	/**
	 * Control whether a Freemius submenu is visible.
	 *
	 * @param bool   $is_visible Whether the submenu is visible.
	 * @param string $submenu_id Submenu ID.
	 *
	 * @return bool
	 */
	public function is_submenu_visible( bool $is_visible, string $submenu_id ): bool {
		return 'account' === $submenu_id ? $is_visible : false;
	}

	/**
	 * Clean up data when the plugin is uninstalled.
	 *
	 * @return void
	 */
	public function uninstall_hook() {
		require_once __DIR__ . '/uninstall.php';
		Uninstall\uninstall_plugin();
	}

	/**
	 * Override default strings used by Freemius to better integrate it with the rest of the plugin.
	 *
	 * @return void
	 */
	public function override_strings() {
		$this->sdk->override_i18n(
			array(
				'yee-haw'  => __( 'Success', 'code-snippets' ),
				'oops'     => __( 'Notice', 'code-snippets' ),
				'woot'     => __( 'Success', 'code-snippets' ),
				'right-on' => __( 'Thanks', 'code-snippets' ),
				'ok'       => __( 'Okay', 'code-snippets' ),
			)
		);

		$this->sdk->add_filter(
			'connect_message_on_update',
			function ( $message, $user_first_name, $product_title, $user_login, $site_link, $freemius_link ) {
				/* translators: 1: site url, 2: Freemius link */
				$text = __( 'Please help us improve Code Snippets! If you opt-in, some data about your usage of %1$s will be sent to %2$s. If you skip this, that\'s okay, Code Snippets will still work just fine.', 'code-snippets' );
				return sprintf( $text, $site_link, $freemius_link );
			},
			10,
			6
		);

		$this->sdk->add_filter( 'show_affiliate_program_notice', '__return_false' );
	}

	/**
	 * Prepare sites array for network activation.
	 *
	 * @param bool $is_network Whether this is a network activation.
	 * @return array Array of site information for network activation.
	 */
	private function prepare_network_sites( bool $is_network ): array {
		if ( ! $is_network || ! is_multisite() ) {
			return [];
		}

		$sites = [];
		$all_sites = get_sites();

		foreach ( $all_sites as $site ) {
			$sites[] = [
				'blog_id' => $site->blog_id,
				'url'     => $site->siteurl,
				'name'    => $site->blogname,
			];
		}

		return $sites;
	}

	/**
	 * Format license status for display.
	 *
	 * @param object $license Freemius license object.
	 * @return array Formatted status information.
	 */
	private function format_license_status( object $license ): array {
		$status_info = [
			'is_licensed' => 'Yes',
			'license_key' => 'Not available',
			'is_expired'  => 'N/A',
			'expires'     => 'N/A',
			'activations' => 'N/A',
		];

		$status_info['license_key'] = $this->get_masked_license_key( $license );

		$status_info['is_expired'] = $license->is_expired() ? 'Yes' : 'No';

		if ( $license->is_lifetime() ) {
			$status_info['expires'] = 'Never';
		} elseif ( isset( $license->expiration ) ) {
			$status_info['expires'] = $license->expiration;
		}

		if ( isset( $license->activated ) ) {
			if ( $license->is_unlimited() ) {
				$status_info['activations'] = $license->activated . '/∞';
			} elseif ( isset( $license->quota ) ) {
				$status_info['activations'] = $license->activated . '/' . $license->quota;
			} else {
				$status_info['activations'] = $license->activated;
			}
		}

		return $status_info;
	}

	/**
	 * Get masked license key for CLI display.
	 *
	 * @param object $license Freemius license object.
	 * @return string Masked license key.
	 */
	private function get_masked_license_key( object $license ): string {
		if ( method_exists( $license, 'get_masked_secret_key' ) ) {
			return $license->get_masked_secret_key();
		}

		return 'Not available';
	}

	/**
	 * Get license object using the first available Freemius method.
	 *
	 * @return object|null License object or null if not available.
	 */
	private function get_license_object(): ?object {
		$fs = $this->sdk;

		$methods = ['_get_license', 'get_license', 'get_user_license'];

		foreach ( $methods as $method ) {
			if ( method_exists( $fs, $method ) ) {
				$result = $fs->$method();
				if ( $result ) {
					return $result;
				}
			}
		}

		return null;
	}
}
