import React, { useMemo } from 'react'
import { __, _x } from '@wordpress/i18n'
import { useSnippetForm } from '../../hooks/useSnippetForm'
import { useConditionOptions } from '../../hooks/useConditionOptions'
import { CONDITION_OPERATOR_LABELS } from '../../types/Condition'
import { CONDITIONS_SUBJECT_GROUPS } from '../../types/ConditionSubjectDefinitions'
import { appendConditionRule, removeConditionRule, updateConditionRule } from '../../utils/conditions'
import { CONDITION_SUBJECTS } from '../../utils/conditions/subjects'
import { buildOptionGroups } from '../../utils/options'
import { Button } from '../common/Button'
import { RemoveIcon } from '../common/icons/RemoveIcon'
import { MultiSelect, SingleSelect } from '../common/Select'
import type { SelectGroups } from '../../types/SelectOption'
import type { ConditionSubject, ConditionSubjects } from '../../types/ConditionSubject'
import type { Dispatch, SetStateAction } from 'react'
import type { Snippet } from '../../types/Snippet'

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
	objectOptions: SelectGroups<ConditionSubjects[S]> | undefined
	objectOptionsLoaded: boolean
}

const ConditionObjectEditor = <S extends ConditionSubject>({
	groupId,
	ruleId,
	objectOptions,
	objectOptionsLoaded
}: ConditionObjectEditorProps<S>) => {
	const { snippet, setSnippet } = useSnippetForm()
	const condition = snippet.conditions[groupId]?.[ruleId]

	const operatorOptions = useMemo(
		() => condition?.subject
			? CONDITION_SUBJECTS[condition.subject].operators.map(operator =>
				({ value: operator, label: CONDITION_OPERATOR_LABELS[operator] }))
			: [],
		[condition?.subject])

	if (!condition?.subject) {
		return null
	}

	const className = 'snippet-condition-field-select snippet-condition-object-select'
	const allowedOperators = CONDITION_SUBJECTS[condition.subject].operators

	const currentOperator = condition.operator && allowedOperators.includes(condition.operator)
		? condition.operator
		: allowedOperators[0]

	const OperatorSelect = () =>
		<SingleSelect
			className="snippet-condition-field-select snippet-condition-operator-select"
			options={operatorOptions}
			currentValue={currentOperator}
			onChange={operator => {
				setSnippet(previous => updateConditionRule(previous, groupId, ruleId, { operator }))
			}}
		/>

	const SingleObjectSelect = () =>
		<SingleSelect
			className={className}
			options={objectOptions}
			currentValue={condition?.object?.[0]}
			isLoading={!objectOptionsLoaded}
			onChange={object => {
				setSnippet(previous =>
					updateConditionRule(previous, groupId, ruleId, { object: object ? [object] : [] }))
			}}
		/>

	const MultiObjectSelect = () =>
		<MultiSelect
			className={className}
			options={objectOptions}
			currentValue={Array.isArray(condition?.object) ? condition.object : []}
			isLoading={!objectOptionsLoaded}
			onChange={object => {
				setSnippet(previous => updateConditionRule(previous, groupId, ruleId, { object }))
			}}
		/>

	switch (currentOperator) {
		case 'is':
		case 'not':
			return <>
				<OperatorSelect />
				<SingleObjectSelect />
			</>

		case 'in':
		case 'not in':
			return <>
				<OperatorSelect />
				<MultiObjectSelect />
			</>

		case 'true':
		case 'false':
			return <>
				<SingleObjectSelect />
				<OperatorSelect />
			</>

		default:
			return null
	}
}

interface ConditionSubjectEditorProps {
	groupId: string
	ruleId: string
	clearObjectOptions: VoidFunction
}

const ConditionSubjectEditor: React.FC<ConditionSubjectEditorProps> = ({ groupId, ruleId, clearObjectOptions }) => {
	const { snippet, setSnippet } = useSnippetForm()

	const options = useMemo(
		() => buildOptionGroups({
			items: Object.entries(CONDITION_SUBJECTS),
			groups: CONDITIONS_SUBJECT_GROUPS,
			getGroup: ([_, subject]) => subject.group,
			buildOption: ([name, { label }]) =>
				({ value: name as ConditionSubject, label })
		}),
		[]
	)

	return (
		<SingleSelect
			className="snippet-condition-field-select snippet-condition-subject-select"
			options={options}
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
	const { snippet, setSnippet } = useSnippetForm()
	const condition = snippet.conditions[groupId]?.[ruleId]
	const { objectOptions, loadedSubject, clearObjectOptions } = useConditionOptions(condition?.subject)

	return (
		<div id={`snippet-condition-group-${groupId}-rule-${ruleId}`} className="snippet-condition-rule">
			<ConditionSubjectEditor
				groupId={groupId}
				ruleId={ruleId}
				clearObjectOptions={clearObjectOptions}
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
