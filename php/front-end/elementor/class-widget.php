<?php

namespace Code_Snippets;

use Elementor\Widget_Base;

/**
 * Base class for building Elementor widgets.
 * @package Code_Snippets
 */
abstract class Elementor_Widget extends Widget_Base {

	/**
	 * Return the section this widget belongs to.
	 * @return array
	 */
	public function get_categories() {
		return [ 'code-snippets' ];
	}

	/**
	 * Return the widget icon class.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'fas fa-cut';
	}
}
