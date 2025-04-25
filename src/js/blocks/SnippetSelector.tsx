import React from 'react'
import classnames from 'classnames'
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
	options: SelectGroups<Snippet>
	onChange: (snippet: Snippet | undefined) => void
	className: string
	renderContent: () => ReactElement
	isValueSelected: boolean
}

export const SnippetSelector: React.FC<SnippetSelectorProps> = ({
	icon,
	label,
	options,
	onChange,
	className,
	renderContent,
	isValueSelected
}) =>
	<>
		<BlockControls controls={undefined}>
			<ToolbarGroup>
				<ToolbarButton
					icon={undo}
					label={__('Choose a different snippet', 'code-snippets')}
					onClick={() => onChange(undefined)}
				/>
			</ToolbarGroup>
		</BlockControls>

		{isValueSelected
			? renderContent()
			: <Placeholder className={classnames('code-snippet-selector', className)} icon={icon} label={label}>
				<form>
					<SingleSelect
						name="snippet-select"
						options={options}
						placeholder={__('Select a snippet to insert…', 'code-snippets')}
						onChange={onChange}
					/>
				</form>
			</Placeholder>}
	</>
