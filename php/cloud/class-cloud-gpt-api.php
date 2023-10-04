<?php

namespace Code_Snippets\Cloud;

use GuzzleHttp\Exception\GuzzleException;
use StdClass;
use WP_Error;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use function Code_Snippets\code_snippets;

/**
 * Class for communicating with Cloud AI API.
 */
class Cloud_GPT_API {

	/**
	 * Base URL for the API.
	 */
	const API_URL = 'https://codesnippets.cloud/api/v1/gpt';

	/**
	 * Path for prompt endpoint.
	 */
	const PROMPT_PATH = '/prompt';

	/**
	 * Path for explain endpoint.
	 */
	const EXPLAIN_PATH = '/explain';

	/**
	 * Cloud API instance.
	 *
	 * @var Cloud_API
	 */
	private Cloud_API $cloud_api;

	/**
	 * Class constructor.
	 *
	 * @param Cloud_API $cloud_api Cloud API instance.
	 *
	 * @return void
	 */
	public function __construct( Cloud_API $cloud_api ) {
		$this->cloud_api = $cloud_api;
	}

	/**
	 * Make a POST request.
	 *
	 * @param string $endpoint Endpoint to contact.
	 * @param string $prompt   Prompt to send as POST data.
	 *
	 * @return array|WP_Error Response data on success or error on failure.
	 */
	private function send_post_request( string $endpoint, string $prompt ) {
		if ( ! $this->cloud_api->is_cloud_key_verified() ) {
			return new WP_Error(
				'cloud_ai_key_error',
				__( 'Cannot access Cloud API without a verified cloud key.', 'code-snippets' )
			);
		}

		$freemius_license = code_snippets()->licensing->get_license_key();
		if ( ! $freemius_license ) {
			return new WP_Error(
				'cloud_ai_license_error',
				__( 'Could not retrieve license details for API request.', 'code-snippets' )
			);
		}

		$parts = [
			'multipart' => [
				[
					'name'     => 'prompt',
					'contents' => $prompt,
				],
				[
					'name'     => 'fs_key',
					'contents' => $freemius_license,
				],
			],
		];

		$url = sprintf( '%s/%s', self::API_URL, ltrim( $endpoint, '/\\' ) );

		$request = new Request( 'POST', $url, $this->cloud_api->build_request_headers() );
		$client = new Client();

		try {
			$response = $client->send( $request, $parts );
		} catch ( GuzzleException $exception ) {
			return new WP_Error(
				'cloud_ai_request_error',
				__( 'Failed to send request to Cloud API', 'code-snippets' ),
				$exception->getMessage()
			);
		}

		$body = json_decode( $response->getBody(), true );

		return $body['response'] && is_array( $body['response'] ) ?
			$body['response'] :
			new WP_Error(
				'cloud_ai_response_error',
				__( 'Did not receive a valid response from the Cloud API.', 'code-snippets' ),
				$body
			);
	}

	/**
	 * Unpack response data by filtering out invalid and missing data, and rewriting field names.
	 *
	 * @param array<string, mixed>  $response Response data.
	 * @param array<string, string> $key_map  Map of valid keys from response key to result key.
	 *
	 * @return array
	 */
	private function unpack_response_data( array $response, array $key_map ): array {
		$result = [];

		foreach ( $response as $key => $value ) {
			if ( isset( $key_map[ $key ] ) && ! empty( $value ) ) {
				$result[ $key_map[ $key ] ] = $value;
			}
		}

		return $result;
	}

	/**
	 * Make a POST request to the Cloud AI prompt endpoint.
	 *
	 * @param string $prompt Message prompt.
	 *
	 * @return array<string, string>|WP_Error
	 */
	public function prompt( string $prompt ) {
		$response = $this->send_post_request( self::PROMPT_PATH, $prompt );

		return is_wp_error( $response ) ?
			$response :
			$this->unpack_response_data(
				$response,
				[
					'n' => 'name',
					'c' => 'code',
					'd' => 'desc',
				]
			);
	}

	/**
	 * Make a POST request to the Cloud AI explain endpoint.
	 *
	 * @param string $code Snippet code to explain.
	 *
	 * @return array<string, mixed>|WP_Error
	 */
	public function explain( string $code ) {
		$response = $this->send_post_request( self::EXPLAIN_PATH, $code );

		return is_wp_error( $response ) ?
			$response :
			$this->unpack_response_data(
				$response,
				[
					'n' => 'name',
					'c' => 'lines',
					't' => 'tags',
					'd' => 'desc',
				]
			);
	}
}
