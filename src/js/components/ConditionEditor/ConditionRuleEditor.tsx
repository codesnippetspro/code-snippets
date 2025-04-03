import { __ } from '@wordpress/i18n'
import React, { useEffect, useState } from 'react'
import { useSnippetForm } from '../../hooks/useSnippetForm'
import { ENABLED_OPTIONS, SUBJECT_OPTIONS, useConditionsAPI } from '../../hooks/useConditionsAPI'
import { cloneConditionRule, removeConditionRule, updateConditionRule } from '../../utils/conditions'
import { handleUnknownError } from '../../utils/errors'
import { CloneIcon } from '../common/icons/CloneIcon'
import { RemoveIcon } from '../common/icons/RemoveIcon'
import { MultiSelect, SingleSelect } from '../common/Select'
import type { Dispatch, SetStateAction } from 'react'
import type { ObjectOptions } from '../../hooks/useConditionsAPI'
import type { Snippet } from '../../types/Snippet'
import type { ConditionSubject } from '../../types/ConditionRule'

const SUBJECT_KEYWORD_RE = /__(?<text>.+)__/

export interface ButtonProps {
	ruleId: string
	setSnippet: Dispatch<SetStateAction<Snippet>>
}

const RemoveButton: React.FC<ButtonProps> = ({ ruleId, setSnippet }) =>
	<button
		type="button"
		className="button condition-remove-button"
		title={__('Remove this condition rule.', 'code-snippets')}
		onClick={event => {
			event.preventDefault()
			setSnippet(previous => removeConditionRule(previous, ruleId))
		}}
	>
		<RemoveIcon />
	</button>

export const CloneButton: React.FC<ButtonProps> = ({ ruleId, setSnippet }) =>
	<button
		type="button"
		className="button condition-clone-button"
		title={__('Clone this condition rule.', 'code-snippets')}
		onClick={event => {
			event.preventDefault()
			setSnippet(previous => cloneConditionRule(previous, ruleId))
		}}
	>
		<CloneIcon />
	</button>

interface ConditionObjectEditorProps<S extends ConditionSubject> {
	ruleId: string
	objectOptions: ObjectOptions<S>
	objectOptionsLoaded: boolean
}

const ConditionObjectEditor = <S extends ConditionSubject>({
	ruleId,
	objectOptions,
	objectOptionsLoaded
}: ConditionObjectEditorProps<S>) => {
	const { snippet, setSnippet } = useSnippetForm()
	const objects = snippet.conditions[ruleId]?.object ?? []

	return objectOptions
		? <>
			<MultiSelect
				className="snippet-condition-field-select snippet-condition-object-select"
				options={objectOptions}
				currentValue={Array.isArray(objects) ? objects : []}
				isLoading={!objectOptionsLoaded}
				onChange={object => {
					setSnippet(previous => updateConditionRule(previous, ruleId, { object }))
				}}
			/>
		</>
		: null
}

interface ConditionSubjectEditorProps {
	ruleId: string
	clearObjectOptions: VoidFunction
}

const ConditionSubjectEditor: React.FC<ConditionSubjectEditorProps> = ({ ruleId, clearObjectOptions }) => {
	const { snippet, setSnippet } = useSnippetForm()

	return (
		<SingleSelect
			className="snippet-condition-field-select snippet-condition-subject-select"
			options={SUBJECT_OPTIONS}
			currentValue={snippet.conditions[ruleId]?.subject}
			onChange={subject => {
				clearObjectOptions()
				setSnippet(previous => updateConditionRule(previous, ruleId, { subject }))
			}}
			formatOptionLabel={option =>
				<span dangerouslySetInnerHTML={{
					__html: option.label.replace(SUBJECT_KEYWORD_RE, '<strong>$1</strong>')
				}}></span>}
		/>
	)
}

export interface ConditionRuleEditorProps {
	ruleId: string
}

export const ConditionRuleEditor: React.FC<ConditionRuleEditorProps> = ({ ruleId }) => {
	const { fetchSubjectOptions } = useConditionsAPI()
	const { snippet, setSnippet } = useSnippetForm()

	const [loadedSubject, setLoadedSubject] = useState<ConditionSubject>()
	const [objectOptions, setObjectOptions] = useState<ObjectOptions<ConditionSubject> | undefined>(undefined)

	const condition = snippet.conditions[ruleId]

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
		<div id={`snippet-condition-${ruleId}`} className="snippet-condition-rule">
			<SingleSelect
				className="snippet-condition-field-select snippet-condition-enabled-select"
				options={ENABLED_OPTIONS}
				currentValue={snippet.conditions[ruleId]?.enabled ?? true}
				onChange={enabled => {
					setSnippet(previous => updateConditionRule(previous, ruleId, { enabled }))
				}}
			/>

			<ConditionSubjectEditor
				ruleId={ruleId}
				clearObjectOptions={() => {
					setLoadedSubject(undefined)
					setObjectOptions(undefined)
				}}
			/>

			<ConditionObjectEditor
				ruleId={ruleId}
				objectOptions={objectOptions}
				objectOptionsLoaded={loadedSubject === condition?.subject}
			/>

			<CloneButton ruleId={ruleId} setSnippet={setSnippet} />
			<RemoveButton ruleId={ruleId} setSnippet={setSnippet} />
		</div>
	)
}
