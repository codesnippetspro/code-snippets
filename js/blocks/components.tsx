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
	value?: SnippetSelectOption
	setAttributes: (attributes: { snippet_id: number }) => void
}

export const SnippetSelect: React.FC<SnippetSelectProps> = ({ options, value, setAttributes }) =>
	<Select
		name="snippet-select"
		className="code-snippets-large-select"
		options={options}
		value={value}
		onChange={option => setAttributes({ snippet_id: option && 'value' in option ? option.value : 0 })}
		placeholder={__('Select a snippet to insert…', 'code-snippets')}
	/>


export interface ResetButtonProps {
	onClick: () => void
}

export const ResetButton: React.FC<ResetButtonProps> = ({ onClick }) =>
	<BlockControls>
		<MenuItem icon="image-rotate" title={__('Choose a different snippet', 'code-snippets')} onClick={onClick} />
	</BlockControls>;
