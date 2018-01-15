<?php

/**
 * This class handles the import admin menu
 * @since 2.4.0
 * @package Code_Snippets
 */
class Code_Snippets_Import_Menu extends Code_Snippets_Admin_Menu {

	/**
	 * Class constructor
	 */
	function __construct() {
		parent::__construct( 'import',
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

		$contextual_help = new Code_Snippets_Contextual_Help( 'import' );
		$contextual_help->load();

		$this->process_import_files();
	}

	/**
	 * Process the uploaded import files
	 *
	 * @uses import_snippets() to process the import file
	 * @uses wp_redirect() to pass the import results to the page
	 * @uses add_query_arg() to append the results to the current URI
	 */
	private function process_import_files() {

		/* Ensure the import file exists */
		if ( ! isset( $_FILES['code_snippets_import_files'] ) || ! count( $_FILES['code_snippets_import_files'] ) ) {
			return;
		}

		$count = 0;
		$network = get_current_screen()->in_admin( 'network' );

		/* Loop through the uploaded files and import the snippets */

		foreach ( $_FILES['code_snippets_import_files']['tmp_name'] as $i => $import_file ) {

			if ( 'application/json' === $_FILES['code_snippets_import_files']['type'][ $i ] ) {
				$result = import_snippets_json( $import_file, $network );
			} else {
				$result = import_snippets_xml( $import_file, $network );
			}

			$count += count( $result );
		}

		/* Send the amount of imported snippets to the page */
		$url = add_query_arg( $count > 0 ? array( 'imported' => $count ) : array( 'error' => true ) );
		wp_redirect( esc_url_raw( $url ) );
		exit;
	}

	/**
	 * Add the importer to the Tools > Import menu
	 */
	function register_importer() {

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
	 * Print the status and error messages
	 */
	protected function print_messages() {
		if ( isset( $_REQUEST['imported'] ) ) {
			echo '<div id="message" class="updated fade"><p>';

			$imported = intval( $_REQUEST['imported'] );

			printf(
				_n(
					'Successfully imported <strong>%d</strong> snippet. <a href="%s">Have fun!</a>',
					'Successfully imported <strong>%d</strong> snippets. <a href="%s">Have fun!</a>',
					$imported, 'code-snippets'
				),
				$imported,
				code_snippets()->get_menu_url( 'manage' )
			);

			echo '</p></div>';

		} elseif ( isset( $_REQUEST['error'] ) && $_REQUEST['error'] ) {
			echo '<div id="message" class="error fade"><p>';
			_e( 'An error occurred when processing the import file.', 'code-snippets' );
			echo '</p></div>';
		}
	}
}
