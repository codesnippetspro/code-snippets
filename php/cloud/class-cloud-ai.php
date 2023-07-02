<?php

namespace Code_Snippets\Cloud;

use WP_Error;
use GuzzleHttp\Client as ApiClient;
use GuzzleHttp\Psr7\Request as ApiRequest;
use GuzzleHttp\Psr7\Response as ApiResponse;
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
    private function sendPostRequest($url, $data, $headers = []) {
        $client = new ApiClient();
        $headers['Content-Type'] = 'multipart/form-data';

        $options = [
            'multipart' => $data
        ];
        
        $request = new ApiRequest('POST', $url, $headers);
        $promise = $client->sendAsync($request, $options)->then(function (ApiResponse $response) {
            return $response->getBody()->getContents();
        });
        
        $response = $promise->wait();
        
        do_action( 'inspect', [ 'Cloud_AI::sendPostRequest', \compact('client', 'options', 'headers', 'request', 'promise', 'response'), __FILE__, __LINE__ ] );
        return $this->unpack_request_json($response);
    }

    /**
     * Make a POST request to the Cloud AI prompt endpoint.
     *
     * @param string $message
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function prompt($message) {
        $url = self::CLOUD_AI_URL . self::PROMT_PATH;
        $data =  [
            [
                'name' => 'prompt',
                'contents' => $message
            ],
            [
                'name' => 'fs_key',
                'contents' => $this->freemius_licence
            ]
        ];

        $headers = [
            'Authorization' => $this->cloud_key,
        ];

        do_action( 'inspect', [ 'Cloud_AI::prompt', \compact('url', 'data', 'headers'), __FILE__, __LINE__ ] );

        return $this->sendPostRequest($url, $data, $headers);
    }

    /**
     * Make a POST request to the Cloud AI explain endpoint.
     *
     * @param string $message
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function explain($message) {
        $url = self::CLOUD_AI_URL . self::EXPLAIN_PATH;
        $data =  [
            [
                'name' => 'prompt',
                'contents' => $message
            ],
            [
                'name' => 'fs_key',
                'contents' => $this->freemius_licence
            ]
        ];

        $headers = [
            'Authorization' => $this->cloud_key,
        ];

        return $this->sendPostRequest($url, $data, $headers);
    }
}
