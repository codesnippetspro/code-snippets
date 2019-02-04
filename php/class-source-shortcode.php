<?php

namespace Code_Snippets;

/**
 * Class Code_Shortcode
 * @package Code_Snippets
 */
class Source_Shortcode {

	/**
	 * Name of the shortcode tag
	 */
	const SHORTCODE_TAG = 'code_snippet_source';

	/**
	 * Class constructor
	 */
	function __construct() {
		add_shortcode( self::SHORTCODE_TAG, array( $this, 'render_shortcode' ) );
		add_action( 'the_posts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue the syntax highlighting assets if they are required for the current posts
	 *
	 * @param array $posts
	 *
	 * @return array
	 */
	function enqueue_assets( $posts ) {

		if ( empty( $posts ) || Settings\get_setting( 'general', 'disable_prism' ) ) {
			return $posts;
		}

		$found = false;

		foreach ( $posts as $post ) {

			if ( false !== stripos( $post->post_content, '[' . self::SHORTCODE_TAG ) ) {
				$found = true;
				break;
			}
		}

		if ( ! $found ) {
			return $posts;
		}

		$plugin = code_snippets();

		wp_enqueue_style(
			'code-snippets-front-end',
			plugins_url( 'css/min/front-end.css', $plugin->file ),
			array(), $plugin->version
		);

		wp_enqueue_script(
			'code-snippets-front-end',
			plugins_url( 'js/min/front-end.js', $plugin->file ),
			array(), $plugin->version, true
		);

		return $posts;
	}

	/**
	 * Render the shortcode content
	 *
	 * @param array $atts
	 *
	 * @return string
	 */
	function render_shortcode( $atts ) {

		$atts = shortcode_atts(
			array(
				'id'           => 0,
				'network'      => false,
				'line_numbers' => false,
			),
			$atts, self::SHORTCODE_TAG
		);

		if ( ! $id = intval( $atts['id'] ) ) {
			return '';
		}

		$network = $atts['network'] ? true : false;
		$snippet = get_snippet( $id, $network );

		if ( ! trim( $snippet->code ) ) {
			return '';
		}

		$class = 'language-' . $snippet->lang;

		if ( $atts['line_numbers'] ) {
			$class .= ' line-numbers';
		}

		return sprintf( '<pre><code class="%s">%s</code></pre>', $class, esc_html( $snippet->code ) );
	}
}

