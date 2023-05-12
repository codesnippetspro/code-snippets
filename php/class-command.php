<?php

namespace Code_Snippets;

use Exception;
use WP_CLI;
use WP_CLI\ExitException;
use WP_CLI\Formatter;
use WP_CLI_Command;
use function WP_CLI\Utils\report_batch_operation_results;

/**
 * Manages snippets.
 *
 * ## EXAMPLES
 *
 *     # Activate snippet
 *     $ wp snippet activate 42
 *     Success: Activated 1 of 1 snippets.
 *
 *     # Deactivate snippets
 *     $ wp snippet deactivate 21 42
 *     Success: Deactivated 2 of 2 snippets.
 *
 * @package Code_Snippets
 */
class Command extends WP_CLI_Command {

	/**
	 * Item type, used in WP CLI API functions.
	 *
	 * @var string
	 */
	protected $item_type = 'snippet';

	/**
	 * Snippet object fields.
	 *
	 * @see Snippet
	 * @var string[]
	 */
	protected $obj_fields = array(
		'id',
		'name',
		'type',
		'scope',
		'status',
	);

	/**
	 * Register this class as a WP-CLI command.
	 *
	 * @return void
	 */
	public static function register() {
		if ( class_exists( '\WP_CLI' ) ) {
			try {
				WP_CLI::add_command( 'snippet', self::class );
			} catch ( Exception $e ) {
				// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
				trigger_error( esc_html( $e ) );
			}
		}
	}

	/**
	 * Retrieve data formatter for snippets.
	 *
	 * @param array $assoc_args Associative array of associative arguments passed to command.
	 *
	 * @return Formatter
	 */
	protected function get_formatter( array &$assoc_args ): Formatter {
		return new Formatter( $assoc_args, $this->obj_fields, $this->item_type );
	}

	/**
	 * Build an array of snippet information for display from a snippet object.
	 *
	 * @param Snippet $snippet Snippet object.
	 *
	 * @return array Snippet information.
	 */
	protected function build_snippet_info( Snippet $snippet ): array {
		$status = $snippet->active ? 'active' : ( $snippet->shared_network ? 'shared on network' : 'inactive' );

		return [
			'id'          => $snippet->id,
			'name'        => $snippet->display_name,
			'description' => $snippet->desc,
			'code'        => $snippet->code,
			'tags'        => $snippet->tags_list,
			'type'        => $snippet->type,
			'scope'       => $snippet->scope,
			'priority'    => $snippet->priority,
			'status'      => $status,
			'modified'    => $snippet->modified,
		];
	}

	/**
	 * Parse the network argument from those passed to a command.
	 *
	 * @param array $assoc_args Associative array of associative arguments.
	 * @param bool  $default    Value to return if argument is not present. Defaults to 'false'.
	 *
	 * @return bool Value of the argument if present, otherwise the default.
	 */
	protected function parse_network_arg( array $assoc_args, bool $default = false ): bool {
		return $assoc_args['network'] ?? $default;
	}

	/**
	 * Gets a list of snippets.
	 *
	 * Displays a list of the snippets currently stored on the site with associated information.
	 *
	 * ## OPTIONS
	 *
	 * [<id>...]
	 * : Identifiers of snippets to display. Defaults to all snippets.
	 *
	 * [--network]
	 * : Show network-wide snippets instead of site-wide snippets.
	 *
	 * [--<field>=<value>]
	 * : Filter results based on the value of a field.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each snippet.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific snippet fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - count
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each snippet:
	 *
	 * * id
	 * * name
	 * * type
	 * * scope
	 * * status
	 *
	 * These fields are optionally available:
	 *
	 * * description
	 * * tags
	 * * priority
	 * * modified
	 *
	 * ## EXAMPLES
	 *
	 *     # List a subset of snippets on a site.
	 *     $ wp snippet list 71 82 68
	 *     +----+------------------------+------+------------+----------+
	 *     | id | name                   | type | scope      | status   |
	 *     +----+------------------------+------+------------+----------+
	 *     | 68 | Update link colours    | css  | site-css   | inactive |
	 *     | 71 | Admin footer text      | php  | global     | active   |
	 *     | 82 | See trashed post names | php  | single-use | inactive |
	 *     +----+------------------------+------+------------+----------+
	 *
	 * @param array $args       Indexed array of positional arguments.
	 * @param array $assoc_args Associative array of associative arguments.
	 *
	 * @subcommand list
	 * @throws ExitException If no snippets available to list.
	 */
	public function list_snippets( array $args, array $assoc_args ) {
		$snippets = get_snippets( $args, $this->parse_network_arg( $assoc_args ) );
		$items = [];

		if ( ! $snippets ) {
			WP_CLI::error( 'No snippets found.' );
		}

		foreach ( $snippets as $snippet ) {
			$item = $this->build_snippet_info( $snippet );
			$include = true;

			foreach ( $this->obj_fields as $field ) {
				if ( ! array_key_exists( $field, $assoc_args ) ) {
					continue;
				}

				$field_filter = $assoc_args[ $field ];
				if ( $item[ $field ] !== $field_filter &&
				     ! in_array( $item[ $field ], array_map( 'trim', explode( ',', $field_filter ) ), true ) ) {
					$include = false;
				}
			}

			if ( $include ) {
				$items[] = $item;
			}
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_items( $items );
	}

	/**
	 * Gets details about a snippet.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : ID of the snippet to get.
	 *
	 * [--network]
	 * : Get a network-wide snippet instead of a side-wide snippet.
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole snippet, returns the value of a single field.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields. Defaults to all fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Get a snippet in JSON format
	 *     $ wp snippet get 42 --format=json --fields=id,name,status
	 *     {"id":42,"name":"Snippet name","status":"inactive"}
	 *
	 * @param array $args       Indexed array of positional arguments.
	 * @param array $assoc_args Associative array of associative arguments.
	 */
	public function get( array $args, array $assoc_args ) {
		$snippet = get_snippet( intval( $args[0] ), $this->parse_network_arg( $assoc_args ) );
		$snippet_info = (object) $this->build_snippet_info( $snippet );

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array_keys( get_object_vars( $snippet_info ) );
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_item( $snippet_info );
	}

	/**
	 * Activates one or more snippets.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : Identifiers of one or more snippets to activate.
	 *
	 * [--network]
	 * : Activates network-wide snippets instead of side-wide snippets.
	 *
	 * ## EXAMPLES
	 *
	 *     # Activate network snippet
	 *     wp snippet activate 42 --network
	 *     Success: Network activated 1 of 1 snippets.
	 *
	 * @param array $args       Indexed array of positional arguments.
	 * @param array $assoc_args Associative array of associative arguments.
	 */
	public function activate( array $args, array $assoc_args ) {
		$network = $this->parse_network_arg( $assoc_args );
		$activated = activate_snippets( $args, $network );

		report_batch_operation_results(
			'snippet',
			$network ? 'network activate' : 'activate',
			count( $args ),
			count( $activated ),
			count( $args ) - count( $activated )
		);
	}

	/**
	 * Deactivates one or more snippets.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : Identifiers of one or more snippets to deactivate.
	 *
	 * [--network]
	 * : Deactivates network-wide snippets instead of side-wide snippets.
	 *
	 * ## EXAMPLES
	 *
	 *     # Deactivate snippets
	 *     wp snippet deactivate 11 19
	 *     Success: Deactivated 2 of 2 snippets.
	 *
	 * @param array $args       Indexed array of positional arguments.
	 * @param array $assoc_args Associative array of associative arguments.
	 */
	public function deactivate( array $args, array $assoc_args ) {
		$network = $this->parse_network_arg( $assoc_args );
		$successes = [];

		foreach ( $args as $id ) {
			if ( deactivate_snippet( intval( $id ), $network ) ) {
				$successes[] = $id;
			}
		}

		report_batch_operation_results(
			'snippet',
			$network ? 'network deactivate' : 'deactivate',
			count( $args ),
			count( $successes ),
			count( $args ) - count( $successes )
		);
	}

	/**
	 * Deletes one or more snippets.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : Identifiers of one or more snippets to delete.
	 *
	 * [--network]
	 * : Deletes network-wide snippets instead of side-wide snippets.
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete snippet
	 *     $ wp snippet delete 77
	 *     Success: Deleted 1 of 1 snippets.
	 *
	 * @param array $args       Indexed array of positional arguments.
	 * @param array $assoc_args Associative array of associative arguments.
	 */
	public function delete( array $args, array $assoc_args ) {
		$network = $this->parse_network_arg( $assoc_args );
		$successes = [];

		foreach ( $args as $id ) {
			if ( delete_snippet( intval( $id ), $network ) ) {
				$successes[] = $id;
			}
		}

		report_batch_operation_results(
			'snippet',
			'delete',
			count( $args ),
			count( $successes ),
			count( $args ) - count( $successes )
		);
	}

	/**
	 * Creates or updates a snippet.
	 *
	 * ## OPTIONS
	 *
	 * [--id=<number>]
	 * : Identifier of snippet to update.
	 *
	 * [--name=<string>]
	 * : Snippet title. Optional.
	 *
	 * [--desc=<string>]
	 * : Snippet description. Optional.
	 *
	 * [--code=<string>]
	 * : Snippet code. Optional.
	 *
	 *  [--tags=<array>]
	 * : Snippet tags. Optional.
	 *
	 * [--scope=<scope>]
	 * : Snippet scope.
	 * ---
	 * default: global
	 * options:
	 *   - global
	 *   - admin
	 *   - front-end
	 *   - single-use
	 *   - content
	 *   - head-content
	 *   - footer-content
	 *   - admin-css
	 *   - site-css
	 *   - site-head-js
	 *   - site-footer-js
	 * ---
	 *
	 * [--priority=<number>]
	 * : Snippet priority. Defaults to 10.
	 *
	 * [--network]
	 * : Create a network-wide snippet instead of a side-wide snippet.
	 *
	 * @param array $args       Indexed array of positional arguments.
	 * @param array $assoc_args Associative array of associative arguments.
	 *
	 * @alias add
	 * @throws ExitException If issue encountered saving snippet data.
	 */
	public function update( array $args, array $assoc_args ) {
		$snippet_id = isset( $assoc_args['id'] ) ? intval( $assoc_args['id'] ) : 0;
		$snippet = 0 === $snippet_id ? new Snippet() :
			get_snippet( $snippet_id, $this->parse_network_arg( $assoc_args ) );

		foreach ( $assoc_args as $field => $value ) {
			$snippet->set_field( $field, $value );
		}

		$result_id = save_snippet( $snippet );
		if ( 0 === $result_id ) {
			WP_CLI::error( 'Could not save snippet data.' );
		}

		WP_CLI::success( 0 === $snippet_id ? 'Snippet created.' : 'Snippet updated.' );

		$assoc_args['fields'] = array( 'id', 'name', 'type', 'description', 'code', 'tags', 'scope', 'priority', 'status' );
		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_item( $this->build_snippet_info( $snippet ) );
	}

	/**
	 * Saves code snippets to an export file.
	 *
	 * ## OPTIONS
	 *
	 * [<id>...]
	 * : Identifiers of one or more snippets to include in the export file. Defaults to all snippets.
	 *
	 * [--network]
	 * : Exports network-wide snippets instead of side-wide snippets.
	 *
	 * [--dir=<dirname>]
	 * : Full path to directory where WXR export files should be stored. Defaults to current working directory.
	 *
	 * [--filename_format=<format>]
	 * : Use a custom format for export filenames. Defaults to '{site|snippet}.code-snippets.{date}.json'.
	 *
	 * [--stdout]
	 * : Output the whole XML using standard output (incompatible with --dir=)
	 *
	 * @param array $ids        Indexed array of positional arguments.
	 * @param array $assoc_args Associative array of associative arguments.
	 *
	 * @throws ExitException If invalid arguments provided or error encountered writing to export file.
	 */
	public function export( array $ids, array $assoc_args ) {
		$assoc_args = wp_parse_args(
			$assoc_args,
			array(
				'network'         => false,
				'stdOut'          => false,
				'dir'             => '',
				'filename_format' => '',
			)
		);

		$export = new Export( $ids, code_snippets()->db->get_table_name( $assoc_args['network'] ) );
		$data = wp_json_encode( $export->create_export_object() );

		if ( $assoc_args['stdout'] && ( $assoc_args['dir'] || $assoc_args['filename_format'] ) ) {
			WP_CLI::error( '--stdout and --dir cannot be used together.' );
		}

		if ( $assoc_args['stdout'] ) {
			$filename = 'php://output';
		} else {
			$path = realpath( $assoc_args['dir'] ? untrailingslashit( $assoc_args['dir'] ) : getcwd() );

			if ( ! is_dir( $path ) ) {
				WP_CLI::error( sprintf( "The directory '%s' does not exist.", $path ) );
			} elseif ( ! is_writable( $path ) ) {
				WP_CLI::error( sprintf( "The directory '%s' is not writable.", $path ) );
			}

			$filename = $path . DIRECTORY_SEPARATOR .
			            sanitize_file_name( $assoc_args['filename_format'] ?: $export->build_filename( 'json' ) );
		}

		// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_read_fopen
		$handle = fopen( $filename, 'w' );
		if ( ! $handle ) {
			WP_CLI::error( "Cannot open '$filename' for writing." );
		}

		// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_read_fwrite
		fwrite( $handle, $data );
		// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_read_fclose
		fclose( $handle );

		if ( ! $assoc_args['stdout'] ) {
			WP_CLI::success( "Exported snippets to '$filename'." );
		}
	}

	/**
	 * Imports content from a given Code Snippets export file.
	 *
	 * ## OPTIONS
	 *
	 * <file>...
	 * : Path to one or more valid .code-snippets.json files for importing. Directories are also accepted.
	 *
	 * [--network]
	 * : Import into the network-wide snippets table instead of the side-wide table.
	 *
	 * [--dup-action=<action>]
	 * : How duplicate snippets should be handled.
	 * ---
	 * default: ignore
	 * options:
	 *   - skip
	 *   - ignore
	 *   - replace
	 * ---
	 *
	 * @param array $args       Indexed array of positional arguments.
	 * @param array $assoc_args Associative array of associative arguments.
	 */
	public function import( array $args, array $assoc_args ) {
		$assoc_args = wp_parse_args(
			$assoc_args,
			array(
				'network'    => false,
				'dup_action' => 'ignore',
			)
		);

		$files = array();
		foreach ( $args as $file ) {
			if ( is_dir( $file ) ) {
				$dir_files = glob( trailingslashit( $file ) . '*.code-snippets.json' );
				if ( ! empty( $dir_files ) ) {
					$files = array_merge( $files, $dir_files );
				}
			} else {
				if ( file_exists( $file ) ) {
					$files[] = $file;
				}
			}
		}

		foreach ( $files as $file ) {
			if ( ! is_readable( $file ) ) {
				WP_CLI::warning( "Cannot read '$file' file." );
				continue;
			}

			$import = new Import( $file, $assoc_args['network'], $assoc_args['dup_action'] );
			$result = $import->import_json();

			if ( false === $result ) {
				WP_CLI::warning( "Failed to import from '$file' file." );
			} else {
				WP_CLI::success(
					sprintf(
						"Imported %d %s from '%s' file.",
						count( $result ),
						1 === count( $result ) ? 'snippet' : 'snippets',
						$file
					)
				);
			}
		}
	}
}
