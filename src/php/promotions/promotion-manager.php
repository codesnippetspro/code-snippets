<?php
namespace Code_Snippets;

class Promotion_Manager {

	private $plugin_promotions = [];

	private function init_plugin_promotions() {
		$this->plugin_promotions = [
			// Notices in admin screens.
			'elementor' => Promotions\Elementor::class,
			'insert-headers-and-footers' => Promotions\Insert_Headers_And_Footers::class,
			'header-footer-code-manager' => Promotions\Header_Footer_Code_Manager::class,
			'insert-php-code-snippet' => Promotions\Insert_PHP_Code_Snippet::class,

			// Other Promotions.
			'elementor-editor' => Promotions\Elementor_Editor::class,
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
