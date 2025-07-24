import React, { ChangeEventHandler, useState } from 'react'
import { useRestAPI } from '../../hooks/useRestAPI'
import { useSnippetsList } from '../../hooks/useSnippetsList'
import { Snippet } from '../../types/Snippet'
import { handleUnknownError } from '../../utils/errors'
import { getSnippetType } from '../../utils/snippets/snippets'
import { stripTags } from '../../utils/text'
import { Badge } from '../common/Badge'
import { ListTableColumn } from '../common/ListTable'
import { __ } from '@wordpress/i18n'
import { addQueryArgs } from '@wordpress/url'
import { humanTimeDiff } from '@wordpress/date'

interface ColumnProps {
	snippet: Snippet
}

const ActivateColumn: React.FC<ColumnProps> = ({ snippet }) => {
	const { snippetsAPI: { activate, deactivate } } = useRestAPI()
	const { refreshSnippetsList } = useSnippetsList()

	return (
		<input
			type="checkbox"
			checked={snippet.active}
			className="switch"
			title={snippet.active
				? __('Deactivate', 'code-snippets')
				: __('Activate', 'code-snippets')}
			onChange={() => {
				(snippet.active ? deactivate(snippet) : activate(snippet))
					.then(() => refreshSnippetsList())
					.catch(handleUnknownError)
			}}
		/>
	)
}

const NameColumn: React.FC<ColumnProps> = ({ snippet }) => {
	return snippet.name
}

const PriorityColumn: React.FC<ColumnProps> = ({ snippet }) => {
	const [value, setValue] = useState(snippet.priority)
	// const { snippetsAPI: { update } } = useRestAPI()
	const id = `snippet-${snippet.id}-priority`

	const handleUpdate: ChangeEventHandler<HTMLInputElement> = event => {
		console.log(event, value)
	}

	return (
		<>
			<label htmlFor={id} className="screen-reader-text">{__('Snippet priority', 'code-snippets')}</label>
			<input
				id={id}
				type="number"
				className="snippet-priority"
				value={snippet.priority}
				step="1"
				onBlur={handleUpdate}
				onChange={event => setValue(Number(event.target.value))}
			/>
		</>
	)
}

export const TableColumns: ListTableColumn<Snippet>[] = [
	{
		id: 'activate',
		render: snippet => <ActivateColumn snippet={snippet} />
	},
	{
		id: 'name',
		title: __('Name', 'code-snippets'),
		isPrimary: true,
		sortedValue: item => item.name.toLowerCase(),
		render: snippet => <NameColumn snippet={snippet} />
	},
	{
		id: 'type',
		title: __('Type', 'code-snippets'),
		sortedValue: item => getSnippetType(item),
		render: snippet => <Badge name={getSnippetType(snippet)} />
	},
	{
		id: 'desc',
		title: __('Description', 'code-snippets'),
		// TODO: figure out how to allow formatting and markup.
		render: snippet => stripTags(snippet.desc)
	},
	{
		id: 'tags',
		title: __('Tags', 'code-snippets'),
		render: snippet =>
			snippet.tags.map((tag, index) =>
				<>
					<a key={tag} href={addQueryArgs(window.location.href, { tag })}>
						{tag}
					</a>
					{index < snippet.tags.length - 1 ? ', ' : ''}
				</>
			)
	},
	{
		id: 'date',
		title: __('Modified', 'code-snippets'),
		sortedValue: snippet => snippet.modified ? new Date(snippet.modified).toISOString() : '',
		render: snippet => snippet.modified ? humanTimeDiff(snippet.modified, undefined) : '&#8212;'
	},
	{
		id: 'priority',
		title: __('Priority', 'code-snippets'),
		sortedValue: snippet => snippet.priority,
		render: snippet => <PriorityColumn snippet={snippet} />
	}
]
