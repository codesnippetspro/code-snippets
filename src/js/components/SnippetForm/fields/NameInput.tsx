import React from 'react'
import { __ } from '@wordpress/i18n'
import { useSnippetForm } from '../../../hooks/useSnippetForm'

export const NameInput: React.FC = () => {
	const { snippet, setSnippet, isReadOnly } = useSnippetForm()

	return (
		<div id="titlediv">
			<div id="titlewrap">
				<label htmlFor="title" className="screen-reader-text">
					{__('Name', 'code-snippets')}
				</label>
				<input
					id="title"
					type="text"
					name="snippet_name"
					autoComplete="off"
					value={snippet.name}
					disabled={isReadOnly}
					placeholder={__('Enter snippet title', 'code-snippets')}
					onChange={event =>
						setSnippet(previous => ({ ...previous, name: event.target.value }))}
				/>
			</div>
		</div>
	)
}
