<?php

namespace Code_Snippets\Admin;

use Code_Snippets\AdminUnitTestCase;
use Code_Snippets\Core\Licensing;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;
use function Code_Snippets\code_snippets;

/**
 * Tests for choosing and dismissing the notice shown on the manage screen.
 *
 * @group admin-notices
 */
class Bootstrap_Admin_Test extends AdminUnitTestCase {

	/**
	 * User meta key listing dismissed notices.
	 *
	 * @var string
	 */
	private const DISMISSED_META_KEY = 'ignore_code_snippets_survey_message';

	/**
	 * A moment while the feed banner used in these tests is scheduled.
	 *
	 * @var string
	 */
	private const DURING_BANNER = '2026-11-25T12:00:00+00:00';

	/**
	 * Request URI in place before each test.
	 *
	 * @var string|null
	 */
	private ?string $request_uri;

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->request_uri = $_SERVER['REQUEST_URI'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Restored verbatim in tear_down().
		set_current_screen( 'dashboard' );
	}

	/**
	 * Tear down after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		delete_transient( 'code_snippets_welcome_data' );
		unset( $_GET[ self::DISMISSED_META_KEY ], $_REQUEST[ self::DISMISSED_META_KEY ], $_GET['_wpnonce'], $_REQUEST['_wpnonce'] );

		if ( null === $this->request_uri ) {
			unset( $_SERVER['REQUEST_URI'] );
		} else {
			$_SERVER['REQUEST_URI'] = $this->request_uri;
		}

		parent::tear_down();
	}

	/**
	 * Build a point in time.
	 *
	 * @param string $datetime Date and time with offset.
	 *
	 * @return DateTimeImmutable
	 */
	private static function moment( string $datetime ): DateTimeImmutable {
		return new DateTimeImmutable( $datetime, new DateTimeZone( 'UTC' ) );
	}

	/**
	 * Build an admin bootstrap whose welcome feed holds the given banner, or no banner.
	 *
	 * @param array<string, mixed>|null $banner Feed banner fields to override, or null for a feed without a banner.
	 *
	 * @return Bootstrap_Admin
	 */
	private function make_admin( ?array $banner = [] ): Bootstrap_Admin {
		$defaults = [
			'key'               => 'bf2026',
			'start_datetime'    => self::moment( '2026-11-23T00:00:00+00:00' ),
			'end_datetime'      => self::moment( '2026-12-01T00:00:00+00:00' ),
			'text_free'         => 'Save up to 40% on Code Snippets Pro! Also see <a href="https://translatewp.ai/" target="_blank">TranslateWP</a>.<script>alert(1)</script>',
			'action_url_free'   => 'https://codesnippets.pro/pricing/?cs_user=new',
			'action_label_free' => 'Get Deal',
		];

		set_transient( 'code_snippets_welcome_data', null === $banner ? [] : [ 'banner' => array_merge( $defaults, $banner ) ] );

		return new Bootstrap_Admin();
	}

	/**
	 * Capture the output of print_notices().
	 *
	 * @param Bootstrap_Admin $admin Admin bootstrap.
	 *
	 * @return string
	 */
	private function capture_notices( Bootstrap_Admin $admin ): string {
		ob_start();
		$admin->print_notices();
		return (string) ob_get_clean();
	}

	/**
	 * A scheduled feed banner is handed to the script as the promotional banner, with its content from the feed.
	 *
	 * @return void
	 */
	public function test_feed_banner_is_offered_as_the_promo_banner(): void {
		$admin = $this->make_admin();

		$promo = $admin->get_promo_banner( self::moment( self::DURING_BANNER ) );

		$this->assertIsArray( $promo );
		$this->assertSame( 'bf2026', $promo['key'] );
		$this->assertStringContainsString( 'Save up to 40% on Code Snippets Pro!', $promo['text'] );
		$this->assertStringContainsString(
			'<a href="https://translatewp.ai/" target="_blank" rel="noopener noreferrer">TranslateWP</a>',
			$promo['text']
		);
		$this->assertStringNotContainsString( '<script', $promo['text'] );
		$this->assertSame( '', $promo['label'] );
		$this->assertSame( '', $promo['secondary'] );
		$this->assertSame( 'Get Deal', $promo['actionLabel'] );
		$this->assertSame( 'https://codesnippets.pro/pricing/?cs_user=new&plugin=cs_core', $promo['actionUrl'] );
		$this->assertSame( '2026-12-01T00:00:00+00:00', $promo['endsAt'] );
		$this->assertStringContainsString( self::DISMISSED_META_KEY . '=bf2026', $promo['dismissUrl'] );
		$this->assertStringNotContainsString( '&amp;', $promo['dismissUrl'] );
		$this->assertSame( 'bf2026', $promo['dismissArgs'][ self::DISMISSED_META_KEY ] );
		$this->assertSame( 1, wp_verify_nonce( $promo['dismissArgs']['_wpnonce'], self::DISMISSED_META_KEY ) );
	}

	/**
	 * A feed banner's short headline, label and secondary text are used in place of its full text.
	 *
	 * @return void
	 */
	public function test_feed_headline_label_and_secondary_text_are_used(): void {
		$admin = $this->make_admin(
			[
				'label'             => 'Black Friday',
				'headline_free'     => '40% off Pro yearly and lifetime plans',
				'secondary_free'    => 'Also: <a href="https://translatewp.ai/black-friday/" target="_blank" rel="noopener noreferrer">TranslateWP</a> 50% off<script>alert(1)</script>',
				'action_label_free' => 'Claim 40% off',
			]
		);

		$promo = $admin->get_promo_banner( self::moment( self::DURING_BANNER ) );

		$this->assertSame( 'Black Friday', $promo['label'] );
		$this->assertSame( '40% off Pro yearly and lifetime plans', $promo['text'] );
		$this->assertSame(
			'Also: <a href="https://translatewp.ai/black-friday/" target="_blank" rel="noopener noreferrer">TranslateWP</a> 50% offalert(1)',
			$promo['secondary']
		);
		$this->assertSame( 'Claim 40% off', $promo['actionLabel'] );
	}

	/**
	 * Feed text in the promo banner keeps only https links and basic emphasis, and its links open safely in a new tab.
	 *
	 * @return void
	 */
	public function test_feed_text_keeps_only_links_and_basic_emphasis(): void {
		$admin = $this->make_admin(
			[
				'headline_free'  => 'Deal <img src="https://example.com/pixel.gif"> <strong>40%</strong> <em>off</em><br>' .
				                    '<a href="javascript:alert(1)" onmouseover="alert(2)" style="position:fixed">Script</a> ' .
				                    '<span style="font-size:80px">Big</span>',
				'secondary_free' => '<iframe src="https://example.com/frame"></iframe>' .
				                    '<a href="https://translatewp.ai/" target="_self" rel="opener" class="x">TranslateWP</a>' .
				                    '<a href="data:text/html,hi">Data</a><svg onload="alert(3)"></svg>',
			]
		);

		$promo = $admin->get_promo_banner( self::moment( self::DURING_BANNER ) );

		$this->assertSame(
			'Deal  <strong>40%</strong> <em>off</em><br><a href="alert(1)" target="_blank" rel="noopener noreferrer">Script</a> Big',
			$promo['text']
		);
		$this->assertSame(
			'<a href="https://translatewp.ai/" target="_blank" rel="noopener noreferrer">TranslateWP</a>' .
			'<a href="text/html,hi" target="_blank" rel="noopener noreferrer">Data</a>',
			$promo['secondary']
		);
	}

	/**
	 * A licensed site is sent to the offer as an existing customer.
	 *
	 * @return void
	 */
	public function test_licensed_site_is_sent_to_the_offer_as_an_existing_customer(): void {
		$licensing = code_snippets()->licensing;

		code_snippets()->licensing = new class() extends Licensing {

			/**
			 * Report an active license.
			 *
			 * @return bool
			 */
			public function is_licensed(): bool {
				return true;
			}
		};

		try {
			$promo = $this->make_admin()->get_promo_banner( self::moment( self::DURING_BANNER ) );
		} finally {
			code_snippets()->licensing = $licensing;
		}

		$this->assertSame( 'https://codesnippets.pro/pricing/?cs_user=existing&plugin=cs_core', $promo['actionUrl'] );
	}

	/**
	 * A feed banner without an action URL offers no deal button.
	 *
	 * @return void
	 */
	public function test_feed_banner_without_action_url_has_no_button_link(): void {
		$promo = $this->make_admin( [ 'action_url_free' => '' ] )->get_promo_banner( self::moment( self::DURING_BANNER ) );

		$this->assertSame( '', $promo['actionUrl'] );
	}

	/**
	 * The promotional banner is not printed as a standard notice.
	 *
	 * @return void
	 */
	public function test_promo_banner_is_not_printed_as_a_notice(): void {
		$admin = $this->make_admin(
            [
				'start_datetime' => null,
				'end_datetime'   => null,
			]
        );

		$this->assertSame( '', $this->capture_notices( $admin ) );
	}

	/**
	 * The feed's start and end dates decide when the banner is shown.
	 *
	 * @return void
	 */
	public function test_feed_dates_limit_the_promo_banner(): void {
		$admin = $this->make_admin();

		$this->assertNull( $admin->get_promo_banner( self::moment( '2026-11-22T23:59:59+00:00' ) ) );
		$this->assertIsArray( $admin->get_promo_banner( self::moment( '2026-11-23T00:00:00+00:00' ) ) );
		$this->assertIsArray( $admin->get_promo_banner( self::moment( '2026-12-01T00:00:00+00:00' ) ) );
		$this->assertNull( $admin->get_promo_banner( self::moment( '2026-12-01T00:00:01+00:00' ) ) );
	}

	/**
	 * A feed banner without dates is shown with no countdown.
	 *
	 * @return void
	 */
	public function test_feed_banner_without_dates_has_no_countdown(): void {
		$admin = $this->make_admin(
            [
				'start_datetime' => null,
				'end_datetime'   => null,
			]
        );

		$promo = $admin->get_promo_banner( self::moment( self::DURING_BANNER ) );

		$this->assertIsArray( $promo );
		$this->assertNull( $promo['endsAt'] );
	}

	/**
	 * Without a feed banner, the survey notice keeps the standard notice markup.
	 *
	 * @return void
	 */
	public function test_survey_notice_keeps_the_standard_markup(): void {
		$admin = $this->make_admin( null );

		$this->assertNull( $admin->get_promo_banner( self::moment( self::DURING_BANNER ) ) );

		$output = $this->capture_notices( $admin );

		$this->assertStringContainsString( 'notice notice-info code-snippets-notice code-snippets-survey-notice', $output );
		$this->assertStringContainsString( 'class="notice-dismiss"', $output );
	}

	/**
	 * A dismissed feed banner is no longer offered, and the survey notice takes its place.
	 *
	 * @return void
	 */
	public function test_dismissed_banner_falls_back_to_the_survey(): void {
		add_user_meta( self::get_user_id(), self::DISMISSED_META_KEY, 'bf2026' );
		$admin = $this->make_admin();

		$this->assertNull( $admin->get_promo_banner( self::moment( self::DURING_BANNER ) ) );
		$this->assertSame( 'survey', $admin->get_active_notice( self::moment( self::DURING_BANNER ) )['key'] );
	}

	/**
	 * The hide filter suppresses the promotional banner as well as the standard notices.
	 *
	 * @return void
	 */
	public function test_hide_filter_suppresses_every_notice(): void {
		add_filter( 'code_snippets/hide_welcome_banner', '__return_true' );
		$admin = $this->make_admin();

		$this->assertNull( $admin->get_promo_banner( self::moment( self::DURING_BANNER ) ) );
		$this->assertSame( '', $this->capture_notices( $admin ) );
	}

	/**
	 * Following a dismissal link records the notice and redirects without the dismissal parameters.
	 *
	 * @return void
	 */
	public function test_dismissal_is_recorded_and_redirects_without_parameters(): void {
		$admin = $this->make_admin();
		$nonce = wp_create_nonce( self::DISMISSED_META_KEY );

		$_SERVER['REQUEST_URI'] = '/wp-admin/admin.php?page=snippets&status=active&' . self::DISMISSED_META_KEY . "=bf2026&_wpnonce=$nonce";
		$_GET[ self::DISMISSED_META_KEY ] = 'bf2026';
		$_REQUEST['_wpnonce'] = $nonce;

		add_filter(
			'wp_redirect',
			static function ( $location ) {
				throw new RuntimeException( esc_url_raw( $location ) );
			}
		);

		try {
			$admin->handle_notice_dismissal();
			$this->fail( 'Expected a redirect.' );
		} catch ( RuntimeException $redirect ) {
			$this->assertSame( '/wp-admin/admin.php?page=snippets&status=active', $redirect->getMessage() );
		}

		$this->assertContains( 'bf2026', get_user_meta( self::get_user_id(), self::DISMISSED_META_KEY, false ) );
		$this->assertNull( $admin->get_promo_banner( self::moment( self::DURING_BANNER ) ) );
	}

	/**
	 * A dismissal link with an invalid nonce is ignored.
	 *
	 * @return void
	 */
	public function test_dismissal_with_invalid_nonce_is_ignored(): void {
		$admin = $this->make_admin();

		$_GET[ self::DISMISSED_META_KEY ] = 'bf2026';
		$_REQUEST['_wpnonce'] = 'invalid';

		$admin->handle_notice_dismissal();

		$this->assertEmpty( get_user_meta( self::get_user_id(), self::DISMISSED_META_KEY, false ) );
		$this->assertIsArray( $admin->get_promo_banner( self::moment( self::DURING_BANNER ) ) );
	}

	/**
	 * A feed banner without any text is not shown, and the survey notice is offered instead.
	 *
	 * @return void
	 */
	public function test_feed_banner_without_text_is_not_shown(): void {
		foreach ( [ '', '   ', '<strong> </strong>' ] as $text ) {
			$admin = $this->make_admin(
				[
					'text_free'     => $text,
					'headline_free' => '',
				]
			);

			$this->assertNull( $admin->get_promo_banner( self::moment( self::DURING_BANNER ) ), "Text: '$text'" );
			$this->assertSame( 'survey', $admin->get_active_notice( self::moment( self::DURING_BANNER ) )['key'], "Text: '$text'" );
		}
	}
}
