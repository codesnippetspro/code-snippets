<?php
/**
 * PRO Plugin Installer Job Class
 *
 * @package Code_Snippets
 */

namespace Code_Snippets\Core;

use Code_Snippets\Flat_Files\WP_Filesystem_Adapter;

/**
 * Handles the actual PRO plugin installation process
 * Designed to be called asynchronously via WP-Cron
 */
class Pro_Plugin_Installer
{

    protected WP_Filesystem_Adapter $filesystem;

    protected string $temp_dir;
    protected string $pro_plugin_path;
    protected string $pro_full_path;

    public function __construct()
    {
        $this->filesystem = new WP_Filesystem_Adapter();
        $this->temp_dir = WP_CONTENT_DIR . '/code-snippets-pro-temp';
        $this->pro_plugin_path = 'code-snippets-pro/code-snippets.php';
        $this->pro_full_path = WP_PLUGIN_DIR . '/' . $this->pro_plugin_path;
    }

    /**
     * Execute the installation
     *
     * @uses array $installation_data Installation data from cloud.
     * Array contents:
     * [
     *  'download_url' => string,
     *  'site_token' => string,
     *  'cloud_token' => string,
     * ]
     * @return bool Success or failure.
     */
    public function __invoke(): bool
    {
        // Get installation data from transient
        $installation_data = get_transient('code_snippets_installation_data');

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CS Installer: Starting PRO plugin installation');
        }

        $zip_file = $this->download_plugin($installation_data);


        if (!$zip_file) {
            $this->log('[Installer] Error: Download returned empty filename.');
            return false;
        }

        $this->log('[Installer] Downloaded PRO plugin: ' . $zip_file);

        if (!$this->install_plugin($zip_file)) {
            return false;
        }

        if (!$this->activate_plugin()) {
            return false;
        }

        //Clean up temp files
        $this->cleanup_temp_files($zip_file);

        return true;
    }

    /**
     * Download the plugin zip file.
     *
     * @param array $installation_data Installation data.
     * @return string|null Path to the downloaded file, or null on failure.
     */
    private function download_plugin(array $installation_data): ?string
    {
        $download_url = $installation_data['download_url'] ?? '';
        $site_token = $installation_data['site_token'] ?? '';
        $cloud_token = $installation_data['cloud_token'] ?? '';

        if (!$this->filesystem->exists($this->temp_dir)) {
            $this->filesystem->mkdir($this->temp_dir, 0755);
        }

        $target_file = $this->temp_dir . '/code-snippets-pro.zip';

        $response = wp_remote_post($download_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $cloud_token,
                'Accept' => 'application/json',
            ],
            'body' => [
                'site_url' => get_site_url(),
                'site_token' => $site_token,
            ],
            'timeout' => 300, // 5 minutes for large files
            'stream' => true,
            'filename' => $target_file,
        ]);

        if (is_wp_error($response)) {
            $this->log('[Installer] Download Failed: WP_Error - ' . $response->get_error_message());
            error_log('Premium plugin download failed: ' . $response->get_error_message());
            return null;
        }

        // Check if file exists FIRST (streaming may succeed despite HTTP error)
        if (file_exists($target_file) && filesize($target_file) > 0) {
            return $target_file;
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code !== 200) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            $error_message = $body['error'] ?? 'Unknown error';
            $this->log('[Installer] Download Failed: HTTP ' . $status_code . ' - ' . $error_message);
            error_log('Premium plugin download failed: ' . $error_message);
            return null;
        }

        $this->log('Downloaded file not found at ' . $target_file);
        error_log('Downloaded file not found at ' . $target_file);
        return null;
    }

    /**
     * Install the plugin from the zip file.
     *
     * @param string $zip_file Path to the zip file.
     * @return bool Success or failure.
     */
    private function install_plugin(string $zip_file): bool
    {
        if (!function_exists('unzip_file')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        // Unzip the file to the plugins directory
        $result = unzip_file($zip_file, WP_PLUGIN_DIR);

        if (is_wp_error($result)) {
            $this->log('[Installer] Unzip Failed: ' . $result->get_error_message());
            error_log('Premium plugin unzip failed: ' . $result->get_error_message());
            return false;
        }

        $this->log('[Installer] Unzipped PRO plugin successfully.');

        // Set transient for pending automated activation for pro plugin to hook into
        set_transient('code_snippets_pro_pending_automated_activation', [
            'pro_plugin_not_activated' => true
        ], 1800); // 30 minutes

        return true;
    }

    /**
     * Activate the installed plugin.
     *
     * @return bool Success or failure.
     */
    private function activate_plugin(): bool
    {
        if (!function_exists('activate_plugin')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // Clear all caches before checking
        wp_cache_delete('plugins', 'plugins');
        clearstatcache();

        if (!file_exists($this->pro_full_path)) {
            $this->log('Premium plugin file not found: ' . $this->pro_full_path);
            return false;
        }

        // Get plugin data
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // Check if already active
        if (is_plugin_active($this->pro_plugin_path)) {
            $this->log('Plugin is already active');
            return true;
        }

        // Attempt activation
        $result = activate_plugin($this->pro_plugin_path, '', false, true);

        if (is_wp_error($result)) {
            $this->log('Activation failed!');
            $this->log('Error message: ' . $result->get_error_message());
            $this->log('Error code: ' . $result->get_error_code());
            $this->log('Error data: ' . print_r($result->get_error_data(), true));
            return false;
        }

        return true;
    }

    /**
     * Delete the Temporary directory and any files in this directory if it exists
     *
     * @return void
     */
    private function cleanup_temp_files(): void
    {
        if ($this->filesystem->exists($this->temp_dir)) {
            $this->filesystem->delete($this->temp_dir, true);
        }
    }

    /**
     * Log a message to the debug log
     *
     * @param string $message The message to log.
     * @return void
     */
    private function log(string $message): void
    {
        $current_log = get_option('cs_debug_log', []);
        if (!is_array($current_log)) {
            $current_log = [];
        }
        $current_log[] = $message;
        update_option('cs_debug_log', $current_log);
    }
}