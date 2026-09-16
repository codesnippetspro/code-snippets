<?php

namespace Code_Snippets\REST_API\Feedback;

use Code_Snippets\Admin\Feedback_Panel;
use Code_Snippets\Client\Feedback_Client;
use Code_Snippets\REST_API\REST_Controller;
use Code_Snippets\Utils\System_Info;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function Code_Snippets\code_snippets;

/**
 * Accepts feedback reports from the panel and forwards them to the cloud.
 *
 * The browser never talks to the cloud directly: the site's credential stays on the server,
 * and the environment attached to a report is collected here rather than being trusted from
 * the request.
 *
 * @package Code_Snippets
 */
class Feedback_REST_Controller extends REST_Controller {

	/**
	 * Current API version.
	 */
	public const VERSION = 1;

	/**
	 * The base of this controller's route.
	 */
	public const BASE_ROUTE = 'feedback';

	/**
	 * Kinds of report the panel can send.
	 */
	private const REPORT_TYPES = [ 'bug', 'feature', 'feedback' ];

	/**
	 * How long, in seconds, a reporter waits between reports.
	 */
	private const THROTTLE_TIMEOUT = 20;

	/**
	 * Shortest search term worth sending to the cloud.
	 */
	private const MIN_SEARCH_LENGTH = 4;

	/**
	 * Most captured JavaScript errors to attach to a report.
	 */
	private const MAX_JS_ERRORS = 10;

	/**
	 * Shortest title that summarises anything.
	 */
	private const MIN_TITLE_LENGTH = 8;

	/**
	 * Shortest free-text answer that describes anything.
	 */
	private const MIN_TEXT_LENGTH = 20;

	/**
	 * Client used to reach the cloud.
	 *
	 * @var Feedback_Client
	 */
	private Feedback_Client $client;

	/**
	 * Class constructor.
	 *
	 * @param Feedback_Client $client Client used to reach the cloud.
	 */
	public function __construct( Feedback_Client $client ) {
		$this->client = $client;

		parent::__construct();
	}

	/**
	 * Register the reporting routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			self::BASE_ROUTE,
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'send_report' ],
				'permission_callback' => [ $this, 'permission_callback' ],
			]
		);

		register_rest_route(
			$this->namespace,
			self::BASE_ROUTE . '/search',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'search_reports' ],
				'permission_callback' => [ $this, 'permission_callback' ],
				'args'                => [
					'q' => [
						'description' => __( 'Report title to look for.', 'code-snippets' ),
						'type'        => 'string',
						'required'    => true,
					],
				],
			]
		);
	}

	/**
	 * Determine whether the request may report feedback.
	 *
	 * The setting is checked alongside the capability so that switching the reporter off
	 * closes these routes rather than leaving a route to the cloud open behind a hidden panel.
	 *
	 * @param WP_REST_Request $request Incoming HTTP request.
	 *
	 * @return bool
	 */
	public function permission_callback( WP_REST_Request $request ): bool {
		return Feedback_Panel::is_enabled() && code_snippets()->current_user_can();
	}

	/**
	 * Offer reports resembling the title being typed.
	 *
	 * @param WP_REST_Request $request Incoming HTTP request.
	 *
	 * @return WP_REST_Response
	 */
	public function search_reports( WP_REST_Request $request ): WP_REST_Response {
		$query = trim( sanitize_text_field( (string) $request->get_param( 'q' ) ) );

		$results = self::text_length( $query ) < self::MIN_SEARCH_LENGTH
			? []
			: $this->client->search_reports( $query );

		return new WP_REST_Response( [ 'results' => $results ], 200 );
	}

	/**
	 * Validate a report and forward it to the cloud.
	 *
	 * @param WP_REST_Request $request Incoming HTTP request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function send_report( WP_REST_Request $request ) {
		$invalid = $this->validate_report( $request );

		if ( $invalid ) {
			return $invalid;
		}

		$user = wp_get_current_user();
		$throttle_key = 'code_snippets_feedback_' . $user->ID;

		if ( get_transient( $throttle_key ) ) {
			return new WP_Error(
				'code_snippets_feedback_throttled',
				__( 'That report was just sent. Wait a moment before sending another.', 'code-snippets' ),
				[ 'status' => 429 ]
			);
		}

		$response = $this->client->send_report(
			$this->build_payload( $request ),
			$this->get_idempotency_key( $request )
		);

		if ( is_wp_error( $response ) ) {
			// The transport's own message names the cause — a blocked outbound request, a
			// certificate problem, a timeout — which is the only way anyone can act on this.
			return new WP_Error(
				'code_snippets_feedback_transport',
				sprintf(
					/* translators: %s: error message describing why the request failed. */
					__( 'Could not reach the reporting service: %s', 'code-snippets' ),
					$response->get_error_message()
				),
				[ 'status' => 502 ]
			);
		}

		if ( $response['status'] < 200 || $response['status'] >= 300 ) {
			return $this->translate_cloud_error( $response );
		}

		set_transient( $throttle_key, 1, self::THROTTLE_TIMEOUT );

		return new WP_REST_Response(
			[
				'sent'      => true,
				'reference' => isset( $response['body']['reference'] ) ? sanitize_text_field( $response['body']['reference'] ) : '',
				'url'       => isset( $response['body']['url'] ) ? esc_url_raw( $response['body']['url'] ) : '',
			],
			200
		);
	}

	/**
	 * Count the characters in a value, as the panel counts them.
	 *
	 * The panel measures with JavaScript's string length, so counting bytes here would let
	 * a report through that the panel refused, and would measure non-Latin scripts against
	 * a limit several times longer than intended.
	 *
	 * @param string $value Value to measure.
	 *
	 * @return int
	 */
	private static function text_length( string $value ): int {
		return (int) preg_match_all( '/./us', $value );
	}

	/**
	 * Check a report says enough to be acted on.
	 *
	 * @param WP_REST_Request $request Incoming HTTP request.
	 *
	 * @return WP_Error|null Error describing the first problem found, or null when there is none.
	 */
	private function validate_report( WP_REST_Request $request ): ?WP_Error {
		$type = sanitize_key( (string) $request->get_param( 'type' ) );

		if ( ! in_array( $type, self::REPORT_TYPES, true ) ) {
			return new WP_Error(
				'code_snippets_feedback_type',
				__( 'Choose what kind of feedback this is.', 'code-snippets' ),
				[ 'status' => 400 ]
			);
		}

		if ( self::text_length( trim( sanitize_text_field( (string) $request->get_param( 'title' ) ) ) ) < self::MIN_TITLE_LENGTH ) {
			return new WP_Error(
				'code_snippets_feedback_title',
				__( 'Give the report a title of at least 8 characters.', 'code-snippets' ),
				[ 'status' => 400 ]
			);
		}

		if ( self::text_length( trim( sanitize_textarea_field( (string) $request->get_param( 'description' ) ) ) ) < self::MIN_TEXT_LENGTH ) {
			return new WP_Error(
				'code_snippets_feedback_description',
				__( 'Describe the problem in a bit more detail.', 'code-snippets' ),
				[ 'status' => 400 ]
			);
		}

		if ( 'bug' === $type && self::text_length( trim( sanitize_textarea_field( (string) $request->get_param( 'steps' ) ) ) ) < self::MIN_TEXT_LENGTH ) {
			return new WP_Error(
				'code_snippets_feedback_steps',
				__( 'List the steps that reproduce the bug.', 'code-snippets' ),
				[ 'status' => 400 ]
			);
		}

		return null;
	}

	/**
	 * Assemble the report sent to the cloud.
	 *
	 * @param WP_REST_Request $request Incoming HTTP request.
	 *
	 * @return array<string, mixed>
	 */
	private function build_payload( WP_REST_Request $request ): array {
		$user = wp_get_current_user();
		$isolation = (array) $request->get_param( 'isolation' );
		$browser = (array) $request->get_param( 'browser' );
		$js_errors = array_slice( (array) $request->get_param( 'js_errors' ), 0, self::MAX_JS_ERRORS );

		$name = trim( sanitize_text_field( (string) $request->get_param( 'name' ) ) );
		$email = sanitize_email( (string) $request->get_param( 'email' ) );

		$payload = [
			'report'       => [
				'type'        => sanitize_key( (string) $request->get_param( 'type' ) ),
				'title'       => trim( sanitize_text_field( (string) $request->get_param( 'title' ) ) ),
				'description' => trim( sanitize_textarea_field( (string) $request->get_param( 'description' ) ) ),
				'steps'       => trim( sanitize_textarea_field( (string) $request->get_param( 'steps' ) ) ),
				'comments'    => trim( sanitize_textarea_field( (string) $request->get_param( 'comments' ) ) ),
				'isolation'   => [
					'plugin_only'  => ! empty( $isolation['plugin_only'] ),
					'blank_theme'  => ! empty( $isolation['blank_theme'] ),
					'reproducible' => ! empty( $isolation['reproducible'] ),
				],
				'page_url'    => esc_url_raw( (string) $request->get_param( 'page_url' ) ),
			],
			'reporter'     => [
				'name'  => $name ? $name : $user->display_name,
				'email' => $email ? $email : $user->user_email,
				'role'  => implode( ', ', $user->roles ),
			],
			'environment'  => System_Info::get_system_info(),
			'browser'      => [
				'user_agent' => isset( $browser['userAgent'] ) ? sanitize_text_field( $browser['userAgent'] ) : '',
				'viewport'   => isset( $browser['viewport'] ) ? sanitize_text_field( $browser['viewport'] ) : '',
				'screen'     => isset( $browser['screen'] ) ? sanitize_text_field( $browser['screen'] ) : '',
				'language'   => isset( $browser['language'] ) ? sanitize_text_field( $browser['language'] ) : '',
			],
			'js_errors'    => array_map( 'sanitize_textarea_field', $js_errors ),
			'submitted_at' => gmdate( 'c' ),
		];

		return apply_filters( 'code_snippets_feedback_payload', $payload );
	}

	/**
	 * Reduce the key naming a submission to the characters the cloud accepts.
	 *
	 * @param WP_REST_Request $request Incoming HTTP request.
	 *
	 * @return string
	 */
	private function get_idempotency_key( WP_REST_Request $request ): string {
		$key = sanitize_text_field( (string) $request->get_param( 'idempotency_key' ) );
		$key = substr( preg_replace( '/[^A-Za-z0-9\-]/', '', $key ), 0, 64 );

		return $key ? $key : wp_generate_uuid4();
	}

	/**
	 * Describe a report the cloud refused.
	 *
	 * A refusal the reporter can act on is passed through with its own status and wording;
	 * anything else is reported as a problem reaching the service.
	 *
	 * @param array{status: int, body: array<string, mixed>} $response Response from the cloud.
	 *
	 * @return WP_Error
	 */
	private function translate_cloud_error( array $response ): WP_Error {
		$is_client_error = $response['status'] >= 400 && $response['status'] < 500;

		$message = $is_client_error && isset( $response['body']['message'] )
			? sanitize_text_field( $response['body']['message'] )
			: __( 'The reporting service could not accept this report. Try again shortly.', 'code-snippets' );

		$code = isset( $response['body']['code'] )
			? 'cloud_' . sanitize_key( $response['body']['code'] )
			: 'code_snippets_feedback_rejected';

		return new WP_Error(
			$code,
			$message,
			[ 'status' => $is_client_error ? $response['status'] : 502 ]
		);
	}
}
