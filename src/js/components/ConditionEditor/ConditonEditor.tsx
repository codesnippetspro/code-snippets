import { __, _x } from '@wordpress/i18n'
import React from 'react'
import { useSnippetForm } from '../../hooks/useSnippetForm'
import { addConditionRule } from '../../utils/conditions'
import { ConditionRuleEditor } from './ConditionRuleEditor'

const AddRuleButton: React.FC = () => {
	const { setSnippet } = useSnippetForm()

	return (
		<button
			type="button"
			className="button condition-add-button"
			onClick={event => {
				event.preventDefault()
				setSnippet(previous => addConditionRule(previous))
			}}
		>
			<span>{_x('Add New', 'condition rule', 'code-snippets')}</span>
		</button>
	)
}

export const ConditionEditor: React.FC = () => {
	const { snippet } = useSnippetForm()

	return (
		<div className="snippet-condition-editor">
			<div className="snippet-condition-rules">
				{Object.keys(snippet.conditions).map(ruleId =>
					<ConditionRuleEditor key={ruleId} ruleId={ruleId} />)}
			</div>

			<AddRuleButton />
		</div>
	)
}
