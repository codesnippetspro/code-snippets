<?php

namespace Code_Snippets;

/**
 * Functions used to manage the database tables.
 *
 * @package Code_Snippets
 */
class DB {

	/**
	 * Unprefixed site-wide table name.
	 */
	public const TABLE_NAME = 'snippets';

	/**
	 * Unprefixed network-wide table name.
	 */
	public const MS_TABLE_NAME = 'ms_snippets';

	/**
	 * Side-wide table name.
	 *
	 * @var string
	 */
	public string $table;

	/**
	 * Network-wide table name.
	 *
	 * @var string
	 */
	public string $ms_table;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->set_table_vars();
	}

	/**
	 * Register the snippet table names with WordPress.
	 *
	 * @since 2.0
	 */
	public function set_table_vars() {
		global $wpdb;

		$this->table = $wpdb->prefix . self::TABLE_NAME;
		$this->ms_table = $wpdb->base_prefix . self::MS_TABLE_NAME;

		// Register the snippet table names with WordPress.
		$wpdb->snippets = $this->table;
		$wpdb->ms_snippets = $this->ms_table;

		$wpdb->tables[] = self::TABLE_NAME;
		$wpdb->ms_global_tables[] = self::MS_TABLE_NAME;
	}

	/**
	 * Validate a provided 'network' or 'multisite' param, converting it to a boolean.
	 *
	 * @param bool|null $network Network argument value.
	 *
	 * @return bool Sanitized value.
	 */
	public static function validate_network_param( ?bool $network = null ): bool {

		// If multisite is not active, then assume the value is false.
		if ( ! is_multisite() ) {
			return false;
		}

		// If $multisite is null, try to base it on the current admin page.
		if ( is_null( $network ) && function_exists( 'is_network_admin' ) ) {
			return is_network_admin();
		}

		return (bool) $network;
	}

	/**
	 * Return the appropriate snippet table name
	 *
	 * @param bool|null $is_network Whether retrieve the multisite table name (true) or the site table name (false).
	 *
	 * @return string The snippet table name
	 * @since 2.0
	 */
	public function get_table_name( ?bool $is_network = null ): string {
		$is_network = is_bool( $is_network ) ? $is_network : self::validate_network_param( $is_network );
		return $is_network ? $this->ms_table : $this->table;
	}

	/**
	 * Determine whether a database table exists.
	 *
	 * @param string  $table_name Name of database table to check.
	 * @param boolean $refresh    Rerun the query, instead of using a cached value.
	 *
	 * @return bool Whether the database table exists.
	 */
	public static function table_exists( string $table_name, bool $refresh = false ): bool {
		global $wpdb;
		static $checked = array();

		if ( $refresh || ! isset( $checked[ $table_name ] ) ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, caching is handled through $checked variable.
			$result = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) ) );
			$checked[ $table_name ] = $result === $table_name;
		}

		return $checked[ $table_name ];
	}

	/**
	 * Create the snippet tables if they do not already exist
	 */
	public function create_missing_tables() {

		// Create the network snippets table if it doesn't exist.
		if ( is_multisite() && ! self::table_exists( $this->ms_table ) ) {
			$this->create_table( $this->ms_table );
		}

		// Create the table if it doesn't exist.
		if ( ! self::table_exists( $this->table ) ) {
			$this->create_table( $this->table );
		}
	}

	/**
	 * Create the snippet tables, or upgrade them if they already exist
	 */
	public function create_or_upgrade_tables() {
		if ( is_multisite() ) {
			$this->create_table( $this->ms_table );
		}

		$this->create_table( $this->table );
	}

	/**
	 * Create a snippet table if it does not already exist
	 *
	 * @param string $table_name Name of database table.
	 */
	public static function create_missing_table( string $table_name ) {
		if ( ! self::table_exists( $table_name ) ) {
			self::create_table( $table_name );
		}
	}

	/**
	 * Create a single snippet table.
	 *
	 * @param string $table_name The name of the table to create.
	 *
	 * @return bool Whether the table creation was successful.
	 * @since 1.6
	 * @uses  dbDelta() to apply the SQL code
	 */
	public static function create_table( string $table_name ): bool {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		/* Create the database table */
		$sql = "CREATE TABLE $table_name (
				id           BIGINT(20)   NOT NULL AUTO_INCREMENT,
				name         TINYTEXT     NOT NULL,
				description  TEXT         NOT NULL,
				code         LONGTEXT     NOT NULL,
				tags         LONGTEXT     NOT NULL,
				scope        VARCHAR(15)  NOT NULL DEFAULT 'global',
				condition_id BIGINT(20)   NOT NULL DEFAULT 0,
				priority     SMALLINT     NOT NULL DEFAULT 10,
				active       TINYINT(1)   NOT NULL DEFAULT 0,
				modified     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
				revision     BIGINT(20)   NOT NULL DEFAULT 1,
				cloud_id     VARCHAR(255) NULL,
				PRIMARY KEY  (id),
				KEY scope (scope),
				KEY active (active)
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
	 * Fetch a list of active snippets from a database table.
	 *
	 * @param string        $table_name  Name of table to fetch snippets from.
	 * @param array<string> $scopes      List of scopes to include in query.
	 * @param boolean       $active_only Whether to only fetch active snippets from the table.
	 *
	 * @return array<string, array<string, mixed>>|false List of active snippets, if any could be retrieved.
	 *
	 * @phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
	 */
	private static function fetch_snippets_from_table( string $table_name, array $scopes, bool $active_only = true ) {
		global $wpdb;

		$cache_key = sprintf( 'active_snippets_%s_%s', sanitize_key( join( '_', $scopes ) ), $table_name );
		$cached_snippets = wp_cache_get( $cache_key, CACHE_GROUP );

		if ( is_array( $cached_snippets ) ) {
			return $cached_snippets;
		}

		if ( ! self::table_exists( $table_name ) ) {
			return false;
		}

		$scopes_format = implode( ',', array_fill( 0, count( $scopes ), '%s' ) );
		$extra_where = $active_only ? 'AND active=1' : '';

		$snippets = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT id, code, scope, active, priority
				FROM $table_name
				WHERE scope IN ($scopes_format) $extra_where
				ORDER BY priority, id",
				$scopes
			),
			'ARRAY_A'
		);

		// Cache the full list of snippets.
		if ( is_array( $snippets ) ) {
			wp_cache_set( $cache_key, $snippets, CACHE_GROUP );
			return $snippets;
		}

		return false;
	}

	/**
	 * Sort the active snippets by priority, table, and ID.
	 *
	 * @param array $active_snippets List of active snippets to sort.
	 */
	private function sort_active_snippets( array &$active_snippets ): void {
		$comparisons = [
			function ( array $a, array $b ) {
				return $a['priority'] <=> $b['priority'];
			},
			function ( array $a, array $b ) {
				$a_table = $a['table'] === $this->ms_table ? 0 : 1;
				$b_table = $b['table'] === $this->ms_table ? 0 : 1;
				return $a_table <=> $b_table;
			},
			function ( array $a, array $b ) {
				return $a['id'] <=> $b['id'];
			},
		];

		usort(
			$active_snippets,
			static function ( $a, $b ) use ( $comparisons ) {
				foreach ( $comparisons as $comparison ) {
					$result = $comparison( $a, $b );
					if ( 0 !== $result ) {
						return $result;
					}
				}

				return 0;
			}
		);
	}

	/**
	 * Generate the SQL for fetching active snippets from the database.
	 *
	 * @param string[] $scopes List of scopes to retrieve in.
	 *
	 * @return array{
	 *     id: int,
	 *     code: string,
	 *     scope: string,
	 *     table: string,
	 *     network: bool,
	 *     priority: int,
	 * } List of active snippets.
	 */
	public function fetch_active_snippets( array $scopes ): array {
		$active_snippets = [];

		// Fetch the active snippets for the current site, if there are any.
		$snippets = $this->fetch_snippets_from_table( $this->table, $scopes, true );
		if ( $snippets ) {
			foreach ( $snippets as $snippet ) {
				$active_snippets[] = [
					'id'       => intval( $snippet['id'] ),
					'code'     => $snippet['code'],
					'scope'    => $snippet['scope'],
					'table'    => $this->table,
					'network'  => false,
					'priority' => intval( $snippet['priority'] ),
				];
			}
		}

		// If multisite is enabled, fetch all snippets from the network table, and filter down to only active snippets.
		if ( is_multisite() ) {
			$ms_snippets = $this->fetch_snippets_from_table( $this->ms_table, $scopes, false );

			if ( $ms_snippets ) {
				$active_shared_ids = get_option( 'active_shared_network_snippets', [] );
				$active_shared_ids = is_array( $active_shared_ids )
					? array_map( 'intval', $active_shared_ids )
					: [];

				foreach ( $ms_snippets as $snippet ) {
					$id = intval( $snippet['id'] );

					if ( ! $snippet['active'] && ! in_array( $id, $active_shared_ids, true ) ) {
						continue;
					}

					$active_snippets[] = [
						'id'       => $id,
						'code'     => $snippet['code'],
						'scope'    => $snippet['scope'],
						'table'    => $this->ms_table,
						'network'  => true,
						'priority' => intval( $snippet['priority'] ),
					];
				}

				$this->sort_active_snippets( $active_snippets );
			}
		}

		return $active_snippets;
	}
}
