<?php

namespace Code_Snippets;

use Elementor\Controls_Manager;
use Elementor\Elements_Manager;
use Elementor\Widgets_Manager;

/**
 * Handles integration with the Elementor website builder plugin.
 *
 * @package Code_Snippets
 */
class Elementor {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'elementor/elements/categories_registered', [ $this, 'add_widget_category' ] );
		add_action( 'elementor/widgets/widgets_registered', [ $this, 'init_widgets' ] );
		add_action( 'elementor/controls/controls_registered', [ $this, 'init_controls' ] );
	}

	/**
	 * Add a new widget category for this plugin.
	 *
	 * @param Elements_Manager $elements_manager
	 */
	public function add_widget_category( $elements_manager ) {

		$elements_manager->add_category(
			'code-snippets',
			[ 'title' => __( 'Code Snippets', 'code-snippets' ) ]
		);
	}

	/**
	 * Register new widget types with Elementor.
	 */
	public function init_widgets() {
		/** @var Widgets_Manager $widgets_manager */
		$widgets_manager = \Elementor\Plugin::instance()->widgets_manager;

		$widgets_manager->register_widget_type( new Elementor_Content_Widget() );
		$widgets_manager->register_widget_type( new Elementor_Source_Widget() );
	}

	public function init_controls() {
		/** @var Controls_Manager $controls_manager */
		$controls_manager = \Elementor\Plugin::instance()->controls_manager;

		$controls_manager->register_control( 'code-snippets-select2', new Elementor_Select2_Control() );
	}
}
