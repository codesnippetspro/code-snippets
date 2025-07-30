import { __, _x } from '@wordpress/i18n'
import React from 'react'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import { createSnippetObject } from '../../../utils/snippets/snippets'
import type { Snippet } from '../../../types/Snippet'

const OPTIONS = window.CODE_SNIPPETS_EDIT

const getAddNewHeading = (snippet: Snippet): string =>
	'condition' === snippet.scope
		? __('Add New Condition', 'code-snippets')
		: __('Add New Snippet', 'code-snippets')

export const PageHeading: React.FC = () => {
	const { snippet, updateSnippet, setCurrentNotice } = useSnippetForm()

	return (
		<h1>
			{snippet.id
				? <>
					{`${'condition' === snippet.scope
						? __('Edit Condition', 'code-snippets')
						: __('Edit Snippet', 'code-snippets')} `}

					<a
						href={window.CODE_SNIPPETS?.urls.addNew}
						className="page-title-action"
						onClick={event => {
							event.preventDefault()
							updateSnippet(({ scope }) => createSnippetObject({ scope }))
							setCurrentNotice(undefined)

							window.document.title = window.document.title
								.replace(__('Edit Snippet', 'code-snippets'), getAddNewHeading(snippet))
								.replace(__('Edit Condition', 'code-snippets'), getAddNewHeading(snippet))

							window.history.pushState({}, '', window.CODE_SNIPPETS?.urls.addNew)
						}}
					>
						{_x('Add New', 'snippet', 'code-snippets')}
					</a>
				</>
				: getAddNewHeading(snippet)}

			{OPTIONS?.pageTitleActions && Object.entries(OPTIONS.pageTitleActions).map(([label, url]) =>
				<>
					<a key={label} href={url} className="page-title-action">{label}</a>{' '}
				</>
			)}
		</h1>
	)
}
