import { __, _x } from '@wordpress/i18n'
import React from 'react'
import { createEmptySnippet } from '../../../utils/snippets'
import { useSnippetForm } from '../../../hooks/useSnippetForm'

const OPTIONS = window.CODE_SNIPPETS_EDIT

export const PageHeading: React.FC = () => {
	const { snippet, updateSnippet, setCurrentNotice } = useSnippetForm()

	return (
		<h1>
			{snippet.id
				? __('Edit Snippet', 'code-snippets')
				: __('Add New Snippet', 'code-snippets')}

			{snippet.id ? <>{' '}
				<a href={window.CODE_SNIPPETS?.urls.addNew} className="page-title-action" onClick={event => {
					event.preventDefault()
					updateSnippet(() => createEmptySnippet())
					setCurrentNotice(undefined)

					window.document.title = window.document.title.replace(
						__('Edit Snippet', 'code-snippets'),
						__('Add New Snippet', 'code-snippets')
					)

					window.history.replaceState({}, '', window.CODE_SNIPPETS?.urls.addNew)
				}}>
					{_x('Add New', 'snippet', 'code-snippets')}
				</a>
			</> : null}

			{OPTIONS?.pageTitleActions && Object.entries(OPTIONS.pageTitleActions).map(([label, url]) =>
				<>
					<a key={label} href={url} className="page-title-action">{label}</a>
					{' '}
				</>
			)}
		</h1>
	)
}
