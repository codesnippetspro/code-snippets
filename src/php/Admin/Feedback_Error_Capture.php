<?php

namespace Code_Snippets\Admin;

use const Code_Snippets\PLUGIN_FILE;
use const Code_Snippets\PLUGIN_VERSION;

/**
 * Collects JavaScript errors thrown on a Code Snippets screen, ready to attach to a report.
 *
 * The panel itself is a React application in the page footer and cannot see anything that
 * failed before it mounted, so the listeners are enqueued in the document head instead.
 * Errors thrown by scripts printed above this one are still missed.
 *
 * @package Code_Snippets
 */
class Feedback_Error_Capture {

	/**
	 * Script handle.
	 */
	private const SCRIPT_HANDLE = 'code-snippets-feedback-capture';

	/**
	 * Panel deciding whether the reporter belongs on this request.
	 *
	 * @var Feedback_Panel
	 */
	private Feedback_Panel $panel;

	/**
	 * Class constructor.
	 *
	 * @param Feedback_Panel $panel Panel deciding whether the reporter belongs on this request.
	 */
	public function __construct( Feedback_Panel $panel ) {
		$this->panel = $panel;

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ], 0 );
	}

	/**
	 * Enqueue the error listeners in the document head.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		if ( ! $this->panel->should_render() ) {
			return;
		}

		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			plugins_url( 'dist/feedback-capture.js', PLUGIN_FILE ),
			[],
			PLUGIN_VERSION,
			false
		);
	}
}
