import { __ } from '@wordpress/i18n'
import React from 'react'
import { useSnippetForm } from '../../hooks/useSnippetForm'
import { AddGroupButton } from './AddButton'
import { ConditionRow } from './ConditionRow'
import type { ConditionGroup , ConditionGroups } from '../../types/Condition'

const getNextIndex = (items: Record<PropertyKey, unknown> | undefined) => {
	const keys = items ? Object.keys(items) : []
	return 1 + (keys.length ? Math.max(...keys.map(Number).filter(value => !Number.isNaN(value))) : 0)
}

const createGroup = (groups: ConditionGroups | undefined = {}): ConditionGroups => ({
	...groups,
	[getNextIndex(groups)]: { 1: { subject: '', operator: 'eq', object: '' } }
})

const createCondition = (conditions: ConditionGroup | undefined = {}): ConditionGroup => ({
	...conditions,
	[getNextIndex(conditions)]: { subject: '', operator: 'eq', object: '' }
})

interface ConditionGroupEditorProps {
	groupId: string
}

const ConditionGroupEditor: React.FC<ConditionGroupEditorProps> = ({ groupId }) => {
	const { snippet, setSnippet } = useSnippetForm()

	return <>
		<fieldset key={groupId} className="snippet-condition-group">
			{snippet.conditions && Object.keys(snippet.conditions[groupId]).map((conditionId, index, keys) =>
				<ConditionRow
					key={conditionId}
					groupId={groupId}
					conditionId={conditionId}
					isLastItem={index === keys.length - 1}
					onAddCondition={() =>
						setSnippet(previous => ({
							...previous,
							conditions: {
								...previous.conditions,
								[groupId]: createCondition(previous.conditions?.[groupId])
							}
						}))}
				/>
			)}
		</fieldset>
		<div className="condition-group-sep">{__('OR', 'code-snippets')}</div>
	</>
}

export const ConditionEditor: React.FC = () => {
	const { snippet, setSnippet } = useSnippetForm()

	return <div id="snippet_conditions" className="snippet-condition-editor">
		<div className="snippet-condition-groups">
			<>
				{snippet.conditions ?
					Object.keys(snippet.conditions).map(groupId =>
						snippet.conditions?.[groupId] ?
							<ConditionGroupEditor key={groupId} groupId={groupId} /> : null
					) :
					<>
						<p>
							{__('Get started by clicking the button below.', 'code-snippets')}{' '}
							{__('Once created, you can choose to apply your condition to individual snippets.', 'code-snippets')}
						</p>
					</>}
			</>

			<AddGroupButton onClick={() =>
				setSnippet(previous => ({ ...previous, conditions: createGroup(previous.conditions) }))}
			/>
		</div>
	</div>
}
