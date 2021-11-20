<?php

namespace Code_Snippets;

use Elementor\Controls_Manager;
use Exception;

/**
 * Widget for embedding the source code of a snippet.
 *
 * @package Code_Snippets
 */
class Elementor_Source_Widget extends Elementor_Widget {

	/**
	 * Whether the Prism source code highlighter is enabled.
	 * @var bool
	 */
	private $prism_enabled = false;

	/**
	 * Class constructor.
	 *
	 * @param array      $data Widget data. Default is an empty array.
	 * @param array|null $args Optional. Widget default arguments. Default is null.
	 *
	 * @throws Exception If arguments are missing when initializing a full widget instance.
	 */
	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );

		if ( ! Settings\get_setting( 'general', 'disable_prism' ) ) {
			$this->prism_enabled = true;
			Frontend::register_prism_assets();

			wp_register_script(
				'code-snippets-elementor',
				plugins_url( 'js/min/elementor.js', code_snippets()->file ),
				[ 'elementor-frontend', Frontend::PRISM_HANDLE ],
				code_snippets()->version, true
			);
		}
	}

	/**
	 * Retrieve the list of widget script dependencies.
	 * @return array
	 */
	public function get_script_depends() {
		return $this->prism_enabled ? [ 'code-snippets-elementor' ] : [];
	}

	/**
	 * Retrieve the list of widget style dependencies.
	 * @return array
	 */
	public function get_style_depends() {
		return $this->prism_enabled ? [ Frontend::PRISM_HANDLE ] : [];
	}

	/**
	 * Return the widget name.
	 */
	public function get_name() {
		return 'code-snippets-source';
	}

	/**
	 * Return the widget title.
	 * @return string
	 */
	public function get_title() {
		return __( 'Code Snippet Source', 'code-snippets' );
	}

	/**
	 * Build a list of snippets for the drop-down menu.
	 * @return array
	 */
	protected function build_snippet_options() {
		$snippets = get_snippets();
		$options = [];

		$labels = [
			'php'  => __( 'Functions (PHP)', 'code-snippets' ),
			'html' => __( 'Content (Mixed)', 'code-snippets' ),
			'css'  => __( 'Styles (CSS)', 'code-snippets' ),
			'js'   => __( 'Scripts (JS)', 'code-snippets' ),
		];

		/** @var Snippet $snippet */
		foreach ( $snippets as $snippet ) {
			$group = $labels[ $snippet->type ];
			if ( ! isset( $options[ $group ] ) ) {
				$options[ $group ] = [];
			}

			$options[ $group ][ $snippet->id ] = $snippet->name;
		}

		return $options;
	}

	/**
	 * Register settings controls.
	 */
	protected function _register_controls() {

		$this->start_controls_section( 'snippet', [
			'label' => __( 'Snippet', 'code-snippets' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'snippet_id', [
			'label'       => esc_html__( 'Snippet', 'code-snippets' ),
			'type'        => 'code-snippets-select2',
			'options'     => $this->build_snippet_options(),
			'default'     => 0,
			'show_label'  => false,
			'label_block' => true,
		] );

		$this->add_control( 'line_numbers', [
			'label'        => esc_html__( 'Line Numbers', 'code-snippets' ),
			'type'         => Controls_Manager::SWITCHER,
			'default'      => false,
		] );

		$this->end_controls_section();
	}

	/**
	 * Render the widget content.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		if ( ! isset( $settings['snippet_id'] ) || 0 === intval( $settings['snippet_id'] ) ) {
			echo '<p>', esc_html__( 'Select a snippet to display', 'code-snippets' ), '</p>';
		} else {
			echo code_snippets()->frontend->render_source_shortcode( $settings );
		}
	}
}