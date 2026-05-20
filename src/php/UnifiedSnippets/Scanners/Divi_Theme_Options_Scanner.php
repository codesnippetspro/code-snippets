<?php

namespace Code_Snippets\UnifiedSnippets\Scanners;

use Code_Snippets\UnifiedSnippets\Model\Discovered_Snippet;
use Code_Snippets\UnifiedSnippets\Scanner_Base;

/**
 * Scans Divi's Theme Options "Integration" tab and Custom CSS field.
 *
 * Divi (and its sibling theme Extra) ships five code-input fields under the Theme Options
 * panel: Custom CSS plus four "Integration" textareas that inject raw markup into the head,
 * footer, and the top/bottom of single posts. All five live in `wp_options` under either a
 * single `et_<shortname>` array (one-row mode) or per-key options like `et_<shortname>_custom_css`
 * (per-row mode). This scanner mirrors Divi's own `et_get_option()` read path so it works in
 * either layout, and surfaces each populated field as a {@see Discovered_Snippet}.
 *
 * The four Integration fields each have a companion enable toggle (e.g. `_integrate_header_enable`)
 * that Divi checks before emitting the code at runtime. We honour those toggles via `is_active`
 * so the Unified view reflects what is actually running.
 *
 * Wrapping logic for the post-scoped fields (which fire on the Divi-only `et_before_post` /
 * `et_after_post` hooks) is intentionally left to a later Phase 3 importer; the scanner stays
 * read-only and records the relevant caveat in `import_notes`.
 *
 * @package Code_Snippets
 */
class Divi_Theme_Options_Scanner extends Scanner_Base {

	/**
	 * Templates whose option storage this scanner understands.
	 */
	private const SUPPORTED_TEMPLATES = [ 'Divi', 'Extra' ];

	/**
	 * Fallback shortname when the active template is not Extra. Production only reaches this
	 * branch when running against Divi itself (the parent theme).
	 */
	private const DEFAULT_SHORTNAME = 'divi';

	/**
	 * {@inheritDoc}
	 */
	public function get_id(): string {
		return 'divi-theme-options';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_label(): string {
		return __( 'Divi Theme Options', 'code-snippets' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * Available only when Divi (or Extra) is the active theme template. We deliberately do
	 * not scan when Divi is merely installed: its hooks are not running, so the code is
	 * dormant and the namespaced option keys belong to an inactive theme.
	 */
	public function is_available(): bool {
		if ( ! function_exists( 'get_template' ) ) {
			return false;
		}

		return in_array( get_template(), self::SUPPORTED_TEMPLATES, true );
	}

	/**
	 * {@inheritDoc}
	 *
	 * Raw HTML/JS is injected unfiltered into every page (head/footer) or every single post
	 * (top/bottom); CSS overrides the active theme's styles. Medium captures that risk without
	 * implying that wp-config-level damage is possible.
	 */
	public function get_risk_level(): string {
		return 'medium';
	}

	/**
	 * {@inheritDoc}
	 */
	public function supports_import(): bool {
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function scan(): array {
		$shortname   = $this->resolve_shortname();
		$source_name = ucfirst( $shortname );

		$snippets = [
			$this->scan_custom_css( $shortname, $source_name ),
			$this->scan_integration_head( $shortname, $source_name ),
			$this->scan_integration_body( $shortname, $source_name ),
			$this->scan_integration_single_top( $shortname, $source_name ),
			$this->scan_integration_single_bottom( $shortname, $source_name ),
		];

		return array_values( array_filter( $snippets ) );
	}

	/**
	 * Resolve the Divi option shortname for the active template.
	 *
	 * @return string 'divi' or 'extra'.
	 */
	private function resolve_shortname(): string {
		if ( ! function_exists( 'get_template' ) ) {
			return self::DEFAULT_SHORTNAME;
		}

		$template = get_template();

		if ( 'Extra' === $template ) {
			return 'extra';
		}

		return self::DEFAULT_SHORTNAME;
	}

	/**
	 * Read a Divi option, mirroring `et_get_option()`'s two storage modes.
	 *
	 * Divi may store its theme options either as a single serialised array under `et_<shortname>`
	 * (one-row mode) or as individual options keyed by the full field id (per-row mode). The
	 * actual mode is decided per-install by `et_options_stored_in_one_row()`. Crucially, the
	 * field IDs in `options_divi.php` are already prefixed with the shortname (e.g.
	 * `divi_custom_css`, `divi_integration_head`), and that full id is what `et_get_option()`
	 * uses as both the array key (one-row) and the option name (per-row). Rather than load
	 * Divi's helper, we check both locations and return whichever yields a value; reads against
	 * an empty install harmlessly return ''.
	 *
	 * @param string $shortname The resolved Divi shortname (e.g. `divi`).
	 * @param string $key       Option key without the shortname prefix (e.g. `custom_css`).
	 *
	 * @return string The stored value, or '' when unset.
	 */
	private function read_option( string $shortname, string $key ): string {
		if ( ! function_exists( 'get_option' ) ) {
			return '';
		}

		$full_key = $shortname . '_' . $key;

		$bundle = get_option( 'et_' . $shortname );
		if ( is_array( $bundle ) && isset( $bundle[ $full_key ] ) && '' !== (string) $bundle[ $full_key ] ) {
			return (string) $bundle[ $full_key ];
		}

		$value = get_option( $full_key, '' );

		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * Read one of Divi's `_integrate_*_enable` toggles.
	 *
	 * Mirrors Divi's runtime check (see `integration_head()` in custom_functions.php), which
	 * emits code only when the toggle value is strictly equal to `'on'`. Other states the
	 * scanner may encounter:
	 *
	 *   - Stored as `'false'` (string) when the checkbox was explicitly unchecked at save.
	 *     Divi's save handler writes the literal string `'false'` for unchecked checkboxes
	 *     (see `epanel_save_data()`).
	 *   - Missing entirely on a never-saved install. `et_get_option()` returns boolean false
	 *     in that case, and `false === 'on'` is also false, so Divi does NOT emit. We match
	 *     that here — the checkbox `std => 'on'` in options_divi.php controls the admin UI's
	 *     default-checked state, not the runtime default.
	 *
	 * @param string $shortname The resolved Divi shortname.
	 * @param string $key       Toggle key without the `<shortname>_` prefix.
	 *
	 * @return bool
	 */
	private function read_toggle( string $shortname, string $key ): bool {
		return 'on' === $this->read_option( $shortname, $key );
	}

	/**
	 * Build the Custom CSS snippet, if populated.
	 *
	 * @param string $shortname   The Divi option shortname.
	 * @param string $source_name Human-readable theme name.
	 *
	 * @return Discovered_Snippet|null
	 */
	private function scan_custom_css( string $shortname, string $source_name ): ?Discovered_Snippet {
		$code = $this->read_option( $shortname, 'custom_css' );

		if ( '' === trim( $code ) ) {
			return null;
		}

		return $this->build_field_snippet(
			[
				'name'         => sprintf(
					/* translators: %s: theme name (e.g. Divi). */
					__( '%s Custom CSS', 'code-snippets' ),
					$source_name
				),
				'code'         => $code,
				'type'         => 'css',
				'source_name'  => $source_name,
				'source_path'  => 'divi://theme-options/custom_css',
				'is_active'    => true,
				'import_notes' => __( 'Imports cleanly as a site-wide CSS snippet (scope: site-css).', 'code-snippets' ),
			]
		);
	}

	/**
	 * Build the Integration > Head snippet, if populated.
	 *
	 * @param string $shortname   The Divi option shortname.
	 * @param string $source_name Human-readable theme name.
	 *
	 * @return Discovered_Snippet|null
	 */
	private function scan_integration_head( string $shortname, string $source_name ): ?Discovered_Snippet {
		$code = $this->read_option( $shortname, 'integration_head' );

		if ( '' === trim( $code ) ) {
			return null;
		}

		return $this->build_field_snippet(
			[
				'name'         => sprintf(
					/* translators: %s: theme name (e.g. Divi). */
					__( '%s Head Code', 'code-snippets' ),
					$source_name
				),
				'code'         => $code,
				'type'         => 'html',
				'source_name'  => $source_name,
				'source_path'  => 'divi://theme-options/integration_head',
				'is_active'    => $this->read_toggle( $shortname, 'integrate_header_enable' ),
				'import_notes' => __( 'Divi renders this on wp_head at priority 12. Importing as head-content runs it on wp_head at priority 10, so the imported snippet executes slightly earlier than Divi did. Usually harmless, but may matter for order-sensitive scripts.', 'code-snippets' ),
			]
		);
	}

	/**
	 * Build the Integration > Body snippet, if populated.
	 *
	 * Despite Divi's UI labelling this "body" code, the theme actually emits the content on
	 * `wp_footer` at priority 12 (see Divi/epanel/custom_functions.php). Importing into
	 * Code Snippets' `footer-content` scope (also wp_footer, default priority 10 per
	 * `Evaluate_Content::init()`) reproduces the runtime behaviour, just executed slightly
	 * earlier. The priority delta is surfaced in `import_notes` for users with order-sensitive
	 * scripts.
	 *
	 * @param string $shortname   The Divi option shortname.
	 * @param string $source_name Human-readable theme name.
	 *
	 * @return Discovered_Snippet|null
	 */
	private function scan_integration_body( string $shortname, string $source_name ): ?Discovered_Snippet {
		$code = $this->read_option( $shortname, 'integration_body' );

		if ( '' === trim( $code ) ) {
			return null;
		}

		return $this->build_field_snippet(
			[
				'name'         => sprintf(
					/* translators: %s: theme name (e.g. Divi). */
					__( '%s Body Code', 'code-snippets' ),
					$source_name
				),
				'code'         => $code,
				'type'         => 'html',
				'source_name'  => $source_name,
				'source_path'  => 'divi://theme-options/integration_body',
				'is_active'    => $this->read_toggle( $shortname, 'integrate_body_enable' ),
				'import_notes' => __( 'Divi renders this on wp_footer at priority 12. Importing as footer-content runs it on wp_footer at priority 10, so the imported snippet executes slightly earlier than Divi did. Usually harmless, but may matter for order-sensitive scripts.', 'code-snippets' ),
			]
		);
	}

	/**
	 * Build the Integration > Single Top snippet, if populated.
	 *
	 * @param string $shortname   The Divi option shortname.
	 * @param string $source_name Human-readable theme name.
	 *
	 * @return Discovered_Snippet|null
	 */
	private function scan_integration_single_top( string $shortname, string $source_name ): ?Discovered_Snippet {
		$code = $this->read_option( $shortname, 'integration_single_top' );

		if ( '' === trim( $code ) ) {
			return null;
		}

		return $this->build_field_snippet(
			[
				'name'         => sprintf(
					/* translators: %s: theme name (e.g. Divi). */
					__( '%s Single Top Code', 'code-snippets' ),
					$source_name
				),
				'code'         => $code,
				'type'         => 'html',
				'source_name'  => $source_name,
				'source_path'  => 'divi://theme-options/integration_single_top',
				'is_active'    => $this->read_toggle( $shortname, 'integrate_singletop_enable' ),
				'import_notes' => __( 'Fires on Divi\'s et_before_post hook. Import wraps the code in an add_action() targeting that hook, so the snippet will only run while the Divi theme is active.', 'code-snippets' ),
			]
		);
	}

	/**
	 * Build the Integration > Single Bottom snippet, if populated.
	 *
	 * @param string $shortname   The Divi option shortname.
	 * @param string $source_name Human-readable theme name.
	 *
	 * @return Discovered_Snippet|null
	 */
	private function scan_integration_single_bottom( string $shortname, string $source_name ): ?Discovered_Snippet {
		$code = $this->read_option( $shortname, 'integration_single_bottom' );

		if ( '' === trim( $code ) ) {
			return null;
		}

		return $this->build_field_snippet(
			[
				'name'         => sprintf(
					/* translators: %s: theme name (e.g. Divi). */
					__( '%s Single Bottom Code', 'code-snippets' ),
					$source_name
				),
				'code'         => $code,
				'type'         => 'html',
				'source_name'  => $source_name,
				'source_path'  => 'divi://theme-options/integration_single_bottom',
				'is_active'    => $this->read_toggle( $shortname, 'integrate_singlebottom_enable' ),
				'import_notes' => __( 'Fires on Divi\'s et_after_post hook. Import wraps the code in an add_action() targeting that hook, so the snippet will only run while the Divi theme is active.', 'code-snippets' ),
			]
		);
	}

	/**
	 * Common field defaults applied to every Divi snippet before delegating to build_snippet().
	 *
	 * @param array<string, mixed> $fields Per-field overrides.
	 *
	 * @return Discovered_Snippet
	 */
	private function build_field_snippet( array $fields ): Discovered_Snippet {
		return $this->build_snippet(
			array_merge(
				[
					'source_type' => 'theme',
					'line_start'  => 0,
					'line_end'    => 0,
				],
				$fields
			)
		);
	}
}
