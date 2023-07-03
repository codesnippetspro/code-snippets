<?php

namespace Code_Snippets\Cloud;

use WP_Error;
use GuzzleHttp\Client as ApiClient;
use GuzzleHttp\Psr7\Request as ApiRequest;
use GuzzleHttp\Psr7\Response as ApiResponse;
use \GuzzleHttp\Psr7\MultipartStream as ApiMultipartStream;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\GuzzleException;
use function Code_Snippets\Settings\get_setting;

class Cloud_AI extends Cloud_API {

    const CLOUD_AI_URL = 'https://codesnippets.cloud/api/v1/gpt';
    const PROMT_PATH = '/prompt';
    const EXPLAIN_PATH = '/explain';

    private $freemius_licence;
    private $cloud_key;
    private $fs_plugin_data;

    /**
     * Class constructor.
     *
     * @return void
     */
    public function __construct() {
        $this->cloud_key = get_setting('cloud', 'cloud_token');
        $this->freemius_licence = $this->get_freemius_licence();
    }

    /**
     * Get the Freemius license key.
     *
     * @return string|false License key on success, false on failure.
     */
    private function get_freemius_licence() {
        $fs_account_data = get_option('fs_accounts');
        if (empty($fs_account_data)) {
            return false;
        }
        $this->fs_plugin_data = $fs_account_data['plugins']['code-snippets'];
        return $fs_account_data['all_licenses'][$this->fs_plugin_data->id][0]->secret_key;
    }

    /**
     * Make a POST request using Guzzle HTTP client.
     *
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return ResponseInterface
     * @throws GuzzleException
     */
    private function sendPostRequest($endpoint, $message) {
        $client = new ApiClient();
        
        $url = self::CLOUD_AI_URL . $endpoint;
        
        $headers = [
            'Authorization' => "Bearer {$this->cloud_key}",
        ];

        $parts = [
            'multipart' => [
                [
                    'name' => 'prompt',
                    'contents' => $message
                ],
                [
                    'name' => 'fs_key',
                    'contents' => $this->freemius_licence
                ]
            ]
        ];
        
        $request = new ApiRequest('POST', $url, $headers);
        $response = $client->sendAsync($request, $parts)->wait();

        $promise = $client->sendAsync($request, $parts)->then(function (ApiResponse $response) {
            return $response->getBody()->getContents();
        });

        $promise = $promise->wait();
        $response = json_decode($promise, true);

        if (!isset($response['response'])) {
            $trace = debug_backtrace();
            return new WP_Error('cloud_ai_error', 'Cloud AI Response Error', $trace);
        }

        return $response['response'];
    }

    /**
     * Make a POST request to the Cloud AI prompt endpoint.
     *
     * @param string $message
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function prompt($message) {
        $endpoint = self::PROMT_PATH;
        return $this->sendPostRequest($endpoint, $message);
    }

    /**
     * Make a POST request to the Cloud AI explain endpoint.
     *
     * @param string $message
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function explain($message) {
        $endpoint = self::EXPLAIN_PATH;
        return $this->sendPostRequest($endpoint, $message);
    }
}
