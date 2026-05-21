import React from 'react'
import { __ } from '@wordpress/i18n'
import { useSnippetForm } from '../WithSnippetFormContext'

export const NameInput: React.FC = () => {
	const { snippet, setSnippet, isReadOnly } = useSnippetForm()

	return (
		<div id="titlediv">
			<div id="titlewrap">
				<label htmlFor="title" className="screen-reader-text" id="snippet-title-label">
					{__('Snippet Name', 'code-snippets')}
				</label>

				<input
					id="title"
					type="text"
					name="snippet_name"
					autoComplete="off"
					value={snippet.name}
					disabled={isReadOnly}
					aria-labelledby="snippet-title-label"
					placeholder={__('Enter snippet title', 'code-snippets')}
					onChange={event =>
						setSnippet(previous => ({ ...previous, name: event.target.value }))}
				/>
			</div>
		</div>
	)
}
