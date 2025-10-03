import { _x } from '@wordpress/i18n'
import classnames from 'classnames'
import React, { Fragment } from 'react'
import { addConditionGroup } from '../../utils/conditions/rules'
import { Button } from '../common/Button'
import { ConditionRuleEditor } from './ConditionRuleEditor'
import type { Snippet } from '../../types/Snippet'
import type { Dispatch, SetStateAction } from 'react'

export interface ConditionEditorProps {
	condition: Snippet
	setCondition?: Dispatch<SetStateAction<Snippet>>
}

export const ConditionEditor: React.FC<ConditionEditorProps> = ({ condition, setCondition }) => {
	const isReadOnly = setCondition === undefined
	const groupIds = Object.keys(condition.conditions).filter(groupId =>
		condition.conditions[groupId] && 0 < Object.keys(condition.conditions[groupId]).length)

	return (
		<div className={classnames('snippet-condition-editor', { 'is-read-only': isReadOnly })}>
			<div className="snippet-condition-groups">
				{groupIds.map((groupId, groupIndex) => {
					const ruleIds = Object.keys(condition.conditions[groupId] ?? {})
					const isLastGroup = groupIndex === groupIds.length - 1

					return 0 < ruleIds.length
						? <Fragment key={groupId}>
							<div className="snippet-condition-group">
								{ruleIds.map(ruleId =>
									<ConditionRuleEditor
										key={`${groupId}-${ruleId}`}
										{...{ ruleId, groupId, condition, setCondition }}
									/>)}
							</div>

							{!isReadOnly || !isLastGroup
								? <div className="condition-group-sep">{_x('or', 'boolean logical operator', 'code-snippets')}</div>
								: null}
						</Fragment>
						: null
				})}

				{!isReadOnly && <AddGroupButton setCondition={setCondition} />
				}
			</div>
		</div>
	)
}

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
