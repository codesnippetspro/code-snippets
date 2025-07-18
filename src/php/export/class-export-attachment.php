<?php

namespace Code_Snippets;

/**
 * Handles exporting snippets from the site to a downloadable file over HTTP.
 *
 * @package Code_Snippets
 */
class Export_Attachment extends Export {

	/**
	 * Set up the current page to act like a downloadable file instead of being shown in the browser
	 *
	 * @param string $language  File format. Used for file extension.
	 * @param string $mime_type File MIME type. Used for Content-Type header.
	 */
	private function do_headers( string $language, string $mime_type = 'text/plain' ) {
		header( 'Content-Disposition: attachment; filename=' . sanitize_file_name( $this->build_filename( $language ) ) );
		header( sprintf( 'Content-Type: %s; charset=%s', sanitize_mime_type( $mime_type ), get_bloginfo( 'charset' ) ) );
	}

	/**
	 * Export snippets in JSON format as a downloadable file.
	 */
	public function download_snippets_json() {
		$this->do_headers( 'json', 'application/json' );
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo wp_json_encode(
			$this->create_export_object(),
			apply_filters( 'code_snippets/export/json_encode_options', 0 )
		);
		exit;
	}

	/**
	 * Export snippets in their code file format.
	 */
	public function download_snippets_code() {
		$lang = $this->snippets_list[0]->lang;

		$mime_types = [
			'php'  => 'text/php',
			'css'  => 'text/css',
			'js'   => 'text/javascript',
			'json' => 'application/json',
		];

		$this->do_headers( $lang, $mime_types[ $lang ] ?? 'text/plain' );

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->export_snippets_code( $this->snippets_list[0]->type );
		exit;
	}
}
