import React, { Dispatch, SetStateAction } from 'react'
import { __ } from '@wordpress/i18n'
import Select from 'react-select'
import { SnippetInputProps } from '../../types/SnippetInputProps'
import { Snippet } from '../../types/Snippet'
import { conditionOptions } from './options'
import { Condition, Conditions } from './types'

interface ConditionRowProps {
	group: keyof Conditions
	condition: Condition
	setSnippet: Dispatch<SetStateAction<Snippet>>
}

const ConditionRow: React.FC<ConditionRowProps> = ({ condition, group, setSnippet }) =>
	<div className="snippet-condition-row">
		{Object.entries(conditionOptions).map(([key, options]) =>
			<Select
				key={key}
				options={options}
				value={options.find(option => option.value === condition?.[key as keyof Condition])}
			/>
		)}

		<button
			className="button condition-remove-button"
			title={__('Remove this condition from the group.', 'code-snippets')}
			onClick={event => {
				event.preventDefault()
				setSnippet(previous => ({
					...previous,
					conditions: {
						...previous.conditions,
						[group]: previous.conditions?.[group]?.filter(item => item !== condition)
					}
				}))
			}}
		>
			<span className="dashicons dashicons-trash"></span>
		</button>
	</div>

interface ConditionGroupProps extends SnippetInputProps {
	heading: string
	insertLabel: string
	description: string
	group: keyof Conditions
}

const ConditionGroup: React.FC<ConditionGroupProps> = ({
	group,
	snippet,
	setSnippet,
	heading,
	description,
	insertLabel
}) =>
	<>
		<h4>{heading}</h4>
		<p className="description">{description}</p>
		<div className="snippet-condition-group">
			{snippet.conditions?.[group]?.map(condition =>
				<ConditionRow
					group={group}
					condition={condition}
					setSnippet={setSnippet}
				/>
			)}

			<button
				className="button condition-add-button"
				onClick={event => {
					event.preventDefault()
					setSnippet(previous => ({
						...previous,
						conditions: {
							...previous.conditions,
							[group]: [...previous.conditions?.[group] ?? [], { subject: '', operator: 'eq', object: '' }]
						}
					}))
				}}
			>
				<span className="dashicons dashicons-insert"></span>
				<span>{insertLabel}</span>
			</button>
		</div>
	</>

export const ConditionEditor: React.FC<SnippetInputProps> = ({ snippet, setSnippet }) =>
	<div id="snippet_conditions" className="snippet-condition-editor">
		<ConditionGroup
			group="AND"
			snippet={snippet}
			setSnippet={setSnippet}
			heading={__('AND Conditions', 'code-snippets')}
			insertLabel={__('Add AND condition', 'code-snippets')}
			description={__('All conditions in this group must be true in order for the snippet to run.', 'code-snippets')}
		/>

		<ConditionGroup
			group="OR"
			snippet={snippet}
			setSnippet={setSnippet}
			heading={__('OR Conditions', 'code-snippets')}
			insertLabel={__('Add OR condition', 'code-snippets')}
			description={__('At least one condition in this group must be true in order for the snippet to run.', 'code-snippets')}
		/>
	</div>
