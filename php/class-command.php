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
				trigger_error( $e );
			}
		}
	}

	/**
	 * Retrieve data formatter for snippets.
	 *
	 * @param array $assoc_args
	 *
	 * @return Formatter
	 */
	protected function get_formatter( &$assoc_args ) {
		return new Formatter( $assoc_args, $this->obj_fields, $this->item_type );
	}

	/**
	 * Build an array of snippet information for display from a snippet object.
	 *
	 * @param Snippet $snippet Snippet object.
	 *
	 * @return array Snippet information.
	 */
	protected function build_snippet_info( Snippet $snippet ) {
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
	 * @throws ExitException
	 */
	public function list_snippets( $args, $assoc_args ) {
		$snippets = get_snippets( $args, $assoc_args['network'] );
		$items = [];

		if ( ! is_array( $snippets ) ) {
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
	 */
	public function get( $args, $assoc_args ) {
		$snippet = get_snippet( $args[0], $assoc_args['network'] );
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
	 * : Activate a network-wide snippet instead of a side-wide snippet.
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
	public function activate( $args, $assoc_args ) {
		$activated = activate_snippets( $args, $assoc_args['network'] );

		report_batch_operation_results(
			'snippet',
			$assoc_args['network'] ? 'network activate' : 'activate',
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
	 * : Deactivates network-wide snippets instead of a side-wide snippets.
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
	public function deactivate( $args, $assoc_args ) {
		$successes = [];

		foreach ( $args as $id ) {
			if ( deactivate_snippet( $id, $assoc_args['network'] ) ) {
				$successes[] = $id;
			}
		}

		report_batch_operation_results(
			'snippet',
			$assoc_args['network'] ? 'network deactivate' : 'deactivate',
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
	 * : Deletes network-wide snippets instead of a side-wide snippets.
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
	public function delete( $args, $assoc_args ) {
		$successes = [];

		foreach ( $args as $id ) {
			if ( delete_snippet( $id, $assoc_args['network'] ) ) {
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
	 * : Create a network-wide snippets instead of a side-wide snippet.
	 *
	 * ## EXAMPLES
	 *
	 *     TODO
	 *
	 * @param array $args       Indexed array of positional arguments.
	 * @param array $assoc_args Associative array of associative arguments.
	 *
	 * @alias add
	 * @throws ExitException
	 */
	public function update( $args, $assoc_args ) {
		$snippet_id = isset( $assoc_args['id'] ) ? intval( $assoc_args['id'] ) : 0;
		$snippet = 0 === $snippet_id ? new Snippet() :
			get_snippet( $snippet_id, isset( $assoc_args['network'] ) ? $assoc_args['network'] : null );

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
}
