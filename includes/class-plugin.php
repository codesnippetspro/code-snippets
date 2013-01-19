<?php

/**
 * Framework for a plugin that hooks into the Code Snippets API
 */
class Code_Snippets_Plugin {

	public $version = 0.1;

	function __construct() {

		$this->plugin_name = sanitize_key( __CLASS__ );

		add_action( 'plugins_loaded', array( $this, 'setup' ) );
	}

	function setup() {
		global $code_snippets;
	}

	function add_database_column( $sql ) {

		$this->current_version = get_site_option( $this->plugin_name . '_version', $this->version );

		if ( $this->current_version >= $this->version ) {
			add_action( 'plugins_loaded', array( $this, 'add_database_table_column' ) );
			update_site_option( 'code_snippets_tags_version', $this->version );
		}
	}

}

