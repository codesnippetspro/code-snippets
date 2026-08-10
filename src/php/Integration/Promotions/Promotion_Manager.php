<?php

namespace Code_Snippets\Integration\Promotions;

/**
 * Manager class to handle all plugin promotions.
 */
class Promotion_Manager {

	/**
	 * Constructor to initialize and display promotions.
	 */
	public function __construct() {
		new Notices\Elementor();
		new Notices\Header_Footer_Code();
		new Notices\Header_Footer_Code_Manager();
		new Notices\Insert_HTML_Snippet();
		new Notices\Insert_PHP_Code_Snippet();
		new Notices\Insert_PHP();
		new Notices\WP_Headers_And_Footers();
		new Other\Elementor_Editor();
	}
}
