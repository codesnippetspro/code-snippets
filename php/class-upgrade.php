<?php

/**
 * Manages upgrade tasks such as deleting and updating options
 */
class Code_Snippets_Upgrade {

	/**
	 * Instance of database class
	 * @var Code_Snippets_DB
	 */
	private $db;

	/**
	 * The current plugin version number
	 * @var string
	 */
	private $current_version;

	/**
	 * Class constructor
	 *
	 * @param string           $version Current plugin version
	 * @param Code_Snippets_DB $db      Instance of database class
	 */
	public function __construct( $version, Code_Snippets_DB $db ) {
		$this->db = $db;
		$this->current_version = $version;
	}

	/**
	 * Run the upgrade functions
	 */
	public function run() {

		/* Always run multisite upgrades, even if not on the main site, as subsites depend on the network snippet table */
		if ( is_multisite() ) {
			$this->do_multisite_upgrades();
		}

		$this->do_site_upgrades();
	}

	/**
	 * Perform upgrades for the current site
	 */
	private function do_site_upgrades() {
		$table_name = $this->db->table;
		$prev_version = get_option( 'code_snippets_version' );

		/* Do nothing if the plugin has not been updated or installed */
		if ( ! version_compare( $prev_version, $this->current_version, '<' ) ) {
			return;
		}

		$this->db->create_table( $table_name );

		/* Update the plugin version stored in the database */
		update_option( 'code_snippets_version', $this->current_version );

		/* Update the scope column of the database */
		if ( version_compare( $prev_version, '2.10.0', '<' ) ) {
			$this->migrate_scope_data( $table_name );
		}

		/* Custom capabilities were removed after version 2.9.5 */
		if ( version_compare( $prev_version, '2.9.5', '<=' ) ) {
			$role = get_role( apply_filters( 'code_snippets_role', 'administrator' ) );
			$role->remove_cap( apply_filters( 'code_snippets_cap', 'manage_snippets' ) );
		}

		if ( false === $prev_version ) {
			$this->create_sample_content();
		}
	}

	/**
	 * Perform multisite-only upgrades
	 */
	private function do_multisite_upgrades() {
		$table_name = $this->db->ms_table;
		$prev_version = get_site_option( 'code_snippets_version' );

		/* Do nothing if the plugin has not been updated or installed */
		if ( ! version_compare( $prev_version, $this->current_version, '<' ) ) {
			return;
		}

		/* Always attempt to create or upgrade the database tables */
		$this->db->create_table( $table_name );

		/* Update the plugin version stored in the database */
		update_site_option( 'code_snippets_version', $this->current_version );

		/* Update the scope column of the database */
		if ( version_compare( $prev_version, '2.10.0', '<' ) ) {
			$this->migrate_scope_data( $table_name );
		}

		/* Custom capabilities were removed after version 2.9.5 */
		if ( version_compare( $prev_version, '2.9.5', '<=' ) ) {
			$network_cap = apply_filters( 'code_snippets_network_cap', 'manage_network_snippets' );

			foreach ( get_super_admins() as $admin ) {
				$user = new WP_User( 0, $admin );
				$user->remove_cap( $network_cap );
			}
		}
	}

	/**
	 * Migrate data from the old integer method of storing scopes to the new string method
	 *
	 * @param string $table_name
	 */
	private function migrate_scope_data( $table_name ) {
		global $wpdb;

		$scopes = array(
			0 => 'global',
			1 => 'admin',
			2 => 'front-end',
		);

		foreach ( $scopes as $scope_number => $scope_name ) {
			$wpdb->query( sprintf(
				"UPDATE %s SET scope = '%s' WHERE scope = %d",
				$table_name, $scope_name, $scope_number
			) );
		}
	}

	/**
	 * Add sample snippet content to the database
	 */
	public function create_sample_content() {

		if ( ! apply_filters( 'code_snippets/create_sample_content', true ) ) {
			return;
		}

		$snippets = array(

			array(
				'name' => __( 'Example HTML shortcode', 'code-snippets' ),
				'code' => sprintf(
					"\nadd_shortcode( 'shortcode_name', function () {\n\n\t\$out = '<p>%s</p>';\n\n\treturn \$out;\n} );",
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

			array(
				'name'  => __( 'Order snippets by name', 'code-snippets' ),
				'code'  => "\nadd_filter( 'code_snippets/list_table/default_orderby', function () {\n\treturn 'name';\n} );\n",
				'desc'  => __( 'Order snippets by name by default in the snippets table.', 'code-snippets' ),
				'tags'  => array( 'code-snippets-plugin' ),
				'scope' => 'admin',
			),
		);

		foreach ( $snippets as $snippet ) {
			$snippet = new Code_Snippet( $snippet );
			$snippet->desc .= ' ' . __( 'You can remove it, or edit it to add your own content.', 'code-snippets' );
			save_snippet( $snippet );
		}
	}
}
