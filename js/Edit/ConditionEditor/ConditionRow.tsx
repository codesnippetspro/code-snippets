import React, { Dispatch, SetStateAction, useEffect, useState } from 'react'
import { Condition, ConditionGroups, ConditionSubject } from '../../types/Condition'
import { Snippet } from '../../types/Snippet'
import { ConditionField } from './ConditionField'
import { ObjectOptions, OPERATOR_OPTIONS, SUBJECT_OPTION_PROMISES, SUBJECT_OPTIONS } from './options'
import { RemoveButton } from './RemoveButton'

export interface ConditionRowProps {
	group: keyof ConditionGroups
	condition: Condition
	conditionId: string
	setSnippet: Dispatch<SetStateAction<Snippet>>
}

export const ConditionRow: React.FC<ConditionRowProps> = ({ group, condition, conditionId, setSnippet }) => {
	const [loadedSubject, setLoadedSubject] = useState<ConditionSubject>()
	const [objectOptions, setObjectOptions] = useState<ObjectOptions | undefined>(undefined)
	const fieldProps = { group, conditionId, condition, setSnippet }

	useEffect(() => {
		if (!objectOptions && condition.subject && SUBJECT_OPTION_PROMISES[condition.subject]) {
			setLoadedSubject(undefined)
			SUBJECT_OPTION_PROMISES[condition.subject]()
				.then(result => {
					setObjectOptions(result)
					setLoadedSubject(condition.subject)
				})
		}
	}, [condition.subject, objectOptions])

	return (
		<div id={`snippet-condition-${group}-${conditionId}`} className="snippet-condition-row">
			<ConditionField
				{...fieldProps}
				field="subject"
				options={SUBJECT_OPTIONS}
				onChange={option => {
					setObjectOptions(undefined)
					setLoadedSubject(undefined)

					setSnippet(previous => ({
						...previous,
						conditions: {
							...previous.conditions,
							[group]: {
								...previous.conditions?.[group],
								[conditionId]: { ...condition, subject: option?.value ?? '', object: '' }
							}
						}
					}))
				}}
			/>

			<ConditionField
				{...fieldProps}
				field="operator"
				options={OPERATOR_OPTIONS}
			/>

			<ConditionField
				{...fieldProps}
				field="object"
				options={objectOptions}
				isLoading={condition.subject && loadedSubject !== condition.subject}
			/>

			<RemoveButton group={group} conditionId={conditionId} setSnippet={setSnippet} />
		</div>
	)
}
