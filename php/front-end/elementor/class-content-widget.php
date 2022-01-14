<?php

namespace Code_Snippets;

use Elementor\Control_Select2;
use Elementor\Controls_Manager;
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Typography;

/**
 * Widget for embedding a content snippet.
 *
 * Parts of this class are derivative work of the code from Elementor,
 * and as such are (C) 2016-2021 Elementor Ltd and licensed under GPLv2 or later.
 *
 * @package Code_Snippets
 */
class Elementor_Content_Widget extends Elementor_Widget {

	/**
	 * Return the widget name.
	 */
	public function get_name() {
		return 'code-snippets-content';
	}

	/**
	 * Return the widget title.
	 * @return string
	 */
	public function get_title() {
		return __( 'Content Snippet', 'code-snippets' );
	}

	/**
	 * Return the widget icon.
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-shortcode';
	}

	/**
	 * Build a list of snippets for the drop-down menu.
	 * @return array
	 */
	protected function build_snippet_options() {
		$snippets = get_snippets();
		$options = [];

		/** @var Snippet $snippet */
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
	protected function _register_controls() {

		$this->start_controls_section( 'snippet', [
			'label' => __( 'Snippet', 'code-snippets' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'snippet_id', [
			'label'       => __( 'Snippet', 'code-snippets' ),
			'type'        => Controls_Manager::SELECT2,
			'options'     => $this->build_snippet_options(),
			'default'     => 0,
			'show_label'  => false,
			'label_block' => true,
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'display_options', [
			'label' => __( 'Processing Options', 'code-snippets' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$switchers = [
			'php'        => __( 'Run PHP code', 'code-snippets' ),
			'format'     => __( 'Add paragraphs and formatting', 'code-snippets' ),
			'shortcodes' => __( 'Enable embedded shortcodes', 'code-snippets' ),
		];

		foreach ( $switchers as $control_id => $control_label ) {
			$this->add_control( $control_id, [
				'label'   => $control_label,
				'type'    => Controls_Manager::SWITCHER,
				'default' => false,
			] );
		}

		$this->end_controls_section();

		$this->start_controls_section( 'snippet_style', [
			'label' => esc_html__( 'Snippet', 'code-snippets' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_responsive_control( 'snippet_text_align', [
			'label'     => esc_html__( 'Alignment', 'code-snippets' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => [
				'left'    => [
					'title' => esc_html__( 'Left', 'code-snippets' ),
					'icon'  => 'eicon-text-align-left',
				],
				'center'  => [
					'title' => esc_html__( 'Center', 'code-snippets' ),
					'icon'  => 'eicon-text-align-center',
				],
				'right'   => [
					'title' => esc_html__( 'Right', 'code-snippets' ),
					'icon'  => 'eicon-text-align-right',
				],
				'justify' => [
					'title' => esc_html__( 'Justified', 'code-snippets' ),
					'icon'  => 'eicon-text-align-justify',
				],
			],
			'selectors' => [ '{{WRAPPER}}' => 'text-align: {{VALUE}};' ],
		] );

		$this->add_control( 'snippet_text_color', [
			'label'     => esc_html__( 'Text Color', 'code-snippets' ),
			'type'      => Controls_Manager::COLOR,
			'global'    => [ 'default' => Global_Colors::COLOR_PRIMARY, ],
			'selectors' => [ '{{WRAPPER}}' => 'color: {{VALUE}};', ],
		] );

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name'     => 'typography',
			'global'   => [ 'default' => Global_Typography::TYPOGRAPHY_PRIMARY ],
			'selector' => '{{WRAPPER}}',
		] );

		$this->add_group_control( Group_Control_Text_Shadow::get_type(), [
			'name'     => 'text_shadow',
			'selector' => '{{WRAPPER}}',
		] );

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
			echo code_snippets()->frontend->render_content_shortcode( $settings );
		}
	}
}

/**
 * Modified version of the Elementor Select2 control which supports multiple groups.
 * @package Code_Snippets
 */
class Elementor_Select2_Control extends Control_Select2 {

	/**
	 * Retrieve the control type.
	 *
	 * @return string Control type.
	 */
	public function get_type() {
		return 'code-snippets-select2';
	}

	/**
	 * Render select2 control output in the editor.
	 *
	 * Used to generate the control HTML in the editor using Underscore JS
	 * template. The variables for the class are available using `data` JS
	 * object.
	 */
	public function content_template() {
		$control_uid = $this->get_control_uid();

		echo '<div class="elementor-control-field">
		<# if (data.label) {#>
			<label for="' . $control_uid . '" class="elementor-control-title">{{{ data.label }}}</label>
		<# } #>
		<div class="elementor-control-input-wrapper elementor-control-unit-5">
			<# var multiple = data.multiple ? "multiple" : ""; #>
			<select id="' . $control_uid . '" class="elementor-select2" type="select2" {{ multiple }} data-setting="{{ data.name }}">
				<# _.each(data.options, function(group_options, group_title) { #>
				<optgroup label="{{ group_title }}">
					<# _.each(group_options, function(option_title, option_value) {
					var value = data.controlValue;
					if (typeof value == "string") {
						var selected = (option_value === value) ? "selected" : "";
					} else if (null !== value) {
						var value = _.values(value);
						var selected = (-1 !== value.indexOf(option_value)) ? "selected" : "";
					}
					#>
					<option {{ selected }} value="{{ option_value }}">{{{ option_title }}}</option>
					<# }); #>
				</optgroup>
				<# }); #>
			</select>
			</div>
		</div>
		<# if ( data.description ) { #>
			<div class="elementor-control-field-description">{{{ data.description }}}</div>
		<# } #>';
	}
}
