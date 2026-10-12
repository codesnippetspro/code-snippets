<?php
/**
 * Utility functions to make managing options easier with WordPress Multisite.
 *
 * @package Code_Snippets
 */

namespace Code_Snippets\Utils;

/**
 * Resolve whether an operation should apply to the network or the current site.
 *
 * A null value is resolved from the current admin screen, so a caller that has
 * no explicit preference follows whichever admin the request is being made from.
 *
 * @param bool|null $network Whether the operation is network-wide, or null to infer it.
 *
 * @return bool
 */
function validate_network_param( ?bool $network = null ): bool {
	// If multisite is not active, then assume the value is false.
	if ( ! is_multisite() ) {
		return false;
	}

	// If the value is null, try to base it on the current admin page.
	if ( is_null( $network ) && function_exists( 'is_network_admin' ) ) {
		return is_network_admin();
	}

	return (bool) $network;
}

/**
 * Retrieves an option value based on an option name from either the current site or the current network.
 *
 * @param bool   $network       Whether to get a network-wide option.
 * @param string $option        Name of option to retrieve. Expected to not be SQL-escaped.
 * @param mixed  $default_value Optional value to return if option doesn't exist. Default false.
 *
 * @return mixed Value set for the option.
 */
function get_self_option( bool $network, string $option, $default_value = false ) {
	return $network
		? get_site_option( $option, $default_value )
		: get_option( $option, $default_value );
}

/**
 * Adds a new option value for either the current site or the current network.
 *
 * @param bool   $network Whether to get a network-wide option.
 * @param string $option  Name of the option to add. Expected to not be SQL-escaped.
 * @param mixed  $value   Option value, can be anything. Expected to not be SQL-escaped.
 *
 * @return bool True if the option was added, false otherwise.
 */
function add_self_option( bool $network, string $option, $value ): bool {
	return $network
		? add_site_option( $option, $value )
		: add_option( $option, $value );
}

/**
 * Update the value of an option that was already added on the current site or the current network.
 *
 * @param bool   $network Whether to update a network-wide option.
 * @param string $option  Name of option. Expected to not be SQL-escaped.
 * @param mixed  $value   Option value. Expected to not be SQL-escaped.
 *
 * @return bool False if value was not updated. True if value was updated.
 */
function update_self_option( bool $network, string $option, $value ): bool {
	return $network
		? update_site_option( $option, $value )
		: update_option( $option, $value );
}

/**
 * Remove an option on th current site or the current network.
 *
 * @param bool   $network Whether to delete a network-wide option.
 * @param string $option  Name of option. Expected to not be SQL-escaped.
 *
 * @return bool False if value was not deleted. True if value was deleted.
 */
function delete_self_option( bool $network, string $option ): bool {
	return $network
		? delete_site_option( $option )
		: delete_option( $option );
}
