import { __ } from '@wordpress/i18n'
import React from 'react'
import { SnippetInputProps } from '../../types/SnippetInputProps'
import { AddGroupButton } from './AddButton'
import { ConditionRow } from './ConditionRow'

interface ConditionGroupProps extends SnippetInputProps {
	groupId: string
}

const ConditionGroup: React.FC<ConditionGroupProps> = ({ groupId, snippet, setSnippet, isReadOnly }) =>
	<>
		<fieldset key={groupId} className="snippet-condition-group">
			{snippet.conditions && Object.keys(snippet.conditions[groupId]).map((conditionId, index, keys) =>
				<ConditionRow
					key={conditionId}
					groupId={groupId}
					conditionId={conditionId}
					isReadOnly={isReadOnly}
					setSnippet={setSnippet}
					snippet={snippet}
					isLastItem={index === keys.length - 1}
				/>
			)}
		</fieldset>
		<div className="condition-group-sep">{__('OR', 'code-snippets')}</div>
	</>

export const ConditionEditor: React.FC<SnippetInputProps> = ({ snippet, setSnippet, ...inputProps }) =>
	<div id="snippet_conditions" className="snippet-condition-editor">
		<div className="snippet-condition-groups">
			<>
				{snippet.conditions ?
					Object.keys(snippet.conditions).map(groupId =>
						snippet.conditions?.[groupId] ?
							<ConditionGroup
								key={groupId}
								groupId={groupId}
								snippet={snippet}
								setSnippet={setSnippet}
								{...inputProps}
							/> : null
					) :
					<>
						<p>
							{__('Get started by clicking the button below.', 'code-snippets')}{' '}
							{__('Once created, you can choose to apply your condition to individual snippets.', 'code-snippets')}
						</p>
					</>}
			</>

			<AddGroupButton setSnippet={setSnippet} />
		</div>
	</div>
