<?php

/**
 * Framework for a plugin that hooks into the Code Snippets API
 */
class Code_Snippets_Plugin {

	public $version = 0.1;

	function __construct( $plugin_name = '' ) {

		if ( ! isset( $this->plugin_name ) )
			$this->plugin_name = ( isset( $plugin_name ) ? $plugin_name : sanitize_key( __CLASS__ ) );

		add_action( 'plugins_loaded', array( $this, 'setup' ) );
	}

	function setup() {
		global $code_snippets;
	}

	function upgrade() {

		$installed_version = get_site_option( $this->plugin_name . '_version', $this->version );

		if ( $this->version !== $installed_version ) {
			update_site_option( $this->plugin_name . '_version', $this->version );
			return true; // plugin was upgraded
		}

		return false; // plugin was not upgraded
	}

	function add_database_column( $name, $type ) {
		global $wpdb, $code_snippets;

		$code_snippets->create_tables();

		$wpdb->query( $wpdb->prepare( "ALTER TABLE %s ADD COLUMN %s %s AFTER code", $wpdb->snippets, $name, $type ) );

		if ( is_multisite() )
			$wpdb->query( $wpdb->prepare( "ALTER TABLE %s ADD COLUMN %s %s AFTER code", $wpdb->ms_snippets, $name, $type ) );
	}

}
