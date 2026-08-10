<?php

namespace Code_Snippets\Admin\Menus;

use Code_Snippets\Admin\Contextual_Help;
use function Code_Snippets\code_snippets;
use const Code_Snippets\PLUGIN_FILE;
use const Code_Snippets\PLUGIN_VERSION;

/**
 * This class handles the import admin menu.
 *
 * @since   2.4.0
 * @package Code_Snippets
 */
class Import_Menu extends Admin_Menu {

	/**
	 * Class constructor
	 */
	public function __construct() {
		parent::__construct(
			'import',
			_x( 'Import', 'menu label', 'code-snippets' ),
			__( 'Import Snippets', 'code-snippets' )
		);

		add_action( 'admin_init', [ $this, 'register_importer' ] );
		add_action( 'load-importer-code-snippets', [ $this, 'load' ] );
	}

	/**
	 * Executed when the menu is loaded
	 */
	public function load() {
		parent::load();

		$contextual_help = new Contextual_Help( 'import' );
		$contextual_help->load();
	}

	/**
	 * Add the importer to the Tools > Import menu
	 */
	public function register_importer() {
		if ( ! defined( 'WP_LOAD_IMPORTERS' ) || ! code_snippets()->current_user_can() ) {
			return;
		}

		register_importer(
			'code-snippets',
			__( 'Code Snippets', 'code-snippets' ),
			__( 'Import snippets from a code snippets export file', 'code-snippets' ),
			[ $this, 'render' ]
		);
	}

	/**
	 * Empty implementation for enqueue_assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		wp_enqueue_script(
			'code-snippets-import',
			plugins_url( 'dist/import.js', PLUGIN_FILE ),
			[
				'react',
				'react-dom',
				'react-jsx-runtime',
				'wp-i18n',
				'wp-components',
			],
			PLUGIN_VERSION,
			true
		);

		wp_enqueue_style(
			'code-snippets-import',
			plugins_url( 'dist/import.css', PLUGIN_FILE ),
			[],
			PLUGIN_VERSION
		);

		code_snippets()->localize_script( 'code-snippets-import' );
	}

	/**
	 * Render Import menu UI.
	 *
	 * @return void
	 */
	public function render() {
		echo '<div id="import-container" class="wrap"></div>';
	}
}
