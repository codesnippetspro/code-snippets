<?php

namespace Code_Snippets;

class Snippet_Files {

	/**
	 * Flag file name that indicates flat files are enabled.
	 */
	private const ENABLED_FLAG_FILE = 'flat-files-enabled.flag';

	private Snippet_Handler_Registry $handler_registry;

	private File_System_Interface $fs;

	private Snippet_Config_Repository_Interface $config_repo;

	public function __construct(
		Snippet_Handler_Registry $handler_registry,
		File_System_Interface $fs,
		Snippet_Config_Repository_Interface $config_repo
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
		$flag_file_path = self::get_flag_file_path();
		return file_exists( $flag_file_path );
	}

	private static function get_flag_file_path(): string {
		return self::get_base_dir() . '/' . self::ENABLED_FLAG_FILE;
	}

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
		add_action( 'code_snippets/settings_updated', [ $this, 'create_all_flat_files' ], 10, 2 );
	}

	public function activate_snippets( $valid_snippets, $table ): void {
		foreach ( $valid_snippets as $snippet ) {
			$snippet->active = true;
			$this->handle_snippet( $snippet, $table );
		}
	}

	public function handle_snippet( Snippet $snippet, string $table ): void {
		if ( 0 === $snippet->id ) {
			return;
		}

		$handler = $this->handler_registry->get_handler( $snippet->type );

		if ( ! $handler ) {
			return;
		}

		$table = self::get_hashed_table_name( $table );
		$base_dir = self::get_base_dir( $table, $handler->get_dir_name() );
		$this->maybe_create_directory( $base_dir );

		$file_path = $this->get_snippet_file_path( $base_dir, $snippet->id, $handler->get_file_extension() );

		$contents = $handler->wrap_code( $snippet->code );

		$this->fs->put_contents( $file_path, $contents, FS_CHMOD_FILE );

		$this->config_repo->update( $base_dir, $snippet );
	}

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

	public function activate_snippet( Snippet $snippet ): void {
		$snippet = get_snippet( $snippet->id, $snippet->network );
		$handler = $this->handler_registry->get_handler( $snippet->type );

		if ( ! $handler ) {
			return;
		}

		$table = self::get_hashed_table_name( code_snippets()->db->get_table_name( $snippet->network ) );
		$base_dir = self::get_base_dir( $table, $handler->get_dir_name() );

		$this->maybe_create_directory( $base_dir );

		$file_path = $this->get_snippet_file_path( $base_dir, $snippet->id, $handler->get_file_extension() );

		$contents = $handler->wrap_code( $snippet->code );

		$this->fs->put_contents( $file_path, $contents, FS_CHMOD_FILE );

		$this->config_repo->update( $base_dir, $snippet );
	}

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

	private function maybe_create_directory( string $dir ): void {
		if ( ! $this->fs->is_dir( $dir ) ) {
			$result = wp_mkdir_p( $dir );

			if ( $result ) {
				$this->fs->chmod( $dir, FS_CHMOD_DIR );
			}
		}
	}

	private function get_snippet_file_path( string $base_dir, int $snippet_id, string $ext ): string {
		return trailingslashit( $base_dir ) . $snippet_id . '.' . $ext;
	}

	private function delete_file( string $file_path ): void {
		if ( $this->fs->exists( $file_path ) ) {
			$this->fs->delete( $file_path );
		}
	}

	public function sync_active_shared_network_snippets( $option, $old_value, $value ): void {
		if ( 'active_shared_network_snippets' !== $option ) {
			return;
		}

		$this->create_active_shared_network_snippets_file( $value );
	}

	public function sync_active_shared_network_snippets_add( $option, $value ): void {
		if ( 'active_shared_network_snippets' !== $option ) {
			return;
		}

		$this->create_active_shared_network_snippets_file( $value );
	}

	private function create_active_shared_network_snippets_file( $value ): void {
		$table = self::get_hashed_table_name( code_snippets()->db->get_table_name( false ) );
		$base_dir = self::get_base_dir( $table );

		$this->maybe_create_directory( $base_dir );

		$file_path = trailingslashit( $base_dir ) . 'active-shared-network-snippets.php';
		$file_content = "<?php\n\nif ( ! defined( 'ABSPATH' ) ) { return; }\n\nreturn " . var_export( $value, true ) . ";\n";

		$this->fs->put_contents( $file_path, $file_content, FS_CHMOD_FILE );
	}

	public static function get_hashed_table_name( string $table ): string {
		return wp_hash( $table );
	}

	public static function get_active_snippets_from_flat_files(
		array $scopes = [],
		$snippet_type = 'php'
	): array {
		$active_snippets = [];
		$db = code_snippets()->db;

		$table = self::get_hashed_table_name( $db->get_table_name() );
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

					if ( ! $snippet['active'] && ! in_array( $id, $active_shared_ids, true ) ) {
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

	private static function sort_active_snippets( array &$active_snippets, $db ): void {
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

		$filtered_snippets = array_filter(
			$file_snippets,
			function ( $snippet ) use ( $scopes, $active_shared_ids ) {
				$is_active = $snippet['active'];

				if ( null !== $active_shared_ids ) {
					$is_active = $is_active || in_array(
						intval( $snippet['id'] ),
						$active_shared_ids,
						true
					);
				}

				return ( $is_active || 'condition' === $snippet['scope'] ) && in_array( $snippet['scope'], $scopes, true );
			}
		);

		wp_cache_set( $cache_key, $filtered_snippets, CACHE_GROUP );

		return $filtered_snippets;
	}

	public function add_settings_fields( array $fields ): array {
		$fields['general']['enable_flat_files'] = [
			'name'  => __( 'Enable file-based execution', 'code-snippets' ),
			'type'  => 'checkbox',
			'label' => __( 'Snippets will be executed directly from files instead of the database.', 'code-snippets' ) . ' ' . sprintf(
				'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
				esc_url( 'https://codesnippets.pro/doc/file-based-execution/' ),
				__( 'Learn more.', 'code-snippets' )
			),
		];

		return $fields;
	}

	public function create_all_flat_files( array $settings, array $input ): void {
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

	private function create_snippet_flat_files(): void {
		$db = code_snippets()->db;

		$scopes = Snippet::get_all_scopes();

		$data = $db->fetch_active_snippets( $scopes );

		foreach ( $data as $snippet ) {
			$snippet_obj = get_snippet( $snippet['id'], $db->ms_table === $snippet['table'] );
			$this->handle_snippet( $snippet_obj, $snippet['table'] );
		}

		if ( is_multisite() ) {
			$current_blog_id = get_current_blog_id();

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

	private function create_active_shared_network_snippets_config_file(): void {
		if ( is_multisite() ) {
			$current_blog_id = get_current_blog_id();
			$sites = get_sites( [ 'fields' => 'ids' ] );
			$db = code_snippets()->db;

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
