<?php

namespace Code_Snippets\Flat_Files;

use Code_Snippets\Core\DB;
use Code_Snippets\Flat_Files\Interfaces\Filesystem_Adapter;
use Code_Snippets\Flat_Files\Interfaces\Snippet_Config_Repository;
use Code_Snippets\Flat_Files\Interfaces\Snippet_Type_Handler;
use Code_Snippets\Model\Snippet;
use function Code_Snippets\code_snippets;
use function Code_Snippets\get_snippet;
use function wp_hash;
use const Code_Snippets\CACHE_GROUP;

/**
/**
 * Manage file-based snippet execution.
 *
 * Responsible for writing snippet code to disk, maintaining per-table config indexes,
 * and retrieving the active snippet list from those config files.
 */
class Snippet_Files {

	/**
	 * Flag file name that indicates flat files are enabled.
	 */
	private const ENABLED_FLAG_FILE = 'flat-files-enabled.flag';

	/**
	 * Instance of handler registry.
	 *
	 * @var Handler_Registry
	 */
	private Handler_Registry $handler_registry;

	/**
	 * Instance of filesystem adapter.
	 *
	 * @var Filesystem_Adapter
	 */
	private Filesystem_Adapter $fs;

	/**
	 * Instance of config repository.
	 *
	 * @var Snippet_Config_Repository
	 */
	private Snippet_Config_Repository $config_repo;

	/**
	 * Class constructor.
	 *
	 * @param Handler_Registry          $handler_registry Registry to use for storing snippet type handlers.
	 * @param Filesystem_Adapter        $fs               Filesystem adapter to use for writing to files.
	 * @param Snippet_Config_Repository $config_repo      Config repository for storing snippets state.
	 */
	public function __construct(
		Handler_Registry $handler_registry,
		Filesystem_Adapter $fs,
		Snippet_Config_Repository $config_repo
	) {
		$this->handler_registry = $handler_registry;
		$this->fs = $fs;
		$this->config_repo = $config_repo;
	}

	/**
	 * Check if flat files are enabled by checking for the flag file.
	 * This avoids database calls for better performance.
	 *
	 * @return bool True if flat files are enabled, false otherwise.
	 */
	public static function is_active(): bool {
		return file_exists( self::get_flag_file_path() );
	}

	/**
	 * Retrieve the full filesystem path to the flag file, used for determining if flat files are enabled.
	 *
	 * @return string
	 */
	private static function get_flag_file_path(): string {
		return self::get_base_dir() . '/' . self::ENABLED_FLAG_FILE;
	}

	/**
	 * Create or delete the enabled flag file.
	 *
	 * @param bool $enabled Whether file-based execution is enabled.
	 *
	 * @return void
	 */
	private function handle_enabled_file_flag( bool $enabled ): void {
		$flag_file_path = self::get_flag_file_path();

		if ( $enabled ) {
			$base_dir = self::get_base_dir();
			$this->maybe_create_directory( $base_dir );

			$this->fs->put_contents( $flag_file_path, '', FS_CHMOD_FILE );
		} else {
			$this->delete_file( $flag_file_path );
		}
	}

	/**
	 * Register WordPress hooks used by file-based execution.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		if ( ! $this->fs->is_writable( WP_CONTENT_DIR ) ) {
			return;
		}

		if ( self::is_active() ) {
			add_action( 'code_snippets/create_snippet', [ $this, 'handle_snippet' ], 10, 2 );
			add_action( 'code_snippets/update_snippet', [ $this, 'handle_snippet' ], 10, 2 );
			add_action( 'code_snippets/delete_snippet', [ $this, 'delete_snippet' ], 10, 2 );
			add_action( 'code_snippets/trash_snippet', [ $this, 'delete_snippet' ], 10, 2 );
			add_action( 'code_snippets/activate_snippet', [ $this, 'activate_snippet' ], 10, 1 );
			add_action( 'code_snippets/deactivate_snippet', [ $this, 'deactivate_snippet' ], 10, 2 );
			add_action( 'code_snippets/activate_snippets', [ $this, 'activate_snippets' ], 10, 2 );

			add_action( 'updated_option', [ $this, 'sync_active_shared_network_snippets' ], 10, 3 );
			add_action( 'add_option', [ $this, 'sync_active_shared_network_snippets_add' ], 10, 2 );
		}

		add_filter( 'code_snippets_settings_fields', [ $this, 'add_settings_fields' ], 10, 1 );
		add_action( 'code_snippets/settings_updated', [ $this, 'create_all_flat_files' ], 10, 1 );
	}

	/**
	 * Set a number of snippets to active status.
	 *
	 * @param Snippet[] $valid_snippets Snippets to activate.
	 * @param string    $table          Database table the snippets belong to.
	 *
	 * @return void
	 */
	public function activate_snippets( array $valid_snippets, string $table ): void {
		foreach ( $valid_snippets as $snippet ) {
			$snippet->active = true;
			$this->handle_snippet( $snippet, $table );
		}
	}

	/**
	 * Write a snippet file and update its config index entry.
	 *
	 * @param Snippet              $snippet Snippet to write.
	 * @param string               $table   Snippet database table name.
	 * @param Snippet_Type_Handler $handler Snippet type handler.
	 *
	 * @return void
	 */
	private function write_snippet( Snippet $snippet, string $table, Snippet_Type_Handler $handler ): void {
		$hashed_table = self::get_hashed_table_name( $table );
		$base_dir = self::get_base_dir( $hashed_table, $handler->get_dir_name() );
		$this->maybe_create_directory( $base_dir );

		$file_path = $this->get_snippet_file_path( $base_dir, $snippet->id, $handler->get_file_extension() );

		$contents = $handler->wrap_code( $snippet->code );

		$this->fs->put_contents( $file_path, $contents, FS_CHMOD_FILE );

		$this->config_repo->update( $base_dir, $snippet );
	}

	/**
	 * Synchronise a snippet with the filesystem storage.
	 *
	 * @param Snippet $snippet Snippet to synchronise.
	 * @param string  $table   Database table snippet belongs to.
	 *
	 * @return void
	 */
	public function handle_snippet( Snippet $snippet, string $table ): void {
		if ( 0 === $snippet->id ) {
			return;
		}

		$handler = $this->handler_registry->get_handler( $snippet->type );

		if ( $handler ) {
			$this->write_snippet( $snippet, $table, $handler );
		}
	}

	/**
	 * Delete a snippet file and remove it from the config index.
	 *
	 * @param Snippet $snippet Snippet to delete.
	 * @param bool    $network Whether this is a network-level snippet.
	 *
	 * @return void
	 */
	public function delete_snippet( Snippet $snippet, bool $network ): void {
		$handler = $this->handler_registry->get_handler( $snippet->type );

		if ( ! $handler ) {
			return;
		}

		$table = self::get_hashed_table_name( code_snippets()->db->get_table_name( $network ) );
		$base_dir = self::get_base_dir( $table, $handler->get_dir_name() );

		$file_path = $this->get_snippet_file_path( $base_dir, $snippet->id, $handler->get_file_extension() );
		$this->delete_file( $file_path );

		$this->config_repo->update( $base_dir, $snippet, true );
	}

	/**
	 * Activate a snippet by writing its code file and updating config.
	 *
	 * @param Snippet $snippet Snippet object.
	 *
	 * @return void
	 */
	public function activate_snippet( Snippet $snippet ): void {
		$snippet = get_snippet( $snippet->id, $snippet->network );
		$handler = $this->handler_registry->get_handler( $snippet->type );

		if ( $handler ) {
			$table = code_snippets()->db->get_table_name( $snippet->network );
			$this->write_snippet( $snippet, $table, $handler );
		}
	}

	/**
	 * Deactivate a snippet by updating its config entry.
	 *
	 * @param int  $snippet_id Snippet ID.
	 * @param bool $network    Whether the snippet is network-wide.
	 *
	 * @return void
	 */
	public function deactivate_snippet( int $snippet_id, bool $network ): void {
		$snippet = get_snippet( $snippet_id, $network );
		$handler = $this->handler_registry->get_handler( $snippet->type );

		if ( ! $handler ) {
			return;
		}

		$table = self::get_hashed_table_name( code_snippets()->db->get_table_name( $network ) );
		$base_dir = self::get_base_dir( $table, $handler->get_dir_name() );

		$this->config_repo->update( $base_dir, $snippet );
	}

	/**
	 * Determine the base directory for storing a snippet given its database table and type.
	 *
	 * @param string $table        Database table name (can be empty).
	 * @param string $snippet_type Snippet type (can be empty).
	 *
	 * @return string Full filesystem path to base directory.
	 */
	public static function get_base_dir( string $table = '', string $snippet_type = '' ): string {
		$base_dir = WP_CONTENT_DIR . '/code-snippets';

		if ( ! empty( $table ) ) {
			$base_dir .= '/' . $table;
		}

		if ( ! empty( $snippet_type ) ) {
			$base_dir .= '/' . $snippet_type;
		}

		return $base_dir;
	}

	/**
	 * Get the base URL for flat files.
	 *
	 * @param string $table       Optional hashed table name.
	 * @param string $snippet_type Optional snippet type directory.
	 *
	 * @return string
	 */
	public static function get_base_url( string $table = '', string $snippet_type = '' ): string {
		$base_url = WP_CONTENT_URL . '/code-snippets';

		if ( ! empty( $table ) ) {
			$base_url .= '/' . $table;
		}

		if ( ! empty( $snippet_type ) ) {
			$base_url .= '/' . $snippet_type;
		}

		return $base_url;
	}

	/**
	 * Create a new directory if it does not already exist.
	 *
	 * @param string $dir Directory path.
	 *
	 * @return void
	 */
	private function maybe_create_directory( string $dir ): void {
		if ( ! $this->fs->is_dir( $dir ) ) {
			$result = wp_mkdir_p( $dir );

			if ( $result ) {
				$this->fs->chmod( $dir, FS_CHMOD_DIR );
			}
		}
	}

	/**
	 * Determine the file path for a snippet.
	 *
	 * @param string $base_dir   Base filesystem directory.
	 * @param int    $snippet_id Snippet identifier.
	 * @param string $ext        File extension, without the period.
	 *
	 * @return string
	 */
	private function get_snippet_file_path( string $base_dir, int $snippet_id, string $ext ): string {
		return trailingslashit( $base_dir ) . $snippet_id . '.' . $ext;
	}

	/**
	 * Delete a file from the filesystem if it exists.
	 *
	 * @param string $file_path Path of file to delete.
	 *
	 * @return void
	 */
	private function delete_file( string $file_path ): void {
		if ( $this->fs->exists( $file_path ) ) {
			$this->fs->delete( $file_path );
		}
	}

	/**
	 * Sync the active shared network snippets list to a config file.
	 *
	 * @param string $option    Option name.
	 * @param mixed  $old_value Previous value.
	 * @param mixed  $value     New value.
	 *
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function sync_active_shared_network_snippets( string $option, $old_value, $value ): void {
		if ( 'active_shared_network_snippets' !== $option ) {
			return;
		}

		$this->create_active_shared_network_snippets_file( $value );
	}

	/**
	 * Handler for 'add_option' to ensure that the stored active network snippet statuses match that in the database.
	 *
	 * @param string|mixed $option Name of option being added.
	 * @param mixed        $value  Initial value of option.
	 *
	 * @return void
	 */
	public function sync_active_shared_network_snippets_add( $option, $value ): void {
		if ( 'active_shared_network_snippets' !== $option ) {
			return;
		}

		$this->create_active_shared_network_snippets_file( $value );
	}

	/**
	 * Create or update the active shared network snippets config file.
	 *
	 * @param mixed $value Option value.
	 *
	 * @return void
	 *
	 * phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_var_export
	 */
	private function create_active_shared_network_snippets_file( $value ): void {
		$table = self::get_hashed_table_name( code_snippets()->db->get_table_name( false ) );
		$base_dir = self::get_base_dir( $table );

		$this->maybe_create_directory( $base_dir );
		$file_path = trailingslashit( $base_dir ) . 'active-shared-network-snippets.php';

		$file_content = sprintf(
			"<?php\n\nif ( ! defined( 'ABSPATH' ) ) { return; }\n\nreturn %s;\n",
			var_export( $value, true )
		);

		$this->fs->put_contents( $file_path, $file_content, FS_CHMOD_FILE );
	}

	/**
	 * Hash a table name.
	 *
	 * @param string $table Table name to hash.
	 *
	 * @return string Hashed table name.
	 */
	public static function get_hashed_table_name( string $table ): string {
		// wp_hash() is pluggable and may not be available during early bootstrap.
		return function_exists( 'wp_hash' ) ? wp_hash( $table ) : md5( $table );
	}

	/**
	 * Get a list of active snippets from flat file config.
	 *
	 * @param array<string> $scopes       Scopes to include.
	 * @param string        $snippet_type Snippet type directory.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_active_snippets_from_flat_files(
		array $scopes = [],
		string $snippet_type = 'php'
	): array {
		$active_snippets = [];
		$db = code_snippets()->db;

		// Always use the site table for "local" snippets, even in Network Admin.
		$table = self::get_hashed_table_name( $db->get_table_name( false ) );
		$snippets = self::load_active_snippets_from_file(
			$table,
			$snippet_type,
			$scopes
		);

		if ( $snippets ) {
			foreach ( $snippets as $snippet ) {
				$active_snippets[] = [
					'id'           => intval( $snippet['id'] ),
					'code'         => $snippet['code'],
					'scope'        => $snippet['scope'],
					'table'        => $db->table,
					'network'      => false,
					'priority'     => intval( $snippet['priority'] ),
					'condition_id' => intval( $snippet['condition_id'] ),
				];
			}
		}

		if ( is_multisite() ) {
			$ms_table = self::get_hashed_table_name( $db->get_table_name( true ) );

			$root_base_dir = self::get_base_dir( $table );
			$active_shared_ids_file_path = $root_base_dir . '/active-shared-network-snippets.php';
			$active_shared_ids = is_file( $active_shared_ids_file_path )
				? require $active_shared_ids_file_path
				: [];

			$ms_snippets = self::load_active_snippets_from_file(
				$ms_table,
				$snippet_type,
				$scopes,
				$active_shared_ids
			);

			if ( $ms_snippets ) {
				$active_shared_ids = is_array( $active_shared_ids )
					? array_map( 'intval', $active_shared_ids )
					: [];

				foreach ( $ms_snippets as $snippet ) {
					$id = intval( $snippet['id'] );
					$active_value = intval( $snippet['active'] );

					if ( ! DB::is_network_snippet_enabled( $active_value, $id, $active_shared_ids ) ) {
						continue;
					}

					$active_snippets[] = [
						'id'           => $id,
						'code'         => $snippet['code'],
						'scope'        => $snippet['scope'],
						'table'        => $db->ms_table,
						'network'      => true,
						'priority'     => intval( $snippet['priority'] ),
						'condition_id' => intval( $snippet['condition_id'] ),
					];
				}

				self::sort_active_snippets( $active_snippets, $db );
			}
		}

		return $active_snippets;
	}

	/**
	 * Sort list of active snippets for evaluation.
	 *
	 * @param array $active_snippets List of active snippet data.
	 * @param DB    $db              Database instance.
	 *
	 * @return void
	 */
	private static function sort_active_snippets( array &$active_snippets, DB $db ): void {
		$comparisons = [
			function ( array $a, array $b ) {
				return $a['priority'] <=> $b['priority'];
			},
			function ( array $a, array $b ) use ( $db ) {
				$a_table = $a['table'] === $db->ms_table ? 0 : 1;
				$b_table = $b['table'] === $db->ms_table ? 0 : 1;
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
	 * Load active snippets from a flat file config index.
	 *
	 * @param string     $table            Hashed table directory name.
	 * @param string     $snippet_type      Snippet type directory.
	 * @param string[]   $scopes           Scopes to include.
	 * @param int[]|null $active_shared_ids Optional list of active shared network snippet IDs.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function load_active_snippets_from_file(
		string $table,
		string $snippet_type,
		array $scopes,
		?array $active_shared_ids = null
	): array {
		$snippets = [];
		$db = code_snippets()->db;

		$base_dir = self::get_base_dir( $table, $snippet_type );
		$snippets_file_path = $base_dir . '/index.php';

		if ( ! is_file( $snippets_file_path ) ) {
			return $snippets;
		}

		$cache_key = sprintf(
			'active_snippets_%s_%s',
			sanitize_key( join( '_', $scopes ) ),
			self::get_hashed_table_name( $db->table ) === $table ? $db->table : $db->ms_table
		);

		$cached_snippets = wp_cache_get( $cache_key, CACHE_GROUP );

		if ( is_array( $cached_snippets ) ) {
			return $cached_snippets;
		}

		$file_snippets = require $snippets_file_path;
		$shared_ids = is_array( $active_shared_ids )
			? array_map( 'intval', $active_shared_ids )
			: [];

		$filtered_snippets = array_filter(
			$file_snippets,
			function ( $snippet ) use ( $scopes, $shared_ids ) {
				$active_value = isset( $snippet['active'] ) ? intval( $snippet['active'] ) : 0;

				$is_active = DB::is_network_snippet_enabled( $active_value, intval( $snippet['id'] ), $shared_ids );

				return ( $is_active || 'condition' === $snippet['scope'] ) &&
				       in_array( $snippet['scope'], $scopes, true );
			}
		);

		wp_cache_set( $cache_key, $filtered_snippets, CACHE_GROUP );

		return $filtered_snippets;
	}

	/**
	 * Add file-based execution settings fields.
	 *
	 * @param array<string, mixed> $fields Settings fields.
	 *
	 * @return array<string, mixed> Settings fields with flat file setting added.
	 */
	public function add_settings_fields( array $fields ): array {

		$learn_more_link = sprintf(
			' <a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( 'https://codesnippets.pro/doc/file-based-execution/' ),
			__( 'Learn more.', 'code-snippets' )
		);

		$fields['general']['enable_flat_files'] = [
			'name'  => __( 'Enable File-Based Execution', 'code-snippets' ),
			'type'  => 'checkbox',
			'label' => __( 'Snippets will be executed directly from files instead of the database.', 'code-snippets' ) . $learn_more_link,
		];

		return $fields;
	}

	/**
	 * Create necessary flat files, if the option is enabled.
	 *
	 * @param array<string, mixed> $settings Settings data.
	 *
	 * @return void
	 */
	public function create_all_flat_files( array $settings ): void {
		if ( ! isset( $settings['general']['enable_flat_files'] ) ) {
			return;
		}

		$this->handle_enabled_file_flag( $settings['general']['enable_flat_files'] );

		if ( ! $settings['general']['enable_flat_files'] ) {
			return;
		}

		$this->create_snippet_flat_files();
		$this->create_active_shared_network_snippets_config_file();
	}

	/**
	 * Create snippet code files and config indexes for all active snippets.
	 *
	 * @return void
	 */
	private function create_snippet_flat_files(): void {
		$db = code_snippets()->db;

		$scopes = Snippet::get_all_scopes();

		$data = $db->fetch_active_snippets( $scopes );

		foreach ( $data as $snippet ) {
			$snippet_obj = get_snippet( $snippet['id'], $db->ms_table === $snippet['table'] );
			$this->handle_snippet( $snippet_obj, $snippet['table'] );
		}

		if ( is_multisite() ) {
			$sites = get_sites( [ 'fields' => 'ids' ] );
			foreach ( $sites as $site_id ) {
				switch_to_blog( $site_id );
				$db->set_table_vars();

				$site_data = $db->fetch_active_snippets( $scopes );
				foreach ( $site_data as $snippet ) {
					$table_name = $snippet['table'];
					$snippet_obj = get_snippet( $snippet['id'], false );
					$this->handle_snippet( $snippet_obj, $table_name );
				}

				restore_current_blog();
			}

			$db->set_table_vars();
		}
	}

	/**
	 * Create active shared network snippet config files for each site (multisite) or the current site.
	 *
	 * @return void
	 */
	private function create_active_shared_network_snippets_config_file(): void {
		if ( is_multisite() ) {
			$db = code_snippets()->db;
			$sites = get_sites( [ 'fields' => 'ids' ] );

			foreach ( $sites as $site_id ) {
				switch_to_blog( $site_id );
				$db->set_table_vars();

				$active_shared_network_snippets = get_option( 'active_shared_network_snippets' );
				if ( false !== $active_shared_network_snippets ) {
					$this->create_active_shared_network_snippets_file( $active_shared_network_snippets );
				}

				restore_current_blog();
			}

			$db->set_table_vars();
		} else {
			$active_shared_network_snippets = get_option( 'active_shared_network_snippets' );
			if ( false !== $active_shared_network_snippets ) {
				$this->create_active_shared_network_snippets_file( $active_shared_network_snippets );
			}
		}
	}
}
