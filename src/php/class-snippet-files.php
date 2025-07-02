<?php

namespace Code_Snippets;

class Snippet_Files {

	/**
	 * Holds the WP_Filesystem instance.
	 *
	 * @var \WP_Filesystem_Base
	 */
	private $fs;

	const TYPES_TO_HANDLE = [ 'php', 'html' ];

	public function init() {
		$this->ensure_filesystem();
		$this->register_hooks();
	}

	public function register_hooks() {
		add_action( 'code_snippets/create_snippet', [ $this, 'handle_snippet' ], 10, 2 );
		add_action( 'code_snippets/update_snippet', [ $this, 'handle_snippet' ], 10, 2 );
		add_action( 'code_snippets/delete_snippet', [ $this, 'delete_snippet' ], 10, 2 );
		add_action( 'code_snippets/activate_snippet', [ $this, 'activate_snippet' ], 10, 2 );
		add_action( 'code_snippets/deactivate_snippet', [ $this, 'deactivate_snippet' ], 10, 2 );
	}

	private function ensure_filesystem() {
		if ( ! $this->fs ) {
			if ( ! function_exists( 'WP_Filesystem' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}

			WP_Filesystem();

			global $wp_filesystem;

			$this->fs = $wp_filesystem;
		}
	}

	private function should_handle_snippet( string $snippet_type ) {
		return in_array( $snippet_type, self::TYPES_TO_HANDLE, true );
	}

	public function handle_snippet( Snippet $snippet, string $table ) {
		$snippet_type = $snippet->get_type();
		if ( ! $this->should_handle_snippet( $snippet_type ) ) {
			return;
		}

		$base_dir = self::get_base_dir( $table, $snippet_type );
		$this->maybe_create_directory( $base_dir );

		$file_path = $this->get_snippet_file_path( $base_dir, $snippet->id );

		$this->write_snippet_file( $file_path, $snippet->code, $snippet_type );

		$this->update_config_file( $base_dir, $snippet );
	}

	public function delete_snippet( Snippet $snippet, bool $network ) {
		$snippet_type = $snippet->get_type();
		if ( ! $this->should_handle_snippet( $snippet_type ) ) {
			return;
		}

		$table = code_snippets()->db->get_table_name( $network );
		$base_dir = self::get_base_dir( $table, $snippet_type );

		$file_path = $this->get_snippet_file_path( $base_dir, $snippet->id );
		$this->delete_file( $file_path );

		$this->update_config_file( $base_dir, $snippet, true );
	}

	public function activate_snippet( Snippet $snippet, bool $network ) {
		$snippet_type = $snippet->get_type();
		if ( ! $this->should_handle_snippet( $snippet_type ) ) {
			return;
		}

		$table = code_snippets()->db->get_table_name( $network );
		$base_dir = self::get_base_dir( $table, $snippet_type );

		$this->maybe_create_directory( $base_dir );

		$file_path = $this->get_snippet_file_path( $base_dir, $snippet->id );
		$this->write_snippet_file( $file_path, $snippet->code, $snippet_type );

		$this->update_config_file( $base_dir, $snippet );
	}

	public function deactivate_snippet( int $snippet_id, bool $network ) {
		$snippet = get_snippet( $snippet_id, $network );
		$snippet_type = $snippet->get_type();

		if ( ! $this->should_handle_snippet( $snippet_type ) ) {
			return;
		}

		$table = code_snippets()->db->get_table_name( $network );
		$base_dir = self::get_base_dir( $table, $snippet_type );

		$this->update_config_file( $base_dir, $snippet );
	}

	/**
	 * Returns the base directory path for a given table.
	 */
	public static function get_base_dir( string $table, string $snippet_type ) {
		return WP_CONTENT_DIR . '/code-snippets/' . $table . '/' . $snippet_type;
	}

	/**
	 * Creates the directory if it does not exist.
	 */
	private function maybe_create_directory( string $dir ) {
		if ( ! $this->fs->is_dir( $dir ) ) {
			$this->fs->mkdir( $dir, FS_CHMOD_DIR );
		}
	}

	/**
	 * Returns the path to the snippet PHP file.
	 */
	private function get_snippet_file_path( string $base_dir, int $snippet_id ) {
		return trailingslashit( $base_dir ) . $snippet_id . '.php';
	}

	/**
	 * Writes the snippet code to a file, with the required header.
	 */
	private function write_snippet_file( string $file_path, string $code, string $snippet_type ) {
		$content = "<?php\n\nif ( ! defined( 'ABSPATH' ) ) { return; }\n\n";

		if ( 'html' === $snippet_type ) {
			$content .= "?>\n\n";
		}

		$content .= $code;

		$this->fs->put_contents( $file_path, $content, FS_CHMOD_FILE );
	}

	/**
	 * Deletes a file if it exists.
	 */
	private function delete_file( string $file_path ) {
		if ( $this->fs->exists( $file_path ) ) {
			$this->fs->delete( $file_path );
		}
	}

	/**
	 * Loads the index.php array by requiring it directly.
	 */
	private function load_config_file( string $config_file_path ) {
		return is_file( $config_file_path ) ? require $config_file_path : [];
	}

	/**
	 * Saves the index.php file via WP_Filesystem.
	 */
	private function save_config_file( string $config_file_path, array $active_snippets ) {
		$index_content = "<?php\n\nif ( ! defined( 'ABSPATH' ) ) { return; }\n\nreturn " . var_export( $active_snippets, true ) . ";\n";
		$this->fs->put_contents( $config_file_path, $index_content, FS_CHMOD_FILE );
	}

	/**
	 * Updates the index.php file with snippet config.
	 */
	private function update_config_file( string $base_dir, Snippet $snippet, bool $remove = false ) {
		$config_file_path = trailingslashit( $base_dir ) . 'index.php';
		$active_snippets = $this->load_config_file( $config_file_path );

		if ( $remove ) {
			unset( $active_snippets[ $snippet->id ] );
		} else {
			$active_snippets[ $snippet->id ] = $snippet->get_fields();
		}

		$this->save_config_file( $config_file_path, $active_snippets );
	}
}
