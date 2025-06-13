import React from 'react'
import { __, _x } from '@wordpress/i18n'
import { useConditionOptions } from '../../hooks/useConditionOptions'
import { CONDITION_OPERATOR_LABELS } from '../../types/ConditionGroups'
import { CONDITIONS_SUBJECT_GROUPS } from '../../types/ConditionSubjectDefinitions'
import { appendConditionRule, getConditionRule, removeConditionRule, updateConditionRule } from '../../utils/conditions/rules'
import { CONDITION_SUBJECTS } from '../../utils/conditions/subjects'
import { buildOptionGroups } from '../../utils/options'
import { Button } from '../common/Button'
import { RemoveIcon } from '../common/icons/RemoveIcon'
import { Select } from '../common/Select'
import type { Snippet } from '../../types/Snippet'
import type { Dispatch, SetStateAction } from 'react'
import type { ConditionOperator } from '../../types/ConditionGroups'
import type { ConditionSubject } from '../../types/ConditionSubject'
import { ConditionObjectEditor } from './ConditionObjectEditor'

interface ConditionSubjectEditorProps extends ConditionRuleEditorProps {
	clearObjectOptions: VoidFunction
}

const CONDITION_SUBJECT_OPTIONS = buildOptionGroups({
	items: Object.entries(CONDITION_SUBJECTS),
	groups: CONDITIONS_SUBJECT_GROUPS,
	getGroup: ([_, subject]) => subject.group,
	buildOption: ([name, { label }]) =>
		({ value: name as ConditionSubject, label })
})

const ConditionSubjectEditor: React.FC<ConditionSubjectEditorProps> = ({ condition, groupId, ruleId, setCondition, clearObjectOptions }) =>
	<Select
		required
		options={CONDITION_SUBJECT_OPTIONS}
		currentValue={condition.conditions[groupId]?.[ruleId]?.subject}
		onSelect={subject => {
			clearObjectOptions()
			setCondition(previous => updateConditionRule(previous, groupId, ruleId, {
				subject,
				...subject
					? { operator: CONDITION_SUBJECTS[subject].operators[0], object: [] }
					: undefined
			}))
		}}
	/>

export interface ConditionRuleEditorProps {
	ruleId: string
	groupId: string
	condition: Snippet
	setCondition: Dispatch<SetStateAction<Snippet>>
}

export const ConditionRuleEditor: React.FC<ConditionRuleEditorProps> = ({ ruleId, groupId, condition, setCondition }) => {
	const rule = getConditionRule(condition, groupId, ruleId)
	const { objectOptions, loadedSubject, clearObjectOptions, loadMoreOptions } = useConditionOptions(rule?.subject)

	const allowedOperators: ConditionOperator[] = rule?.subject
		? CONDITION_SUBJECTS[rule.subject].operators
		: []

	const currentOperator = rule?.operator && allowedOperators.includes(rule.operator)
		? rule.operator
		: allowedOperators[0]

	return (
		<tr id={`snippet-condition-group-${groupId}-rule-${ruleId}`} className="snippet-condition-rule">
			<td className="snippet-condition-field snippet-condition-subject">
				<ConditionSubjectEditor {...{ condition, groupId, ruleId, setCondition, clearObjectOptions }} />
			</td>

			<ConditionObjectEditor
				{...{ ruleId, groupId, condition, setCondition, currentOperator, loadMoreOptions }}
				objectOptions={objectOptions ?? []}
				operatorOptions={allowedOperators.map(operator =>
					({ value: operator, label: CONDITION_OPERATOR_LABELS[operator] }))}
				objectOptionsLoaded={loadedSubject === rule?.subject}
			/>

			<td>
				<Button
					primary
					className="condition-add-rule-button"
					title={__('Add a new rule after this one.', 'code-snippets')}
					onClick={() => setCondition(previous => appendConditionRule(previous, groupId, ruleId))}
				>
					{_x('and', 'boolean logical operator', 'code-snippets')}
				</Button>
			</td>

			<td>
				<Button
					className="condition-remove-rule-button"
					title={__('Remove this condition rule.', 'code-snippets')}
					onClick={() => setCondition(previous => removeConditionRule(previous, groupId, ruleId))}
				>
					<RemoveIcon />
				</Button>
			</td>
		</tr>
	)
}
