<?php

namespace Code_Snippets;

/**
 * Handles integration with the Gutenberg block editor.
 *
 * @package Code_Snippets
 */
class Block_Editor {

	/**
	 * Class constructor.
	 */
	public function __construct() {

		if ( function_exists( 'register_block_type' ) ) {
			add_action( 'init', array( $this, 'init' ) );
		}
	}

	/**
	 * Initialise the editor blocks.
	 */
	public function init() {
		$version = code_snippets()->version;
		$file = code_snippets()->file;

		wp_register_script(
			'code-snippets-content-block-editor',
			plugins_url( 'js/min/block.js', $file ),
			array( 'wp-blocks', 'wp-block-editor', 'wp-i18n', 'wp-components', 'wp-data', 'wp-element' ),
			$version
		);
		wp_set_script_translations( 'code-snippets-content-block-editor', 'code-snippets' );

		wp_register_style(
			'code-snippets-content-block-editor',
			plugins_url( 'css/min/block-editor.css', $file ),
			array(), $version
		);

		register_block_type( 'code-snippets/content', array(
			'editor_script'   => 'code-snippets-content-block-editor',
			'editor_style'    => 'code-snippets-content-block-editor',
			'render_callback' => array( $this, 'render_content' ),
		) );
	}

	/**
	 * Render the output of a content snippet block
	 *
	 * @param array  $atts          Block attributes.
	 * @param string $block_content Block content (should be empty).
	 *
	 * @return string Block output.
	 */
	public function render_content( $atts, $block_content ) {

		$atts = wp_parse_args( $atts, array(
			'snippet_id' => 0,
			'network'    => false,
			'php'        => false,
			'format'     => false,
			'shortcodes' => false,
		) );

		if ( ! $id = intval( $atts['snippet_id'] ) ) {
			return '';
		}

		$snippet = get_snippet( $id, $atts['network'] ? true : false );
		$content = $snippet->code;

		if ( $atts['php'] ) {
			ob_start();
			eval( "?>\n\n" . $snippet->code . "\n\n<?php" );
			$content = ob_get_clean();
		}

		if ( $atts['format'] ) {
			$functions = [ 'wptexturize', 'convert_smilies', 'convert_chars', 'wpautop', 'capital_P_dangit' ];
			foreach ( $functions as $function ) {
				$content = call_user_func( $function, $content );
			}
		}

		// evaluate shortcodes
		if ( $atts['shortcodes'] ) {
			$content = do_shortcode( $atts['format'] ? shortcode_unautop( $content ) : $content );
		}

		return $content;
	}
}
