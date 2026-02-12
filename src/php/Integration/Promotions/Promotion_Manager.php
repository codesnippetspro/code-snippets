<?php

namespace Code_Snippets\Integration\Promotions;

class Promotion_Manager {

	private array $plugin_promotions = [];

	private function init_plugin_promotions() {
		$this->plugin_promotions = [
			// Notices in admin screens.
			'elementor'                  => Notices\Elementor::class,
			'head-footer-code'           => Notices\Header_Footer_Code::class,
			'header-footer-code-manager' => Notices\Header_Footer_Code_Manager::class,
			'insert-html-snippet'        => Notices\Insert_HTML_Snippet::class,
			'insert-php-code-snippet'    => Notices\Insert_PHP_Code_Snippet::class,
			'insert-php'                 => Notices\Insert_PHP::class,
			'wp-headers-and-footers'     => Notices\WP_Headers_And_Footers::class,

			// Other Promotions.
			'elementor-editor'           => Other\Elementor_Editor::class,
		];
	}

	public function __construct() {
		if ( empty( $this->plugin_promotions ) ) {
			$this->init_plugin_promotions();
		}

		foreach ( $this->plugin_promotions as $plugin_slug => $promotion_class ) {
			new $promotion_class();
		}
	}
}
