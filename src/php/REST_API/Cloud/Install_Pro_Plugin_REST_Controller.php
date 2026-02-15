<?php

namespace Code_Snippets\REST_API\Cloud;

use Code_Snippets\Settings\Plugin_Installation_Token;
use Code_Snippets\Core\Pro_Plugin_Installer;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;
use Exception;
use function Code_Snippets\code_snippets;
use const Code_Snippets\REST_API_NAMESPACE;

/**
 * Handles installation of the Pro plugin via secure cloud payload.
 *
 * @package Code_Snippets
 */
class Install_Pro_Plugin_REST_Controller extends WP_REST_Controller
{

    /**
     * Current API version.
     */
    public const VERSION = 1;

    /**
     * The base of this controller's route.
     */
    public const BASE_ROUTE = 'install-pro-plugin';

    /**
     * The namespace of this controller's route.
     *
     * @var string
     */
    protected $namespace = REST_API_NAMESPACE . self::VERSION;

    /**
     * The base of this controller's route.
     *
     * @var string
     */
    protected $rest_base = self::BASE_ROUTE;

    /**
     * Class constructor.
     */
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Register REST routes.
     */
    public function register_routes()
    {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => [$this, 'install_plugin'],
                    'permission_callback' => '__return_true', // Validation happens via signature and token
                    'args' => $this->get_endpoint_args(),
                ],
            ]
        );

        // Internal background processing endpoint - not publicly discoverable
        register_rest_route(
            $this->namespace,
            '/internal/process-installation',
            [
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => [$this, 'process_installation_internal'],
                    'permission_callback' => [$this, 'verify_internal_request'],
                ]
            ]
        );
    }

    /**
     * Get the endpoint arguments for installation
     * 
     * @return array
     */
    private function get_endpoint_args(): array
    {
        return [
            'payload' => [
                'description' => __('Encrypted payload containing installation data.', 'code-snippets'),
                'type' => 'string',
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function ($param) {
                    return !empty($param);
                },
            ],
            'signature' => [
                'description' => __('HMAC SHA256 signature of the payload.', 'code-snippets'),
                'type' => 'string',
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function ($param) {
                    return !empty($param);
                },
            ],
        ];
    }

    /**
     * Verify this is an internal request from the plugin itself
     * 
     * @param WP_REST_Request $request
     * @return bool
     */
    public function verify_internal_request(WP_REST_Request $request)
    {
        $signature = $request->get_header('X-CS-Signature');
        $timestamp = $request->get_header('X-CS-Timestamp');

        if (empty($signature) || empty($timestamp)) {
            return false;
        }

        // Reject requests older than 60 seconds
        // if (abs(time() - (int) $timestamp) > 60) {
        //     return false;
        // }

        // Generate expected signature using WordPress auth key
        $secret = defined('AUTH_KEY') ? AUTH_KEY : '';
        $expected = hash_hmac('sha256', $timestamp, $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Process plugin installation internally
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function process_installation_internal(WP_REST_Request $request)
    {
        // Increase time limit for long operations
        set_time_limit(300);

        // Execute the installer
        $installer = new Pro_Plugin_Installer();
        $installer();

        return rest_ensure_response(['success' => true]);
    }

    /**
     * Handle PRO plugin installation request from cloud.
     *
     * @param WP_REST_Request $request The request object.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function install_plugin(WP_REST_Request $request)
    {

        $payload_encrypted = $request->get_param('payload');
        $signature = $request->get_param('signature');

        // Validate required parameters
        if (empty($payload_encrypted) || empty($signature)) {
            return new WP_Error(
                'missing_parameters',
                __('Missing required installation parameters.', 'code-snippets'),
                ['status' => 400]
            );
        }

        // Get stored installation token
        $token_instance = new Plugin_Installation_Token();
        $stored_token = $token_instance->get_token();

        if (!$stored_token) {
            return new WP_Error(
                'missing_token',
                __('No installation token found. Please regenerate from plugin dashboard.', 'code-snippets'),
                ['status' => 403]
            );
        }

        // Verify HMAC signature
        $expected_signature = hash_hmac('sha256', $payload_encrypted, $stored_token);

        if (!hash_equals($expected_signature, $signature)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('CS Install: Signature verification failed');
            }

            return new WP_Error(
                'invalid_signature',
                __('The Plugin Installation Token has expired, please update the cloud with the new one', 'code-snippets'),
                ['status' => 403]
            );
        }

        // Decrypt payload
        $decrypted_data = $this->decrypt_payload($payload_encrypted, $stored_token);

        if (false === $decrypted_data || !is_array($decrypted_data)) {
            return new WP_Error(
                'decryption_failed',
                __('Failed to decrypt installation payload.', 'code-snippets'),
                ['status' => 403]
            );
        }

        // Validate token matches
        if (!isset($decrypted_data['pi_token']) || !hash_equals($stored_token, $decrypted_data['pi_token'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('CS Install: Token mismatch in payload');
            }

            return new WP_Error(
                'token_mismatch',
                __('Installation token mismatch.', 'code-snippets'),
                ['status' => 403]
            );
        }

        // Validate site URL matches
        $payload_site_url = $decrypted_data['site_url'] ?? '';
        $current_site_url = get_site_url();

        if (empty($payload_site_url) || !hash_equals($current_site_url, $payload_site_url)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf(
                    'CS Install: Site URL mismatch. Expected: %s, Got: %s',
                    $current_site_url,
                    $payload_site_url
                ));
            }

            return new WP_Error(
                'site_mismatch',
                __('This installation payload is not for this site.', 'code-snippets'),
                ['status' => 403]
            );
        }

        // Validate timestamp (prevent old requests)
        if (isset($decrypted_data['expires_at'])) {
            if (!is_numeric($decrypted_data['expires_at']) || time() > (int) $decrypted_data['expires_at']) {
                return new WP_Error(
                    'expired_request',
                    __('Installation request has expired. Please request a new installation.', 'code-snippets'),
                    ['status' => 410]
                );
            }
        }

        // Validate required payload fields
        $required_fields = ['download_url', 'site_token', 'cloud_token'];
        foreach ($required_fields as $field) {
            if (empty($decrypted_data[$field])) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log(sprintf('CS Install: Missing required field: %s', $field));
                }

                return new WP_Error(
                    'invalid_payload',
                    __('Installation payload is incomplete.', 'code-snippets'),
                    ['status' => 400]
                );
            }
        }

        // ANTI-REPLAY: Rotate token immediately (before installation attempt)
        // Even if installation fails, token is now invalid
        $token_instance->regenerate_token();

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CS Install: Token rotated successfully');
        }

        // Extract installation data
        $download_url = esc_url_raw($decrypted_data['download_url']);
        $site_token = sanitize_text_field($decrypted_data['site_token']);
        $cloud_token = sanitize_text_field($decrypted_data['cloud_token']);
        $license_key = !empty($decrypted_data['license_key'])
            ? sanitize_text_field($decrypted_data['license_key'])
            : '';

        // Store installation data in transient temporarily
        set_transient('code_snippets_installation_data', [
            'download_url' => $download_url,
            'site_token' => $site_token,
            'cloud_token' => $cloud_token,
            'license_key' => $license_key,
        ], 1800); // 30 minutes

        // Generate signature for internal request
        $timestamp = (string) time();
        $secret = defined('AUTH_KEY') ? AUTH_KEY : '';
        $signature = hash_hmac('sha256', $timestamp, $secret);

        // Trigger internal background process so that the plugin 
        // can immediatly respond to the cloud request
        wp_remote_post(rest_url('code-snippets/v1/internal/process-installation'), [
            'timeout' => 0.01,
            'blocking' => false,
            'sslverify' => apply_filters('https_local_ssl_verify', false),
            'headers' => [
                'X-CS-Signature' => $signature,
                'X-CS-Timestamp' => $timestamp,
            ],
        ]);

        return rest_ensure_response([
            'success' => true,
            'message' => __('PRO plugin installation initiated successfully.', 'code-snippets'),
            'status' => 'installing',
        ]);
    }

    /**
     * Decrypt the installation payload from cloud
     * 
     * @param string $encrypted_payload Base64 encoded encrypted data
     * @param string $pi_token Plugin installation token
     * @return array|false Decrypted payload array or false on failure
     */
    private function decrypt_payload(string $encrypted_payload, string $pi_token)
    {

        // Decode from base64
        $encrypted_data = base64_decode($encrypted_payload, true);

        if (false === $encrypted_data) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('CS Decrypt: Invalid base64 encoding');
            }
            return false;
        }

        // Extract encryption components
        $iv_length = 12;
        $tag_length = 16;

        $min_length = $iv_length + $tag_length;
        if (strlen($encrypted_data) < $min_length) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('CS Decrypt: Encrypted data too short');
            }
            return false;
        }

        $iv = substr($encrypted_data, 0, $iv_length);
        $tag = substr($encrypted_data, $iv_length, $tag_length);
        $ciphertext = substr($encrypted_data, $iv_length + $tag_length);

        // Derive encryption key
        $hash = hash('sha256', $pi_token, true);

        // Decrypt the data
        $decrypted_json = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $hash,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if (false === $decrypted_json) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('CS Decrypt: Decryption failed');
            }
            return false;
        }

        // Decode JSON
        $payload = json_decode($decrypted_json, true);

        if (null === $payload || JSON_ERROR_NONE !== json_last_error()) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('CS Decrypt: JSON decode failed');
            }
            return false;
        }

        return $payload;
    }
}
