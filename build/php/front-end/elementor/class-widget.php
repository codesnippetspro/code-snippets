<?php

namespace Code_Snippets\Elementor;

use Elementor\Widget_Base;

/**
 * Base class for building Elementor widgets.
 *
 * @package Code_Snippets
 */
abstract class Widget extends Widget_Base {

	/**
	 * Return the section this widget belongs to.
	 *
	 * @return array
	 */
	public function get_categories() {
		return [ 'code-snippets' ];
	}

	/**
	 * Return the widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-code';
	}
}
