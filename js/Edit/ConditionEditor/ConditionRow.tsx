import React, { useEffect, useState } from 'react'
import { ConditionSubject } from '../../types/Condition'
import { Snippet } from '../../types/Snippet'
import { SnippetInputProps } from '../../types/SnippetInputProps'
import { ConditionField } from './ConditionField'
import { ObjectOptions, OPERATOR_OPTIONS, SUBJECT_OPTION_PROMISES, SUBJECT_OPTIONS } from './options'
import { RemoveButton } from './RemoveButton'

const updateSubject = (snippet: Snippet, groupId: string, conditionId: string, value?: ConditionSubject): Snippet => ({
	...snippet,
	conditions: {
		...snippet.conditions,
		[groupId]: {
			...snippet.conditions?.[groupId],
			[conditionId]: { ...snippet.conditions?.[groupId][conditionId], subject: value, object: undefined }
		}
	}
})

export interface ConditionRowProps extends SnippetInputProps {
	groupId: string
	conditionId: string
}

export const ConditionRow: React.FC<ConditionRowProps> = ({ ...fieldProps }) => {
	const [loadedSubject, setLoadedSubject] = useState<ConditionSubject>()
	const [objectOptions, setObjectOptions] = useState<ObjectOptions | undefined>(undefined)

	const { groupId, conditionId, snippet, setSnippet } = fieldProps
	const condition = snippet.conditions?.[groupId][conditionId]

	useEffect(() => {
		if (!objectOptions && condition?.subject && SUBJECT_OPTION_PROMISES[condition.subject]) {
			setLoadedSubject(undefined)
			SUBJECT_OPTION_PROMISES[condition.subject]()
				.then(result => {
					setObjectOptions(result)
					setLoadedSubject(condition.subject)
				})
		}
	}, [condition?.subject, objectOptions])

	return (
		<div id={`snippet-condition-${groupId}-${conditionId}`} className="snippet-condition-row">
			<ConditionField
				{...fieldProps}
				field="subject"
				options={SUBJECT_OPTIONS}
				onChange={option => {
					setObjectOptions(undefined)
					setLoadedSubject(undefined)
					setSnippet(previous => updateSubject(previous, groupId, conditionId, option?.value))
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
				isLoading={!!condition?.subject && loadedSubject !== condition.subject}
			/>

			<RemoveButton groupId={groupId} conditionId={conditionId} setSnippet={setSnippet} />
		</div>
	)
}
