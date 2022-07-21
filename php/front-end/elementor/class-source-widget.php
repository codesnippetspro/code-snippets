<?php

namespace Code_Snippets\Elementor;

use Code_Snippets\Frontend;
use Code_Snippets\Snippet;
use Elementor\Controls_Manager;
use Exception;
use function Code_Snippets\code_snippets;
use function Code_Snippets\get_snippets;
use function Code_Snippets\Settings\get_setting;

/**
 * Widget for embedding the source code of a snippet.
 *
 * Parts of this class are derivative work of the code from Elementor,
 * and as such are (C) 2016-2021 Elementor Ltd and licensed under GPLv2 or later.
 *
 * @package Code_Snippets
 */
class Source_Widget extends Widget {

	private $snippets;

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
		$this->load_prism_dependencies( $data );

		$this->snippets = get_snippets();
	}

	/**
	 * Load necessary dependencies for the PrismJS library.
	 *
	 * @param array $data Widget data.
	 *
	 * @return void
	 */
	private function load_prism_dependencies( $data ) {
		if ( get_setting( 'general', 'disable_prism' ) ) {
			return;
		}

		Frontend::register_prism_assets();
		$handle = 'code-snippets-elementor';

		wp_register_script(
			$handle,
			plugins_url( 'dist/elementor.js', code_snippets()->file ),
			[ 'elementor-frontend', Frontend::PRISM_HANDLE ],
			code_snippets()->version,
			true
		);

		$this->add_script_depends( $handle );

		if ( ! empty( $data['settings']['theme'] ) && 'default' !== $data['settings']['theme'] ) {
			$this->add_style_depends( Frontend::get_prism_theme_style_handle( $data['settings']['theme'] ) );
		}

		$this->add_style_depends( Frontend::PRISM_HANDLE );

		add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'enqueue_all_prism_themes' ) );
	}

	/**
	 * Enqueue all available Prism themes.
	 *
	 * @return void
	 */
	public function enqueue_all_prism_themes() {
		foreach ( Frontend::get_prism_themes() as $theme => $label ) {
			wp_enqueue_style( Frontend::get_prism_theme_style_handle( $theme ) );
		}

		wp_enqueue_style( Frontend::PRISM_HANDLE );
	}

	/**
	 * Return the widget name.
	 */
	public function get_name() {
		return 'code-snippets-source';
	}

	/**
	 * Return the widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Code Snippet Source', 'code-snippets' );
	}

	/**
	 * Build a list of snippets for the drop-down menu.
	 *
	 * @return array
	 */
	protected function build_snippet_options() {
		$options = [];

		$labels = [
			'php'  => __( 'Functions (PHP)', 'code-snippets' ),
			'html' => __( 'Content (Mixed)', 'code-snippets' ),
			'css'  => __( 'Styles (CSS)', 'code-snippets' ),
			'js'   => __( 'Scripts (JS)', 'code-snippets' ),
		];

		/**
		 * Snippet object.
		 *
		 * @var Snippet $snippet
		 */
		foreach ( $this->snippets as $snippet ) {
			$group = $labels[ $snippet->type ];
			if ( ! isset( $options[ $group ] ) ) {
				$options[ $group ] = [];
			}

			$options[ $group ][ $snippet->id ] = $snippet->display_name;
		}

		return $options;
	}

	/**
	 * Register settings controls.
	 */
	protected function register_controls() {

		$this->start_controls_section(
			'snippet',
			array(
				'label' => __( 'Snippet', 'code-snippets' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'snippet_id',
			array(
				'label'       => esc_html__( 'Snippet', 'code-snippets' ),
				'type'        => Control_Select::CONTROL_TYPE,
				'options'     => $this->build_snippet_options(),
				'default'     => 0,
				'show_label'  => false,
				'label_block' => true,
			)
		);

		$this->add_control(
			'line_numbers',
			array(
				'label'   => esc_html__( 'Line Numbers', 'code-snippets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => false,
			)
		);

		$this->add_control(
			'highlight_lines',
			array(
				'label'       => esc_html__( 'Highlight Lines', 'code-snippets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => '1, 3-6',
			)
		);

		$this->add_control(
			'word_wrap',
			array(
				'label'        => esc_html__( 'Word Wrap', 'code-snippets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'code-snippets' ),
				'label_off'    => esc_html__( 'Off', 'code-snippets' ),
				'return_value' => 'word-wrap',
				'default'      => '',
			)
		);

		$this->add_control(
			'theme',
			array(
				'label'              => esc_html__( 'Theme', 'code-snippets' ),
				'type'               => Controls_Manager::SELECT,
				'default'            => 'default',
				'options'            => array_merge(
					[ 'default' => __( 'Default', 'code-snippets' ) ],
					Frontend::get_prism_themes()
				),
				'separator'          => 'before',
				'frontend_available' => true,
			)
		);

		$this->add_responsive_control(
			'height',
			array(
				'label'      => esc_html__( 'Height', 'code-snippets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'vh', 'em' ],
				'range'      => array(
					'px' => [
						'min' => 115,
						'max' => 1000,
					],
					'em' => [
						'min' => 6,
						'max' => 50,
					],
				),
				'selectors'  => [ '{{WRAPPER}} pre' => 'height: {{SIZE}}{{UNIT}};' ],
				'separator'  => 'before',
			)
		);

		$this->add_responsive_control(
			'font_size',
			array(
				'label'      => esc_html__( 'Font Size', 'code-snippets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'vw' ],
				'range'      => array(
					'px' => [
						'min' => 1,
						'max' => 200,
					],
					'vw' => [
						'min'  => 0.1,
						'max'  => 10,
						'step' => 0.1,
					],
				),
				'responsive' => true,
				'selectors'  => [ '{{WRAPPER}} pre, {{WRAPPER}} code, {{WRAPPER}} .line-numbers .line-numbers-rows' => 'font-size: {{SIZE}}{{UNIT}};' ],
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render the widget content.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		if ( ! isset( $settings['snippet_id'] ) || 0 === intval( $settings['snippet_id'] ) ) {
			echo '<p>', esc_html__( 'Select a snippet to display', 'code-snippets' ), '</p>';
			return;
		}

		printf(
			'<div class="%s%s">%s</div>',
			esc_attr( $settings['word_wrap'] ),
			esc_attr( 'default' === $settings['theme'] ? '' : " is-style-prism-${settings['theme']}" ),
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
			code_snippets()->frontend->render_source_shortcode( $settings )
		);
	}
}
