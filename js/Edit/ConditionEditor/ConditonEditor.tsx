import React, { Dispatch, MouseEventHandler, SetStateAction, useState } from 'react'
import { __ } from '@wordpress/i18n'
import Select from 'react-select'
import { BaseSnippetProps } from '../../types/BaseSnippetProps'
import { conditionOptions } from './options'
import { Condition, Conditions } from './types'

interface ConditionRowProps {
	condition: Condition
	onRemove: MouseEventHandler<HTMLButtonElement>
}

const ConditionRow: React.FC<ConditionRowProps> = ({ condition, onRemove }) =>
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
			onClick={onRemove}
		>
			<span className="dashicons dashicons-trash"></span>
		</button>
	</div>

interface ConditionGroupProps {
	heading: string
	insertLabel: string
	description: string
	group: keyof Conditions
	conditions: Conditions
	setConditions: Dispatch<SetStateAction<Conditions>>
}

const ConditionGroup: React.FC<ConditionGroupProps> = ({
	group,
	conditions,
	setConditions,
	heading,
	description,
	insertLabel
}) =>
	<>
		<h4>{heading}</h4>
		<p className="description">{description}</p>
		<div className="snippet-condition-group">
			{conditions[group].map(condition =>
				<ConditionRow
					condition={condition}
					onRemove={() => setConditions(previous => {
						previous[group] = previous[group].filter(item => item !== condition)
						return previous
					})}
				/>
			)}

			<button
				className="button condition-add-button"
				onClick={() => {
					setConditions(previous => {
						previous[group].push({ subject: '', operator: 'eq', object: '' })
						return previous
					})
				}}
			>
				<span className="dashicons dashicons-insert"></span>
				<span>{insertLabel}</span>
			</button>
		</div>
	</>

export const ConditionEditor: React.FC<BaseSnippetProps> = ({ snippet, setSnippetField }) => {
	const [conditions, setConditions] = useState<Conditions>(() =>
		'condition' === snippet.scope ? JSON.parse(snippet.code) : { AND: [], OR: [] })

	return 'condition' === snippet.scope ?
		<>
			<ConditionGroup
				group="AND"
				conditions={conditions}
				setConditions={setConditions}
				heading={__('AND Conditions', 'code-snippets')}
				insertLabel={__('Add AND condition', 'code-snippets')}
				description={__('All conditions in this group must be true in order for the snippet to run.', 'code-snippets')}
			/>

			<ConditionGroup
				group="OR"
				conditions={conditions}
				setConditions={setConditions}
				heading={__('OR Conditions', 'code-snippets')}
				insertLabel={__('Add OR condition', 'code-snippets')}
				description={__('At least one condition in this group must be true in order for the snippet to run.', 'code-snippets')}
			/>
		</> :
		null
}
