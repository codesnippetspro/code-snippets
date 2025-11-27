<?php

namespace Code_Snippets;

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
	}

	/**
	 * Register action and filter hooks
	 */
	public function run() {
		parent::run();
		add_action( 'admin_init', array( $this, 'register_importer' ) );
		add_action( 'load-importer-code-snippets', array( $this, 'load' ) );
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

		/* Only register the importer if the current user can manage snippets */
		if ( ! defined( 'WP_LOAD_IMPORTERS' ) || ! code_snippets()->current_user_can() ) {
			return;
		}

		/* Register the Code Snippets importer with WordPress */
		register_importer(
			'code-snippets',
			__( 'Code Snippets', 'code-snippets' ),
			__( 'Import snippets from a code snippets export file', 'code-snippets' ),
			array( $this, 'render' )
		);
	}

	/**
	 * Enqueue assets for the import menu.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		$plugin = code_snippets();

		wp_enqueue_script(
			'code-snippets-import',
			plugins_url( 'dist/import.js', $plugin->file ),
			[
				'react',
				'react-dom',
				'wp-i18n',
				'wp-components',
			],
			$plugin->version,
			true
		);

		$plugin->localize_script( 'code-snippets-import' );
	}
}
