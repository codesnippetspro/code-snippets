<?php

namespace Code_Snippets\REST_API;

use Code_Snippets\Model\Authenticated_Cloud_Connection;
use Code_Snippets\REST_API\Cloud\Cloud_Plugin_REST_Controller;
use Code_Snippets\UnitTestCase;
use WP_REST_Request;
use ZipArchive;

/**
 * Tests for the cloud "install bundle plugin" REST endpoint.
 *
 * @group rest-api
 */
class Cloud_Plugin_REST_Controller_Test extends UnitTestCase {

	/**
	 * REST API namespace and base route.
	 *
	 * @var string
	 */
	protected string $endpoint = '/code-snippets/v1/cloud/plugin';

	/**
	 * Slug used for the fixture plugin.
	 *
	 * @var string
	 */
	private string $slug = 'csp-test-bundle';

	/**
	 * Temporary directory holding generated fixture archives.
	 *
	 * @var string
	 */
	private string $work_dir = '';

	/**
	 * The connection instance backing the controller under test.
	 *
	 * @var Authenticated_Cloud_Connection
	 */
	private Authenticated_Cloud_Connection $connection;

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		// Force a fresh REST server/route table each test: routes registered
		// without $override=true otherwise accumulate handlers bound to a
		// previous test's connection instance, and the dispatcher always
		// uses the first-registered handler for a given route+method.
		global $wp_rest_server;
		$wp_rest_server = null;

		$this->connection = new Authenticated_Cloud_Connection();
		new Cloud_Plugin_REST_Controller( $this->connection );
		do_action( 'rest_api_init' );

		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$this->work_dir = get_temp_dir() . 'csp-plugin-test-' . wp_generate_password( 8, false );
		wp_mkdir_p( $this->work_dir );
	}

	/**
	 * Tear down after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		global $wp_filesystem;

		// Remove any plugin the tests installed (bundle fixture + Pro fixture).
		foreach ( [ $this->slug, 'code-snippets-pro' ] as $installed_slug ) {
			$installed = trailingslashit( WP_PLUGIN_DIR ) . $installed_slug;
			if ( is_dir( $installed ) ) {
				deactivate_plugins( $installed_slug . '/' . $installed_slug . '.php', true );
				$wp_filesystem->delete( $installed, true );
			}
		}

		$wp_filesystem->delete( $this->work_dir, true );

		$this->set_connection_local_token( $this->connection, '' );

		parent::tear_down();
	}

	/**
	 * Read the active cloud connection's local token.
	 *
	 * @return string
	 */
	private function get_connection_token(): string {
		$plugin = \Code_Snippets\code_snippets();

		$property = new \ReflectionProperty( $plugin, 'cloud_connection' );
		$property->setAccessible( true );

		return $property->getValue( $plugin )->get_local_token();
	}

	/**
	 * Seed a real, non-empty local_token directly onto a connection instance
	 * via reflection, bypassing the settings that were loaded (empty) at
	 * bootstrap and won't otherwise pick up a changed option.
	 *
	 * @param Authenticated_Cloud_Connection $connection Connection to mutate.
	 * @param string                         $token      Token value to set.
	 *
	 * @return void
	 */
	private function set_connection_local_token( Authenticated_Cloud_Connection $connection, string $token ): void {
		$settings_prop = new \ReflectionProperty( $connection, 'settings' );
		$settings_prop->setAccessible( true );

		$settings = $settings_prop->getValue( $connection );
		$settings['local_token'] = $token;
		$settings_prop->setValue( $connection, $settings );
	}

	/**
	 * Build a standalone-plugin zip whose top-level folder is $slug.
	 *
	 * @param string      $slug      Plugin slug / folder name.
	 * @param string      $version   Plugin version written into the header.
	 * @param string      $body      Extra PHP appended after the plugin header.
	 * @param string|null $main_file Main file name inside the folder. Defaults to
	 *                               "<slug>.php"; pass a different name to model a
	 *                               plugin whose file is not named after its folder.
	 *
	 * @return string Path to the generated zip.
	 */
	private function make_plugin_zip( string $slug, string $version = '1.0.0', string $body = '', ?string $main_file = null ): string {
		$main_file = $main_file ?? $slug . '.php';
		$zip_path = $this->work_dir . '/' . $slug . '-' . $version . '.zip';
		$header = "<?php\n/**\n * Plugin Name: " . $slug . "\n * Version: " . $version . "\n */\n" . $body;

		$zip = new ZipArchive();
		$zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE );
		$zip->addFromString( $slug . '/' . $main_file, $header );
		$zip->close();

		return $zip_path;
	}

	/**
	 * Build a request for the endpoint with a file attached.
	 *
	 * @param string                         $zip_path  Path to the zip to upload.
	 * @param array<string, string>          $params    Body params (slug, version, activate).
	 * @param array<string, int|string>|null $overrides Overrides merged into the file entry.
	 *
	 * @return WP_REST_Request
	 */
	private function build_request( string $zip_path, array $params, ?array $overrides = null ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', $this->endpoint );

		$file = array_merge(
			[
				'name'     => basename( $zip_path ),
				'type'     => 'application/zip',
				'tmp_name' => $zip_path,
				'error'    => 0,
				'size'     => (int) filesize( $zip_path ),
			],
			(array) $overrides
		);

		$request->set_file_params( [ 'plugin' => $file ] );

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		return $request;
	}

	/**
	 * Helper: build a zip for the fixture slug at a given version and send it.
	 *
	 * @param string $version Version string.
	 *
	 * @return \WP_REST_Response
	 */
	private function build_and_send( string $version ) {
		$zip = $this->make_plugin_zip( $this->slug, $version );
		$request = $this->build_request(
			$zip,
			[
				'slug'    => $this->slug,
				'version' => $version,
			]
		);
		return rest_do_request( $request );
	}

	/**
	 * A valid archive is installed and reported as a fresh (non-update) install.
	 *
	 * @return void
	 */
	public function test_installs_plugin(): void {
		$zip = $this->make_plugin_zip( $this->slug );
		$request = $this->build_request(
			$zip,
			[
				'slug'    => $this->slug,
				'version' => '1.0.0',
			]
		);

		$response = rest_do_request( $request );
		$data = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $data['success'] );
		$this->assertSame( $this->slug, $data['slug'] );
		$this->assertFalse( $data['updated'] );
		$this->assertFalse( $data['activated'] );
		$this->assertFileExists( trailingslashit( WP_PLUGIN_DIR ) . $this->slug . '/' . $this->slug . '.php' );
	}

	/**
	 * Re-installing an existing slug overwrites it and reports updated=true.
	 *
	 * @return void
	 */
	public function test_reinstall_reports_update(): void {
		$this->build_and_send( '1.0.0' );
		$response = $this->build_and_send( '1.1.0' );
		$data = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $data['updated'] );
		$this->assertSame( '1.1.0', $data['version'] );
	}

	/**
	 * The reported version comes from the installed plugin header, not the
	 * value the cloud sent in the request.
	 *
	 * @return void
	 */
	public function test_reports_disk_version_not_request_echo(): void {
		$zip = $this->make_plugin_zip( $this->slug, '2.5.0' );
		$request = $this->build_request(
			$zip,
			[
				'slug'    => $this->slug,
				'version' => '9.9.9',
			]
		);

		$response = rest_do_request( $request );
		$data = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( '2.5.0', $data['version'] );
	}

	/**
	 * An explicit mode=update forces the updated flag even on a first install.
	 *
	 * @return void
	 */
	public function test_mode_update_forces_updated_flag(): void {
		$zip = $this->make_plugin_zip( $this->slug );
		$request = $this->build_request(
			$zip,
			[
				'slug' => $this->slug,
				'mode' => 'update',
			]
		);

		$response = rest_do_request( $request );

		$this->assertTrue( $response->get_data()['updated'] );
	}

	/**
	 * The completed response carries the identifiers-only license object and
	 * never leaks a secret.
	 *
	 * @return void
	 */
	public function test_response_includes_license_identifiers_without_secrets(): void {
		$data = $this->build_and_send( '1.0.0' )->get_data();

		$this->assertArrayHasKey( 'license', $data );
		$this->assertSame(
			[ 'is_registered', 'has_valid_license', 'is_paying', 'license_id', 'install_id', 'freemius_user_id', 'plan_id' ],
			array_keys( $data['license'] )
		);

		$json = wp_json_encode( $data );
		foreach ( [ 'secret_key', 'license_key', 'secret', 'activation' ] as $needle ) {
			$this->assertStringNotContainsStringIgnoringCase( $needle, $json, "response leaked \"$needle\"" );
		}
	}

	/**
	 * An in-place update must not disturb Freemius license state, which lives in
	 * options rather than in the plugin files being overwritten.
	 *
	 * @return void
	 */
	public function test_update_preserves_stored_license_state(): void {
		$sentinel = [
			'id'         => 42,
			'secret_key' => 'sk_keep_me',
		];
		update_option( 'fs_accounts', $sentinel );

		$this->build_and_send( '1.0.0' );
		$this->build_and_send( '1.1.0' );

		$this->assertSame( $sentinel, get_option( 'fs_accounts' ) );

		delete_option( 'fs_accounts' );
	}

	/**
	 * Send a Code Snippets Pro archive over the token-authed path.
	 *
	 * @param string $version  Version written into the plugin header.
	 * @param string $sig_mode One of 'valid', 'invalid', or 'missing'.
	 *
	 * @return \WP_REST_Response
	 */
	private function send_pro_install( string $version, string $sig_mode = 'valid' ) {
		wp_set_current_user( 0 );

		$token = 'test-site-token-abc123';
		$this->set_connection_local_token( $this->connection, $token );

		// Model the real Pro layout: folder code-snippets-pro, main file code-snippets.php.
		$zip = $this->make_plugin_zip( 'code-snippets-pro', $version, '', 'code-snippets.php' );
		$request = $this->build_request(
			$zip,
			[
				'slug'    => 'code-snippets-pro',
				'version' => $version,
			]
		);
		$request->add_header( 'Access-Control', $token );

		if ( 'missing' !== $sig_mode ) {
			$key = 'invalid' === $sig_mode ? 'wrong-signing-key' : $token;
			$request->add_header( 'X-CS-Artifact-Signature', hash_hmac_file( 'sha256', $zip, $key ) );
		}

		return rest_do_request( $request );
	}

	/**
	 * Read the version currently installed for the Pro fixture, or '' if absent.
	 *
	 * @return string
	 */
	private function installed_pro_version(): string {
		$file = trailingslashit( WP_PLUGIN_DIR ) . 'code-snippets-pro/code-snippets.php';
		if ( ! is_file( $file ) ) {
			return '';
		}

		$data = get_plugin_data( $file, false, false );
		return (string) ( $data['Version'] ?? '' );
	}

	/**
	 * Pushing the Pro slug without a signature is rejected and nothing installs.
	 *
	 * @return void
	 */
	public function test_pro_requires_signature(): void {
		$response = $this->send_pro_install( '3.9.2', 'missing' );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'signature_required', $response->get_data()['code'] );
		$this->assertFalse( is_dir( trailingslashit( WP_PLUGIN_DIR ) . 'code-snippets-pro' ) );
	}

	/**
	 * A Pro push with a signature that does not match is rejected.
	 *
	 * @return void
	 */
	public function test_pro_rejects_invalid_signature(): void {
		$response = $this->send_pro_install( '3.9.2', 'invalid' );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'invalid_signature', $response->get_data()['code'] );
		$this->assertFalse( is_dir( trailingslashit( WP_PLUGIN_DIR ) . 'code-snippets-pro' ) );
	}

	/**
	 * A correctly signed Pro archive installs and reports its header version.
	 *
	 * @return void
	 */
	public function test_pro_installs_with_valid_signature(): void {
		$response = $this->send_pro_install( '3.9.2' );
		$data = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $data['success'] );
		$this->assertSame( '3.9.2', $data['version'] );
		$this->assertSame( '3.9.2', $this->installed_pro_version() );
	}

	/**
	 * A newer signed Pro archive upgrades an existing install in place.
	 *
	 * @return void
	 */
	public function test_pro_upgrade_in_place(): void {
		$this->send_pro_install( '3.9.0' );
		$response = $this->send_pro_install( '3.9.2' );
		$data = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $data['updated'] );
		$this->assertSame( '3.9.2', $data['version'] );
		$this->assertSame( '3.9.2', $this->installed_pro_version() );
	}

	/**
	 * Re-pushing the installed Pro version is a no-op, not a reinstall.
	 *
	 * @return void
	 */
	public function test_pro_same_version_is_noop(): void {
		$this->send_pro_install( '3.9.2' );
		$response = $this->send_pro_install( '3.9.2' );
		$data = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertFalse( $data['updated'] );
		$this->assertSame( '3.9.2', $data['version'] );
		$this->assertSame( __( 'Already up to date.', 'code-snippets' ), $data['message'] );
	}

	/**
	 * An older signed Pro archive is refused with 409 and the install is intact.
	 *
	 * @return void
	 */
	public function test_pro_downgrade_rejected(): void {
		$this->send_pro_install( '3.9.2' );
		$response = $this->send_pro_install( '3.9.0' );

		$this->assertSame( 409, $response->get_status() );
		$this->assertSame( 'version_downgrade', $response->get_data()['code'] );
		$this->assertSame( '3.9.2', $this->installed_pro_version() );
	}

	/**
	 * For a non-Pro slug a signature is optional, but if supplied it must match.
	 *
	 * @return void
	 */
	public function test_bundle_rejects_invalid_signature_when_present(): void {
		wp_set_current_user( 0 );

		$token = 'test-site-token-abc123';
		$this->set_connection_local_token( $this->connection, $token );

		$zip = $this->make_plugin_zip( $this->slug );
		$request = $this->build_request( $zip, [ 'slug' => $this->slug ] );
		$request->add_header( 'Access-Control', $token );
		$request->add_header( 'X-CS-Artifact-Signature', hash_hmac_file( 'sha256', $zip, 'wrong-signing-key' ) );

		$response = rest_do_request( $request );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'invalid_signature', $response->get_data()['code'] );
		$this->assertFalse( is_dir( trailingslashit( WP_PLUGIN_DIR ) . $this->slug ) );
	}

	/**
	 * A non-Pro slug with a correct signature installs normally.
	 *
	 * @return void
	 */
	public function test_bundle_accepts_valid_signature(): void {
		wp_set_current_user( 0 );

		$token = 'test-site-token-abc123';
		$this->set_connection_local_token( $this->connection, $token );

		$zip = $this->make_plugin_zip( $this->slug );
		$request = $this->build_request( $zip, [ 'slug' => $this->slug ] );
		$request->add_header( 'Access-Control', $token );
		$request->add_header( 'X-CS-Artifact-Signature', hash_hmac_file( 'sha256', $zip, $token ) );

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['success'] );
	}

	/**
	 * A failed install (archive whose top folder does not match the slug) must
	 * leave any existing install untouched rather than delete it first.
	 *
	 * @return void
	 */
	public function test_failed_install_leaves_existing_plugin_intact(): void {
		$this->build_and_send( '1.0.0' );
		$main = trailingslashit( WP_PLUGIN_DIR ) . $this->slug . '/' . $this->slug . '.php';
		$this->assertFileExists( $main );

		$mismatched = $this->make_plugin_zip( 'some-other-folder', '2.0.0' );
		$request = $this->build_request(
			$mismatched,
			[
				'slug'    => $this->slug,
				'version' => '2.0.0',
			]
		);
		$response = rest_do_request( $request );

		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( 'install_failed', $response->get_data()['code'] );
		$this->assertFileExists( $main );
		$this->assertSame( '1.0.0', get_plugin_data( $main, false, false )['Version'] );
	}

	/**
	 * When an active plugin is updated and its main file is renamed, the active
	 * state must carry across (re-activated under the new file) and the response
	 * must report the real activation state.
	 *
	 * @return void
	 */
	public function test_update_reactivates_when_main_file_renamed(): void {
		$zip = $this->make_plugin_zip( $this->slug, '1.0.0' );
		$activate = $this->build_request(
			$zip,
			[
				'slug'     => $this->slug,
				'activate' => '1',
			]
		);
		$this->assertTrue( rest_do_request( $activate )->get_data()['activated'] );
		$this->assertTrue( is_plugin_active( $this->slug . '/' . $this->slug . '.php' ) );

		$renamed = $this->make_plugin_zip( $this->slug, '1.1.0', '', 'main.php' );
		$update = $this->build_request(
			$renamed,
			[
				'slug'    => $this->slug,
				'version' => '1.1.0',
			]
		);
		$data = rest_do_request( $update )->get_data();

		$this->assertTrue( $data['updated'] );
		$this->assertTrue( $data['activated'], 'active state should carry across a main-file rename' );
		$this->assertTrue( is_plugin_active( $this->slug . '/main.php' ) );
		$this->assertFalse( is_plugin_active( $this->slug . '/' . $this->slug . '.php' ) );
	}

	/**
	 * Passing activate=1 activates the freshly installed plugin.
	 *
	 * @return void
	 */
	public function test_activates_when_requested(): void {
		$zip = $this->make_plugin_zip( $this->slug );
		$request = $this->build_request(
			$zip,
			[
				'slug'     => $this->slug,
				'activate' => '1',
			]
		);

		$response = rest_do_request( $request );
		$data = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $data['activated'] );
		$this->assertTrue( is_plugin_active( $this->slug . '/' . $this->slug . '.php' ) );
	}

	/**
	 * A request without a file is rejected with 400 bad_request.
	 *
	 * @return void
	 */
	public function test_rejects_missing_file(): void {
		$request = new WP_REST_Request( 'POST', $this->endpoint );
		$request->set_param( 'slug', $this->slug );

		$response = rest_do_request( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'bad_request', $response->get_data()['code'] );
	}

	/**
	 * A non-zip upload is rejected with 400 bad_request.
	 *
	 * @return void
	 */
	public function test_rejects_non_zip(): void {
		global $wp_filesystem;
		$txt = $this->work_dir . '/not-a-plugin.txt';
		$wp_filesystem->put_contents( $txt, 'just some text' );

		$request = $this->build_request(
			$txt,
			[ 'slug' => $this->slug ],
			[
				'name' => 'not-a-plugin.txt',
				'type' => 'text/plain',
			]
		);

		$response = rest_do_request( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'bad_request', $response->get_data()['code'] );
	}

	/**
	 * An oversized upload is rejected with 413 before anything is written.
	 *
	 * @return void
	 */
	public function test_rejects_oversized_upload(): void {
		$zip = $this->make_plugin_zip( $this->slug );
		$request = $this->build_request(
			$zip,
			[ 'slug' => $this->slug ],
			[ 'size' => ( 25 * 1024 * 1024 ) + 1 ]
		);

		$response = rest_do_request( $request );

		$this->assertSame( 413, $response->get_status() );
		$this->assertSame( 'payload_too_large', $response->get_data()['code'] );
	}

	/**
	 * An empty slug (nothing left after sanitising) is rejected with 400.
	 *
	 * @return void
	 */
	public function test_rejects_bad_slug(): void {
		$zip = $this->make_plugin_zip( $this->slug );
		$request = $this->build_request( $zip, [ 'slug' => '../../' ] );

		$response = rest_do_request( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'bad_request', $response->get_data()['code'] );
	}

	/**
	 * A valid Access-Control token alone (no logged-in user) authorises the install.
	 *
	 * @return void
	 */
	public function test_installs_plugin_with_valid_token_and_no_user(): void {
		wp_set_current_user( 0 );

		$token = 'test-site-token-abc123';
		$this->set_connection_local_token( $this->connection, $token );

		$zip = $this->make_plugin_zip( $this->slug );
		$request = $this->build_request(
			$zip,
			[
				'slug'    => $this->slug,
				'version' => '1.0.0',
			]
		);
		$request->add_header( 'Access-Control', $token );

		$response = rest_do_request( $request );
		$data = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $data['success'] );
		$this->assertFileExists( trailingslashit( WP_PLUGIN_DIR ) . $this->slug . '/' . $this->slug . '.php' );
	}

	/**
	 * A request with neither a valid token nor an authenticated admin is rejected, and
	 * nothing is installed.
	 *
	 * @return void
	 */
	public function test_rejects_unauthenticated(): void {
		wp_set_current_user( 0 );

		$this->set_connection_local_token( $this->connection, 'test-site-token-abc123' );

		$zip = $this->make_plugin_zip( $this->slug );
		$request = $this->build_request( $zip, [ 'slug' => $this->slug ] );
		$request->add_header( 'Access-Control', 'not-the-right-token' );

		$response = rest_do_request( $request );

		$this->assertContains( $response->get_status(), [ 401, 403 ] );
		$this->assertFalse( is_dir( trailingslashit( WP_PLUGIN_DIR ) . $this->slug ) );
	}

	/**
	 * A never-connected site (empty local_token), no logged-in user, and a
	 * present-but-empty Access-Control header must be rejected outright —
	 * the highest-risk endpoint must never fail open on an empty token.
	 * Nothing gets installed.
	 *
	 * @return void
	 */
	public function test_rejects_empty_token_with_empty_header_and_no_user(): void {
		wp_set_current_user( 0 );

		$this->set_connection_local_token( $this->connection, '' );

		$zip = $this->make_plugin_zip( $this->slug );
		$request = $this->build_request( $zip, [ 'slug' => $this->slug ] );
		$request->add_header( 'Access-Control', '' );

		$response = rest_do_request( $request );

		$this->assertContains( $response->get_status(), [ 401, 403 ] );
		$this->assertFalse( is_dir( trailingslashit( WP_PLUGIN_DIR ) . $this->slug ) );
	}
}
