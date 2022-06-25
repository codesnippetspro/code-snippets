<?php

namespace Code_Snippets;

use Freemius;
use Freemius_Exception;
use function fs_dynamic_init;

class Licensing {

	/**
	 * Freemius product ID.
	 */
	const PRODUCT_ID = 10565;

	/**
	 * Freemius public key.
	 */
	const PUBLIC_KEY = 'pk_107ff34fc0b2a9700c150c1acf13a';

	/**
	 * Freemius SDK instance.
	 *
	 * @var Freemius
	 */
	public $sdk;

	/**
	 * Class constructor.
	 *
	 * @throws Freemius_Exception
	 */
	public function __construct() {
		$plugin = code_snippets();
		$this->enable_multisite_support();

		require_once dirname( CODE_SNIPPETS_FILE ) . '/vendor/freemius/wordpress-sdk/start.php';

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
				'has_affiliation'     => 'customers',
				'secret_key'          => defined( 'CODE_SNIPPETS_SECRET_KEY' ) ? CODE_SNIPPETS_SECRET_KEY : null,
				'menu'                => array(
					'slug'        => $plugin->get_menu_slug(),
					'contact'     => false,
					'support'     => false,
					'affiliation' => false,
					'network'     => true,
				),
			)
		);

		do_action( 'freemius_loaded' );
		$this->add_filters();
		$this->override_strings();
	}

	private function enable_multisite_support() {
		$constant_name = sprintf( "WP_FS__PRODUCT_%d_MULTISITE", self::PRODUCT_ID );

		if ( ! defined( $constant_name ) ) {
			define( $constant_name, true );
		}
	}

	public function is_licensed() {
		return $this->sdk->can_use_premium_code();
	}

	public function was_licensed() {
		return $this->sdk->has_any_license();
	}

	public function add_filters() {
		$this->sdk->add_filter( 'connect_message_on_update', [ $this, 'connect_message_on_update' ], 10, 6 );
	}

	public function override_strings() {
		$this->sdk->override_i18n(
			array(
				'yee-haw' => __( 'Success', 'code-snippets' ),
			)
		);
	}

	function connect_message_on_update( $message, $user_first_name, $product_title, $user_login, $site_link, $freemius_link ) {
		$text = __( 'Please help us improve Code Snippets! If you opt-in, some data about your usage of Code Snippets will be sent to %5$s. If you skip this, that\'s okay, Code Snippets will still work just fine.', 'code-snippets' );

		return sprintf(
			$text,
			"<strong>$product_title</strong>",
			"<strong>$user_login</strong>",
			$site_link,
			$freemius_link
		);
	}

}
