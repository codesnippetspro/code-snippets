<?php

namespace Code_Snippets\UnifiedSnippets\Scanners;

use Code_Snippets\UnifiedSnippets\Scanner_Base;
use WP_Post;

/**
 * Scans the WordPress Customizer "Additional CSS" for the active theme.
 *
 * @package Code_Snippets
 */
class Additional_CSS_Scanner extends Scanner_Base {

	/**
	 * {@inheritDoc}
	 */
	public function get_id(): string {
		return 'additional-css';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_label(): string {
		return __( 'Additional CSS (Customizer)', 'code-snippets' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function is_available(): bool {
		return function_exists( 'wp_get_custom_css_post' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function scan(): array {
		$post = wp_get_custom_css_post();

		if ( ! $post instanceof WP_Post || '' === trim( (string) $post->post_content ) ) {
			return [];
		}

		$stylesheet  = get_stylesheet();
		$theme_name  = function_exists( 'wp_get_theme' ) ? wp_get_theme()->get( 'Name' ) : $stylesheet;

		return [
			$this->build_snippet(
				[
					'name'        => sprintf(
						/* translators: %s: theme name */
						__( 'Additional CSS (%s)', 'code-snippets' ),
						$theme_name
					),
					'code'        => $post->post_content,
					'type'        => 'css',
					'source_type' => 'customizer',
					'source_name' => $theme_name,
					'source_path' => 'customizer://custom_css/' . $stylesheet,
					'line_start'  => 0,
					'line_end'    => 0,
					'is_active'   => true,
				]
			),
		];
	}
}
