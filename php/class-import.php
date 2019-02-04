<?php

namespace Code_Snippets;

use DOMDocument;
use DOMElement;

/**
 * Handles importing snippets from export files into the site
 * @package Code_Snippets
 * @since   3.0.0
 */
class Import {

	/**
	 * Imports snippets from a JSON file
	 *
	 * @since 2.9.7
	 *
	 * @uses  save_snippet() to add the snippets to the database
	 *
	 * @param string    $file       The path to the file to import
	 * @param bool|null $multisite  Import into network-wide table or site-wide table?
	 * @param string    $dup_action Action to take if duplicate snippets are detected. Can be 'skip', 'ignore', or 'replace'
	 *
	 * @return array|bool An array of imported snippet IDs on success, false on failure
	 */
	public static function import_json_file( $file, $multisite = null, $dup_action = 'ignore' ) {

		if ( ! file_exists( $file ) || ! is_file( $file ) ) {
			return false;
		}

		$raw_data = file_get_contents( $file );
		$data = json_decode( $raw_data, true );
		$snippets = array();

		/* Reformat the data into snippet objects */
		foreach ( $data['snippets'] as $snippet ) {
			$snippet = new Snippet( $snippet );
			$snippet->network = $multisite;
			$snippets[] = $snippet;
		}

		$imported = self::save_imported_snippets( $snippets, $multisite, $dup_action );
		do_action( 'code_snippets/import/json', $file, $multisite );

		return $imported;
	}

	/**
	 * Imports snippets from an XML file
	 *
	 * @since 2.0
	 *
	 * @uses  save_snippet() to add the snippets to the database
	 *
	 * @param string    $file       The path to the file to import
	 * @param bool|null $multisite  Import into network-wide table or site-wide table?
	 * @param string    $dup_action Action to take if duplicate snippets are detected. Can be 'skip', 'ignore', or 'replace'
	 *
	 * @return array|bool An array of imported snippet IDs on success, false on failure
	 */
	public static function import_xml_file( $file, $multisite = null, $dup_action = 'ignore' ) {

		if ( ! file_exists( $file ) || ! is_file( $file ) ) {
			return false;
		}

		$dom = new DOMDocument( '1.0', get_bloginfo( 'charset' ) );
		$dom->load( $file );

		$snippets_xml = $dom->getElementsByTagName( 'snippet' );
		$fields = array( 'name', 'description', 'desc', 'code', 'tags', 'scope' );

		$snippets = array();

		/* Loop through all snippets */

		/** @var DOMElement $snippet_xml */
		foreach ( $snippets_xml as $snippet_xml ) {
			$snippet = new Snippet();
			$snippet->network = $multisite;

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

		$imported = self::save_imported_snippets( $snippets, $dup_action, $multisite );
		do_action( 'code_snippets/import/xml', $file, $multisite );

		return $imported;
	}

	/**
	 * @access private
	 *
	 * @param        $snippets
	 * @param null   $multisite
	 * @param string $dup_action
	 *
	 * @return array
	 */
	private static function save_imported_snippets( $snippets, $multisite = null, $dup_action = 'ignore' ) {

		/* Get a list of existing snippet names keyed to their IDs */
		$existing_snippets = array();
		if ( 'replace' == $dup_action || 'skip' === $dup_action ) {
			$all_snippets = get_snippets( array(), $multisite );

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
			if ( 'ignore' !== $dup_action && isset( $existing_snippets[ $snippet->name ] ) ) {

				/* If so, either overwrite the existing ID, or skip this import */
				if ( 'replace' === $dup_action ) {
					$snippet->id = $existing_snippets[ $snippet->name ];
				} elseif ( 'skip' === $dup_action ) {
					continue;
				}
			}

			/* Save the snippet and increase the counter if successful */
			if ( $snippet_id = save_snippet( $snippet ) ) {
				$imported[] = $snippet_id;
			}
		}

		return $imported;
	}
}
