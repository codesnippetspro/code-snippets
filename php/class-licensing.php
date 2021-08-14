<?php

namespace Code_Snippets;

use WP_Error;

/**
 * Handles license activation and automatic updates.
 * @package Code_Snippets
 * @property      string $key              License key
 * @property-read string $license          License status.
 * @property-read int    $license_limit    Number of times this license can be activated.
 * @property-read int    $site_count       Number of sites this license is active on.
 * @property-read int    $activations_left Number of times this license can again be activated.
 * @property-read string expires           License expiry date and time.
 * @property-read string $customer_name    Name of license holder.
 * @property-read string $customer_email   Email address of license holder.
 * @property-read int    $price_id         ID of pricing plan the license belongs to.
 * @property-read string $error            Error encountered when contacting the server, if applicable.
 */
class Licensing {

	/**
	 * URL to Easy Digital Downloads store.
	 */
	const EDD_STORE_URL = 'https://codesnippets.pro';

	/**
	 * The download name for the product in Easy Digital Downloads.
	 */
	const EDD_ITEM_NAME = 'Code Snippets Pro';

	/**
	 * Option name for storing license data.
	 */
	const OPTION_NAME = 'code_snippets_license';

	/**
	 * Current license information, including key and status.
	 * @var array
	 */
	private $data;

	/**
	 * Class constructor.
	 */
	public function __construct() {

		$this->data = array_fill_keys( [
			'key',
			'license',
			'license_limit',
			'site_count',
			'activations_left',
			'expires',
			'customer_name',
			'customer_email',
			'price_id',
			'error',
		], null );

		add_action( 'admin_init', [ $this, 'init' ], 1 );
		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'update_status' ] );
	}

	/**
	 * Retrieve a piece of license information.
	 *
	 * @param string $prop Information to retrieve
	 *
	 * @return mixed
	 */
	public function __get( $prop ) {
		return $this->data[ $prop ];
	}

	/**
	 * Set the license key.
	 *
	 * @param string $prop  Property name. Must be 'key'.
	 * @param string $value New license key.
	 *
	 */
	public function __set( $prop, $value ) {
		if ( 'key' !== $prop ) {
			trigger_error( 'Cannot override license property' . $prop, E_USER_ERROR );
		}

		$this->data['key'] = $value;
	}

	/**
	 * Update the license data with new values.
	 *
	 * @param array|object $data List of new values.
	 */
	protected function set_data( $data ) {

		// if the data is an object, convert it into an array.
		if ( is_object( $data ) ) {
			$data = get_object_vars( $data );
		}

		// if the data is not valid, then stop now.
		if ( ! $data || ! is_array( $data ) ) {
			return;
		}

		// loop through possible properties, updating with new values.
		foreach ( array_keys( $this->data ) as $prop ) {
			if ( isset( $data[ $prop ] ) ) {
				$this->data[ $prop ] = $data[ $prop ];
			}
		}
	}

	/**
	 * Retrieve all license data.
	 *
	 * @return array
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Initialise the plugin updater.
	 */
	public function init() {
		$plugin = code_snippets();

		// fetch the license information from the database
		$stored_data = get_option( self::OPTION_NAME );
		$this->set_data( $stored_data );

		// set up the updater
		if ( $this->key ) {
			new EDD_SL_Plugin_Updater( self::EDD_STORE_URL, $plugin->file, [
				'version'   => $plugin->version,
				'license'   => $this->key,
				'item_name' => self::EDD_ITEM_NAME,
				'author'    => __( 'Code Snippets Pro', 'code-snippets' ),
				'beta'      => false,
			] );
		}
	}


	/**
	 * Determine whether this site currently has a valid license.
	 *
	 * @return bool
	 */
	public function is_licensed() {
		return 'valid' === $this->license;
	}

	/**
	 * Determine whether this site has (or had) a valid license.
	 *
	 * @return bool
	 */
	public function was_licensed() {
		return in_array( $this->license, [ 'valid', 'expired', 'disabled', 'inactive' ] );
	}

	/**
	 * Send a request to the remote licensing server.
	 *
	 * @param string $action Action to perform.
	 *
	 * @return array|WP_Error Request response.
	 */
	private function do_remote_request( $action ) {
		$api_params = array(
			'edd_action' => $action,
			'license'    => $this->key,
			'item_name'  => urlencode( self::EDD_ITEM_NAME ),
			'url'        => home_url(),
		);

		return wp_remote_post( self::EDD_STORE_URL, [ 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ] );
	}

	/**
	 * Extract license data from a request
	 *
	 * @param array|WP_Error $response Retrieved response data.
	 *
	 * @return object|false Extracted data on success, false on failure.
	 */
	protected static function retrieve_request_data( $response ) {
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		return json_decode( wp_remote_retrieve_body( $response ) );
	}

	/**
	 * Attempt to activate the current license key.
	 *
	 * @return string Error message if failure, empty sting on success.
	 */
	public function activate_license() {
		$response = $this->do_remote_request( 'activate_license' );
		$data = self::retrieve_request_data( $response );

		if ( ! $data || false === $data->success ) {
			return $this->translate_activation_error();
		} else if ( is_wp_error( $response ) ) {
			return $response->get_error_message();
		}
		$this->set_data( $data );
		if ( ! update_option( self::OPTION_NAME, $this->data ) ) {
			return __( 'An error occurred, please try again.', 'code-snippets' );
		}

		return '';
	}

	/**
	 * Query the server for the current license status.
	 */
	public function update_status() {
		$response = $this->do_remote_request( 'check_license' );
		$data = self::retrieve_request_data( $response );

		if ( $data ) {
			$this->set_data( $data );
			update_option( self::OPTION_NAME, $this->data );
		}
	}

	public function remove_license() {
		$response = $this->do_remote_request( 'deactivate_license' );
		$data = self::retrieve_request_data( $response );
		var_dump( $data, $response );
		$this->set_data( $data );
		update_option( self::OPTION_NAME, $this->data );
	}

	/**
	 * Translate a request error code into an error message.
	 *
	 * @return string
	 */
	public function translate_activation_error() {
		switch ( $this->error ) {
			case 'expired':
				/* translators: %s: expiry date */
				return sprintf( __( 'Your license key expired on %s.', 'code-snippets' ),
					date_i18n( get_option( 'date_format' ), strtotime( $this->expires, current_time( 'timestamp' ) ) )
				);

			case 'disabled':
			case 'revoked':
				return __( 'Your license key has been disabled.', 'code-snippets' );

			case 'missing':
			case 'key_mismatch':
			case 'item_name_mismatch' :
				return __( 'Invalid license.', 'code-snippets' );

			case 'invalid' :
			case 'site_inactive' :
				return __( 'Your license is not active for this URL.', 'code-snippets' );

			case 'no_activations_left':
				return __( 'Your license key has reached its activation limit.', 'code-snippets' );

			default:
				return __( 'An error occurred activating the license.', 'code-snippets' );
		}
	}
}
