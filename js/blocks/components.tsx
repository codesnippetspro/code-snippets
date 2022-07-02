import React from 'react';
import { __ } from '@wordpress/i18n';
import { BlockControls } from '@wordpress/block-editor';
import { MenuItem, Placeholder, Dashicon } from '@wordpress/components';
import Select, { OptionsOrGroups } from 'react-select';
import ServerSideRender from '@wordpress/server-side-render';

export interface SnippetSelectOption {
	value: number
	label: string
}

export interface SnippetSelectGroup {
	label: string
	options: SnippetSelectOption[]
}

export interface SnippetSelectorProps {
	block: string
	label: string
	className: string
	icon: Dashicon.Icon
	options: OptionsOrGroups<SnippetSelectOption, SnippetSelectGroup>
	attributes: { snippet_id: number }
	setAttributes: (attributes: SnippetSelectorProps['attributes']) => void
}

export const SnippetSelector: React.FC<SnippetSelectorProps> = ({ block, icon, label, className, attributes, setAttributes, options }) =>
	<>
		<BlockControls>
			<MenuItem
				icon="image-rotate"
				title={__('Choose a different snippet', 'code-snippets')}
				onClick={() => setAttributes({ snippet_id: 0 })}
			/>
		</BlockControls>

		{0 === attributes.snippet_id ?
			<Placeholder className={`code-snippet-selector ${className}`} icon={icon} label={label}>
				<form>
					<Select
						name="snippet-select"
						className="code-snippets-large-select"
						options={options}
						onChange={option => setAttributes({ snippet_id: option && 'value' in option ? option.value : 0 })}
						placeholder={__('Select a snippet to insert…', 'code-snippets')}
					/>
				</form>
			</Placeholder> :
			<ServerSideRender block={block} attributes={attributes} />}
	</>
