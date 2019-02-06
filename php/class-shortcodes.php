<?php

namespace Code_Snippets;

/**
 * This class manages the shortcodes included with the plugin
 * @package Code_Snippets
 */
class Shortcodes {

	/**
	 * Name of the shortcode tag for rendering the code source
	 */
	const SOURCE_TAG = 'code_snippet_source';

	/**
	 * Name of the shortcode tag for rendering content snippets
	 */
	const CONTENT_TAG = 'code_snippet';

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_shortcode( self::CONTENT_TAG, array( $this, 'render_content_shortcode' ) );
		add_shortcode( self::SOURCE_TAG, array( $this, 'render_source_shortcode' ) );
		add_action( 'the_posts', array( $this, 'enqueue_highlighting' ) );
	}

	/**
	 * Enqueue the syntax highlighting assets if they are required for the current posts
	 *
	 * @param array $posts
	 *
	 * @return array
	 */
	public function enqueue_highlighting( $posts ) {

		if ( empty( $posts ) || Settings\get_setting( 'general', 'disable_prism' ) ) {
			return $posts;
		}

		$found = false;

		foreach ( $posts as $post ) {

			if ( false !== stripos( $post->post_content, '[' . self::SOURCE_TAG ) ) {
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
	 * Render the value of a content shortcode
	 *
	 * @param array $atts
	 *
	 * @return string
	 */
	public function render_content_shortcode( $atts ) {

		$atts = shortcode_atts(
			array(
				'id'         => 0,
				'network'    => false,
				'php'        => false,
				'format'     => false,
				'shortcodes' => false,
			),
			$atts, self::CONTENT_TAG
		);

		if ( ! $id = intval( $atts['id'] ) ) {
			return '';
		}

		$snippet = get_snippet( $id, $atts['network'] ? true : false );

		// render the source code if this is not a shortcode snippet
		if ( 'shortcode' !== $snippet->scope ) {
			return $snippet->id ? $this->render_snippet_source( $snippet ) : '';
		}

		$content = $snippet->code;

		if ( $atts['php'] ) {
			ob_start();
			eval( "?>\n\n" . $snippet->code . "\n\n<?php" );
			$content = ob_get_clean();
		}

		if ( $atts['format'] ) {
			$functions = array( 'wptexturize', 'convert_smilies', 'convert_chars', 'wpautop', 'capital_P_dangit' );
			foreach ( $functions as $function ) {
				$content = call_user_func( $function, $content );
			}
		}

		if ( $atts['shortcodes'] ) {

			// remove this shortcode from the list to prevent recursion
			global $shortcode_tags;
			if ( isset( $shortcode_tags[ self::CONTENT_TAG ] ) ) {
				$backup = $shortcode_tags[ self::CONTENT_TAG ];
				unset( $shortcode_tags[ self::CONTENT_TAG ] );
			}

			// evaluate shortcodes
			if ( $atts['format'] ) {
				$content = shortcode_unautop( $content );
			}
			$content = do_shortcode( $content );

			// add this shortcode back to the list
			if ( isset( $backup ) ) {
				$shortcode_tags[ self::CONTENT_TAG ] = $backup;
			}
		}

		return $content;
	}

	/**
	 * Render the source code of a given snippet
	 *
	 * @param Snippet $snippet
	 * @param array   $atts
	 *
	 * @return string
	 */
	private function render_snippet_source( Snippet $snippet, $atts = [] ) {
		$atts = array_merge( [ 'line_numbers' => false ], $atts );

		if ( ! trim( $snippet->code ) ) {
			return '';
		}

		$class = 'language-' . $snippet->type;

		if ( $atts['line_numbers'] ) {
			$class .= ' line-numbers';
		}

		return sprintf(
			'<pre><code class="%s">%s</code></pre>',
			$class, esc_html( $snippet->code )
		);
	}

	/**
	 * Render the value of a source shortcode
	 *
	 * @param array $atts
	 *
	 * @return string
	 */
	public function render_source_shortcode( $atts ) {

		$atts = shortcode_atts(
			array(
				'id'           => 0,
				'network'      => false,
				'line_numbers' => false,
			),
			$atts, self::SOURCE_TAG
		);

		if ( ! $id = intval( $atts['id'] ) ) {
			return '';
		}

		$snippet = get_snippet( $id, $atts['network'] ? true : false );

		return $this->render_snippet_source( $snippet, $atts );
	}
}

