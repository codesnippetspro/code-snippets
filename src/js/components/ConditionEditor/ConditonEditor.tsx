import { __, _x } from '@wordpress/i18n'
import React, { Fragment } from 'react'
import { useSnippetForm } from '../../hooks/useSnippetForm'
import { addConditionGroup } from '../../utils/conditions'
import { Button } from '../common/Button'
import { ConditionRuleEditor } from './ConditionRuleEditor'

export const ConditionEditor: React.FC = () => {
	const { snippet } = useSnippetForm()

	return (
		<div className="snippet-condition-editor">
			<div className="snippet-condition-groups">
				{Object.keys(snippet.conditions).map(groupId =>
					<Fragment key={groupId}>
						<fieldset className="snippet-condition-group">
							{snippet.conditions[groupId] && 0 < Object.keys(snippet.conditions[groupId]).length
								&& Object.keys(snippet.conditions[groupId]).map(ruleId =>
									<ConditionRuleEditor key={`${groupId}-${ruleId}`} groupId={groupId} ruleId={ruleId} />)}
						</fieldset>

						<div className="condition-group-sep">{_x('or', 'boolean logical operator', 'code-snippets')}</div>
					</Fragment>)}
				<AddGroupButton />
			</div>
		</div>
	)
}

const AddGroupButton: React.FC = () => {
	const { setSnippet } = useSnippetForm()

	return (
		<Button
			className="condition-add-group-button"
			onClick={() => setSnippet(previous => addConditionGroup(previous))}
		>
			<span>{_x('Add New Group', 'condition', 'code-snippets')}</span>
		</Button>
	)
}
