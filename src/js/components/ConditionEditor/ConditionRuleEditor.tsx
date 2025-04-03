import { __, _x } from '@wordpress/i18n'
import React, { useEffect, useState } from 'react'
import { useSnippetForm } from '../../hooks/useSnippetForm'
import { OPERATOR_OPTIONS, SUBJECT_OPTIONS, useConditionsAPI } from '../../hooks/useConditionsAPI'
import { appendConditionRule, removeConditionRule, updateConditionRule } from '../../utils/conditions'
import { handleUnknownError } from '../../utils/errors'
import { Button } from '../common/Button'
import { RemoveIcon } from '../common/icons/RemoveIcon'
import { MultiSelect, SingleSelect } from '../common/Select'
import type { Dispatch, SetStateAction } from 'react'
import type { ObjectOptions } from '../../hooks/useConditionsAPI'
import type { Snippet } from '../../types/Snippet'
import type { ConditionSubject } from '../../types/Condition'

const SUBJECT_KEYWORD_RE = /__(?<text>.+)__/

export interface ButtonProps {
	groupId: string
	ruleId: string
	setSnippet: Dispatch<SetStateAction<Snippet>>
}

const RemoveButton: React.FC<ButtonProps> = ({ groupId, ruleId, setSnippet }) =>
	<Button
		className="condition-remove-rule-button"
		title={__('Remove this condition rule.', 'code-snippets')}
		onClick={() => setSnippet(previous => removeConditionRule(previous, groupId, ruleId))}
	>
		<RemoveIcon />
	</Button>

export const AddRuleButton: React.FC<ButtonProps> = ({ groupId, ruleId, setSnippet }) =>
	<Button
		primary
		className="condition-add-rule-button"
		title={__('Add a new rule after this one.', 'code-snippets')}
		onClick={() => setSnippet(previous => appendConditionRule(previous, groupId, ruleId))}
	>
		{_x('and', 'boolean logical operator', 'code-snippets')}
	</Button>

interface ConditionObjectEditorProps<S extends ConditionSubject> {
	groupId: string
	ruleId: string
	objectOptions: ObjectOptions<S>
	objectOptionsLoaded: boolean
}

const ConditionObjectEditor = <S extends ConditionSubject>({
	groupId,
	ruleId,
	objectOptions,
	objectOptionsLoaded
}: ConditionObjectEditorProps<S>) => {
	const { snippet, setSnippet } = useSnippetForm()
	const objects = snippet.conditions[groupId]?.[ruleId]?.object ?? []

	return objectOptions
		? <>
			<SingleSelect
				className="snippet-condition-field-select snippet-condition-operator-select"
				options={OPERATOR_OPTIONS}
				currentValue={snippet.conditions[groupId]?.[ruleId]?.operator ?? 'is'}
				onChange={operator => {
					setSnippet(previous => updateConditionRule(previous, groupId, ruleId, { operator }))
				}}
			/>

			<MultiSelect
				className="snippet-condition-field-select snippet-condition-object-select"
				options={objectOptions}
				currentValue={Array.isArray(objects) ? objects : []}
				isLoading={!objectOptionsLoaded}
				onChange={object => {
					setSnippet(previous => updateConditionRule(previous, groupId, ruleId, { object }))
				}}
			/>
		</>
		: null
}

interface ConditionSubjectEditorProps {
	groupId: string
	ruleId: string
	clearObjectOptions: VoidFunction
}

const ConditionSubjectEditor: React.FC<ConditionSubjectEditorProps> = ({ groupId, ruleId, clearObjectOptions }) => {
	const { snippet, setSnippet } = useSnippetForm()

	return (
		<SingleSelect
			className="snippet-condition-field-select snippet-condition-subject-select"
			options={SUBJECT_OPTIONS}
			currentValue={snippet.conditions[groupId]?.[ruleId]?.subject}
			onChange={subject => {
				clearObjectOptions()
				setSnippet(previous => updateConditionRule(previous, groupId, ruleId, { subject }))
			}}
			formatOptionLabel={option =>
				<span dangerouslySetInnerHTML={{
					__html: option.label.replace(SUBJECT_KEYWORD_RE, '<strong>$1</strong>')
				}}></span>}
		/>
	)
}

export interface ConditionRuleEditorProps {
	groupId: string
	ruleId: string
}

export const ConditionRuleEditor: React.FC<ConditionRuleEditorProps> = ({ groupId, ruleId }) => {
	const { fetchSubjectOptions } = useConditionsAPI()
	const { snippet, setSnippet } = useSnippetForm()

	const [loadedSubject, setLoadedSubject] = useState<ConditionSubject>()
	const [objectOptions, setObjectOptions] = useState<ObjectOptions<ConditionSubject> | undefined>(undefined)

	const condition = snippet.conditions[groupId]?.[ruleId]

	useEffect(() => {
		if (objectOptions === undefined && condition?.subject) {
			setLoadedSubject(undefined)

			fetchSubjectOptions(condition.subject)
				.then(options => {
					setObjectOptions(options)
					setLoadedSubject(condition.subject)
				})
				.catch(handleUnknownError)
		}
	}, [condition?.subject, objectOptions, fetchSubjectOptions])

	return (
		<div id={`snippet-condition-group-${groupId}-rule-${ruleId}`} className="snippet-condition-rule">
			<ConditionSubjectEditor
				groupId={groupId}
				ruleId={ruleId}
				clearObjectOptions={() => {
					setLoadedSubject(undefined)
					setObjectOptions(undefined)
				}}
			/>

			<ConditionObjectEditor
				groupId={groupId}
				ruleId={ruleId}
				objectOptions={objectOptions}
				objectOptionsLoaded={loadedSubject === condition?.subject}
			/>

			<AddRuleButton groupId={groupId} ruleId={ruleId} setSnippet={setSnippet} />
			<RemoveButton groupId={groupId} ruleId={ruleId} setSnippet={setSnippet} />
		</div>
	)
}
