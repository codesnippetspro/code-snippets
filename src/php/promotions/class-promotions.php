<?php
namespace Code_Snippets;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Promotions manager class.
 */
class Promotions {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		new Promotions\Elementor();
	}
}
