<?php

namespace Code_Snippets\Integration\Classic_Editor;

use function Code_Snippets\code_snippets;
use const Code_Snippets\PLUGIN_FILE;

/**
 * This class registers a custom TinyMCE plugin and button.
 *
 * @package Code_Snippets
 */
class MCE_Plugin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'setup_mce_plugin' ] );
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
				$plugins['code_snippets'] = plugins_url( 'dist/mce.js', PLUGIN_FILE );
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
}
