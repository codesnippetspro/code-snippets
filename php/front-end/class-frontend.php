<?php

namespace Code_Snippets;

/**
 * This class manages the shortcodes included with the plugin
 *
 * @package Code_Snippets
 */
class Frontend {

	/**
	 * Name of the shortcode tag for rendering the code source
	 */
	const SOURCE_SHORTCODE = 'code_snippet_source';

	/**
	 * Name of the shortcode tag for rendering content snippets
	 */
	const CONTENT_SHORTCODE = 'code_snippet';

	/**
	 * Handle to use for front-end scripts and styles.
	 */
	const PRISM_HANDLE = 'code-snippets-prism';

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action( 'the_posts', [ $this, 'enqueue_highlighting' ] );
		add_action( 'init', [ $this, 'setup_mce_plugin' ] );

		add_shortcode( self::CONTENT_SHORTCODE, [ $this, 'render_content_shortcode' ] );
		add_shortcode( self::SOURCE_SHORTCODE, [ $this, 'render_source_shortcode' ] );
	}

	/**
	 * Perform the necessary actions to add a button to the TinyMCE editor
	 */
	public function setup_mce_plugin() {
		if ( ! code_snippets()->current_user_can() ) {
			return;
		}

		/* Register the TinyMCE plugin */
		add_filter(
			'mce_external_plugins',
			function ( $plugins ) {
				$plugins['code_snippets'] = plugins_url( 'js/min/mce.js', PLUGIN_FILE );
				return $plugins;
			}
		);

		/* Add the button to the editor toolbar */
		add_filter(
			'mce_buttons',
			function ( $buttons ) {
				$buttons[] = 'code_snippets';
				return $buttons;
			}
		);

		/* Add the translation strings to the TinyMCE editor */
		add_filter(
			'mce_external_languages',
			function ( $languages ) {
				$languages['code_snippets'] = __DIR__ . '/mce-strings.php';
				return $languages;
			}
		);
	}

	/**
	 * Enqueue the syntax highlighting assets if they are required for the current posts
	 *
	 * @param array $posts List of currently visible posts.
	 *
	 * @return array Unchanged list of posts.
	 */
	public function enqueue_highlighting( $posts ) {

		// Exit early if there are no posts to check or if the highlighter has been disabled.
		if ( empty( $posts ) || Settings\get_setting( 'general', 'disable_prism' ) ) {
			return $posts;
		}

		// Loop through all the posts, checking for instances where the source tag is used.
		foreach ( $posts as $post ) {

			if ( false === stripos( $post->post_content, '[' . self::SOURCE_SHORTCODE ) &&
			     ! ( function_exists( 'has_block' ) && has_block( 'code-snippets/source', $post ) ) ) {
				continue;
			}

			// Register the syntax highlighter assets and exit from the loop once a match is discovered.
			$this->register_prism_assets();
			wp_enqueue_style( self::PRISM_HANDLE );
			wp_enqueue_script( self::PRISM_HANDLE );
			break;
		}

		return $posts;
	}

	/**
	 * Enqueue the styles and scripts for the Prism syntax highlighter.
	 */
	public static function register_prism_assets() {
		$plugin = code_snippets();

		wp_register_style(
			self::PRISM_HANDLE,
			plugins_url( 'css/min/prism.css', $plugin->file ),
			array(),
			$plugin->version
		);

		wp_enqueue_script(
			self::PRISM_HANDLE,
			plugins_url( 'js/min/prism.js', $plugin->file ),
			array(),
			$plugin->version,
			true
		);
	}

	/**
	 * Print a message to the user if the snippet ID attribute is invalid.
	 *
	 * @param int $id Snippet ID.
	 *
	 * @return string Warning message.
	 */
	private function invalid_id_warning( $id ) {
		/* translators: %d: snippet ID */
		$text = esc_html__( 'Could not load snippet with an invalid ID: %d.', 'code-snippets' );
		return current_user_can( 'edit_posts' ) ? sprintf( $text, intval( $id ) ) : '';
	}

	/**
	 * Render the value of a content shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string Shortcode content.
	 */
	public function render_content_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'         => 0,
				'snippet_id' => 0,
				'network'    => false,
				'php'        => false,
				'format'     => false,
				'shortcodes' => false,
				'debug'      => false,
			),
			$atts,
			self::CONTENT_SHORTCODE
		);

		$id = intval( $atts['snippet_id'] ) ?: intval( $atts['id'] );
		if ( ! $id ) {
			return $this->invalid_id_warning( $id );
		}

		$snippet = get_snippet( $id, (bool) $atts['network'] );

		// Render the source code if this is not a shortcode snippet.
		if ( 'content' !== $snippet->scope ) {
			return $snippet->id ? $this->render_snippet_source( $snippet ) : '';
		}

		// If the snippet is inactive, either display a message or render nothing.
		if ( ! $snippet->active ) {
			if ( ! $atts['debug'] ) {
				return '';
			}

			/* translators: 1: snippet name, 2: snippet edit link */
			$text = __( '<strong>%1$s</strong> is currently inactive. You can <a href="%2$s">edit this snippet</a> to activate it and make it visible. This message will not appear in the published post.', 'code-snippets' );

			$edit_url = add_query_arg( 'id', $snippet->id, code_snippets()->get_menu_url( 'edit' ) );
			return wp_kses(
				sprintf( $text, $snippet->name, $edit_url ),
				[
					'strong' => [],
					'a'      => [
						'href' => [],
					],
				]
			);
		}

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

		if ( $atts['shortcodes'] ) {
			// Remove this shortcode from the list to prevent recursion.
			remove_shortcode( self::CONTENT_SHORTCODE );

			// Evaluate shortcodes.
			$content = do_shortcode( $atts['format'] ? shortcode_unautop( $content ) : $content );

			// Add this shortcode back to the list.
			add_shortcode( self::CONTENT_SHORTCODE, [ $this, 'render_content_shortcode' ] );
		}

		return $content;
	}

	/**
	 * Render the source code of a given snippet
	 *
	 * @param Snippet $snippet Snippet object.
	 * @param array   $atts    Shortcode attributes.
	 *
	 * @return string Shortcode content.
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
			$class,
			esc_html( $snippet->code )
		);
	}

	/**
	 * Render the value of a source shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string Shortcode content.
	 */
	public function render_source_shortcode( $atts ) {

		$atts = shortcode_atts(
			array(
				'id'           => 0,
				'snippet_id'   => 0,
				'network'      => false,
				'line_numbers' => false,
			),
			$atts,
			self::SOURCE_SHORTCODE
		);

		$id = intval( $atts['snippet_id'] ) ?: intval( $atts['id'] );
		if ( ! $id ) {
			return $this->invalid_id_warning( $id );
		}

		$snippet = get_snippet( $id, (bool) $atts['network'] );

		return $this->render_snippet_source( $snippet, $atts );
	}
}

