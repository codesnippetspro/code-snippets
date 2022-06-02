<?php

namespace Code_Snippets;

use DateTime;
use DateTimeZone;

/**
 * A snippet object.
 *
 * @since   2.4.0
 * @package Code_Snippets
 *
 * @property int           $id                      The database ID.
 * @property string        $name                    The snippet title.
 * @property string        $desc                    The formatted description.
 * @property string        $code                    The executable code.
 * @property array         $tags                    An array of the tags.
 * @property string        $scope                   The scope name.
 * @property int           $priority                Execution priority.
 * @property bool          $active                  The active status.
 * @property bool          $network                 true if is multisite-wide snippet, false if site-wide.
 * @property bool          $shared_network          Whether the snippet is a shared network snippet.
 * @property string        $modified                The date and time when the snippet data was most recently saved to the database.
 *
 * @property-read string   $display_name            The snippet name if it exists or a placeholder if it does not.
 * @property-read string   $tags_list               The tags in string list format.
 * @property-read string   $scope_icon              The dashicon used to represent the current scope.
 * @property-read string   $scope_name              Human-readable description of the snippet type.
 * @property-read string   $type                    The type of snippet.
 * @property-read string   $lang                    The language that the snippet code is written in.
 * @property-read int      $modified_timestamp      The last modification date in Unix timestamp format.
 * @property-read DateTime $modified_local          The last modification date in the local timezone.
 * @property-read string   $type_desc               Human-readable description of the snippet type.
 */
class Snippet {

	/**
	 * MySQL datetime format (YYYY-MM-DD hh:mm:ss).
	 */
	const DATE_FORMAT = 'Y-m-d H:i:s';

	/**
	 * Default value used for a datetime variable.
	 */
	const DEFAULT_DATE = '0000-00-00 00:00:00';

	/**
	 * The snippet metadata fields.
	 * Initialized with default values.
	 *
	 * @var array Two-dimensional array of field names keyed to current values.
	 */
	private $fields = array(
		'id'             => 0,
		'name'           => '',
		'desc'           => '',
		'code'           => '',
		'tags'           => array(),
		'scope'          => 'global',
		'active'         => false,
		'priority'       => 10,
		'network'        => null,
		'shared_network' => null,
		'modified'       => null,
	);

	/**
	 * List of field aliases
	 *
	 * @var array Two-dimensional array of field alias names keyed to actual field names.
	 */
	private static $field_aliases = array(
		'description' => 'desc',
		'language'    => 'lang',
	);

	/**
	 * Constructor function
	 *
	 * @param array|object $fields Initial snippet fields.
	 */
	public function __construct( $fields = null ) {

		// If we've accidentally passed a snippet object, then fetch its fields before constructing the new object.
		if ( is_object( $fields ) && method_exists( $fields, 'get_fields' ) ) {
			$fields = $fields->get_fields();
		}

		$this->set_fields( $fields );
	}

	/**
	 * Set all snippet fields from an array or object.
	 * Invalid fields will be ignored.
	 *
	 * @param array|object $fields List of fields.
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
			$this->set_field( $field, $value );
		}
	}

	/**
	 * Retrieve all snippet fields
	 *
	 * @return array Two-dimensional array of field names keyed to current values
	 */
	public function get_fields() {
		return $this->fields;
	}

	/**
	 * Internal function for validating the name of a field
	 *
	 * @param string $field A field name.
	 *
	 * @return string The validated field name.
	 */
	private function validate_field_name( $field ) {

		/* If a field alias is set, remap it to the valid field name */
		if ( isset( self::$field_aliases[ $field ] ) ) {
			return self::$field_aliases[ $field ];
		}

		return $field;
	}

	/**
	 * Check if a field is set
	 *
	 * @param string $field The field name.
	 *
	 * @return bool Whether the field is set.
	 */
	public function __isset( $field ) {
		$field = $this->validate_field_name( $field );

		return isset( $this->fields[ $field ] ) || method_exists( $this, 'get_' . $field );
	}

	/**
	 * Retrieve a field's value
	 *
	 * @param string $field The field name.
	 *
	 * @return mixed The field value
	 */
	public function __get( $field ) {
		$field = $this->validate_field_name( $field );

		if ( method_exists( $this, 'get_' . $field ) ) {
			return call_user_func( array( $this, 'get_' . $field ) );
		}

		if ( ! $this->is_allowed_field( $field ) ) {
			if ( WP_DEBUG ) {
				// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
				trigger_error( 'Trying to access invalid property on Snippets class: ' . esc_html( $field ), E_WARNING );
			}

			return null;
		}

		return $this->fields[ $field ];
	}

	/**
	 * Set the value of a field
	 *
	 * @param string $field The field name.
	 * @param mixed  $value The field value.
	 */
	public function __set( $field, $value ) {
		$field = $this->validate_field_name( $field );

		if ( ! $this->is_allowed_field( $field ) ) {
			if ( WP_DEBUG ) {
				// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
				trigger_error( 'Trying to set invalid property on Snippets class: ' . esc_html( $field ), E_WARNING );
			}

			return;
		}

		/* Check if the field value should be filtered */
		if ( method_exists( $this, 'prepare_' . $field ) ) {
			$value = call_user_func( array( $this, 'prepare_' . $field ), $value );
		}

		$this->fields[ $field ] = $value;
	}

	/**
	 * Retrieve the list of fields allowed to be written to
	 *
	 * @return array Single-dimensional array of field names.
	 */
	public function get_allowed_fields() {
		return array_keys( $this->fields ) + array_keys( self::$field_aliases );
	}

	/**
	 * Determine whether a field is allowed to be written to
	 *
	 * @param string $field The field name.
	 *
	 * @return bool true if the is allowed, false if invalid.
	 */
	public function is_allowed_field( $field ) {
		return array_key_exists( $field, $this->fields ) || array_key_exists( $field, self::$field_aliases );
	}

	/**
	 * Safely set the value for a field
	 * If the field name is invalid, false will be returned instead of an error thrown.
	 *
	 * @param string $field The field name.
	 * @param mixed  $value The field value.
	 *
	 * @return bool true if the field was set successfully, false if the field name is invalid.
	 */
	public function set_field( $field, $value ) {
		if ( ! $this->is_allowed_field( $field ) ) {
			return false;
		}

		$this->__set( $field, $value );

		return true;
	}

	/**
	 * Add a new tag
	 *
	 * @param string $tag Tag content to add to list.
	 */
	public function add_tag( $tag ) {
		$this->fields['tags'][] = $tag;
	}

	/**
	 * Prepare the ID by ensuring it is an absolute integer
	 *
	 * @param int $id The field as provided.
	 *
	 * @return int The field in the correct format.
	 */
	private function prepare_id( $id ) {
		return absint( $id );
	}

	/**
	 * Prepare the scope by ensuring that it is a valid choice
	 *
	 * @param int|string $scope The field as provided.
	 *
	 * @return string The field in the correct format.
	 */
	private function prepare_scope( $scope ) {
		$scopes = self::get_all_scopes();

		if ( in_array( $scope, $scopes, true ) ) {
			return $scope;
		}

		if ( is_numeric( $scope ) && isset( $scopes[ $scope ] ) ) {
			return $scopes[ $scope ];
		}

		return $this->fields['scope'];
	}

	/**
	 * Prepare the snippet tags by ensuring they are in the correct format
	 *
	 * @param string|array $tags The field as provided.
	 *
	 * @return array The field in the correct format.
	 */
	private function prepare_tags( $tags ) {
		return code_snippets_build_tags_array( $tags );
	}

	/**
	 * Prepare the active field by ensuring it is the correct type
	 *
	 * @param bool|int $active The field as provided.
	 *
	 * @return bool The field in the correct format.
	 */
	private function prepare_active( $active ) {

		if ( is_bool( $active ) ) {
			return $active;
		}

		return (bool) $active;
	}

	/**
	 * Prepare the priority field by ensuring it is an integer
	 *
	 * @param int $priority The field as provided.
	 *
	 * @return int The field in the correct format.
	 */
	private function prepare_priority( $priority ) {
		return intval( $priority );
	}

	/**
	 * If $network is anything other than true, set it to false
	 *
	 * @param bool $network The field as provided.
	 *
	 * @return bool The field in the correct format.
	 */
	private function prepare_network( $network ) {

		if ( null === $network && function_exists( 'is_network_admin' ) ) {
			return is_network_admin();
		}

		return true === $network;
	}

	/**
	 * Determine the type of code this snippet is, based on its scope
	 *
	 * @return string The snippet type – will be a filename extension.
	 */
	private function get_type() {
		if ( '-css' === substr( $this->scope, -4 ) ) {
			return 'css';
		}

		if ( '-js' === substr( $this->scope, -3 ) ) {
			return 'js';
		}

		if ( 'content' === substr( $this->scope, -7 ) ) {
			return 'html';
		}

		return 'php';
	}

	/**
	 * Retrieve a list of all valid types.
	 *
	 * @return string[]
	 */
	public static function get_types() {
		return [ 'php', 'html', 'css', 'js' ];
	}

	/**
	 * Retrieve description of snippet type.
	 *
	 * @return string
	 */
	private function get_type_desc() {
		$labels = [
			'php'  => __( 'Functions', 'code-snippets' ),
			'html' => __( 'Content', 'code-snippets' ),
			'css'  => __( 'Styles', 'code-snippets' ),
			'js'   => __( 'Scripts', 'code-snippets' ),
		];

		return isset( $labels[ $this->type ] ) ? $labels[ $this->type ] : strtoupper( $this->type );
	}

	/**
	 * Determine the language that the snippet code is written in, based on the scope
	 *
	 * @return string The name of a language filename extension.
	 */
	private function get_lang() {
		return $this->type;
	}

	/**
	 * Prepare the modification field by ensuring it is in the correct format.
	 *
	 * @param DateTime|string $modified Snippet modification date.
	 *
	 * @return string
	 */
	private function prepare_modified( $modified ) {

		/* if the supplied value is a DateTime object, convert it to string representation */
		if ( $modified instanceof DateTime ) {
			return $modified->format( self::DATE_FORMAT );
		}

		/* if the supplied value is probably a timestamp, attempt to convert it to a string */
		if ( is_numeric( $modified ) ) {
			return gmdate( self::DATE_FORMAT, $modified );
		}

		/* if the supplied value is a string, check it is not just the default value */
		if ( is_string( $modified ) && self::DEFAULT_DATE !== $modified ) {
			return $modified;
		}

		/* otherwise, discard the supplied value */

		return null;
	}

	/**
	 * Update the last modification date to the current date and time.
	 */
	public function update_modified() {
		$this->modified = gmdate( self::DATE_FORMAT );
	}

	/**
	 * Retrieve the snippet title if set or a placeholder title if not.
	 *
	 * @return string
	 */
	private function get_display_name() {
		/* translators: %d: snippet ID */
		return empty( $this->name ) ? sprintf( esc_html__( 'Untitled #%d', 'code-snippets' ), $this->id ) : $this->name;
	}

	/**
	 * Retrieve the tags in list format
	 *
	 * @return string The tags separated by a comma and a space.
	 */
	private function get_tags_list() {
		return implode( ', ', $this->tags );
	}

	/**
	 * Retrieve a list of all available scopes
	 *
	 * @return array Single-dimensional array of scope names.
	 *
	 * @phpcs:disable WordPress.Arrays.ArrayDeclarationSpacing.ArrayItemNoNewLine
	 */
	public static function get_all_scopes() {
		return array(
			'global', 'admin', 'front-end', 'single-use',
			'content', 'head-content', 'footer-content',
			'admin-css', 'site-css',
			'site-head-js', 'site-footer-js',
		);
	}

	/**
	 * Retrieve a list of all scope icons
	 *
	 * @return array Two-dimensional array with scope name keyed to the class name of a dashicon.
	 */
	public static function get_scope_icons() {
		return array(
			'global'         => 'admin-site',
			'admin'          => 'admin-tools',
			'front-end'      => 'admin-appearance',
			'single-use'     => 'clock',
			'content'        => 'shortcode',
			'head-content'   => 'editor-code',
			'footer-content' => 'editor-code',
			'admin-css'      => 'dashboard',
			'site-css'       => 'admin-customizer',
			'site-head-js'   => 'media-code',
			'site-footer-js' => 'media-code',
		);
	}

	/**
	 * Retrieve the string representation of the scope
	 *
	 * @return string The name of the scope.
	 */
	private function get_scope_name() {
		switch ( $this->scope ) {
			case 'global':
				return __( 'Global function', 'code-snippets' );
			case 'admin':
				return __( 'Admin function', 'code-snippets' );
			case 'front-end':
				return __( 'Front-end function', 'code-snippets' );
			case 'single-use':
				return __( 'Single-use function', 'code-snippets' );
			case 'content':
				return __( 'Content', 'code-snippets' );
			case 'head-content':
				return __( 'Head content', 'code-snippets' );
			case 'footer-content':
				return __( 'Footer content', 'code-snippets' );
			case 'admin-css':
				return __( 'Admin styles', 'code-snippets' );
			case 'site-css':
				return __( 'Front-end styles', 'code-snippets' );
			case 'site-head-js':
				return __( 'Head styles', 'code-snippets' );
			case 'site-footer-js':
				return __( 'Footer styles', 'code-snippets' );
		}

		return '';
	}

	/**
	 * Retrieve the icon used for the current scope
	 *
	 * @return string A dashicon name.
	 */
	private function get_scope_icon() {
		$icons = self::get_scope_icons();

		return $icons[ $this->scope ];
	}

	/**
	 * Determine if the snippet is a shared network snippet
	 *
	 * @return bool Whether the snippet is a shared network snippet.
	 */
	private function get_shared_network() {

		if ( isset( $this->fields['shared_network'] ) ) {
			return $this->fields['shared_network'];
		}

		if ( ! is_multisite() || ! $this->fields['network'] ) {
			$this->fields['shared_network'] = false;
		} else {
			$shared_network_snippets = get_site_option( 'shared_network_snippets', array() );
			$this->fields['shared_network'] = in_array( $this->fields['id'], $shared_network_snippets, true );
		}

		return $this->fields['shared_network'];
	}

	/**
	 * Retrieve the snippet modification date as a timestamp.
	 *
	 * @return int Timestamp value.
	 */
	private function get_modified_timestamp() {
		$datetime = DateTime::createFromFormat( self::DATE_FORMAT, $this->modified, new DateTimeZone( 'UTC' ) );

		return $datetime ? $datetime->getTimestamp() : 0;
	}

	/**
	 * Retrieve the modification time in the local timezone.
	 *
	 * @return DateTime
	 */
	private function get_modified_local() {

		if ( function_exists( 'wp_timezone' ) ) {
			$timezone = wp_timezone();
		} else {
			$timezone = get_option( 'timezone_string' );

			/* calculate the timezone manually if it is not available */
			if ( ! $timezone ) {
				$offset = (float) get_option( 'gmt_offset' );
				$hours = (int) $offset;
				$minutes = ( $offset - $hours ) * 60;

				$sign = ( $offset < 0 ) ? '-' : '+';
				$timezone = sprintf( '%s%02d:%02d', $sign, abs( $hours ), abs( $minutes ) );
			}

			$timezone = new DateTimeZone( $timezone );
		}

		$datetime = DateTime::createFromFormat( self::DATE_FORMAT, $this->modified, new DateTimeZone( 'UTC' ) );
		$datetime->setTimezone( $timezone );

		return $datetime;
	}

	/**
	 * Retrieve the last modified time, nicely formatted for readability.
	 *
	 * @param boolean $include_html Whether to include HTML in the output.
	 *
	 * @return string
	 */
	public function format_modified( $include_html = true ) {
		if ( ! $this->modified ) {
			return '';
		}

		$timestamp = $this->modified_timestamp;
		$time_diff = time() - $timestamp;
		$local_time = $this->modified_local;

		if ( $time_diff >= 0 && $time_diff < YEAR_IN_SECONDS ) {
			/* translators: %s: Human-readable time difference. */
			$human_time = sprintf( __( '%s ago', 'code-snippets' ), human_time_diff( $timestamp ) );
		} else {
			$human_time = $local_time->format( __( 'Y/m/d', 'code-snippets' ) );
		}

		if ( ! $include_html ) {
			return $human_time;
		}

		/* translators: 1: date format, 2: time format */
		$date_format = _x( '%1$s \a\t %2$s', 'date and time format', 'code-snippets' );
		$date_format = sprintf( $date_format, get_option( 'date_format' ), get_option( 'time_format' ) );

		return sprintf( '<span title="%s">%s</span>', $local_time->format( $date_format ), $human_time );
	}
}
