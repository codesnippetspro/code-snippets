import { __ } from '@wordpress/i18n'
import React, { useEffect, useState } from 'react'
import { useSnippetForm } from '../../hooks/useSnippetForm'
import { OPERATOR_OPTIONS, SUBJECT_OPTIONS, fetchSubjectOptions } from '../../services/edit/conditions'
import { handleUnknownError } from '../../utils/errors'
import { AddConditionButton } from './AddButton'
import { ConditionField } from './ConditionField'
import { RemoveButton } from './RemoveButton'
import type { ObjectOptions } from '../../services/edit/conditions'
import type { Snippet } from '../../types/Snippet'
import type { ConditionSubject } from '../../types/Condition'

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

export interface ConditionRowProps {
	groupId: string
	conditionId: string
	onAddCondition: VoidFunction
	isLastItem?: boolean
}

export const ConditionRow: React.FC<ConditionRowProps> = ({ isLastItem, groupId, conditionId, onAddCondition }) => {
	const [loadedSubject, setLoadedSubject] = useState<ConditionSubject>()
	const [objectOptions, setObjectOptions] = useState<ObjectOptions | undefined>(undefined)

	const { snippet, setSnippet } = useSnippetForm()
	const condition = snippet.conditions?.[groupId][conditionId]

	useEffect(() => {
		if (!objectOptions && condition?.subject) {
			setLoadedSubject(undefined)

			fetchSubjectOptions(condition.subject)
				.then(options => {
					setObjectOptions(options)
					setLoadedSubject(condition.subject)
				})
				.catch(handleUnknownError)
		}
	}, [condition?.subject, objectOptions])

	return (
		<div id={`snippet-condition-${groupId}-${conditionId}`} className="snippet-condition-row">
			<ConditionField
				field="subject"
				groupId={groupId}
				conditionId={conditionId}
				options={SUBJECT_OPTIONS}
				onChange={option => {
					setObjectOptions(undefined)
					setLoadedSubject(undefined)
					setSnippet(previous => updateSubject(previous, groupId, conditionId, option?.value))
				}}
			/>

			<ConditionField
				field="operator"
				groupId={groupId}
				conditionId={conditionId}
				options={OPERATOR_OPTIONS}
			/>

			<ConditionField
				field="object"
				groupId={groupId}
				conditionId={conditionId}
				options={objectOptions}
				isLoading={!!condition?.subject && loadedSubject !== condition.subject}
			/>

			{isLastItem ?
				<AddConditionButton onClick={onAddCondition} /> :
				<span className="condition-row-sep">{__('AND', 'code-snippets')}</span>}

			<RemoveButton groupId={groupId} conditionId={conditionId} setSnippet={setSnippet} />
		</div>
	)
}
