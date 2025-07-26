<?php

namespace Code_Snippets;

/**
 * This class handles the welcome menu.
 *
 * @since   3.7.0
 * @package Code_Snippets
 */
class Welcome_Menu extends Admin_Menu {

	/**
	 * Instance of Welcome_API class.
	 *
	 * @var Welcome_API
	 */
	protected Welcome_API $api;

	/**
	 * Class constructor
	 *
	 * @param Welcome_API $api Instance of API class.
	 */
	public function __construct( $api ) {
		parent::__construct(
			'welcome',
			_x( "What's New", 'menu label', 'code-snippets' ),
			__( 'Resources and Updates', 'code-snippets' )
		);

		$this->api = $api;
	}

	/**
	 * Load the welcome menu.
	 *
	 * @return void
	 */
	public function render() {
		echo '<div id="code-snippets-welcome-container"></div>';
	}

	/**
	 * Enqueue assets necessary for the welcome menu.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		$handle = 'code-snippets-welcome';

		wp_enqueue_style(
			$handle,
			plugins_url( 'dist/welcome.css', PLUGIN_FILE ),
			self::$style_deps,
			PLUGIN_VERSION
		);

		wp_enqueue_script(
			$handle,
			plugins_url( 'dist/welcome.js', PLUGIN_FILE ),
			self::$script_deps,
			PLUGIN_VERSION,
			true
		);

		code_snippets()->localize_script( $handle );

		wp_localize_script(
			$handle,
			'CODE_SNIPPETS_WELCOME',
			[
				'banner'    => $this->api->get_banner(),
				'hero'      => $this->api->get_hero_item(),
				'changelog' => $this->api->get_changelog(),
				'features'  => $this->api->get_features(),
				'partners'  => $this->api->get_partners(),
			]
		);
	}
}
