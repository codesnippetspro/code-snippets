<?php

namespace Code_Snippets\Admin\Menus;

use Code_Snippets\Migration\Export\Download_Code;
use Code_Snippets\Model\Snippet;
use WP_Error;
use function Code_Snippets\code_snippets;
use function Code_Snippets\get_snippet;

/**
 * Handles bulk snippet code downloads from the manage screen.
 */
class Manage_Menu_Bulk_Download {

	/**
	 * Handle a bulk download request.
	 *
	 * @return void
	 */
	public function handle(): void {
		if ( ! $this->is_request() ) {
			return;
		}

		if ( ! current_user_can( code_snippets()->get_cap() ) ) {
			$this->send_error( __( 'You are not allowed to download these snippets.', 'code-snippets' ), 403 );
		}

		$nonce = filter_input( INPUT_POST, 'code_snippets_bulk_download_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ?? '';

		if ( ! wp_verify_nonce( $nonce, 'code_snippets_bulk_download' ) ) {
			$this->send_error(
				__( 'The download request is no longer valid. Please refresh and try again.', 'code-snippets' ),
				403
			);
		}

		$snippets_json = wp_unslash( filter_input( INPUT_POST, 'snippets', FILTER_DEFAULT ) ?? '' );
		$snippets = $this->resolve_snippets( $snippets_json );

		if ( $snippets instanceof WP_Error ) {
			$status = $snippets->get_error_data( 'status' );
			$this->send_error( $snippets->get_error_message(), is_numeric( $status ) ? (int) $status : 403 );
		}

		if ( empty( $snippets ) ) {
			$this->send_error( __( 'No snippets were selected for download.', 'code-snippets' ) );
		}

		$download = 1 === count( $snippets )
			? Download_Code::build_snippet_download( $snippets[0] )
			: Download_Code::build_archive_download( $snippets );

		if ( $download instanceof WP_Error ) {
			$status = $download->get_error_data( 'status' );
			$this->send_error( $download->get_error_message(), is_numeric( $status ) ? (int) $status : 500 );
		}

		$this->send_response( $download );
	}

	/**
	 * Resolve snippets from a JSON request payload.
	 *
	 * @param string $snippets_json JSON-encoded list of requested snippets.
	 *
	 * @return Snippet[]|WP_Error
	 */
	public function resolve_snippets( string $snippets_json ) {
		$payload = '' === $snippets_json ? [] : json_decode( $snippets_json, true );

		if ( ! is_array( $payload ) ) {
			return [];
		}

		$snippets = [];

		foreach ( $payload as $snippet_data ) {
			if ( ! is_array( $snippet_data ) || empty( $snippet_data['id'] ) ) {
				continue;
			}

			if ( ! empty( $snippet_data['network'] ) && ! current_user_can( code_snippets()->get_network_cap_name() ) ) {
				return new WP_Error(
					'code_snippets_forbidden_network_download',
					__( 'You are not allowed to download network snippets.', 'code-snippets' ),
					[ 'status' => 403 ]
				);
			}

			$snippet = get_snippet(
				absint( $snippet_data['id'] ),
				! empty( $snippet_data['network'] )
			);

			if ( $snippet->id ) {
				$snippets[] = $snippet;
			}
		}

		return $snippets;
	}

	/**
	 * Determine whether the current request is a bulk download request.
	 *
	 * @return bool
	 */
	private function is_request(): bool {
		$page = sanitize_key( filter_input( INPUT_GET, 'page' ) ?? '' );
		$action = sanitize_key( filter_input( INPUT_POST, 'code_snippets_action' ) ?? '' );

		return code_snippets()->get_menu_slug() === $page && 'bulk-download' === $action;
	}

	/**
	 * Send a download response and end execution.
	 *
	 * @param array{filename:string, content_type:string, content:string} $download Download data.
	 *
	 * @return void
	 */
	private function send_response( array $download ): void {
		while ( ob_get_level() ) {
			ob_end_clean();
		}

		nocache_headers();
		send_nosniff_header();
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: ' . $download['content_type'] );
		header( 'Content-Disposition: attachment; filename="' . $download['filename'] . '"' );
		header( 'Content-Length: ' . strlen( $download['content'] ) );
		header( 'X-Suggested-Filename: ' . $download['filename'] );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary download payload.
		echo $download['content'];
		exit;
	}

	/**
	 * Send a download error and end execution.
	 *
	 * @param string $message Error message.
	 * @param int    $status  HTTP status code.
	 *
	 * @return void
	 */
	private function send_error( string $message, int $status = 400 ): void {
		while ( ob_get_level() ) {
			ob_end_clean();
		}

		status_header( $status );
		nocache_headers();
		send_nosniff_header();
		header( 'Content-Type: text/plain; charset=' . get_option( 'blog_charset' ) );

		echo esc_html( $message );
		exit;
	}
}
