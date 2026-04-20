<?php

namespace Code_Snippets\UnifiedSnippets\Scanners;

use Code_Snippets\UnifiedSnippets\Filesystem_Reader;
use Code_Snippets\UnifiedSnippets\Scanner_Base;

/**
 * Scans the mu-plugins directory. Each mu-plugin file becomes a single
 * Discovered_Snippet with the full file contents.
 *
 * @package Code_Snippets
 */
class Mu_Plugins_Scanner extends Scanner_Base {

	/**
	 * Absolute path to the mu-plugins directory.
	 *
	 * @var string
	 */
	private string $directory;

	/**
	 * Class constructor.
	 *
	 * @param string|null $directory Optional directory override. Defaults to WPMU_PLUGIN_DIR.
	 */
	public function __construct( ?string $directory = null ) {
		$default         = defined( 'WPMU_PLUGIN_DIR' ) ? WPMU_PLUGIN_DIR : '';
		$this->directory = $directory ?? $default;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_id(): string {
		return 'mu-plugins';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_label(): string {
		return __( 'Must-Use Plugins', 'code-snippets' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function is_available(): bool {
		return '' !== $this->directory && is_dir( $this->directory );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_risk_level(): string {
		return 'medium';
	}

	/**
	 * {@inheritDoc}
	 */
	public function scan(): array {
		if ( ! $this->is_available() ) {
			return [];
		}

		$mu_plugins = $this->collect_mu_plugins();
		$snippets   = [];

		foreach ( $mu_plugins as $file => $data ) {
			$path = trailingslashit( $this->directory ) . $file;

			if ( ! Filesystem_Reader::is_readable( $path ) ) {
				continue;
			}

			$code = Filesystem_Reader::get_contents( $path );

			if ( null === $code ) {
				continue;
			}

			$line_end = max( 1, substr_count( $code, "\n" ) + 1 );
			$name     = ! empty( $data['Name'] ) ? $data['Name'] : $file;

			$snippets[] = $this->build_snippet(
				[
					'name'        => $name,
					'code'        => $code,
					'type'        => 'php',
					'source_type' => 'mu-plugin',
					'source_name' => $name,
					'source_path' => $path,
					'line_start'  => 1,
					'line_end'    => $line_end,
					'is_active'   => true,
				]
			);
		}

		return $snippets;
	}

	/**
	 * Collect mu-plugin metadata from the configured directory.
	 *
	 * @return array<string, array<string, string>>
	 */
	private function collect_mu_plugins(): array {
		if ( function_exists( 'get_mu_plugins' ) && defined( 'WPMU_PLUGIN_DIR' ) && WPMU_PLUGIN_DIR === $this->directory ) {
			return get_mu_plugins();
		}

		if ( defined( 'ABSPATH' ) && ! function_exists( 'get_plugin_data' ) ) {
			$plugin_includes = ABSPATH . 'wp-admin/includes/plugin.php';
			if ( is_readable( $plugin_includes ) ) {
				require_once $plugin_includes;
			}
		}

		$results = [];

		foreach ( (array) glob( trailingslashit( $this->directory ) . '*.php' ) as $path ) {
			if ( ! is_file( $path ) ) {
				continue;
			}

			$file = basename( $path );
			$data = function_exists( 'get_plugin_data' )
				? get_plugin_data( $path, false, false )
				: [ 'Name' => $file ];

			$results[ $file ] = $data;
		}

		return $results;
	}
}
