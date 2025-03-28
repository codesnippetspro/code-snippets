import { __, _x } from '@wordpress/i18n'
import React from 'react'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import { createSnippetObject } from '../../../utils/snippets'

const OPTIONS = window.CODE_SNIPPETS_EDIT

const editHeading = __('Edit Snippet', 'code-snippets')
const addNewHeading = __('Add New Snippet', 'code-snippets')

export const PageHeading: React.FC = () => {
	const { snippet, updateSnippet, setCurrentNotice } = useSnippetForm()

	return (
		<h1>
			{snippet.id
				? <>
					{`${editHeading} `}

					<a
						href={window.CODE_SNIPPETS?.urls.addNew}
						className="page-title-action"
						onClick={event => {
							event.preventDefault()
							updateSnippet(() => createSnippetObject())
							setCurrentNotice(undefined)

							window.document.title = window.document.title.replace(editHeading, addNewHeading)
							window.history.replaceState({}, '', window.CODE_SNIPPETS?.urls.addNew)
						}}
					>
						{_x('Add New', 'snippet', 'code-snippets')}
					</a>
				</>
				: addNewHeading}

			{OPTIONS?.pageTitleActions && Object.entries(OPTIONS.pageTitleActions).map(([label, url]) =>
				<>
					<a key={label} href={url} className="page-title-action">{label}</a>{' '}
				</>
			)}
		</h1>
	)
}
