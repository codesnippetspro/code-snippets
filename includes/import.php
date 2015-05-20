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
}

add_action( 'admin_init', 'code_snippets_register_importer' );
