import React, { useMemo } from 'react'
import { __, _x } from '@wordpress/i18n'
import { useSnippetForm } from '../../hooks/useSnippetForm'
import { useConditionOptions } from '../../hooks/useConditionOptions'
import { CONDITION_OPERATOR_LABELS } from '../../types/Condition'
import { CONDITIONS_SUBJECT_GROUPS } from '../../types/ConditionSubjectDefinitions'
import { appendConditionRule, getConditionRule, removeConditionRule, updateConditionRule } from '../../utils/conditions'
import { CONDITION_SUBJECTS } from '../../utils/conditions/subjects'
import { buildOptionGroups } from '../../utils/options'
import { Button } from '../common/Button'
import { RemoveIcon } from '../common/icons/RemoveIcon'
import { Select } from '../common/Select'
import type { ConditionOperator } from '../../types/Condition'
import type { SelectGroups, SelectOptions } from '../../types/SelectOption'
import type { ConditionSubject, ConditionSubjects } from '../../types/ConditionSubject'

interface ObjectSelectProps<S extends ConditionSubject> {
	groupId: string
	ruleId: string
	isMulti?: boolean
	options: SelectGroups<ConditionSubjects[S]>
	optionsLoaded: boolean
}

const ObjectSelect = <S extends ConditionSubject>({ options, groupId, ruleId, optionsLoaded, isMulti = false }: ObjectSelectProps<S>) => {
	const { snippet, setSnippet } = useSnippetForm()
	const rule = getConditionRule(snippet, groupId, ruleId)

	return (
		<Select
			isMulti={isMulti}
			className="snippet-condition-field-select snippet-condition-object-select"
			options={options}
			currentValue={isMulti ? rule?.object : rule?.object?.[0]}
			isLoading={!optionsLoaded}
			onSelect={value => {
				setSnippet(previous =>
					updateConditionRule(previous, groupId, ruleId, { object: value ? [value] : [] }))
			}}
			onSelectMulti={values => {
				setSnippet(previous =>
					updateConditionRule(previous, groupId, ruleId, { object: values }))
			}}
		/>
	)
}

interface OperatorSelectProps {
	groupId: string
	ruleId: string
	options: SelectGroups<ConditionOperator>
	currentOperator: ConditionOperator | undefined
}

const OperatorSelect: React.FC<OperatorSelectProps> = ({ options, currentOperator, groupId, ruleId }) => {
	const { setSnippet } = useSnippetForm()

	return (
		<Select
			className="snippet-condition-field-select snippet-condition-operator-select"
			options={options}
			currentValue={currentOperator}
			onChange={selected => {
				setSnippet(previous => updateConditionRule(previous, groupId, ruleId, {
					operator: selected?.value ?? undefined
				}))
			}}
		/>
	)
}

interface ConditionObjectEditorProps<S extends ConditionSubject> {
	groupId: string
	ruleId: string
	objectOptions: SelectGroups<ConditionSubjects[S]>
	currentOperator: ConditionOperator | undefined
	operatorOptions: SelectOptions<ConditionOperator>
	objectOptionsLoaded: boolean
}

const ConditionObjectEditor = <S extends ConditionSubject>({
	groupId,
	ruleId,
	objectOptions,
	currentOperator,
	operatorOptions,
	objectOptionsLoaded
}: ConditionObjectEditorProps<S>) => {
	const operatorProps: OperatorSelectProps = { groupId, ruleId, currentOperator, options: operatorOptions }
	const objectProps: ObjectSelectProps<S> = { groupId, ruleId, options: objectOptions, optionsLoaded: objectOptionsLoaded }

	switch (currentOperator) {
		case 'is':
		case 'not':
			return (
				<>
					<OperatorSelect {...operatorProps} />
					<ObjectSelect {...objectProps} />
				</>
			)

		case 'in':
		case 'not in':
			return (
				<>
					<OperatorSelect {...operatorProps} />
					<ObjectSelect {...objectProps} isMulti />
				</>
			)

		case 'true':
		case 'false':
			return (
				<>
					<ObjectSelect {...objectProps} />
					<OperatorSelect {...operatorProps} />
				</>
			)

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
		<Select
			className="snippet-condition-field-select snippet-condition-subject-select"
			options={options}
			currentValue={snippet.conditions[groupId]?.[ruleId]?.subject}
			onSelect={subject => {
				clearObjectOptions()
				setSnippet(previous => updateConditionRule(previous, groupId, ruleId, { subject }))
			}}
		/>
	)
}

export interface ConditionRuleEditorProps {
	groupId: string
	ruleId: string
}

export const ConditionRuleEditor: React.FC<ConditionRuleEditorProps> = ({ groupId, ruleId }) => {
	const { snippet, setSnippet } = useSnippetForm()
	const rule = getConditionRule(snippet, groupId, ruleId)
	const { objectOptions, loadedSubject, clearObjectOptions } = useConditionOptions(rule?.subject)

	const allowedOperators: ConditionOperator[] = useMemo(
		() => rule?.subject ? CONDITION_SUBJECTS[rule.subject].operators : [],
		[rule?.subject])

	const currentOperator = rule?.operator && allowedOperators.includes(rule.operator)
		? rule.operator
		: allowedOperators[0]

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
				objectOptions={objectOptions ?? []}
				currentOperator={currentOperator}
				operatorOptions={allowedOperators.map(operator =>
					({ value: operator, label: CONDITION_OPERATOR_LABELS[operator] }))}
				objectOptionsLoaded={loadedSubject === rule?.subject}
			/>

			<Button
				primary
				className="condition-add-rule-button"
				title={__('Add a new rule after this one.', 'code-snippets')}
				onClick={() => setSnippet(previous => appendConditionRule(previous, groupId, ruleId))}
			>
				{_x('and', 'boolean logical operator', 'code-snippets')}
			</Button>

			<Button
				className="condition-remove-rule-button"
				title={__('Remove this condition rule.', 'code-snippets')}
				onClick={() => setSnippet(previous => removeConditionRule(previous, groupId, ruleId))}
			>
				<RemoveIcon />
			</Button>
		</div>
	)
}
