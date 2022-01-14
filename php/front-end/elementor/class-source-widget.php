<?php

namespace Code_Snippets;

use Elementor\Controls_Manager;
use Exception;

/**
 * Widget for embedding the source code of a snippet.
 *
 * Parts of this class are derivative work of the code from Elementor,
 * and as such are (C) 2016-2021 Elementor Ltd and licensed under GPLv2 or later.
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
			'label'   => esc_html__( 'Line Numbers', 'code-snippets' ),
			'type'    => Controls_Manager::SWITCHER,
			'default' => false,
		] );

		$this->add_control( 'highlight_lines', [
			'label'       => esc_html__( 'Highlight Lines', 'code-snippets' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => '',
			'placeholder' => '1, 3-6',
		] );

		$this->add_control( 'word_wrap', [
			'label'        => esc_html__( 'Word Wrap', 'code-snippets' ),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => esc_html__( 'On', 'code-snippets' ),
			'label_off'    => esc_html__( 'Off', 'code-snippets' ),
			'return_value' => 'word-wrap',
			'default'      => '',
		] );

		$this->add_responsive_control( 'height', [
			'label'      => esc_html__( 'Height', 'code-snippets' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'vh', 'em' ],
			'range'      => [
				'px' => [ 'min' => 115, 'max' => 1000 ],
				'em' => [ 'min' => 6, 'max' => 50 ],
			],
			'selectors'  => [ '{{WRAPPER}} pre' => 'height: {{SIZE}}{{UNIT}};' ],
			'separator'  => 'before',
		] );

		$this->add_responsive_control( 'font_size', [
			'label'      => esc_html__( 'Font Size', 'code-snippets' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'em', 'rem', 'vw' ],
			'range'      => [
				'px' => [ 'min' => 1, 'max' => 200 ],
				'vw' => [ 'min' => 0.1, 'max' => 10, 'step' => 0.1 ],
			],
			'responsive' => true,
			'selectors'  => [ '{{WRAPPER}} pre, {{WRAPPER}} code, {{WRAPPER}} .line-numbers .line-numbers-rows' => 'font-size: {{SIZE}}{{UNIT}};' ],
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
			$classname = esc_attr( $settings['word_wrap'] );
			printf( '<div class="%s">%s</div>', $classname, code_snippets()->frontend->render_source_shortcode( $settings ) );
		}
	}
}