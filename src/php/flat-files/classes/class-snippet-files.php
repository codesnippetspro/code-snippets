<?php

namespace Code_Snippets;

class Snippet_Files {

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

	public function register_hooks() {
		if ( Settings\get_setting( 'general', 'enable_flat_files' ) ) {
			add_action( 'code_snippets/create_snippet', [ $this, 'handle_snippet' ], 10, 2 );
			add_action( 'code_snippets/update_snippet', [ $this, 'handle_snippet' ], 10, 2 );
			add_action( 'code_snippets/delete_snippet', [ $this, 'delete_snippet' ], 10, 2 );
			add_action( 'code_snippets/activate_snippet', [ $this, 'activate_snippet' ], 10, 2 );
			add_action( 'code_snippets/deactivate_snippet', [ $this, 'deactivate_snippet' ], 10, 2 );

			add_action( 'updated_option', [ $this, 'sync_active_shared_network_snippets' ], 10, 3 );
		}

		add_filter( 'code_snippets_settings_fields', [ $this, 'add_settings_fields' ], 10, 1 );
		add_action( 'code_snippets/settings_updated', [ $this, 'create_all_flat_files' ], 10, 2 );
	}

	public function handle_snippet( Snippet $snippet, string $table ) {
		$snippet_type = $snippet->get_type();
		$handler = $this->handler_registry->get_handler( $snippet_type );

		if ( ! $handler ) {
			return;
		}

		$base_dir = self::get_base_dir( $table, $handler->get_dir_name() );
		$this->maybe_create_directory( $base_dir );

		$file_path = $this->get_snippet_file_path( $base_dir, $snippet->id, $handler->get_file_extension() );

		$contents = $handler->wrap_code( $snippet->code );

		$this->fs->put_contents( $file_path, $contents, FS_CHMOD_FILE );

		$this->config_repo->update( $base_dir, $snippet );
	}

	public function delete_snippet( Snippet $snippet, bool $network ) {
		$snippet_type = $snippet->get_type();
		$handler = $this->handler_registry->get_handler( $snippet_type );

		if ( ! $handler ) {
			return;
		}

		$table = code_snippets()->db->get_table_name( $network );
		$base_dir = self::get_base_dir( $table, $handler->get_dir_name() );

		$file_path = $this->get_snippet_file_path( $base_dir, $snippet->id, $handler->get_file_extension() );
		$this->delete_file( $file_path );

		$this->config_repo->update( $base_dir, $snippet, true );
	}

	public function activate_snippet( Snippet $snippet, bool $network ) {
		$snippet = get_snippet( $snippet->id, $network );
		$snippet_type = $snippet->get_type();
		$handler = $this->handler_registry->get_handler( $snippet_type );

		if ( ! $handler ) {
			return;
		}

		$table = code_snippets()->db->get_table_name( $network );
		$base_dir = self::get_base_dir( $table, $handler->get_dir_name() );

		$this->maybe_create_directory( $base_dir );

		$file_path = $this->get_snippet_file_path( $base_dir, $snippet->id, $handler->get_file_extension() );

		$contents = $handler->wrap_code( $snippet->code );

		$this->fs->put_contents( $file_path, $contents, FS_CHMOD_FILE );

		$this->config_repo->update( $base_dir, $snippet );
	}

	public function deactivate_snippet( int $snippet_id, bool $network ) {
		$snippet = get_snippet( $snippet_id, $network );
		$snippet_type = $snippet->get_type();
		$handler = $this->handler_registry->get_handler( $snippet_type );

		if ( ! $handler ) {
			return;
		}

		$table = code_snippets()->db->get_table_name( $network );
		$base_dir = self::get_base_dir( $table, $handler->get_dir_name() );

		$this->config_repo->update( $base_dir, $snippet );
	}

	public static function get_base_dir( string $table = '', string $snippet_type = '' ) {
		$base_dir = WP_CONTENT_DIR . '/code-snippets';

		if ( ! empty( $table ) ) {
			$base_dir .= '/' . $table;
		}

		if ( ! empty( $snippet_type ) ) {
			$base_dir .= '/' . $snippet_type;
		}

		return $base_dir;
	}

	private function maybe_create_directory( string $dir ) {
		if ( ! $this->fs->is_dir( $dir ) ) {
			$this->fs->mkdir( $dir, FS_CHMOD_DIR );
		}
	}

	private function get_snippet_file_path( string $base_dir, int $snippet_id, string $ext ) {
		return trailingslashit( $base_dir ) . $snippet_id . '.' . $ext;
	}

	private function delete_file( string $file_path ) {
		if ( $this->fs->exists( $file_path ) ) {
			$this->fs->delete( $file_path );
		}
	}

	public function sync_active_shared_network_snippets( $option, $old_value, $value ) {
		if ( 'active_shared_network_snippets' !== $option ) {
			return;
		}

		$table = code_snippets()->db->get_table_name();
		$base_dir = self::get_base_dir( $table );

		$this->maybe_create_directory( $base_dir );

		$file_path = trailingslashit( $base_dir ) . 'active-shared-network-snippets.php';
		$file_content = "<?php\n\nif ( ! defined( 'ABSPATH' ) ) { return; }\n\nreturn " . var_export( $value, true ) . ";\n";

		$this->fs->put_contents( $file_path, $file_content, FS_CHMOD_FILE );
	}

	public static function get_active_snippets_from_flat_files() {
		$snippets = [];

		$table = code_snippets()->db->get_table_name();
		$base_dir = self::get_base_dir( $table, 'php' );
		$snippets_file_path = $base_dir . '/index.php';

		if ( is_file( $snippets_file_path ) ) {
			$site_snippets = is_file( $snippets_file_path ) ? require $snippets_file_path : [];

			$snippets[ $table ] = array_filter(
				$site_snippets,
				function ( $snippet ) {
					return $snippet['active'];
				}
			);
		}

		if ( is_multisite() ) {
			$ms_table = code_snippets()->db->get_table_name( true );
			$ms_base_dir = self::get_base_dir( $ms_table, 'php' );
			$ms_snippets_file_path = $ms_base_dir . '/index.php';

			if ( is_file( $ms_snippets_file_path ) ) {
				$ms_snippets = is_file( $ms_snippets_file_path ) ? require $ms_snippets_file_path : [];

				$root_base_dir = self::get_base_dir( $table );
				$active_shared_ids_file_path = $root_base_dir . '/active-shared-network-snippets.php';
				$active_shared_ids = is_file( $active_shared_ids_file_path ) ? require $active_shared_ids_file_path : [];

				$snippets[ $ms_table ] = array_filter(
					$ms_snippets,
					function ( $snippet ) use ( $active_shared_ids ) {
						return $snippet['active'] || in_array( intval( $snippet['id'] ), $active_shared_ids, true );
					}
				);
			}
		}

		return $snippets;
	}

	public function add_settings_fields( array $fields ) {
		$fields['general']['enable_flat_files'] = [
			'name'  => __( 'Enable Flat Files', 'code-snippets' ),
			'type'  => 'checkbox',
			'label' => __( 'Snippets will be executed from flat files instead of the database.', 'code-snippets' ),
		];

		return $fields;
	}

	public function create_all_flat_files( array $settings, array $input ) {
		if ( ! isset( $settings['general']['enable_flat_files'] ) ) {
			return;
		}

		if ( ! $settings['general']['enable_flat_files'] ) {
			return;
		}

		$db = code_snippets()->db;
		$data = $db->fetch_active_snippets( Snippet::get_all_scopes() );

		if ( empty( $data ) ) {
			return;
		}

		foreach ( $data as $table_name => $active_snippets ) {
			foreach ( $active_snippets as $snippet ) {
				$snippet_obj = get_snippet( $snippet['id'], $table_name === $db->ms_table );
				$this->handle_snippet( $snippet_obj, $table_name );
			}
		}
	}
}
