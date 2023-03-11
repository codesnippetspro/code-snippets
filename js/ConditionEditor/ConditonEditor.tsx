import React, { Dispatch, MouseEventHandler, SetStateAction, useState } from 'react'
import { __ } from '@wordpress/i18n'
import Select from 'react-select'
import { conditionOptions } from './options'
import { Condition } from './types'

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
	conditions: Condition[]
	setConditions: Dispatch<SetStateAction<Condition[]>>
}

const ConditionGroup: React.FC<ConditionGroupProps> = ({
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
			{conditions.map(condition =>
				<ConditionRow
					condition={condition}
					onRemove={() => setConditions(value => value.filter(item => item !== condition))}
				/>
			)}

			<button
				className="button condition-add-button"
				onClick={() => {
					setConditions(value => [...value, { subject: '', operator: 'eq', object: '' }])
				}}
			>
				<span className="dashicons dashicons-insert"></span>
				<span>{insertLabel}</span>
			</button>
		</div>
	</>

export const ConditionEditor: React.FC = () => {
	const [conditionsOR, setConditionsOR] = useState<Condition[]>(() => [])
	const [conditionsAND, setConditionsAND] = useState<Condition[]>(() => [])

	return (
		<>
			<ConditionGroup
				conditions={conditionsAND}
				setConditions={setConditionsAND}
				heading={__('AND Conditions', 'code-snippets')}
				insertLabel={__('Add AND condition', 'code-snippets')}
				description={__('All conditions in this group must be true in order for the snippet to run.', 'code-snippets')}
			/>

			<ConditionGroup
				conditions={conditionsOR}
				setConditions={setConditionsOR}
				heading={__('OR Conditions', 'code-snippets')}
				insertLabel={__('Add OR condition', 'code-snippets')}
				description={__('At least one condition in this group must be true in order for the snippet to run.', 'code-snippets')}
			/>
		</>
	)
}
