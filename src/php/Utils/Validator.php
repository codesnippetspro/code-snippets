<?php

namespace Code_Snippets\Utils;

/**
 * Validates code prior to execution.
 *
 * @package Code_Snippets
 */
class Validator {

	/**
	 * Code to validate.
	 *
	 * @var string
	 */
	private string $code;

	/**
	 * List of tokens.
	 *
	 * @var array<string>
	 */
	private array $tokens;

	/**
	 * The index of the token currently being examined.
	 *
	 * @var int
	 */
	private int $current;

	/**
	 * The total number of tokens.
	 *
	 * @var int
	 */
	private int $length;

	/**
	 * Array to keep track of the various function, class and interface identifiers which have been defined.
	 *
	 * @var array<string, string[]>
	 */
	private array $defined_identifiers = [];

	/**
	 * Exclude certain tokens from being checked.
	 *
	 * @var array<string, string[]>
	 */
	private array $exceptions = [];

	/**
	 * Identifiers already claimed by other snippets being validated alongside
	 * this one.
	 *
	 * A snippet is validated against everything PHP has declared so far, which
	 * does not include a snippet that is about to be activated in the same
	 * batch. Two snippets declaring the same function therefore both passed and
	 * both activated, and the site fataled on the next request.
	 *
	 * @var array<string, string[]>
	 */
	private array $claimed_identifiers = [];

	/**
	 * Namespace the code being read currently declares, lower-cased, or empty for the global namespace.
	 *
	 * @var string
	 */
	private string $namespace = '';

	/**
	 * Class constructor.
	 *
	 * @param string                  $code                Snippet code for parsing.
	 * @param array<string, string[]> $claimed_identifiers Identifiers already claimed by
	 *                                                     snippets validated alongside this one.
	 */
	public function __construct( string $code, array $claimed_identifiers = [] ) {
		$this->claimed_identifiers = $claimed_identifiers;
		$this->code = $code;
		$this->tokens = token_get_all( "<?php\n" . $this->code );
		$this->length = count( $this->tokens );
		$this->current = 0;
	}

	/**
	 * Retrieve the identifiers claimed so far, including this snippet's own.
	 *
	 * Pass the result to the next Validator in a batch so that two snippets
	 * cannot both claim the same name.
	 *
	 * @return array<string, string[]>
	 */
	public function get_claimed_identifiers(): array {
		return $this->claimed_identifiers;
	}

	/**
	 * Determine whether the parser has reached the end of the list of tokens.
	 *
	 * @return bool
	 */
	private function end(): bool {
		return $this->current === $this->length;
	}

	/**
	 * Retrieve the next token without moving the pointer
	 *
	 * @return string|array<string|int>|null The current token if the list has not been expended, null otherwise.
	 */
	private function peek() {
		return $this->end() ? null : $this->tokens[ $this->current ];
	}

	/**
	 * Move the pointer to the next token, if there is one.
	 *
	 * If the first argument is provided, only move the pointer if the tokens match.
	 */
	private function next() {
		if ( ! $this->end() ) {
			++$this->current;
		}
	}

	/**
	 * Check whether a particular identifier has been used previously.
	 *
	 * @param string $type       Which type of identifier this is. Supports T_FUNCTION, T_CLASS and T_INTERFACE.
	 * @param string $identifier The name of the identifier itself.
	 *
	 * @return bool true if the identifier is not unique.
	 */
	private function check_duplicate_identifier( string $type, string $identifier ): bool {
		$identifier = strtolower( ltrim( $identifier, '\\' ) );

		// PHP keeps declared names fully qualified, so that is the form compared
		// and claimed: the same short name in two namespaces is two names.
		$qualified = '' === $this->namespace ? $identifier : $this->namespace . '\\' . $identifier;
		$namespaced_identifier = 'code_snippets\\' . $identifier;

		if ( ! isset( $this->defined_identifiers[ $type ] ) ) {
			switch ( $type ) {
				case T_FUNCTION:
					$this->defined_identifiers[ T_FUNCTION ] = array_map(
						'strtolower',
						array_merge( get_defined_functions()['internal'], get_defined_functions()['user'] )
					);
					break;

				case T_CLASS:
					$this->defined_identifiers[ T_CLASS ] = array_map( 'strtolower', get_declared_classes() );
					break;

				case T_INTERFACE:
					$this->defined_identifiers[ T_INTERFACE ] = array_map( 'strtolower', get_declared_interfaces() );
					break;

				default:
					return false;
			}
		}

		$known = array_merge(
			$this->defined_identifiers[ $type ],
			$this->claimed_identifiers[ $type ] ?? []
		);

		$duplicate_identifier = in_array( $qualified, $known, true );
		$duplicate_namespaced = '' === $this->namespace && in_array( $namespaced_identifier, $known, true );
		$exceptions = $this->exceptions[ $type ] ?? [];
		$exception_identifier = in_array( $identifier, $exceptions, true ) || in_array( $qualified, $exceptions, true );
		$exception_namespaced = in_array( $identifier, $exceptions, true ) || in_array( $namespaced_identifier, $exceptions, true );

		array_unshift( $this->defined_identifiers[ $type ], $qualified );
		$this->claimed_identifiers[ $type ][] = $qualified;

		return ( $duplicate_identifier && ! $exception_identifier ) || ( $duplicate_namespaced && ! $exception_namespaced );
	}

	/**
	 * Read the name a namespace declaration introduces, leaving the cursor after it.
	 *
	 * A bare "namespace {" opens the global namespace; "namespace\\foo()" is a
	 * relative name rather than a declaration and is left alone.
	 *
	 * @return string Lower-cased namespace, or empty for the global namespace.
	 */
	private function read_namespace_declaration(): string {
		$name = '';

		while ( ! $this->end() ) {
			$token = $this->peek();

			if ( is_array( $token ) ) {
				if ( T_WHITESPACE === $token[0] || T_COMMENT === $token[0] || T_DOC_COMMENT === $token[0] ) {
					$this->next();
					continue;
				}

				if ( T_NS_SEPARATOR === $token[0] && '' === $name ) {
					return $this->namespace;
				}

				if ( defined( 'T_NAME_RELATIVE' ) && T_NAME_RELATIVE === $token[0] ) {
					return $this->namespace;
				}

				if ( T_STRING === $token[0] || T_NS_SEPARATOR === $token[0]
					|| ( defined( 'T_NAME_QUALIFIED' ) && T_NAME_QUALIFIED === $token[0] ) ) {
					$name .= $token[1];
					$this->next();
					continue;
				}

				return $this->namespace;
			}

			if ( ';' === $token || '{' === $token ) {
				$this->next();
				return strtolower( trim( $name, '\\' ) );
			}

			return $this->namespace;
		}

		return strtolower( trim( $name, '\\' ) );
	}

	/**
	 * Validate the given PHP code and return the result.
	 *
	 * @return array<string, mixed>|false Array containing message if an error was encountered, false if validation was successful.
	 */
	public function validate() {

		while ( ! $this->end() ) {
			$token = $this->peek();
			$this->next();

			if ( ! is_array( $token ) ) {
				continue;
			}

			if ( T_NAMESPACE === $token[0] ) {
				$this->namespace = $this->read_namespace_declaration();
				continue;
			}

			// If this is a function or class exists check, then allow this function or class to be defined.
			if ( T_STRING === $token[0] && ( 'function_exists' === $token[1] || 'class_exists' === $token[1] ) ) {
				$type = 'function_exists' === $token[1] ? T_FUNCTION : T_CLASS;

				// Eat tokens until we find the function or class name.
				while ( ! $this->end() && T_CONSTANT_ENCAPSED_STRING !== $token[0] ) {
					$token = $this->peek();
					$this->next();
				}

				// Add the identifier to the list of exceptions.
				$identifier = strtolower( ltrim( trim( $token[1], '\'"' ), '\\' ) );

				if ( '' !== $identifier ) {
					$this->exceptions[ $type ] = $this->exceptions[ $type ] ?? [];
					$this->exceptions[ $type ][] = $identifier;
				}
				continue;
			}

			// If we have a double colon, followed by a class, then consume it before the next section.
			if ( T_DOUBLE_COLON === $token[0] ) {
				$token = $this->peek();
				$this->next();

				if ( T_CLASS === $token[0] ) {
					$this->next();
					$token = $this->peek();
				}
			}

			// Only look for class and function declaration tokens.
			if ( T_CLASS !== $token[0] && T_FUNCTION !== $token[0] ) {
				continue;
			}

			/**
			 * Ensure the type of $token is inferred correctly.
			 *
			 * @var string|array<string|int> $token
			 */
			$structure_type = $token[0];

			// Continue eating tokens until we find the name of the class or function.
			while ( ! $this->end() && T_STRING !== $token[0] &&
			        ( T_FUNCTION !== $structure_type || '(' !== $token ) && ( T_CLASS !== $structure_type || '{' !== $token ) ) {
				$token = $this->peek();
				$this->next();
			}

			// If we've eaten all the tokens without discovering a name, then there must be a syntax error, so return appropriately.
			if ( $this->end() ) {
				return array(
					'message' => __( 'Parse error: syntax error, unexpected end of snippet.', 'code-snippets' ),
					'line'    => $token[2],
				);
			}

			// If the function or class is anonymous, with no name, then no need to check.
			if ( ! ( T_FUNCTION === $structure_type && '(' === $token ) && ! ( T_CLASS === $structure_type && '{' === $token ) ) {

				// Check whether the name has already been defined.
				if ( $this->check_duplicate_identifier( $structure_type, $token[1] ) ) {
					switch ( $structure_type ) {
						case T_FUNCTION:
							/* translators: %s: PHP function name */
							$message = __( 'Cannot redeclare function %s.', 'code-snippets' );
							break;
						case T_CLASS:
							/* translators: %s: PHP class name */
							$message = __( 'Cannot redeclare class %s.', 'code-snippets' );
							break;
						case T_INTERFACE:
							/* translators: %s: PHP interface name */
							$message = __( 'Cannot redeclare interface %s.', 'code-snippets' );
							break;
						default:
							/* translators: %s: PHP identifier name*/
							$message = __( 'Cannot redeclare %s.', 'code-snippets' );
					}

					return array(
						'message' => sprintf( $message, $token[1] ),
						'line'    => $token[2],
					);
				}
			}

			// If we have entered into a class, eat tokens until we find the closing brace.
			if ( T_CLASS !== $structure_type ) {
				continue;
			}

			// Find the opening brace for the class.
			while ( ! $this->end() && '{' !== $token ) {
				$token = $this->peek();
				$this->next();
			}

			// Continue traversing the class tokens until we have found the class closing brace.
			$depth = 1;
			while ( ! $this->end() && $depth > 0 ) {
				$token = $this->peek();

				if ( '{' === $token ) {
					++$depth;
				} elseif ( '}' === $token ) {
					--$depth;
				}

				$this->next();
			}

			// If we did not make it out of the class, then there's a problem.
			if ( $depth > 0 ) {
				return array(
					'message' => __( 'Parse error: syntax error, unexpected end of snippet.', 'code-snippets' ),
					'line'    => $token[2],
				);
			}
		}

		return false;
	}
}
