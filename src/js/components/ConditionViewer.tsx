import React from 'react'
import { CONDITION_OPERATOR_LABELS, ConditionGroups } from '../types/ConditionGroups'
import { _x } from '@wordpress/i18n'
import { CONDITION_SUBJECTS } from '../utils/conditions/subjects'

interface ConditionRuleViewerProps {
	ruleId: string
	groupId: string
	groups: ConditionGroups
	isLastRule: boolean
}

const ConditionRuleViewer: React.FC<ConditionRuleViewerProps> = ({ groupId, ruleId, groups, isLastRule }) => {
	const rule = groups[groupId]?.[ruleId]

	if (!rule?.subject) {
		return null
	}

	const subjectDefinition = CONDITION_SUBJECTS[rule.subject]
	const operator = rule.operator ?? subjectDefinition.operators[0]

	return (
		<tr id={`snippet-condition-group-${groupId}-rule-${ruleId}`} className="snippet-condition-rule">
			<td className="snippet-condition-field snippet-condition-subject">
				{subjectDefinition.label}
			</td>

			<td className="snippet-condition-field snippet-condition-operator">
				{CONDITION_OPERATOR_LABELS[operator]}
			</td>

			<td className="snippet-condition-field snippet-condition-object">
				{rule.object?.join(_x(', ', 'condition object separator', 'code-snippets'))}
			</td>

			<td className="condition-add-rule-button">
				{isLastRule ? '' : _x('and', 'boolean logical operator', 'code-snippets')}
			</td>
		</tr>
	)
}

export interface ConditionViewerProps {
	groups: ConditionGroups
}

export const ConditionViewer: React.FC<ConditionViewerProps> = ({ groups }) => {

	return (
		<div className="snippet-condition-viewer">
			<div className="snippet-condition-groups">
				{Object.entries(groups)
					.filter(([_, rules]) => rules && Object.keys(rules).length > 0)
					.map(([groupId, rules]) =>
						<table className="snippet-condition-group">
							{rules && Object.keys(rules).map((ruleId, index) =>
								<ConditionRuleViewer
									key={`${groupId}-${ruleId}`}
									ruleId={ruleId}
									groupId={groupId}
									groups={groups}
									isLastRule={index === Object.keys(rules).length - 1}
								/>)}
						</table>)}
			</div>
		</div>
	)
}
