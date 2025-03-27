import { __ } from '@wordpress/i18n'
import React, { useEffect, useState } from 'react'
import { useSnippetForm } from '../../hooks/useSnippetForm'
import { ENABLED_OPTIONS, OPERATOR_OPTIONS, SUBJECT_OPTIONS, fetchSubjectOptions } from '../../services/edit/conditions/options'
import { cloneConditionRule, removeConditionRule, updateConditionField } from '../../services/edit/conditions/rules'
import { handleUnknownError } from '../../utils/errors'
import { CloneIcon } from '../common/icons/CloneIcon'
import { RemoveIcon } from '../common/icons/RemoveIcon'
import { ConditionFieldEditor } from './ConditionFieldEditor'
import type { Dispatch, SetStateAction } from 'react'
import type { ObjectOptions } from '../../services/edit/conditions/options'
import type { Snippet } from '../../types/Snippet'
import type { ConditionSubject } from '../../types/ConditionRule'

export interface ButtonProps {
	ruleId: string
	setSnippet: Dispatch<SetStateAction<Snippet>>
}

const RemoveButton: React.FC<ButtonProps> = ({ ruleId, setSnippet }) =>
	<div>
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
	</div>

export const CloneButton: React.FC<ButtonProps> = ({ ruleId, setSnippet }) =>
	<div>
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
	</div>

interface ConditionObjectEditorProps {
	ruleId: string
	objectOptions: ObjectOptions
	objectOptionsLoaded: boolean
}

const ConditionObjectEditor: React.FC<ConditionObjectEditorProps> = ({ ruleId, objectOptions, objectOptionsLoaded }) =>
	objectOptions
		? <>
			<ConditionFieldEditor
				field="operator"
				ruleId={ruleId}
				options={OPERATOR_OPTIONS}
			/>

			<ConditionFieldEditor
				field="object"
				ruleId={ruleId}
				options={objectOptions}
				isLoading={!objectOptionsLoaded}
			/>
		</>
		: null

export interface ConditionRuleEditorProps {
	ruleId: string
}

export const ConditionRuleEditor: React.FC<ConditionRuleEditorProps> = ({ ruleId }) => {
	const [loadedSubject, setLoadedSubject] = useState<ConditionSubject>()
	const [objectOptions, setObjectOptions] = useState<ObjectOptions | undefined>(undefined)

	const { snippet, setSnippet } = useSnippetForm()
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
	}, [condition?.subject, objectOptions])

	return (
		<div id={`snippet-condition-${ruleId}`} className="snippet-condition-rule">
			<ConditionFieldEditor
				field="enabled"
				ruleId={ruleId}
				options={ENABLED_OPTIONS}
			/>

			<ConditionFieldEditor
				field="subject"
				ruleId={ruleId}
				options={SUBJECT_OPTIONS}
				onChange={option => {
					setLoadedSubject(undefined)
					setObjectOptions(undefined)
					setSnippet(previous => updateConditionField(previous, ruleId, 'subject', option?.value))
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
