<?php

class Code_Snippets_Shortcode {

	function __construct() {
		add_shortcode( 'snippet', array( $this, 'render_shortcode' ) );
	}

	function render_shortcode( $atts ) {

		if ( ! isset( $atts['id'] ) || ! intval( $atts['id'] ) ) {
			return '';
		}

		$id = intval( $atts['id'] );
		$network = isset( $atts['network'] ) && $atts['network'] ? true : false;

		$snippet = get_snippet( $id, $network );

		return '<pre><code class="language-php">' . esc_html( $snippet->code ) . '</code></pre>';
	}
}