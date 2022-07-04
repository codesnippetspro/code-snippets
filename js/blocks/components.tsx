import React from 'react';
import { __ } from '@wordpress/i18n';
import { BlockControls, useBlockProps } from '@wordpress/block-editor';
import { Placeholder, ToolbarGroup, ToolbarButton, Icon } from '@wordpress/components';
import { undo } from '@wordpress/icons'
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
	icon: Icon.IconType<unknown>
	options: OptionsOrGroups<SnippetSelectOption, SnippetSelectGroup>
	attributes: { snippet_id: number }
	setAttributes: (attributes: SnippetSelectorProps['attributes']) => void
}

export const SnippetSelector: React.FC<SnippetSelectorProps> = ({
	block,
	label,
	className,
	icon,
	options,
	attributes,
	setAttributes
}) =>
	<>
		<BlockControls>
			<ToolbarGroup>
				<ToolbarButton
					icon={undo}
					label={__('Choose a different snippet', 'code-snippets')}
					onClick={() => setAttributes({ snippet_id: 0 })}
				/>
			</ToolbarGroup>
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
