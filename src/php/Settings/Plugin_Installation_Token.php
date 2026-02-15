<?php

namespace Code_Snippets\Settings;

use function Code_Snippets\code_snippets;

/**
 * Handles generation and validation of plugin installation tokens.
 *
 * @package Code_Snippets
 */
class Plugin_Installation_Token
{

    /**
     * Transient key for storing the token.
     *
     * @var string
     */
    const TRANSIENT_KEY = 'code_snippets_pi_token';

    /**
     * Token expiration time in seconds (6 hours).
     *
     * @var int
     */
    const EXPIRY = 21600;

    /**
     * Token prefix.
     *
     * @var string
     */
    const PREFIX = 'pi1';

    /**
     * Retrieve the current token, or generate a new one if it doesn't exist or is expired.
     *
     * @return string The valid token.
     */
    public function get_token(): string
    {
        $token = get_transient(self::TRANSIENT_KEY);

        if (false === $token || !is_string($token) || !$this->validate_token($token)) {
            return $this->generate_token();
        }

        return $token;
    }

    /**
     * Generate a new token and store it in a transient.
     *
     *Format: pi1_[timestamp]_[random_string]
     *
     * @return string The generated token.
     */
    public function generate_token(): string
    {
        $timestamp = time();
        $random_string = wp_generate_password(32, false);
        $token = sprintf('%s_%d_%s', self::PREFIX, $timestamp, $random_string);

        set_transient(self::TRANSIENT_KEY, $token, self::EXPIRY);

        return $token;
    }

    /**
     * Validate a token against the required format and expiry.
     *
     * @param string $token The token to validate.
     *
     * @return bool True if valid, false otherwise.
     */
    public function validate_token(string $token): bool
    {
        $parts = explode('_', $token);

        if (count($parts) !== 3) {
            return false;
        }

        list($prefix, $timestamp, $random_string) = $parts;

        // Check prefix.
        if (self::PREFIX !== $prefix) {
            return false;
        }

        // Check timestamp is numeric.
        if (!is_numeric($timestamp)) {
            return false;
        }

        // Check timestamp freshness.
        if (time() - (int) $timestamp > self::EXPIRY) {
            return false;
        }

        if (!ctype_alnum($random_string) || strlen($random_string) < 16 || strlen($random_string) > 32) {
            return false;
        }

        return true;
    }

    /**
     * Force regeneration of the token.
     *
     * @return string The new token.
     */
    public function regenerate_token(): string
    {
        $this->delete_token();
        return $this->generate_token();
    }

    /**
     * Delete the stored token.
     *
     * @return void
     */
    public function delete_token()
    {
        delete_transient(self::TRANSIENT_KEY);
    }

    /**
     * Hook to auto-generate token on admin load.
     *
     * @return void
     */
    public function init()
    {
        // Only run on Code Snippets admin pages.
        if (isset($_GET['page']) && strpos(sanitize_key($_GET['page']), 'snippets') !== false) {
            $this->get_token();
        }

        // AJAX action for regenerating token.
        add_action('wp_ajax_code_snippets_regenerate_token', [$this, 'ajax_regenerate_token']);
    }

    /**
     * Render the token field in settings.
     *
     * @return void
     */
    public static function render_token_field()
    {
        $instance = new self();
        $token = $instance->get_token();
        $nonce = wp_create_nonce('code_snippets_regenerate_token');

        printf(
            '<div id="code-snippets-token-settings" data-token="%s" data-nonce="%s"></div>',
            esc_attr($token),
            esc_attr($nonce)
        );
    }

    /**
     * AJAX handler to regenerate the token.
     *
     * @return void
     */
    public function ajax_regenerate_token()
    {
        check_ajax_referer('code_snippets_regenerate_token', 'nonce');

        if (!current_user_can(code_snippets()->get_cap())) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'code-snippets')]);
        }

        $token = $this->regenerate_token();
        wp_send_json_success(['token' => $token]);
    }
}
