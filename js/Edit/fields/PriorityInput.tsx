import React from 'react'
import { __ } from '@wordpress/i18n'
import { SnippetInputProps } from '../../types/SnippetInputProps'
import { getSnippetType } from '../../utils/snippets'

export const PriorityInput: React.FC<SnippetInputProps> = ({ snippet, setSnippet, isReadOnly }) =>
	'html' === getSnippetType(snippet) ? null :
		<p
			className="snippet-priority"
			title={__('Snippets with a lower priority number will run before those with a higher number.', 'code-snippets')}
		>
			<label htmlFor="snippet_priority">{`${__('Priority', 'code-snippets')} `}</label>
			<input
				type="number"
				id="snippet_priority"
				name="snippet_priority"
				value={snippet.priority}
				disabled={isReadOnly}
				onChange={event => setSnippet(previous => ({ ...previous, priority: parseInt(event.target.value, 10) }))}
			/>
		</p>
