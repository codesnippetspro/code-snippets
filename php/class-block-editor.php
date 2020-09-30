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
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		add_action( 'init', array( $this, 'init' ) );
		add_filter( 'block_categories', array( $this, 'block_categories' ) );
	}

	/**
	 * Initialise the editor blocks.
	 */
	public function init() {
		$version = code_snippets()->version;
		$file = code_snippets()->file;

		wp_register_script(
			'code-snippets-block-editor',
			plugins_url( 'js/min/blocks.js', $file ),
			[
				'wp-blocks', 'wp-block-editor', 'wp-i18n', 'wp-components', 'wp-data', 'wp-element',
				'wp-api-fetch', 'wp-server-side-render', 'react-dom',
			],
			$version
		);
		wp_set_script_translations( 'code-snippets-content-block-editor', 'code-snippets' );

		wp_register_style(
			'code-snippets-block-editor',
			plugins_url( 'css/min/block-editor.css', $file ),
			array(), $version
		);

		register_block_type( 'code-snippets/content', array(
			'editor_script'   => 'code-snippets-block-editor',
			'editor_style'    => 'code-snippets-block-editor',
			'render_callback' => array( $this, 'render_content' ),
			'attributes'      => array(
				'snippet_id' => [ 'type' => 'integer', 'default' => 0 ],
				'network'    => [ 'type' => 'boolean', 'default' => false ],
				'php'        => [ 'type' => 'boolean', 'default' => false ],
				'format'     => [ 'type' => 'boolean', 'default' => false ],
				'shortcodes' => [ 'type' => 'boolean', 'default' => false ],
			),
		) );

		register_block_type( 'code-snippets/source', array(
			'editor_script'   => 'code-snippets-block-editor',
			'editor_style'    => 'code-snippets-block-editor',
			'render_callback' => array( $this, 'render_source' ),
			'attributes'      => array(
				'snippet_id' => [ 'type' => 'integer', 'default' => 0 ],
				'network'    => [ 'type' => 'boolean', 'default' => false ],
			),
		) );
	}

	/**
	 * Register a new block category for this plugin.
	 *
	 * @param array $categories Block categories.
	 *
	 * @return array Modified block categories.
	 */
	public function block_categories( $categories ) {
		$position = -1;

		foreach ( $categories as $index => $category ) {
			if ( 'widgets' === $category['slug'] ) {
				$position = $index;
				break;
			}
		}

		$category = array(
			'slug'  => 'code-snippets',
			'title' => __( 'Code Snippets', 'code-snippets' ),
			'icon'  => null,
		);

		array_splice( $categories, $position, 0, array( $category ) );

		return $categories;
	}

	/**
	 * Render the output of a content snippet block
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return string Block output.
	 */
	public function render_content( $attributes ) {
		return code_snippets()->shortcode->render_content_shortcode( $attributes );
	}

	/**
	 * Render the output of a snippet source block.
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return string Block output.
	 */
	public function render_source( $attributes ) {
		return code_snippets()->shortcode->render_source_shortcode( $attributes );
	}
}
