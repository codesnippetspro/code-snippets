<?php

namespace Code_Snippets\Admin\Menus;

use Code_Snippets\Admin\Whats_New_Badge;
use Code_Snippets\Client\Welcome_Client;
use function Code_Snippets\code_snippets;
use const Code_Snippets\PLUGIN_FILE;
use const Code_Snippets\PLUGIN_VERSION;

/**
 * This class handles the welcome menu.
 *
 * @since   3.7.0
 * @package Code_Snippets
 */
class Welcome_Menu extends Admin_Menu {

	/**
	 * Client instance.
	 *
	 * @var Welcome_Client
	 */
	protected Welcome_Client $client;

	/**
	 * Class constructor
	 *
	 * @param Welcome_Client $client Client instance.
	 */
	public function __construct( $client ) {
		parent::__construct(
			'welcome',
			_x( "What's New", 'menu label', 'code-snippets' ),
			__( 'Resources and Updates', 'code-snippets' )
		);

		$this->client = $client;
	}

	/**
	 * Load the welcome menu.
	 *
	 * @return void
	 */
	public function load() {
		parent::load();
		Whats_New_Badge::mark_seen_release();
	}

	/**
	 * Render the welcome menu.
	 *
	 * @return void
	 */
	public function render() {
		echo '<div id="code-snippets-welcome-container" class="wrap"></div>';
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
				'banner'    => $this->client->get_banner(),
				'hero'      => $this->client->get_hero_item(),
				'changelog' => $this->client->get_changelog(),
				'features'  => $this->client->get_features(),
				'partners'  => $this->client->get_partners(),
			]
		);
	}
}
