<?php

namespace Code_Snippets;

use Elementor\Control_Select2;
use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use Exception;

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
			'label'       => __( 'Snippet', 'code-snippets' ),
			'type'        => 'code-snippets-select2',
			'options'     => $this->build_snippet_options(),
			'default'     => 0,
			'show_label'  => false,
			'label_block' => true,
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

/**
 * Widget for embedding a content snippet.
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
