import React from 'react'
import { __ } from '@wordpress/i18n'
import { BlockControls } from '@wordpress/block-editor'
import { Placeholder, ToolbarButton, ToolbarGroup } from '@wordpress/components'
import { undo } from '@wordpress/icons'
import { SingleSelect } from '../components/common/Select'
import type { Snippet } from '../types/Snippet'
import type { SelectGroups } from '../types/SelectOption'
import type { IconType } from '@wordpress/components'
import type { ReactElement } from 'react'

export interface SnippetSelectorProps {
	icon: IconType
	label: string
	className: string
	options: SelectGroups<Snippet>
	attributes: { snippet_id: number }
	setAttributes: (attributes: SnippetSelectorProps['attributes']) => void
	renderContent: () => ReactElement
}

export const SnippetSelector: React.FC<SnippetSelectorProps> = ({
	label,
	className,
	icon,
	options,
	attributes,
	setAttributes,
	renderContent
}) =>
	<>
		<BlockControls controls={undefined}>
			<ToolbarGroup>
				<ToolbarButton
					icon={undo}
					label={__('Choose a different snippet', 'code-snippets')}
					onClick={() => setAttributes({ snippet_id: 0 })}
				/>
			</ToolbarGroup>
		</BlockControls>

		{0 === attributes.snippet_id
			? <Placeholder className={`code-snippet-selector ${className}`} icon={icon} label={label}>
				<form>
					<SingleSelect
						name="snippet-select"
						options={options}
						className="code-snippets-select"
						placeholder={__('Select a snippet to insert…', 'code-snippets')}
						onChange={snippet =>
							setAttributes({ snippet_id: snippet?.id ?? 0 })}
					/>
				</form>
			</Placeholder>
			: renderContent()}
	</>
