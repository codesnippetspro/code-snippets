<?php
/**
 * Imposter prefixes vendor namespaces in declarations, use statements and type
 * references, but leaves fully-qualified names embedded in string literals
 * untouched (for example dynamic class names passed to call_user_func_array).
 * Re-point those literals at the prefixed namespace so runtime lookups resolve.
 *
 * The affected namespaces are derived from the installed packages, so the fix
 * covers every prefixed package, and the rewrite is idempotent — already
 * prefixed literals are skipped, so it is safe on every autoload dump.
 *
 * @package Code_Snippets
 */

$src            = __DIR__ . '/../src';
$composer_file  = $src . '/composer.json';
$vendor_dir     = $src . '/vendor';
$installed_file = $vendor_dir . '/composer/installed.json';

if ( ! is_file( $composer_file ) || ! is_dir( $vendor_dir ) || ! is_file( $installed_file ) ) {
	return;
}

$composer = json_decode( file_get_contents( $composer_file ), true );
$prefix   = trim( (string) ( $composer['extra']['imposter']['namespace'] ?? '' ), '\\' );

if ( '' === $prefix ) {
	return;
}

// Original vendor roots come from each installed package's declared namespaces.
$installed = json_decode( file_get_contents( $installed_file ), true );
$packages  = $installed['packages'] ?? $installed;
$roots     = [];

foreach ( $packages as $package ) {
	foreach ( [ 'psr-4', 'psr-0' ] as $autoload_type ) {
		foreach ( array_keys( $package['autoload'][ $autoload_type ] ?? [] ) as $namespace ) {
			$namespace = trim( $namespace, '\\' );

			if ( '' !== $namespace ) {
				$roots[ $namespace ] = true;
			}
		}
	}
}

$roots = array_keys( $roots );

if ( ! $roots ) {
	return;
}

// Match deeper namespaces before their parents.
usort(
	$roots,
	static function ( $a, $b ) {
		return strlen( $b ) <=> strlen( $a );
	}
);

$prefix_segments = explode( '\\', $prefix );
$prefix_tail     = preg_quote( (string) end( $prefix_segments ), '#' );

$patterns     = [];
$replacements = [];

foreach ( $roots as $root ) {
	$segments = array_map(
		static function ( $segment ) {
			return preg_quote( $segment, '#' );
		},
		explode( '\\', $root )
	);

	// A whole leading backslash run then the root, when the preceding segment is not the prefix tail.
	$patterns[]     = '#(?<!\\\\)(?<!' . $prefix_tail . ')(\\\\+)' . implode( '\\\\+', $segments ) . '#';
	$replacements[] = '$1' . addcslashes( $prefix . '\\' . $root, '\\' );
}

// Composer's generated maps already carry the prefix and pair namespaces with byte lengths; leave them alone.
$skip_prefix = $vendor_dir . '/composer/';

$files = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator( $vendor_dir, FilesystemIterator::SKIP_DOTS )
);

foreach ( $files as $file ) {
	if ( ! $file->isFile() || 'php' !== strtolower( $file->getExtension() ) ) {
		continue;
	}

	if ( 0 === strpos( $file->getPathname(), $skip_prefix ) ) {
		continue;
	}

	$contents = file_get_contents( $file->getPathname() );
	$updated  = preg_replace( $patterns, $replacements, $contents );

	if ( null !== $updated && $updated !== $contents ) {
		file_put_contents( $file->getPathname(), $updated );
	}
}
