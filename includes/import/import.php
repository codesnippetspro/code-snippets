<?php

/**
 * Functions to handle the import snippets menu
 *
 * @package    Code_Snippets
 * @subpackage Administration
 */

/**
 * Add the importer to the Tools > Import menu
 *
 * @since 1.6
 * @access private
 */
function code_snippets_register_importer() {

	/* Only register the importer if the current user can manage snippets */
	if ( defined( 'WP_LOAD_IMPORTERS' ) && current_user_can( get_snippets_cap() ) ) {

		/* Load Importer API */
		require_once ABSPATH . 'wp-admin/includes/import.php';

		if ( ! class_exists( 'WP_Importer' ) ) {
			$class_wp_importer = ABSPATH .  'wp-admin/includes/class-wp-importer.php';
			if ( file_exists( $class_wp_importer ) ) {
				require_once $class_wp_importer;
			}
		}

		/* Register the Code Snippets importer with WordPress */
		register_importer(
			'code-snippets',
			__( 'Code Snippets', 'code-snippets' ),
			__( 'Import snippets from a code snippets export file', 'code-snippets' ),
			'code_snippets_render_import_menu'
		);
	}

	add_action( 'load-importer-code-snippets', 'code_snippets_load_import_menu' );
}

add_action( 'admin_init', 'code_snippets_register_importer' );

class Code_Snippets_Import_Menu extends Code_Snippets_Admin_Menu {

	function __construct() {
		parent::__construct( __FILE__,
			'import',
			__( 'Import', 'code-snippets' ),
			__( 'Import Snippets', 'code-snippets' )
		);
	}

	/**
	 * Processes import files and loads the help tabs for the Import Snippets page
	 *
	 * @since 1.3
	 *
	 * @uses import_snippets() To process the import file
	 * @uses wp_redirect() To pass the import results to the page
	 * @uses add_query_arg() To append the results to the current URI
	 */
	function load() {
		$network = get_current_screen()->is_network;

		/* Make sure the user has permission to be here */
		if ( ! current_user_can( get_snippets_cap() ) ) {
			wp_die( __( 'You are not access this page.', 'code-snippets' ) );
		}

		/* Create the snippet tables if they don't exist */
		create_code_snippets_tables();

		/* Process import files */

		if ( isset( $_FILES['code_snippets_import_file']['tmp_name'] ) ) {

			/* Import the snippets. The result is the number of snippets that were imported */
			$result = import_snippets( $_FILES['code_snippets_import_file']['tmp_name'], $network );

			/* Send the amount of imported snippets to the page */
			$url = add_query_arg( false === $result ? array( 'error' => true ) : array( 'imported' => $result ) );
			wp_redirect( esc_url_raw( $url ) );
		}

		/* Load the screen help tabs */
		require plugin_dir_path( __FILE__ ) . 'admin-help.php';
	}
}

new Code_Snippets_Import_Menu();
