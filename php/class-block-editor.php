<?php

namespace Code_Snippets;

class Block_Editor {

	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	public function init() {
		$version = code_snippets()->version;
		$file = code_snippets()->file;

		wp_register_script(
			'code-snippets-content-block-editor',
			plugins_url( 'js/min/block.js', $file ),
			array( 'wp-blocks', 'wp-element', 'wp-i18n' ), $version
		);
		wp_set_script_translations( 'code-snippets-content-block-editor', 'code-snippets' );

		wp_register_style(
			'code-snippets-content-block-editor',
			plugins_url( 'css/min/block-editor.css', $file ),
			array(), $version
		);

		wp_register_style(
			'code-snippets-content-block',
			plugins_url( 'css/min/block-style.css', $file ),
			array(), $version
		);

		register_block_type( 'code-snippets/content', array(
			'editor_script' => 'code-snippets-content-block-editor',
			'editor_style'  => 'code-snippets-content-block-editor',
			'style'         => 'code-snippets-content-block',
		) );
	}

}
