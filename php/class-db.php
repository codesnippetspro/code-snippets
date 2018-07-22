<?php

/**
 * Functions used to manage the database tables
 *
 * @package Code_Snippets
 */
class Code_Snippets_DB {

	public $table;

	public $ms_table;

	/**
	 * Class constructor
	 */
	function __construct() {
		$this->set_table_vars();
	}

	/**
	 * Register the snippet table names with WordPress
	 *
	 * @since 2.0
	 * @uses $wpdb
	 */
	function set_table_vars() {
		global $wpdb;

		$this->table = 'snippets';
		$this->ms_table = 'ms_snippets';

		/* Register the snippet table names with WordPress */
		$wpdb->tables[] = $this->table;
		$wpdb->ms_global_tables[] = $this->ms_table;

		/* Setup initial table variables */
		$wpdb->snippets = $this->table = $wpdb->prefix . $this->table;
		$wpdb->ms_snippets = $this->ms_table = $wpdb->base_prefix . $this->ms_table;
	}

	/**
	 * Validate the multisite parameter of the get_table_name() function
	 *
	 * @param bool|null $network
	 *
	 * @return bool
	 */
	function validate_network_param( $network ) {

		/* If multisite is not active, then the parameter should always be false */
		if ( ! is_multisite() ) {
			return false;
		}

		/* If $multisite is null, try to base it on the current admin page */
		if ( is_null( $network ) && function_exists( 'is_network_admin' ) ) {
			$network = is_network_admin();
		}

		return $network;
	}

	/**
	 * Return the appropriate snippet table name
	 *
	 * @since  2.0
	 *
	 * @param  string|bool|null $multisite Retrieve the multisite table name or the site table name?
	 *
	 * @return string                      The snippet table name
	 */
	function get_table_name( $multisite = null ) {
		global $wpdb;

		/* If the first parameter is a string, assume it is a table name */
		if ( is_string( $multisite ) ) {
			return $multisite;
		}

		/* Validate the multisite parameter */
		$multisite = $this->validate_network_param( $multisite );

		/* Retrieve the table name from $wpdb depending on the value of $multisite */

		return ( $multisite ? $wpdb->ms_snippets : $wpdb->snippets );
	}

	/**
	 * Create the snippet tables
	 * This function will only execute once per page load, except if $redo is true
	 *
	 * @since 1.7.1
	 *
	 * @param bool $upgrade Run the table creation code even if the table exists
	 */
	function create_tables( $upgrade = false ) {
		global $wpdb;

		/* Set the table name variables if not yet defined */
		if ( ! isset( $wpdb->snippets, $wpdb->ms_snippets ) ) {
			$this->set_table_vars();
		}

		if ( is_multisite() ) {

			/* Create the network snippets table if it doesn't exist, or upgrade it */
			if ( $upgrade || $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->ms_snippets'" ) !== $wpdb->ms_snippets ) {
				$this->create_table( $wpdb->ms_snippets );
			}
		}

		/* Create the table if it doesn't exist, or upgrade it */
		if ( $upgrade || $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->snippets'" ) !== $wpdb->snippets ) {
			$this->create_table( $wpdb->snippets );
		}
	}

	/**
	 * Create a single snippet table
	 *
	 * @since 1.6
	 * @uses dbDelta() to apply the SQL code
	 *
	 * @param string $table_name The name of the table to create
	 *
	 * @return bool whether the table creation was successful
	 */
	function create_table( $table_name ) {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		/* Create the database table */
		$sql = "CREATE TABLE $table_name (
				id          bigint(20)  NOT NULL AUTO_INCREMENT,
				name        tinytext    NOT NULL default '',
				description text        NOT NULL default '',
				code        longtext    NOT NULL default '',
				tags        longtext    NOT NULL default '',
				scope       varchar(15) NOT NULL default 'global',
				priority    smallint    NOT NULL default 10,
				active      tinyint(1)  NOT NULL default 0,
				PRIMARY KEY  (id)
			) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		$success = empty( $wpdb->last_error );

		if ( $success ) {
			do_action( 'code_snippets/create_table', $table_name );
		}

		return $success;
	}

	/**
	 * Add sample snippet content to the database
	 *
	 * @param bool $network
	 */
	public function create_sample_content( $network = false ) {

		$snippets = array(

			array(
				'name' => __( 'Example HTML shortcode', 'code-snippets' ),
				'code' => sprintf(
					"\nadd_shortcode( 'shortcode_name', function () { ?>\n\n\t<p>%s</p>\n\n<?php } );",
					strip_tags( __( 'write your HTML shortcode content here', 'code-snippets' ) )
				),
				'desc' => __( 'This is an example snippet for demonstrating how to add an HTML shortcode.', 'code-snippets' ),
				'tags' => array( 'shortcode' ),
			),

			array(
				'name'  => __( 'Example CSS snippet', 'code-snippets' ),
				'code'  => sprintf(
					"\nadd_action( 'wp_head', function () { ?>\n\t<style>\n\n\t\t/* %s */\n\n\t</style>\n<?php } );\n",
					strip_tags( __( 'write your CSS code here', 'code-snippets' ) )
				),
				'desc'  => __( 'This is an example snippet for demonstrating how to add custom CSS code to your website.', 'code-snippets' ),
				'tags'  => array( 'css' ),
				'scope' => 'front-end',
			),

			array(
				'name'  => __( 'Example JavaScript snippet', 'code-snippets' ),
				'code'  => sprintf(
					"\nadd_action( 'wp_head', function () { ?>\n\t<script>\n\n\t\t/* %s */\n\n\t</script>\n<?php } );\n",
					strip_tags( __( 'write your JavaScript code here', 'code-snippets' ) )
				),
				'desc'  => __( 'This is an example snippet for demonstrating how to add custom JavaScript code to your website.', 'code-snippets' ),
				'tags'  => array( 'javascript' ),
				'scope' => 'front-end',
			),
		);

		foreach ( $snippets as $snippet ) {
			$snippet = new Code_Snippet( $snippet );
			$snippet->desc .= ' ' . __( 'You can remove it, or edit it to add your own content.', 'code-snippets' );
			$snippet->network = $network;

			save_snippet( $snippet );
		}
	}
}
