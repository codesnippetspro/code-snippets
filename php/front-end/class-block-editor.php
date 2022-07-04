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
		add_filter( 'block_categories_all', array( $this, 'block_categories' ) );
	}

	/**
	 * Initialise the editor blocks.
	 */
	public function init() {
		$version = code_snippets()->version;
		$file = code_snippets()->file;
		$handle = 'code-snippets-block-editor';

		$prism_dep = [];
		if ( ! Settings\get_setting( 'general', 'disable_prism' ) ) {
			Frontend::register_prism_assets();
			$prism_dep = [ Frontend::PRISM_HANDLE ];
		}

		wp_register_script(
			$handle,
			plugins_url( 'js/min/blocks.js', $file ),
			array_merge(
				$prism_dep,
				array(
					'wp-blocks',
					'wp-block-editor',
					'wp-i18n',
					'wp-components',
					'wp-data',
					'wp-element',
					'wp-api-fetch',
					'wp-server-side-render',
					'react-dom',
				)
			),
			$version,
			false
		);

		wp_set_script_translations( $handle, 'code-snippets' );

		wp_register_style(
			$handle,
			plugins_url( 'css/min/block-editor.css', $file ),
			$prism_dep,
			$version,
			false
		);

		register_block_type(
			'code-snippets/content',
			array(
				'editor_script'   => $handle,
				'editor_style'    => $handle,
				'render_callback' => array( $this, 'render_content' ),
				'supports'        => array(
					'className' => true,
				),
				'attributes'      => array(
					'snippet_id' => [
						'type'    => 'integer',
						'default' => 0,
					],
					'network'    => [
						'type'    => 'boolean',
						'default' => false,
					],
					'php'        => [
						'type'    => 'boolean',
						'default' => false,
					],
					'format'     => [
						'type'    => 'boolean',
						'default' => false,
					],
					'shortcodes' => [
						'type'    => 'boolean',
						'default' => false,
					],
					'debug'      => [
						'type'    => 'boolean',
						'default' => false,
					],
				),
			)
		);

		register_block_type(
			'code-snippets/source',
			array(
				'editor_script'   => $handle,
				'editor_style'    => $handle,
				'render_callback' => array( $this, 'render_source' ),
				'supports'        => array(
					'className'       => true,
					'customClassName' => true,
					'color'           => true,
				),
				'attributes'      => array(
					'snippet_id'   => [
						'type'    => 'integer',
						'default' => 0,
					],
					'network'      => [
						'type'    => 'boolean',
						'default' => false,
					],
					'line_numbers' => [
						'type'    => 'boolean',
						'default' => true,
					],
					'theme'        => [
						'type'    => 'string',
						'default' => 'default',
					],
				),
			)
		);

		$prism_themes = [
			'dark'           => __( 'Dark', 'code-snippets' ),
			'funky'          => __( 'Funky', 'code-snippets' ),
			'okaidia'        => __( 'Okaidia', 'code-snippets' ),
			'twilight'       => __( 'Twilight', 'code-snippets' ),
			'coy'            => __( 'Coy', 'code-snippets' ),
			'solarizedlight' => __( 'Solarized Light', 'code-snippets' ),
			'tomorrow'       => __( 'Tomorrow Night', 'code-snippets' ),
		];

		foreach ( $prism_themes as $theme => $label ) {
			$style_handle = "code-snippets-prism-theme-$theme";

			wp_register_style(
				$style_handle,
				plugins_url( "css/min/prism-themes/prism-$theme.css", $file ),
				[ Frontend::PRISM_HANDLE ],
				$version
			);

			register_block_style(
				'code-snippets/source',
				[
					'name'         => "prism-$theme",
					'label'        => $label,
					'style_handle' => $style_handle,
				]
			);
		}
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
		return sprintf(
			"<div %s>%s</div>",
			get_block_wrapper_attributes(),
			code_snippets()->frontend->render_content_shortcode( $attributes )
		);
	}

	/**
	 * Render the output of a snippet source block.
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return string Block output.
	 */
	public function render_source( $attributes ) {
		return sprintf(
			"<div %s>%s</div>",
			get_block_wrapper_attributes(),
			code_snippets()->frontend->render_source_shortcode( $attributes )
		);
	}
}
