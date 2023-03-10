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

		$this->process_import_files();
	}

	/**
	 * Process the uploaded import files
	 */
	private function process_import_files() {

		/* Ensure the import file exists */
		if ( ! isset(
			$_FILES['code_snippets_import_files']['name'],
			$_FILES['code_snippets_import_files']['type'],
			$_FILES['code_snippets_import_files']['tmp_name']
		) ) {
			return;
		}

		check_admin_referer( 'import_code_snippets_file' );

		$count = 0;
		$network = is_network_admin();
		$error = false;

		$upload_files = array_map( 'sanitize_text_field', wp_unslash( $_FILES['code_snippets_import_files']['tmp_name'] ) );
		$upload_filenames = array_map( 'sanitize_text_field', wp_unslash( $_FILES['code_snippets_import_files']['name'] ) );
		$upload_mime_types = array_map( 'sanitize_mime_type', wp_unslash( $_FILES['code_snippets_import_files']['type'] ) );

		$dup_action = isset( $_POST['duplicate_action'] ) ? sanitize_key( $_POST['duplicate_action'] ) : 'ignore';

		/* Loop through the uploaded files and import the snippets */

		foreach ( $upload_files as $i => $import_file ) {
			$ext = pathinfo( $upload_filenames[ $i ] );
			$ext = $ext['extension'];
			$mime_type = $upload_mime_types[ $i ];

			$import = new Import( $import_file, $network, $dup_action );

			if ( 'json' === $ext || 'application/json' === $mime_type ) {
				$result = $import->import_json();
			} elseif ( 'xml' === $ext || 'text/xml' === $mime_type ) {
				$result = $import->import_xml();
			} else {
				$result = false;
			}

			if ( false === $result ) {
				$error = true;
			} else {
				$count += count( $result );
			}
		}

		/* Send the amount of imported snippets to the page */
		$url = add_query_arg( $error ? array( 'error' => true ) : array( 'imported' => $count ) );
		wp_safe_redirect( esc_url_raw( $url ) );
		exit;
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
	 * Print the status and error messages
	 */
	protected function print_messages() {

		if ( ! empty( $_REQUEST['error'] ) ) {
			echo '<div id="message" class="error fade"><p>';
			esc_html_e( 'An error occurred when processing the import files.', 'code-snippets' );
			echo '</p></div>';
		}

		if ( ! empty( $_REQUEST['imported'] ) && intval( $_REQUEST['imported'] ) >= 0 ) {
			echo '<div id="message" class="updated fade"><p>';

			$imported = intval( $_REQUEST['imported'] );

			if ( 0 === $imported ) {
				esc_html_e( 'No snippets were imported.', 'code-snippets' );

			} else {
				/* translators: 1: amount of snippets imported, 2: link to Snippets menu */
				$text = _n(
					'Successfully imported <strong>%1$d</strong> snippet. <a href="%2$s">Have fun!</a>',
					'Successfully imported <strong>%1$d</strong> snippets. <a href="%2$s">Have fun!</a>',
					$imported,
					'code-snippets'
				);

				printf( wp_kses_post( $text ), esc_html( $imported ), esc_url( code_snippets()->get_menu_url( 'manage' ) ) );
			}

			echo '</p></div>';
		}
	}

	/**
	 * Empty implementation for enqueue_assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		// none required.
	}
}
