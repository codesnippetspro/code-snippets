<?php

namespace Code_Snippets\Elementor;

use Elementor\Control_Select2;

/**
 * Modified version of the Elementor Select2 control which supports multiple groups.
 *
 * @package Code_Snippets
 */
class Control_Select extends Control_Select2 {

	const CONTROL_TYPE = 'code-snippets-select';

	/**
	 * Retrieve the control type.
	 *
	 * @return string Control type.
	 */
	public function get_type(): string {
		return self::CONTROL_TYPE;
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
			<label for="' . esc_attr( $control_uid ) . '" class="elementor-control-title">{{{ data.label }}}</label>
		<# } #>
		<div class="elementor-control-input-wrapper elementor-control-unit-5">
			<# var multiple = data.multiple ? "multiple" : ""; #>
			<select id="' . esc_attr( $control_uid ) . '" class="elementor-select2" type="select2" {{ multiple }} data-setting="{{ data.name }}">
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
