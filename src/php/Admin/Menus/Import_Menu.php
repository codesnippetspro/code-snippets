<?php

namespace Code_Snippets\Admin\Menus;

use Code_Snippets\Admin\Contextual_Help;
use Code_Snippets\Export\Import;
use function Code_Snippets\code_snippets;

/**
 * This class handles the import admin menu.
 *
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
	 * Empty implementation for enqueue_assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		// none required.
	}

	/**
	 * Process the uploaded import files
	 */
	private function process_import_files() {

		// Ensure the import file exists.
		if ( ! isset(
			$_FILES['code_snippets_import_files']['name'],
			$_FILES['code_snippets_import_files']['type'],
			$_FILES['code_snippets_import_files']['tmp_name']
		) ) {
			return;
		}

		check_admin_referer( 'import_code_snippets_file' );

		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$upload_files = $_FILES['code_snippets_import_files']['tmp_name'];
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$upload_filenames = $_FILES['code_snippets_import_files']['name'];
		$upload_mime_types = array_map( 'sanitize_mime_type', wp_unslash( $_FILES['code_snippets_import_files']['type'] ) );

		$count = 0;
		$network = is_network_admin();
		$error = false;
		$dup_action = isset( $_POST['duplicate_action'] ) ? sanitize_key( $_POST['duplicate_action'] ) : 'ignore';

		// Loop through the uploaded files and import the snippets.
		foreach ( $upload_files as $i => $import_file ) {
			$filename_info = pathinfo( $upload_filenames[ $i ] );
			$ext = $filename_info['extension'];
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

		// Send the amount of imported snippets to the page.
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
			echo '<div class="notice notice-error"><p>';
			esc_html_e( 'An error occurred when processing the import files.', 'code-snippets' );
			echo '</p></div>';
		}

		if ( isset( $_REQUEST['imported'] ) ) {
			echo '<div class="notice notice-success"><p>';

			$imported = intval( $_REQUEST['imported'] );

			if ( 0 === $imported ) {
				esc_html_e( 'No snippets were imported.', 'code-snippets' );

			} else {
				/* translators: %d: amount of snippets imported */
				$text = _n( 'Successfully imported %d snippet.', 'Successfully imported %d snippets.', $imported, 'code-snippets' );
				printf(
					esc_html( $text ),
					'<strong>' . esc_html( number_format_i18n( $imported ) ) . '</strong>',
				);

				printf(
					' <a href="%s">%s</a>',
					esc_url( code_snippets()->get_menu_url( 'manage' ) ),
					esc_html__( 'Have fun!', 'code-snippets' )
				);
			}

			echo '</p></div>';
		}
	}

	/**
	 * Render the page title.
	 */
	private function render_page_title() {
		echo '<h1>';
		esc_html_e( 'Import Snippets', 'code-snippets' );

		if ( code_snippets()->is_compact_menu() ) {
			foreach ( $this->page_title_action_links( [ 'manage', 'add', 'settings' ] ) as $label => $url ) {
				printf( '<a href="%s" class="page-title-action">%s</a>', esc_url( $url ), esc_html( $label ) );
			}
		}

		echo '</h1>';
	}

	/**
	 * Render the introduction text for the import form.
	 *
	 * @return void
	 */
	private function render_form_introduction() {
		echo '<p>', esc_html__( 'Upload one or more Code Snippets export files and the snippets will be imported.', 'code-snippets' ), '</p>';

		echo '<p>';
		/* translators: %s: link to snippets admin menu */
		$text = __( 'Afterward, you will need to visit the <a href="%s" >All Snippets</a> page to activate the imported snippets.', 'code-snippets' );
		$url = esc_url( code_snippets()->get_menu_url( 'manage' ) );

		echo wp_kses(
			sprintf( $text, $url ),
			[
				'a' => [
					'href'   => [],
					'target' => [],
				],
			]
		);

		echo '</p>';
	}

	/**
	 * Render the options for handling duplicate snippets during import.
	 *
	 * @return void
	 */
	private function render_duplicate_snippets_options() {
		echo '<h2>', esc_html__( 'Duplicate Snippets', 'code-snippets' ), '</h2>';

		echo '<p class="description">',
		esc_html__( 'What should happen if an existing snippet is found with an identical name to an imported snippet?', 'code-snippets' ),
		'</p>';

		$options = [
			'ignore'  => __( 'Ignore any duplicate snippets: import all snippets from the file regardless and leave all existing snippets unchanged.', 'code-snippets' ),
			'replace' => __( 'Replace any existing snippets with a newly imported snippet of the same name.', 'code-snippets' ),
			'skip'    => __( 'Do not import any duplicate snippets; leave all existing snippets unchanged.', 'code-snippets' ),
		];

		echo '<fieldset>';

		foreach ( $options as $value => $label ) {
			printf(
				'<p><label><input type="radio" name="duplicate_action" value="%s"%s>%s</label></p>',
				esc_attr( $value ),
				checked( 'ignore', $value, false ),
				esc_html( $label )
			);
		}

		echo '</fieldset>';
	}

	/**
	 * Render the import file uploader.
	 *
	 * @return void
	 */
	private function render_file_upload() {
		echo '<h2>', esc_html__( 'Upload Files', 'code-snippets' ), '</h2>';

		echo '<p class="description">',
		esc_html__( 'Choose one or more Code Snippets (.xml or .json) files to upload, then click "Upload files and import".', 'code-snippets' ),
		'</p>';

		echo '<fieldset><p>';
		printf( '<label for="upload">%s</label>', esc_html__( 'Choose files from your computer:', 'code-snippets' ) );

		$max_size_bytes = apply_filters( 'import_upload_size_limit', wp_max_upload_size() );

		/* translators: %s: size in bytes */
		printf( esc_html__( '(Maximum size: %s)', 'code-snippets' ), esc_html( size_format( $max_size_bytes ) ) );

		echo '<input type="file" id="upload" name="code_snippets_import_files[]" size="25" accept="application/json,.json,text/xml" multiple="multiple">';
		echo '<input type="hidden" name="action" value="save">';

		printf( '<input type="hidden" name="max_file_size" value="%s">', esc_attr( $max_size_bytes ) );
		echo '</p></fieldset>';
	}

	/**
	 * Render the import menu interface.
	 *
	 * @return void
	 */
	public function render() {
		$this->render_navigation();

		echo '<div class="wrap">';
		$this->render_page_title();
		$this->print_messages();

		echo '<div class="narrow">';
		$this->render_form_introduction();

		echo '<form enctype="multipart/form-data" id="import-upload-form" method="post" class="wp-upload-form" name="code_snippets_import">';
		wp_nonce_field( 'import_code_snippets_file' );

		$this->render_duplicate_snippets_options();
		$this->render_file_upload();

		do_action( 'code_snippets/admin/import_form' );
		submit_button( __( 'Upload files and import', 'code-snippets' ) );
		echo '</form>';

		echo '</div>';
		echo '</div>';
	}
}
