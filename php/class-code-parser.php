<?php

/**
 * The main plugin class
 *
 * @package Code_Snippets
 */
class Code_Snippets_Code_Parser {

	/**
	 * @var string
	 */
	private $code;

	/**
	 * @var array
	 */
	private $tokens;

	/**
	 * The index of the token currently being examined.
	 *
	 * @var int
	 */
	private $current;

	/**
	 * The total number of tokens.
	 *
	 * @var int
	 */
	private $length;

	private $class_names = array();

	private $function_names = array();

	private $errors = array();

	/**
	 * Class constructor.
	 *
	 * @param string $code Snippet code for parsing.
	 */
	public function __construct( $code ) {
		$this->code = $code;
		$this->tokens = token_get_all( "<?php\n" . $this->code );
		$this->length = count( $this->tokens );
		$this->current = 0;
	}

	/**
	 * Determine whether the parser has reached the end of the list of tokens.
	 *
	 * @return bool
	 */
	private function end() {
		return $this->current === $this->length;
	}

	/**
	 * Retrieve the next token without moving the pointer
	 *
	 * @return string|array|null The current token if the list has not been expended, null otherwise.
	 */
	private function peek() {
		return $this->end() ? null : $this->tokens[ $this->current ];
	}

	/**
	 * Move the pointer to the next token, if there is one
	 *
	 * If the first argument is provided, only move the pointer if the tokens match
	 *
	 * @return bool Whether the pointer was advanced.
	 */
	private function next() {
		if ( $this->end()) {
			return false;
		}

		$this->current++;
		return true;
	}

	/**
	 * Parse the provided tokens.
	 */
	public function parse() {

		while ( ! $this->end() ) {
			$token = $this->peek();
			$this->next();

			if ( ! is_array( $token ) ) {
				continue;
			}

			// only look for class and function declaration tokens
			if ( T_CLASS !== $token[0] && T_FUNCTION !== $token[0] ) {
				continue;
			}

			$structure_type = $token[0];

			// continue eating tokens until we find the name of the class or function
			while ( ! $this->end() && $token[0] !== T_STRING ) {
				$token = $this->peek();
				$this->next();
			}

			// if we've eaten all of the tokens without discovering a name, then there must be a syntax error, so return appropriately
			if ( $this->end() ) {
				$this->errors[] = T_CLASS === $structure_type ?
					__( 'Snippet contains an incomplete class', 'code-snippets' ) :
					__( 'Snippet contains an incomplete function', 'code-snippets' );
				return false;
			}

			// add the discovered class or function name to an appropriate array
			if ( T_CLASS === $structure_type ) {
				$this->class_names[] = $token[1];
			} elseif ( T_FUNCTION === $structure_type ) {
				$this->function_names[] = $token[1];
			}

			// if we have entered into a class, eat tokens until we find the closing brace
			if ( T_CLASS !== $structure_type ) continue;

			// find the opening brace for the class
			while ( ! $this->end() && '{' !== $token ) {
				$token = $this->peek();
				$this->next();
			}

			// continue traversing the class tokens until we have found the class closing brace
			$depth = 1;
			while ( ! $this->end() && $depth > 0 ) {
				$token = $this->peek();

				if ( '{' === $token ) {
					$depth++;
				} elseif ( '}' === $token ) {
					$depth--;
				}

				$this->next();
			}

			// if we did not make it out of the class, then there's a problem
			if ( $depth > 0 ) {
				$this->errors[] = __( 'Snippet contains a syntax error', 'code-snippets' );
				return false;
			}
		}

		return true;
	}

	/**
	 * Retrieve a list of functions defined in the code.
	 *
	 * @return array
	 */
	public function get_defined_functions() {
		return $this->function_names;
	}

	/**
	 * Retrieve a list of classes declared in the code.
	 *
	 * @return array
	 */
	public function get_declared_classes() {
		return $this->class_names;
	}

	/**
	 * Retrieve the errors encountered when parsing.
	 *
	 * @return array
	 */
	public function get_parse_errors() {
		return $this->errors;
	}
}
