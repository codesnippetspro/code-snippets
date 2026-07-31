<?php

namespace Code_Snippets\Model;

use WP_Exception;

/**
 * Base class for representing an item of data without needing to use direct access or individual getter and setter functions.
 *
 * @package Code_Snippets
 */
abstract class Model {

	/**
	 * List of data fields keyed to their current values. Will be initialised with default values.
	 *
	 * @var array<string, mixed>
	 */
	protected array $fields;

	/**
	 * List of default values provided for fields.
	 *
	 * @var array<string, mixed>
	 */
	protected static array $default_values = [];

	/**
	 * Optional list of field name aliases to map when resolving a field name.
	 *
	 * @var array<string, string> Field alias names keyed to actual field names.
	 */
	protected static array $field_aliases = [];

	/**
	 * Class constructor.
	 *
	 * @param array<string, mixed>|Model $initial_data Optional initial data to populate fields.
	 */
	public function __construct( $initial_data = null ) {
		assert( static::$default_values, get_class( $this ) . '::$default_values not set' );
		$this->fields = static::$default_values;
		$this->set_fields( $initial_data );
	}

	/**
	 * Set all data fields from an array or object. Invalid fields will be ignored.
	 *
	 * @param array<string, mixed>|Model|mixed $data List of data.
	 */
	public function set_fields( $data ) {
		// Only accept arrays or objects.
		if ( ! is_array( $data ) && ! is_object( $data ) ) {
			return;
		}

		// Convert objects into arrays.
		if ( is_object( $data ) ) {
			$data = method_exists( $data, 'get_fields' )
				? $data->get_fields()
				: get_object_vars( $data );
		}

		// Loop through the provided fields and set their values.
		foreach ( $data as $field => $value ) {
			$this->set_field( $field, $value );
		}
	}

	/**
	 * Retrieve list of current data fields.
	 *
	 * @return array<string, mixed> Field names keyed to current values.
	 */
	public function get_fields(): array {
		$fields = [];

		foreach ( $this->get_allowed_fields() as $field_name ) {
			$fields[ $field_name ] = $this->$field_name;
		}

		return $fields;
	}

	/**
	 * Retrieve a list of current data fields, excluding values that are unchanged from the default.
	 *
	 * @return array<string, mixed>
	 */
	public function get_modified_fields(): array {
		return array_filter(
			$this->get_fields(),
			function ( $value, $field ) {
				return $value && $value !== static::$default_values[ $field ];
			},
			ARRAY_FILTER_USE_BOTH
		);
	}

	/**
	 * Internal function for resolving the actual name of a field.
	 *
	 * @param string $field A field name, potentially a field alias.
	 *
	 * @return string The resolved field name.
	 */
	protected static function resolve_field_name( string $field ): string {
		return static::$field_aliases[ $field ] ?? $field;
	}

	/**
	 * Check if a field is set.
	 *
	 * @param string $field The field name.
	 *
	 * @return bool Whether the field is set.
	 */
	public function __isset( string $field ) {
		$field = self::resolve_field_name( $field );
		return isset( $this->fields[ $field ] ) || method_exists( $this, 'get_' . $field );
	}

	/**
	 * Retrieve a field's value.
	 *
	 * @param string $field The field name.
	 *
	 * @return mixed The field value
	 *
	 * @throws WP_Exception If the field name is not allowed.
	 */
	public function __get( string $field ) {
		$field = self::resolve_field_name( $field );

		if ( method_exists( $this, 'get_' . $field ) ) {
			return call_user_func( array( $this, 'get_' . $field ) );
		}

		if ( ! $this->is_allowed_field( $field ) ) {
			if ( function_exists( 'wp_trigger_error' ) ) {
				// translators: 1: class name, 2: field name.
				$message = sprintf( 'Trying to access invalid property on "%1$s" class: %2$s', get_class( $this ), $field );
				wp_trigger_error( __FUNCTION__, $message, E_USER_WARNING );
			}

			return null;
		}

		return $this->fields[ $field ];
	}

	/**
	 * Set the value of a field without any validation.
	 *
	 * @param string $resolved_field The resolved field name.
	 * @param mixed  $value          The field value.
	 */
	private function set_value_internal( string $resolved_field, $value ) {
		$value = method_exists( $this, 'prepare_' . $resolved_field ) ?
			call_user_func( array( $this, 'prepare_' . $resolved_field ), $value ) :
			$this->prepare_field( $value, $resolved_field );

		$this->fields[ $resolved_field ] = $value;
	}

	/**
	 * Set the value of a field.
	 *
	 * @param string $field The field name.
	 * @param mixed  $value The field value.
	 *
	 * @throws WP_Exception If the field name is not allowed.
	 */
	public function __set( string $field, $value ) {
		$resolved_field = $this->resolve_field_name( $field );

		if ( $this->is_allowed_field( $resolved_field ) ) {
			$this->set_value_internal( $resolved_field, $value );
		} elseif ( function_exists( 'wp_trigger_error' ) ) {
			// translators: 1: class name, 2: field name.
			$message = sprintf( 'Trying to set invalid property on "%s" class: %s', get_class( $this ), $field );
			wp_trigger_error( __FUNCTION__, $message, E_USER_ERROR );
		}
	}

	/**
	 * Prepare a value before it is stored.
	 *
	 * @param mixed  $value Value to prepare.
	 * @param string $field Field name.
	 *
	 * @return mixed Value in the correct format.
	 */
	abstract protected function prepare_field( $value, string $field );

	/**
	 * Retrieve the list of fields that can be written to.
	 *
	 * @return array<string> List of field names.
	 */
	public function get_allowed_fields(): array {
		return array_merge( array_keys( $this->fields ), array_keys( static::$field_aliases ) );
	}

	/**
	 * Determine whether a field is allowed to be written to
	 *
	 * @param string $field The field name.
	 *
	 * @return bool true if the is allowed, false if invalid.
	 */
	public function is_allowed_field( string $field ): bool {
		return ( $this->fields && array_key_exists( $field, $this->fields ) ) ||
		       ( static::$field_aliases && array_key_exists( $field, static::$field_aliases ) );
	}

	/**
	 * Safely set the value for a field.
	 * If the field name is invalid, false will be returned instead of an error thrown.
	 *
	 * @param string $field The field name.
	 * @param mixed  $value The field value.
	 *
	 * @return bool true if the field was set successfully, false if the field name is invalid.
	 */
	public function set_field( string $field, $value ): bool {
		$resolved_field = $this->resolve_field_name( $field );

		if ( ! $this->is_allowed_field( $resolved_field ) ) {
			return false;
		}

		$this->set_value_internal( $resolved_field, $value );
		return true;
	}
}
