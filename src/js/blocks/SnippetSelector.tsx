import React, { useEffect, useState } from 'react'
import classnames from 'classnames'
import { __ } from '@wordpress/i18n'
import { BlockControls } from '@wordpress/block-editor'
import { Placeholder, ToolbarButton, ToolbarGroup } from '@wordpress/components'
import { undo } from '@wordpress/icons'
import { Select } from '../components/common/Select'
import { useSnippetsList } from '../hooks/useSnippetsList'
import type { ReactNode} from 'react'
import type { Snippet } from '../types/Snippet'
import type { SelectGroups } from '../types/SelectOption'
import type { IconType } from '@wordpress/components'

export interface SnippetSelectorProps {
	icon: IconType
	label: string
	selectedId: number
	onChange: (snippet: Snippet | undefined) => void
	className: string
	buildOptions: (snippets: Snippet[]) => SelectGroups<Snippet>
	renderContent: (selectedSnippet?: Snippet) => ReactNode
}

export const SnippetSelector: React.FC<SnippetSelectorProps> = ({
	icon,
	label,
	onChange,
	className,
	selectedId,
	buildOptions,
	renderContent
}) => {
	const { snippetsList } = useSnippetsList()
	const [options, setOptions] = useState<SelectGroups<Snippet>>()

	useEffect(() => {
		if (snippetsList) {
			setOptions(buildOptions([...snippetsList]))
		}
	}, [snippetsList, buildOptions])

	return (
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

			{selectedId
				? renderContent(snippetsList?.find(snippet => snippet.id === selectedId))
				: <Placeholder className={classnames('code-snippet-selector', className)} icon={icon} label={label}>
					<form>
						<Select
							name="snippet-select"
							options={options ?? []}
							placeholder={__('Select a snippet to insert…', 'code-snippets')}
							onSelect={value => onChange(value)}
						/>
					</form>
				</Placeholder>}
		</>
	)
}
