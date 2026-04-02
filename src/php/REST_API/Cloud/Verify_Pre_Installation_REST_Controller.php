<?php

namespace Code_Snippets\REST_API\Cloud;

use Code_Snippets\Settings\Plugin_Installation_Token;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function Code_Snippets\code_snippets;
use const Code_Snippets\REST_API_NAMESPACE;

/**
 * Verifies site readiness for Pro installation.
 *
 * @package Code_Snippets
 */
class Verify_Pre_Installation_REST_Controller extends WP_REST_Controller
{

    /**
     * Current API version.
     */
    public const VERSION = 1;

    /**
     * The base of this controller's route.
     */
    public const BASE_ROUTE = 'verify-pre-installation';

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
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'verify_site'],
                    'permission_callback' => '__return_true', // Public endpoint, validated via token
                    'args' => [
                        'pro_installation_token' => [
                            'description' => __('Token to verify site identity.', 'code-snippets'),
                            'type' => 'string',
                            'required' => true,
                        ]
                    ],
                ],
            ]
        );
    }

    /**
     * Verify site readiness.
     *
     * @param WP_REST_Request $request The request object.
     *
     * @return WP_REST_Response|array
     */
    public function verify_site(WP_REST_Request $request)
    {
        $token = $request->get_param('pro_installation_token');

        // Validate token
        $token_validator = new Plugin_Installation_Token();
        if (!$token_validator->validate_token($token)) {
            return new \WP_Error(
                'invalid_token',
                __('Invalid or expired installation token.', 'code-snippets'),
                ['status' => 403]
            );
        }

        // Verify the request is from cloud domain
        $referer = $request->get_header('referer');
        $origin = $request->get_header('origin');

        $cloud_url = \Code_Snippets\Client\Cloud_API::get_cloud_url();
        $cloud_host = parse_url($cloud_url, PHP_URL_HOST);
        $allowed_domains = [$cloud_host, 'codesnippets.cloud', 'localhost'];

        // Prefer Origin over Referer for API requests
        $source_url = $origin ?: $referer;

        if ($source_url) {
            $source_host = parse_url($source_url, PHP_URL_HOST);
            if (!in_array($source_host, $allowed_domains, true)) {
                return new \WP_Error(
                    'invalid_origin',
                    __('Request must originate from Code Snippets Cloud.', 'code-snippets'),
                    ['status' => 403]
                );
            }
        }

        return [
            'free_plugin_installed' => true,
            'php_version' => phpversion(),
            'wp_version' => get_bloginfo('version'),
        ];
    }
}
