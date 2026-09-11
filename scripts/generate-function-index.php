<?php
/**
 * Generate the index of WordPress and PHP functions offered by the code editor's autocompletion.
 *
 * Usage: php scripts/generate-function-index.php <path-to-wordpress> > src/js/editor/data/function-index.json
 *
 * WordPress functions are read from the source of the given installation, skipping those documented as private or
 * deprecated. PHP functions are read by reflection from the running PHP, limited to extensions that are always or
 * almost always present, so the output does not depend on which optional extensions the machine happens to have.
 *
 * @package Code_Snippets
 */

// phpcs:disable WordPress.WP.AlternativeFunctions -- Runs outside WordPress.

const MAX_SUMMARY_LENGTH = 160;

const PHP_EXTENSIONS = [ 'Core', 'standard', 'date', 'pcre', 'json', 'ctype', 'filter', 'hash', 'SPL', 'random', 'mbstring' ];

/**
 * Paths that hold bundled libraries, polyfills of PHP functions, stubs, deprecated functions or block render
 * callbacks rather than the WordPress API, relative to the installation root.
 */
const EXCLUDED_PATHS = [
	'wp-includes/ID3/',
	'wp-includes/IXR/',
	'wp-includes/PHPMailer/',
	'wp-includes/Requests/',
	'wp-includes/SimplePie/',
	'wp-includes/Text/',
	'wp-includes/blocks/',
	'wp-includes/compat.php',
	'wp-includes/compat-utf8.php',
	'wp-includes/php-compat/',
	'wp-includes/spl-autoload-compat.php',
	'wp-includes/pomo/',
	'wp-includes/sodium_compat/',
	'wp-includes/deprecated.php',
	'wp-includes/ms-deprecated.php',
	'wp-includes/pluggable-deprecated.php',
	'wp-admin/includes/deprecated.php',
	'wp-admin/includes/ms-deprecated.php',
	'wp-admin/includes/noop.php',
];

/**
 * Collapse a run of tokens into source text with single spaces.
 *
 * @param array<int, array{0: int, 1: string}|string> $tokens Tokens.
 *
 * @return string
 */
function tokens_to_text( array $tokens ): string {
	$text = '';

	foreach ( $tokens as $token ) {
		if ( is_array( $token ) && in_array( $token[0], [ T_COMMENT, T_DOC_COMMENT ], true ) ) {
			continue;
		}

		$text .= is_array( $token ) ? $token[1] : $token;
	}

	return trim( preg_replace( '/\s+/', ' ', $text ) );
}

/**
 * Read the summary and relevant tags from a docblock.
 *
 * @param string $docblock Docblock comment.
 *
 * @return array{summary: string, private: bool, deprecated: bool}
 */
function parse_docblock( string $docblock ): array {
	$lines = preg_split( '/\R/', preg_replace( '#^/\*\*|\*/$#', '', $docblock ) );
	$summary = [];

	foreach ( $lines as $line ) {
		$line = trim( preg_replace( '/^\s*\*\s?/', '', $line ) );

		if ( '' === $line || '@' === $line[0] ) {
			if ( $summary ) {
				break;
			}

			continue;
		}

		$summary[] = $line;
	}

	$summary = implode( ' ', $summary );

	if ( strlen( $summary ) > MAX_SUMMARY_LENGTH ) {
		$summary = rtrim( substr( $summary, 0, MAX_SUMMARY_LENGTH - 1 ) ) . '…';
	}

	return [
		'summary'    => $summary,
		'private'    => (bool) preg_match( '/@access\s+private/', $docblock ),
		'deprecated' => (bool) preg_match( '/@deprecated\b/', $docblock ),
	];
}

/**
 * Find the functions declared at the top level of a file, outside any class or function body.
 *
 * @param string $filename PHP file.
 *
 * @return array<int, array{0: string, 1: string, 2: string}> Name, parameter list and summary of each public function.
 */
function find_functions( string $filename ): array {
	$tokens = token_get_all( file_get_contents( $filename ) );
	$count = count( $tokens );
	$functions = [];
	$blocks = [];
	$next_block = 'other';
	$docblock = '';

	for ( $i = 0; $i < $count; $i++ ) {
		$token = $tokens[ $i ];
		$type = is_array( $token ) ? $token[0] : $token;

		if ( T_DOC_COMMENT === $type ) {
			$docblock = $token[1];
			continue;
		}

		if ( in_array( $type, [ T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM ], true ) ) {
			$previous = $tokens[ $i - 1 ] ?? null;
			$is_class_constant = is_array( $previous ) && T_DOUBLE_COLON === $previous[0];

			if ( ! $is_class_constant ) {
				$next_block = 'class';
			}
		} elseif ( T_FUNCTION === $type ) {
			$j = $i + 1;
			while ( $j < $count && is_array( $tokens[ $j ] ) && T_WHITESPACE === $tokens[ $j ][0] ) {
				++$j;
			}

			$is_named = is_array( $tokens[ $j ] ) && T_STRING === $tokens[ $j ][0];

			if ( $is_named && ! in_array( 'class', $blocks, true ) && ! in_array( 'function', $blocks, true ) ) {
				$name = $tokens[ $j ][1];

				while ( '(' !== $tokens[ $j ] ) {
					++$j;
				}

				$depth = 0;
				$start = $j;

				do {
					if ( '(' === $tokens[ $j ] ) {
						++$depth;
					} elseif ( ')' === $tokens[ $j ] ) {
						--$depth;
					}

					++$j;
				} while ( $depth > 0 );

				$doc = parse_docblock( $docblock );

				if ( ! $doc['private'] && ! $doc['deprecated'] ) {
					$params = tokens_to_text( array_slice( $tokens, $start + 1, $j - $start - 2 ) );
					$functions[] = [ $name, '' === $params ? '()' : "( $params )", $doc['summary'] ];
				}
			}

			$next_block = 'function';
		} elseif ( '{' === $type || T_CURLY_OPEN === $type || T_DOLLAR_OPEN_CURLY_BRACES === $type ) {
			$blocks[] = '{' === $type ? $next_block : 'other';
			$next_block = 'other';
		} elseif ( '}' === $type ) {
			array_pop( $blocks );
		} elseif ( ';' === $type && 'function' === $next_block ) {
			$next_block = 'other';
		}

		if ( T_WHITESPACE !== $type && T_FUNCTION !== $type && T_STRING !== $type && T_ATTRIBUTE !== $type ) {
			$docblock = T_DOC_COMMENT === $type ? $docblock : '';
		}
	}

	return $functions;
}

/**
 * Describe a PHP function's parameters.
 *
 * @param ReflectionFunction $reflection Function.
 *
 * @return string
 */
function describe_parameters( ReflectionFunction $reflection ): string {
	$params = array_map(
		function ( ReflectionParameter $param ) {
			$text = $param->hasType() ? $param->getType() . ' ' : '';
			$text .= ( $param->isPassedByReference() ? '&' : '' ) . ( $param->isVariadic() ? '...' : '' ) . '$' . $param->getName();

			if ( $param->isOptional() && ! $param->isVariadic() ) {
				$text .= $param->isDefaultValueAvailable() && ! $param->isDefaultValueConstant()
					? ' = ' . preg_replace( '/\s+/', ' ', var_export( $param->getDefaultValue(), true ) ) // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export -- Formats a default value as PHP source.
					: ' = …';
			}

			return $text;
		},
		$reflection->getParameters()
	);

	return $params ? '( ' . implode( ', ', $params ) . ' )' : '()';
}

$wordpress_path = rtrim( $argv[1] ?? '', '/' ) . '/';

if ( ! is_file( $wordpress_path . 'wp-includes/version.php' ) ) {
	fwrite( STDERR, "Usage: php scripts/generate-function-index.php <path-to-wordpress>\n" );
	exit( 1 );
}

/**
 * Read the version of a WordPress installation.
 *
 * @param string $wordpress_path Installation root, with a trailing slash.
 *
 * @return string
 */
function read_wordpress_version( string $wordpress_path ): string {
	$wp_version = '';
	require $wordpress_path . 'wp-includes/version.php';
	return $wp_version;
}

$files = [];

foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $wordpress_path, FilesystemIterator::SKIP_DOTS ) ) as $file ) {
	$files[] = $file->getPathname();
}

// Where a function is declared more than once, the first declaration wins, so read files in a stable order.
sort( $files );

$wordpress_functions = [];

foreach ( $files as $filename ) {
	$relative = substr( $filename, strlen( $wordpress_path ) );
	$is_api_file = 0 === strpos( $relative, 'wp-includes/' ) || 0 === strpos( $relative, 'wp-admin/includes/' );

	if ( '.php' !== substr( $filename, -4 ) || ! $is_api_file ) {
		continue;
	}

	foreach ( EXCLUDED_PATHS as $excluded ) {
		if ( 0 === strpos( $relative, $excluded ) ) {
			continue 2;
		}
	}

	foreach ( find_functions( $filename ) as $function ) {
		$wordpress_functions[ $function[0] ] = $wordpress_functions[ $function[0] ] ?? $function;
	}
}

ksort( $wordpress_functions );

$php_functions = [];

foreach ( get_defined_functions()['internal'] as $name ) {
	$function = new ReflectionFunction( $name );

	if ( in_array( $function->getExtensionName(), PHP_EXTENSIONS, true ) && ! $function->isDeprecated() && ! isset( $wordpress_functions[ $name ] ) ) {
		$php_functions[ $name ] = [ $name, describe_parameters( $function ) ];
	}
}

ksort( $php_functions );

echo json_encode(
	[
		'wordpress' => read_wordpress_version( $wordpress_path ),
		'php'       => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
		'functions' => [
			'wordpress' => array_values( $wordpress_functions ),
			'php'       => array_values( $php_functions ),
		],
	],
	JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
), "\n";
