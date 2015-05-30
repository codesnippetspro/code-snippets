<?php

class Snippet {

	/**
	 * Holds the snippet fields. Initialized with default values
	 * @var array
	 */
	private $fields = array(
		'id' => 0,
		'name' => '',
		'description' => '',
		'code' => '',
		'tags' => '',
		'scope' => 0,
		'active' => 0,
	);

	/**
	 * Retrieve the array of snippet fields
	 * @return array
	 */
	public function get_fields() {
		return $this->fields;
	}

	/**
	 * Set all of the snippet fields from an array or object
	 * @param array|object $fields List of fields
	 */
	public function set_fields( $fields ) {

		/* Only accept arrays or objects */
		if ( ! $fields || is_string( $fields ) ) {
			return;
		}

		/* Convert objects into arrays */
		if ( is_object( $fields ) ) {
			$fields = get_object_vars( $fields );
		}

		/* Loop through the passed fields and set them */
		foreach ( $fields as $field => $value ) {
			$this->__set( $field, $value );
		}
	}

	/**
	 * Constructor function
	 * @param array|object $fields Initial snippet fields
	 */
	public function __construct( $fields = null ) {
		$this->set_fields( $fields );
	}

	/**
	 * Check if a field is set
	 * @param  string  $field The field name
	 * @return boolean        Whether the field is set
	 */
	public function __isset( $field ) {
		return isset( $this->fields[ $field ] ) || method_exists( $this, 'get_' . $field );
	}

	/**
	 * Retrieve a field's value
	 * @param  string $field The field name
	 * @return mixed         The field value
	 */
	public function __get( $field ) {
		if ( ! isset( $this->fields[ $field ] ) && method_exists( $this, 'get_' . $field ) ) {
			return call_user_func( array( $this, 'get_' . $field ) );
		}

		return $this->fields[ $field ];
	}

	/**
	 * Set a field's value
	 * @param string $field The field name
	 * @param mixed  $value The field value
	 */
	public function __set( $field, $value ) {

		if ( method_exists( $this, 'prepare_' . $field ) ) {
			$value = call_user_func( array( $this, 'prepare_' . $field ), $value );
		}

		$this->fields[ $field ] = $value;
	}

	/**
	 * Prepare the ID by ensuring it is an absolute integer
	 * @param  int $id
	 * @return int
	 */
	private function prepare_id( $id ) {
		return absint( $id );
	}

	/**
	 * Prepare the code by removing php tags from beginning and end
	 * @param  string $code
	 * @return string
	 */
	private function prepare_code( $code ) {

		/* Remove <?php and <? from beginning of snippet */
		$code = preg_replace( '|^[\s]*<\?(php)?|', '', $code );

		/* Remove ?> from end of snippet */
		$code = preg_replace( '|\?>[\s]*$|', '', $code );

		return $code;
	}

	/**
	 * Prepare the scope by ensuring that it is a valid number
	 * @param  int $scope
	 * @return int
	 */
	private function prepare_scope( $scope ) {

		if ( in_array( $scope, array( 0, 1, 2 ) ) ) {
			return $scope;
		}

		return $this->fields['scope'];
	}

	/**
	 * Prepare the snippet tags by ensuring they are in the correct format
	 * @param  string|array $tags The tags as provided
	 * @return string             The tags in the correct string format
	 */
	private function prepare_tags( $tags ) {
		if ( is_array( $tags ) ) {
			return implode( ', ', $tags );
		}

		return $tags;
	}

	/**
	 * Retrieve the tags in array format
	 * @return array An array of the snippet's tags
	 */
	private function get_tags_array() {
		return code_snippets_build_tags_array( $this->fields['tags'] );
	}
}
