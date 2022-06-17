<?php

namespace Code_Snippets;

use DOMDocument;
use DOMElement;

/**
 * Handles importing snippets from export files into the site
 *
 * @package Code_Snippets
 * @since   3.0.0
 *
 * phpcs:disable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
 * phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
 */
class Import {

	/**
	 * Path to file to import
	 *
	 * @var string
	 */
	private $file;

	/**
	 * Whether snippets should be imported into the network-wide or site-wide table
	 *
	 * @var bool|null
	 */
	private $multisite;

	/**
	 * Action to take if duplicate snippets are detected. Can be 'skip', 'ignore', or 'replace'
	 *
	 * @var string
	 */
	private $dup_action;

	/**
	 * Class constructor
	 *
	 * @param string    $file       The path to the file to import.
	 * @param bool|null $multisite  Import into network-wide table (true) or site-wide table (false).
	 * @param string    $dup_action Action to take if duplicate snippets are detected. Can be 'skip', 'ignore', or 'replace'.
	 */
	public function __construct( $file, $multisite = null, $dup_action = 'ignore' ) {
		$this->file = $file;
		$this->multisite = $multisite;
		$this->dup_action = $dup_action;
	}

	/**
	 * Imports snippets from a JSON file.
	 *
	 * @return array|bool An array of imported snippet IDs on success, false on failure
	 */
	public function import_json() {

		if ( ! file_exists( $this->file ) || ! is_file( $this->file ) ) {
			return false;
		}

		$raw_data = file_get_contents( $this->file );
		$data = json_decode( $raw_data, true );
		$snippets = array();

		/* Reformat the data into snippet objects */
		foreach ( $data['snippets'] as $snippet_data ) {
			$snippet = new Snippet( $snippet_data );
			$snippet->network = $this->multisite;
			$snippets[] = $snippet;
		}

		$imported = $this->save_snippets( $snippets );

		do_action( 'code_snippets/import/json', $this->file, $this->multisite );

		return $imported;
	}

	/**
	 * Imports snippets from an XML file
	 *
	 * @return array|bool An array of imported snippet IDs on success, false on failure
	 */
	public function import_xml() {

		if ( ! file_exists( $this->file ) || ! is_file( $this->file ) ) {
			return false;
		}

		$dom = new DOMDocument( '1.0', get_bloginfo( 'charset' ) );
		$dom->load( $this->file );

		$snippets_xml = $dom->getElementsByTagName( 'snippet' );
		$fields = array( 'name', 'description', 'desc', 'code', 'tags', 'scope' );

		$snippets = array();

		/**
		 * Loop through all snippets
		 *
		 * @var DOMElement $snippet_xml
		 */
		foreach ( $snippets_xml as $snippet_xml ) {
			$snippet = new Snippet();
			$snippet->network = $this->multisite;

			/* Build a snippet object by looping through the field names */
			foreach ( $fields as $field_name ) {

				/* Fetch the field element from the document */
				$field = $snippet_xml->getElementsByTagName( $field_name )->item( 0 );

				/* If the field element exists, add it to the snippet object */
				if ( isset( $field->nodeValue ) ) {
					$snippet->set_field( $field_name, $field->nodeValue );
				}
			}

			/* Get scope from attribute */
			$scope = $snippet_xml->getAttribute( 'scope' );
			if ( ! empty( $scope ) ) {
				$snippet->scope = $scope;
			}

			$snippets[] = $snippet;
		}

		$imported = $this->save_snippets( $snippets );
		do_action( 'code_snippets/import/xml', $this->file, $this->multisite );

		return $imported;
	}

	/**
	 * Save imported snippets to the database
	 *
	 * @access private
	 *
	 * @param array $snippets List of snippets to save.
	 *
	 * @return array IDs of imported snippets.
	 */
	private function save_snippets( $snippets ) {

		/* Get a list of existing snippet names keyed to their IDs */
		$existing_snippets = array();
		if ( 'replace' === $this->dup_action || 'skip' === $this->dup_action ) {
			$all_snippets = get_snippets( array(), $this->multisite );

			foreach ( $all_snippets as $snippet ) {
				if ( $snippet->name ) {
					$existing_snippets[ $snippet->name ] = $snippet->id;
				}
			}
		}

		/* Save a record of the snippets which were imported */
		$imported = array();

		/* Loop through the provided snippets */
		foreach ( $snippets as $snippet ) {

			/* Check if the snippet already exists */
			if ( 'ignore' !== $this->dup_action && isset( $existing_snippets[ $snippet->name ] ) ) {

				/* If so, either overwrite the existing ID, or skip this import */
				if ( 'replace' === $this->dup_action ) {
					$snippet->id = $existing_snippets[ $snippet->name ];
				} elseif ( 'skip' === $this->dup_action ) {
					continue;
				}
			}

			/* Save the snippet and increase the counter if successful */
			$snippet_id = save_snippet( $snippet );

			if ( $snippet_id ) {
				$imported[] = $snippet_id;
			}
		}

		return $imported;
	}
}
