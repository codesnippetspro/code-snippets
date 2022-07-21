<?php

namespace Code_Snippets\Elementor;

use Code_Snippets\Snippet;
use Elementor\Controls_Manager;
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Typography;
use function Code_Snippets\code_snippets;
use function Code_Snippets\get_snippets;

/**
 * Widget for embedding a content snippet.
 *
 * Parts of this class are derivative work of the code from Elementor,
 * and as such are (C) 2016-2021 Elementor Ltd and licensed under GPLv2 or later.
 *
 * @package Code_Snippets
 */
class Content_Widget extends Widget {

	/**
	 * Return the widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'code-snippets-content';
	}

	/**
	 * Return the widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Content Snippet', 'code-snippets' );
	}

	/**
	 * Return the widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-shortcode';
	}

	/**
	 * Build a list of snippets for the drop-down menu.
	 *
	 * @return array
	 */
	protected function build_snippet_options() {
		$snippets = get_snippets();
		$options = [];

		/**
		 * Snippet object.
		 *
		 * @var Snippet $snippet
		 */
		foreach ( $snippets as $snippet ) {
			if ( 'html' === $snippet->type ) {
				$options[ $snippet->id ] = $snippet->name;
			}
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
				'label'       => __( 'Snippet', 'code-snippets' ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => $this->build_snippet_options(),
				'default'     => 0,
				'show_label'  => false,
				'label_block' => true,
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'display_options',
			array(
				'label' => __( 'Processing Options', 'code-snippets' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$switchers = [
			'php'        => __( 'Run PHP code', 'code-snippets' ),
			'format'     => __( 'Add paragraphs and formatting', 'code-snippets' ),
			'shortcodes' => __( 'Enable embedded shortcodes', 'code-snippets' ),
		];

		foreach ( $switchers as $control_id => $control_label ) {
			$this->add_control(
				$control_id,
				array(
					'label'   => $control_label,
					'type'    => Controls_Manager::SWITCHER,
					'default' => false,
				)
			);
		}

		$this->end_controls_section();

		$this->start_controls_section(
			'snippet_style',
			array(
				'label' => esc_html__( 'Snippet', 'code-snippets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'snippet_text_align',
			array(
				'label'     => esc_html__( 'Alignment', 'code-snippets' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'left'    => array(
						'title' => esc_html__( 'Left', 'code-snippets' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center'  => array(
						'title' => esc_html__( 'Center', 'code-snippets' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'   => array(
						'title' => esc_html__( 'Right', 'code-snippets' ),
						'icon'  => 'eicon-text-align-right',
					),
					'justify' => array(
						'title' => esc_html__( 'Justified', 'code-snippets' ),
						'icon'  => 'eicon-text-align-justify',
					),
				),
				'selectors' => array( '{{WRAPPER}}' => 'text-align: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'snippet_text_color',
			array(
				'label'     => esc_html__( 'Text Color', 'code-snippets' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => [ 'default' => Global_Colors::COLOR_PRIMARY ],
				'selectors' => [ '{{WRAPPER}}' => 'color: {{VALUE}};' ],
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography',
				'global'   => [ 'default' => Global_Typography::TYPOGRAPHY_PRIMARY ],
				'selector' => '{{WRAPPER}}',
			)
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			array(
				'name'     => 'text_shadow',
				'selector' => '{{WRAPPER}}',
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
			echo '<p>', esc_html__( 'Select a snippet to show', 'code-snippets' ), '</p>';
		} else {
			$settings['debug'] = is_admin();
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
			echo code_snippets()->frontend->render_content_shortcode( $settings );
		}
	}
}
