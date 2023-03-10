<?php

namespace Code_Snippets\Elementor;

use Elementor\Elements_Manager;
use Elementor\Plugin as Elementor_Plugin;

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
		add_action( 'elementor/widgets/register', [ $this, 'init_widgets' ] );
		add_action( 'elementor/elements/categories_registered', [ $this, 'add_widget_category' ] );
		add_action( 'elementor/controls/controls_registered', [ $this, 'init_controls' ] );
	}

	/**
	 * Add a new widget category for this plugin.
	 *
	 * @param Elements_Manager $elements_manager Elements manager.
	 */
	public function add_widget_category( $elements_manager ) {

		$elements_manager->add_category(
			'code-snippets',
			[ 'title' => __( 'Code Snippets', 'code-snippets' ) ]
		);
	}

	/**
	 * Register new widget types with Elementor.
	 *
	 * @return void
	 */
	public function init_widgets() {
		$widgets_manager = Elementor_Plugin::instance()->widgets_manager;

		$widgets_manager->register( new Content_Widget() );
		$widgets_manager->register( new Source_Widget() );
	}

	/**
	 * Initialize widget controls.
	 *
	 * @return void
	 */
	public function init_controls() {
		$controls_manager = Elementor_Plugin::instance()->controls_manager;

		$controls_manager->register( new Control_Select() );
	}
}
