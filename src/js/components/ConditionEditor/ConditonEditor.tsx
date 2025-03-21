import { __, _x } from '@wordpress/i18n'
import React from 'react'
import { useSnippetForm } from '../../hooks/useSnippetForm'
import { addConditionRule } from '../../services/edit/conditions/rules'
import { ConditionRuleEditor } from './ConditionRuleEditor'

const AddRuleButton: React.FC = () => {
	const { setSnippet } = useSnippetForm()

	return (
		<button
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
	const ruleIds = snippet.conditions ? Object.keys(snippet.conditions) : []

	return (
		<div id="snippet_conditions" className="snippet-condition-editor">
			<h2>{__('Condition Rules', 'code-snippets')}</h2>

			<div className="snippet-condition-rules">
				{0 < ruleIds.length
					? ruleIds.map(ruleId =>
						<ConditionRuleEditor key={ruleId} ruleId={ruleId} />)
					: <>
						<p>
							{__('Get started by clicking the button below.', 'code-snippets')}{' '}
							{__('Once created, you can choose to apply your condition to individual snippets.', 'code-snippets')}
						</p>
					</>}
			</div>

			<AddRuleButton />
		</div>
	)
}
