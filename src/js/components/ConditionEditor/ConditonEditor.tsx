import { __, _x } from '@wordpress/i18n'
import React, { Fragment } from 'react'
import { addConditionGroup } from '../../utils/conditions/rules'
import { Button } from '../common/Button'
import { ConditionRuleEditor } from './ConditionRuleEditor'
import type { Snippet } from '../../types/Snippet'
import type { Dispatch, SetStateAction } from 'react'

export interface ConditionEditorProps {
	condition: Snippet
	setCondition: Dispatch<SetStateAction<Snippet>>
}

export const ConditionEditor: React.FC<ConditionEditorProps> = ({ condition, setCondition }) =>
	<div className="snippet-condition-editor">
		<div className="snippet-condition-groups">
			{Object.keys(condition.conditions)
				.filter(groupId => condition.conditions[groupId] && 0 < Object.keys(condition.conditions[groupId]).length)
				.map(groupId =>
					<Fragment key={groupId}>
						<table className="snippet-condition-group">
							{condition.conditions[groupId] && 0 < Object.keys(condition.conditions[groupId]).length &&
								Object.keys(condition.conditions[groupId]).map(ruleId =>
									<ConditionRuleEditor
										key={`${groupId}-${ruleId}`}
										{...{ ruleId, groupId, condition, setCondition }}
									/>)}
						</table>

						<div className="condition-group-sep">{_x('or', 'boolean logical operator', 'code-snippets')}</div>
					</Fragment>)}

			<AddGroupButton setCondition={setCondition} />
		</div>
	</div>

interface AddGroupButtonProps {
	setCondition: Dispatch<SetStateAction<Snippet>>
}

const AddGroupButton: React.FC<AddGroupButtonProps> = ({ setCondition }) =>
	<Button
		className="condition-add-group-button"
		onClick={() => setCondition(previous => addConditionGroup(previous))}
	>
		<span>{_x('Add New Group', 'condition', 'code-snippets')}</span>
	</Button>
