import React from 'react'
import { ConditionGroups } from '../../types/Condition'
import { SnippetInputProps } from '../../types/SnippetInputProps'
import { AddButton } from './AddButton'
import { ConditionRow } from './ConditionRow'

export interface ConditionGroupProps extends SnippetInputProps {
	heading: string
	insertLabel: string
	description: string
	group: keyof ConditionGroups
}

export const ConditionGroup: React.FC<ConditionGroupProps> = ({
	group,
	snippet,
	setSnippet,
	heading,
	description,
	insertLabel
}) => {
	const conditions = snippet.conditions?.[group]

	return <>
		<h4>{heading}</h4>
		<p className="description">{description}</p>
		<div className="snippet-condition-group">
			{conditions ?
				Object.keys(conditions).map(conditionId =>
					<ConditionRow
						key={conditionId}
						group={group}
						condition={conditions[conditionId]}
						conditionId={conditionId}
						setSnippet={setSnippet}
					/>
				) : null}

			<AddButton group={group} insertLabel={insertLabel} setSnippet={setSnippet} />
		</div>
	</>
}
