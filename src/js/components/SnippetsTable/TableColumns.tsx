import React, { Fragment, useState } from 'react'
import { __, sprintf } from '@wordpress/i18n'
import { addQueryArgs } from '@wordpress/url'
import { humanTimeDiff } from '@wordpress/date'
import { Modal } from '@wordpress/components'
import { useRestAPI } from '../../hooks/useRestAPI'
import { useSnippetsList } from '../../hooks/useSnippetsList'
import { handleUnknownError } from '../../utils/errors'
import { downloadSnippetExportFile } from '../../utils/files'
import { isNetworkAdmin } from '../../utils/screen'
import { getSnippetEditUrl, getSnippetType } from '../../utils/snippets/snippets'
import { stripTags } from '../../utils/text'
import { Badge } from '../common/Badge'
import { Button } from '../common/Button'
import type { Snippet } from '../../types/Snippet'
import type { ListTableColumn } from '../common/ListTable'

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

const DeleteRowAction: React.FC<ColumnProps> = ({ snippet }) => {
	const { snippetsAPI } = useRestAPI()
	const [confirmDeleteDialogOpen, setConfirmDeleteDialogOpen] = useState(false)
	const { refreshSnippetsList } = useSnippetsList()

	return (
		<>
			<Button link className="delete" onClick={() => setConfirmDeleteDialogOpen(true)}>
				{__('Delete', 'code-snippets')}
			</Button>

			{confirmDeleteDialogOpen
				? <Modal
					className="code-snippets-confirm-delete-dialog"
					title={__('Are you sure?', 'code-snippets')}
					isDismissible
					onRequestClose={() => setConfirmDeleteDialogOpen(false)}
					closeButtonLabel={__('Cancel', 'code-snippets')}
				>
					{__('You are about to permanently delete this snippet.', 'code-snippets')}

					<Button onClick={() => setConfirmDeleteDialogOpen(false)}>
						{__('Cancel', 'code-snippets')}
					</Button>

					<Button primary onClick={() => {
						snippetsAPI.delete(snippet)
							.then(() => refreshSnippetsList())
							.catch(handleUnknownError)
					}
					}>
						{__('Delete', 'code-snippets')}
					</Button>
				</Modal>
				: null}
		</>
	)
}

const RowActions: React.FC<ColumnProps> = ({ snippet }) => {
	const { snippetsAPI } = useRestAPI()

	if (!isNetworkAdmin() && snippet.network && !snippet.shared_network) {
		return (
			<div className="row-actions visible">
				{snippet.active
					? <span className="network-active">{__('Network Active', 'code-snippets')}</span>
					: <span className="network-only">{__('Network Only', 'code-snippets')}</span>}
			</div>
		)
	}

	if (snippet.shared_network && !window.CODE_SNIPPETS_MANAGE?.hasNetworkCap) {
		return undefined
	}

	return (
		<div className="row-actions visible">
			<a href={getSnippetEditUrl(snippet)}>{__('Edit', 'code-snippets')}</a>{' | '}

			<Button link onClick={() => {
				snippetsAPI.export(snippet)
					.then(response => downloadSnippetExportFile(response, snippet))
					.catch(handleUnknownError)
			}}>
				{__('Export', 'code-snippets')}
			</Button>{' | '}

			<DeleteRowAction snippet={snippet} />
		</div>
	)
}

const NameColumn: React.FC<ColumnProps> = ({ snippet }) => {
	// translators: %s: snippet identifier.
	const displayName = snippet.name.trim() ? snippet.name : sprintf(__('Snippet #%d', 'code-snippets'), snippet.id)

	return (
		<>
			{isNetworkAdmin() || !snippet.network || window.CODE_SNIPPETS_MANAGE?.hasNetworkCap
				? <a href={getSnippetEditUrl(snippet)}>{displayName}</a>
				: displayName}

			{snippet.shared_network && <span className="badge">{__('Shared on Network', 'code-snippets')}</span>}

			<RowActions snippet={snippet} />
		</>
	)
}

const PriorityColumn: React.FC<ColumnProps> = ({ snippet }) => {
	const [value, setValue] = useState(snippet.priority)
	const { snippetsAPI } = useRestAPI()
	const { refreshSnippetsList } = useSnippetsList()
	const id = `snippet-${snippet.id}-priority`

	const handleUpdate = () => {
		snippetsAPI.update({ ...snippet, priority: value })
			.then(response => {
				if (response.id === snippet.id) {
					setValue(response.priority)
				}
			})
			.then(refreshSnippetsList)
			.catch(handleUnknownError)
	}

	return (
		<form onSubmit={event => {
			event.preventDefault()
			handleUpdate()
		}}>
			<label htmlFor={id} className="screen-reader-text">{__('Snippet priority', 'code-snippets')}</label>
			<input
				id={id}
				type="number"
				className="snippet-priority"
				value={value}
				step="1"
				onBlur={handleUpdate}
				onChange={event => setValue(Number(event.target.value))}
			/>
		</form>
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
				<Fragment key={tag}>
					<a key={tag} href={addQueryArgs(window.location.href, { tag })}>
						{tag}
					</a>
					{index < snippet.tags.length - 1 ? ', ' : ''}
				</Fragment>
			)
	},
	{
		id: 'date',
		title: __('Modified', 'code-snippets'),
		sortedValue: snippet => snippet.modified ? new Date(snippet.modified).toISOString() : '',
		render: snippet => snippet.modified
			? <time dateTime={snippet.modified}>{humanTimeDiff(snippet.modified, undefined)}</time>
			: '&#8212;'
	},
	{
		id: 'priority',
		title: __('Priority', 'code-snippets'),
		sortedValue: snippet => snippet.priority,
		render: snippet => <PriorityColumn snippet={snippet} />
	}
]
