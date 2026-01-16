<?php

namespace Code_Snippets;

use WP_REST_Server;
use WP_REST_Request;
use WP_Error;
use const Code_Snippets\REST_API_NAMESPACE;

class Files_Import_Manager {

	const VERSION = 1;

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	public function register_rest_routes() {
		$namespace = REST_API_NAMESPACE . self::VERSION;

		register_rest_route( $namespace, 'file-upload/parse', [
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => [ $this, 'parse_uploaded_files' ],
			'permission_callback' => function() {
				return current_user_can( 'manage_options' );
			},
		] );

		register_rest_route( $namespace, 'file-upload/import', [
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => [ $this, 'import_selected_snippets' ],
			'permission_callback' => function() {
				return current_user_can( 'manage_options' );
			},
			'args' => [
				'snippets' => [
					'description' => __( 'Array of snippet data to import', 'code-snippets' ),
					'type' => 'array',
					'required' => true,
				],
				'duplicate_action' => [
					'description' => __( 'Action to take when duplicate snippets are found', 'code-snippets' ),
					'type' => 'string',
					'enum' => [ 'ignore', 'replace', 'skip' ],
					'default' => 'ignore',
				],
				'network' => [
					'description' => __( 'Whether to import to network table', 'code-snippets' ),
					'type' => 'boolean',
					'default' => false,
				],
			],
		] );
	}

	public function parse_uploaded_files( WP_REST_Request $request ) {
		// Verify nonce for security
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'rest_cookie_invalid_nonce',
				__( 'Cookie check failed', 'code-snippets' ),
				[ 'status' => 403 ]
			);
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above via REST API header
		if ( empty( $_FILES['files'] ) ) {
			return new WP_Error(
				'no_files',
				__( 'No files were uploaded.', 'code-snippets' ),
				[ 'status' => 400 ]
			);
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Nonce verified above, file data validated below
		$files = $_FILES['files'];

		if ( ! isset( $files['name'], $files['type'], $files['tmp_name'], $files['error'] ) ) {
			return new WP_Error(
				'invalid_file_data',
				__( 'Invalid file upload data.', 'code-snippets' ),
				[ 'status' => 400 ]
			);
		}

		$all_snippets = [];
		$errors = [];

		$file_count = is_array( $files['name'] ) ? count( $files['name'] ) : 1;

		for ( $i = 0; $i < $file_count; $i++ ) {
			$file_name = is_array( $files['name'] ) ? $files['name'][ $i ] : $files['name'];
			$file_type = is_array( $files['type'] ) ? $files['type'][ $i ] : $files['type'];
			$file_tmp = is_array( $files['tmp_name'] ) ? $files['tmp_name'][ $i ] : $files['tmp_name'];
			$file_error = is_array( $files['error'] ) ? $files['error'][ $i ] : $files['error'];

			if ( UPLOAD_ERR_OK !== $file_error ) {
				$errors[] = sprintf(
					/* translators: %1$s: file name, %2$s: error message */
					__( 'Upload error for file %1$s: %2$s', 'code-snippets' ),
					$file_name,
					$this->get_upload_error_message( $file_error )
				);
				continue;
			}

			$file_info = pathinfo( $file_name );
			$extension = strtolower( $file_info['extension'] ?? '' );
			$mime_type = sanitize_mime_type( $file_type );

			if ( ! $this->is_valid_file_type( $extension, $mime_type ) ) {
				$errors[] = sprintf(
					/* translators: %s: file name */
					__( 'Invalid file type for %s. Only JSON and XML files are allowed.', 'code-snippets' ),
					$file_name,
				);
				continue;
			}

			$snippets = $this->parse_file_content( $file_tmp, $extension, $mime_type, $file_name );

			if ( is_wp_error( $snippets ) ) {
				$errors[] = sprintf(
					/* translators: %1$s: file name, %2$s: error message */
					__( 'Error parsing %1$s: %2$s', 'code-snippets' ),
					$file_name,
					$snippets->get_error_message(),
				);
			} else {
				$all_snippets = array_merge( $all_snippets, $snippets );
			}
		}

		if ( empty( $all_snippets ) ) {
			return new WP_Error(
				'no_snippets_found',
				__( 'No valid snippets found in the uploaded files.', 'code-snippets' ),
				[
					'status' => 400,
					'errors' => $errors,
				],
			);
		}

		$response = [
			'snippets' => $all_snippets,
			'total_count' => count( $all_snippets ),
			'message' => sprintf(
				/* translators: %d: number of snippets */
				_n(
					'Found %d snippet ready for import.',
					'Found %d snippets ready for import.',
					count( $all_snippets ),
					'code-snippets',
				),
				count( $all_snippets )
			),
		];

		if ( ! empty( $errors ) ) {
			$response['warnings'] = $errors;
		}

		return rest_ensure_response( $response );
	}

	public function import_selected_snippets( WP_REST_Request $request ) {
		$snippets_data = $request->get_param( 'snippets' );
		$duplicate_action = $request->get_param( 'duplicate_action' ) ?? 'ignore';
		$network = $request->get_param( 'network' ) ?? false;

		if ( empty( $snippets_data ) || ! is_array( $snippets_data ) ) {
			return new WP_Error(
				'no_snippets',
				__( 'No snippet data provided for import.', 'code-snippets' ),
				[ 'status' => 400 ]
			);
		}

		$snippets = [];
		foreach ( $snippets_data as $snippet_data ) {
			$snippet = new Snippet();
			$snippet->network = $network;

			$import_fields = [
				'name',
				'desc',
				'description',
				'code',
				'tags',
				'scope',
				'priority',
				'shared_network',
				'modified',
				'cloud_id',
			];

			foreach ( $import_fields as $field ) {
				if ( isset( $snippet_data[ $field ] ) ) {
					$snippet->set_field( $field, $snippet_data[ $field ] );
				}
			}

			$snippets[] = $snippet;
		}

		$imported = $this->save_snippets( $snippets, $duplicate_action, $network );

		$response = [
			'imported' => count( $imported ),
			'imported_ids' => $imported,
			'message' => sprintf(
				/* translators: %d: number of snippets */
				_n(
					'Successfully imported %d snippet.',
					'Successfully imported %d snippets.',
					count( $imported ),
					'code-snippets',
				),
				count( $imported )
			),
		];

		return rest_ensure_response( $response );
	}

	private function parse_file_content( string $file_path, string $extension, string $mime_type, string $file_name ) {
		if ( ! file_exists( $file_path ) || ! is_file( $file_path ) ) {
			return new WP_Error(
				'file_not_found',
				__( 'File not found or is not a valid file.', 'code-snippets' )
			);
		}

		if ( 'json' === $extension || 'application/json' === $mime_type ) {
			return $this->parse_json_file( $file_path, $file_name );
		} elseif ( 'xml' === $extension || in_array( $mime_type, [ 'text/xml', 'application/xml' ], true ) ) {
			return $this->parse_xml_file( $file_path, $file_name );
		}

		return new WP_Error(
			'unsupported_file_type',
			__( 'Unsupported file type.', 'code-snippets' )
		);
	}

	private function parse_json_file( string $file_path, string $file_name ) {
		$raw_data = file_get_contents( $file_path );
		$data = json_decode( $raw_data, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error(
				'invalid_json',
				sprintf(
					/* translators: %1$s: file name, %2$s: error message */
					__( 'Invalid JSON in file %1$s: %2$s', 'code-snippets' ),
					$file_name,
					json_last_error_msg()
				)
			);
		}

		if ( ! isset( $data['snippets'] ) || ! is_array( $data['snippets'] ) ) {
			return new WP_Error(
				'no_snippets_in_file',
				sprintf(
					/* translators: %s: file name */
					__( 'No snippets found in file %s', 'code-snippets' ),
					$file_name
				)
			);
		}

		$snippets = [];
		foreach ( $data['snippets'] as $snippet_data ) {
			$snippet_data['source_file'] = $file_name;

			$snippet_data['table_data'] = [
				'id' => $snippet_data['id'] ?? uniqid(),
				'title' => $snippet_data['name'] ?? __( 'Untitled Snippet', 'code-snippets' ),
				'scope' => $snippet_data['scope'] ?? 'global',
				'tags' => is_array( $snippet_data['tags'] ?? null ) ? implode( ', ', $snippet_data['tags'] ) : '',
				'description' => $snippet_data['desc'] ?? $snippet_data['description'] ?? '',
				'type' => Snippet::get_type_from_scope( $snippet_data['scope'] ?? 'global' )
			];

			$snippets[] = $snippet_data;
		}

		return $snippets;
	}

	private function parse_xml_file( string $file_path, string $file_name ) {
		$dom = new \DOMDocument( '1.0', get_bloginfo( 'charset' ) );

		if ( ! $dom->load( $file_path ) ) {
			return new WP_Error(
				'invalid_xml',
				sprintf(
					/* translators: %s: file name */
					__( 'Invalid XML in file %s', 'code-snippets' ),
					$file_name
				)
			);
		}

		$snippets_xml = $dom->getElementsByTagName( 'snippet' );
		$fields = [ 'name', 'description', 'desc', 'code', 'tags', 'scope' ];

		$snippets = [];
		$index = 0;

		foreach ( $snippets_xml as $snippet_xml ) {
			$snippet_data = [];

			foreach ( $fields as $field_name ) {
				$field = $snippet_xml->getElementsByTagName( $field_name )->item( 0 );

				if ( isset( $field->nodeValue ) ) {
					$snippet_data[ $field_name ] = $field->nodeValue;
				}
			}

			$scope = $snippet_xml->getAttribute( 'scope' );
			if ( ! empty( $scope ) ) {
				$snippet_data['scope'] = $scope;
			}

			$snippet_data['source_file'] = $file_name;

			$snippet_data['table_data'] = [
				'id' => ++$index,
				'title' => $snippet_data['name'] ?? __( 'Untitled Snippet', 'code-snippets' ),
				'scope' => $snippet_data['scope'] ?? 'global',
				'tags' => $snippet_data['tags'] ?? '',
				'description' => $snippet_data['desc'] ?? $snippet_data['description'] ?? '',
				'type' => Snippet::get_type_from_scope( $snippet_data['scope'] ?? 'global' ),
			];

			$snippets[] = $snippet_data;
		}

		return $snippets;
	}

	private function save_snippets( array $snippets, string $duplicate_action, bool $network ): array {
		$existing_snippets = [];

		if ( 'replace' === $duplicate_action || 'skip' === $duplicate_action ) {
			$all_snippets = get_snippets( [], $network );

			foreach ( $all_snippets as $snippet ) {
				if ( $snippet->name ) {
					$existing_snippets[ $snippet->name ] = $snippet->id;
				}
			}
		}

		$imported = [];

		foreach ( $snippets as $snippet ) {
			if ( 'ignore' !== $duplicate_action && isset( $existing_snippets[ $snippet->name ] ) ) {
				if ( 'replace' === $duplicate_action ) {
					$snippet->id = $existing_snippets[ $snippet->name ];
				} elseif ( 'skip' === $duplicate_action ) {
					continue;
				}
			}

			$saved_snippet = save_snippet( $snippet );

			$snippet_id = $saved_snippet->id;

			if ( $snippet_id ) {
				$imported[] = $snippet_id;
			}
		}

		return $imported;
	}

	private function is_valid_file_type( string $extension, string $mime_type ): bool {
		$valid_extensions = [ 'json', 'xml' ];
		$valid_mime_types = [ 'application/json', 'text/xml', 'application/xml' ];

		return in_array( $extension, $valid_extensions, true ) || 
			in_array( $mime_type, $valid_mime_types, true );
	}



	private function get_upload_error_message( int $error_code ): string {
		$error_messages = [
			UPLOAD_ERR_INI_SIZE => __( 'File exceeds the upload_max_filesize directive.', 'code-snippets' ),
			UPLOAD_ERR_FORM_SIZE => __( 'File exceeds the MAX_FILE_SIZE directive.', 'code-snippets' ),
			UPLOAD_ERR_PARTIAL => __( 'File was only partially uploaded.', 'code-snippets' ),
			UPLOAD_ERR_NO_FILE => __( 'No file was uploaded.', 'code-snippets' ),
			UPLOAD_ERR_NO_TMP_DIR => __( 'Missing a temporary folder.', 'code-snippets' ),
			UPLOAD_ERR_CANT_WRITE => __( 'Failed to write file to disk.', 'code-snippets' ),
			UPLOAD_ERR_EXTENSION => __( 'A PHP extension stopped the file upload.', 'code-snippets' ),
		];

		return $error_messages[ $error_code ] ?? __( 'Unknown upload error.', 'code-snippets' );
	}
}
