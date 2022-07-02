import React from 'react';
import { __ } from '@wordpress/i18n';
import { BlockControls } from '@wordpress/block-editor';
import { MenuItem } from '@wordpress/components';
import Select, { OptionsOrGroups } from 'react-select';

export interface SnippetSelectOption {
	value: number
	label: string
}

export interface SnippetSelectGroup {
	label: string
	options: SnippetSelectOption[]
}

export interface SnippetSelectProps {
	options: OptionsOrGroups<SnippetSelectOption, SnippetSelectGroup>
	attributes: {
		snippet_id: number
	}
	setAttributes: (attributes: SnippetSelectProps['attributes']) => void
}

const getSelectOption = (optionOrGroup: SnippetSelectOption | SnippetSelectGroup): SnippetSelectOption =>
	'options' in optionOrGroup ? getSelectOption(optionOrGroup) : optionOrGroup

export const SnippetSelect: React.FC<SnippetSelectProps> = ({ options, attributes, setAttributes }) =>
	<Select
		name="snippet-select"
		className="code-snippets-large-select"
		options={options}
		value={options.find(option => getSelectOption(option).value === attributes.snippet_id)}
		placeholder={__('Select a snippet to insert…', 'code-snippets')}
		onChange={option => setAttributes({ snippet_id: option ? getSelectOption(option).value : 0 })} />


export interface ResetButtonProps {
	onClick: () => void
}

export const ResetButton: React.FC<ResetButtonProps> = ({ onClick }) =>
	<BlockControls>
		<MenuItem icon="image-rotate" title={__('Choose a different snippet', 'code-snippets')} onClick={onClick} />
	</BlockControls>;
