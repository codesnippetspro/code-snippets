<?php

namespace Code_Snippets;

/**
 * Base class for representing an item of data without needing to use direct access or individual getter and setter functions.
 *
 * @package Code_Snippets
 *
 * @since   3.4.0
 */
abstract class Data_Item {

	/**
	 * List of data fields keyed to their current values. Will be initialised with default values.
	 *
	 * @var array<string, mixed>
	 */
	protected $fields;

	/**
	 * List of default values provided for fields.
	 *
	 * @var array<string, mixed>
	 */
	protected $default_values;

	/**
	 * Optional list of field name aliases to map when resolving a field name.
	 *
	 * @var array<string, string> Field alias names keyed to actual field names.
	 */
	protected $field_aliases;

	/**
	 * Class constructor.
	 *
	 * @param array<string, mixed>           $default_values List of valid fields mapped to their default values.
	 * @param array<string, mixed>|Data_Item $initial_data   Optional initial data to populate fields.
	 * @param array<string, string>          $field_aliases  Optional list of field name aliases to map when resolving a field name.
	 */
	public function __construct( array $default_values, $initial_data = null, array $field_aliases = [] ) {
		$this->fields = $default_values;
		$this->default_values = $default_values;
		$this->field_aliases = $field_aliases;

		// If we've accidentally passed an existing object, then fetch its fields before constructing the new object.
		if ( is_object( $initial_data ) && method_exists( $initial_data, 'get_fields' ) ) {
			$initial_data = $initial_data->get_fields();
		}

		$this->set_fields( $initial_data );
	}


	/**
	 * Set all data fields from an array or object. Invalid fields will be ignored.
	 *
	 * @param array<string, mixed>|mixed $data List of data.
	 */
	public function set_fields( $data ) {
		// Only accept arrays or objects.
		if ( ! $data || is_string( $data ) ) {
			return;
		}

		// Convert objects into arrays.
		if ( is_object( $data ) ) {
			$data = get_object_vars( $data );
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
		return $this->fields;
	}

	/**
	 * Retrieve a list of current data fields, excluding values that are unchanged from the default.
	 *
	 * @return array<string, mixed>
	 */
	public function get_modified_fields(): array {
		$modified_fields = [];

		foreach ( $this->get_fields() as $field => $value ) {
			if ( $value && $value !== $this->default_values[ $field ] ) {
				$modified_fields[ $field ] = $value;
			}
		}

		return $modified_fields;
	}

	/**
	 * Internal function for resolving the actual name of a field.
	 *
	 * @param string $field A field name, potentially a field alias.
	 *
	 * @return string The resolved field name.
	 */
	protected function resolve_field_name( string $field ): string {
		return $this->field_aliases[ $field ] ?? $field;
	}

	/**
	 * Check if a field is set.
	 *
	 * @param string $field The field name.
	 *
	 * @return bool Whether the field is set.
	 */
	public function __isset( string $field ) {
		$field = $this->resolve_field_name( $field );
		return isset( $this->fields[ $field ] ) || method_exists( $this, 'get_' . $field );
	}

	/**
	 * Retrieve a field's value.
	 *
	 * @param string $field The field name.
	 *
	 * @return mixed The field value
	 */
	public function __get( string $field ) {
		$field = $this->resolve_field_name( $field );

		if ( method_exists( $this, 'get_' . $field ) ) {
			return call_user_func( array( $this, 'get_' . $field ) );
		}

		if ( ! $this->is_allowed_field( $field ) ) {
			if ( WP_DEBUG ) {
				$message = sprintf( 'Trying to access invalid property on "%s" class: %s', get_class( $this ), $field );
				// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
				trigger_error( esc_html( $message ), E_USER_WARNING );
			}

			return null;
		}

		return $this->fields[ $field ];
	}

	/**
	 * Set the value of a field.
	 *
	 * @param string $field The field name.
	 * @param mixed  $value The field value.
	 */
	public function __set( string $field, $value ) {
		$field = $this->resolve_field_name( $field );

		if ( ! $this->is_allowed_field( $field ) ) {
			if ( WP_DEBUG ) {
				$message = sprintf( 'Trying to set invalid property on "%s" class: %s', get_class( $this ), $field );
				// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
				trigger_error( esc_html( $message ), E_USER_ERROR );
			}

			return;
		}

		$value = method_exists( $this, 'prepare_' . $field ) ?
			call_user_func( array( $this, 'prepare_' . $field ), $value ) :
			$this->prepare_field( $value, $field );

		$this->fields[ $field ] = $value;
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
		return array_keys( $this->fields ) + array_keys( $this->field_aliases );
	}

	/**
	 * Determine whether a field is allowed to be written to
	 *
	 * @param string $field The field name.
	 *
	 * @return bool true if the is allowed, false if invalid.
	 */
	public function is_allowed_field( string $field ): bool {
		return $this->fields && array_key_exists( $field, $this->fields ) ||
		       $this->field_aliases && array_key_exists( $field, $this->field_aliases );
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
		if ( ! $this->is_allowed_field( $field ) ) {
			return false;
		}

		$this->__set( $field, $value );

		return true;
	}
}
