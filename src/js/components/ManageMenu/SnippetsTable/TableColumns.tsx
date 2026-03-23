import classnames from 'classnames'
import React, { Fragment, useState } from 'react'
import { __, sprintf } from '@wordpress/i18n'
import { humanTimeDiff } from '@wordpress/date'
import { RawHTML } from '@wordpress/element'
import { useSnippetsAPI } from '../../../hooks/useSnippetsAPI'
import { useSnippetsList } from '../../../hooks/useSnippetsList'
import { handleUnknownError } from '../../../utils/errors'
import { downloadSnippetExportFile } from '../../../utils/files'
import { isNetworkAdmin } from '../../../utils/screen'
import { createSnippetObject, getSnippetDisplayName, getSnippetEditUrl, getSnippetType } from '../../../utils/snippets/snippets'
import { buildUrl } from '../../../utils/urls'
import { Badge } from '../../common/Badge'
import { Button } from '../../common/Button'
import { DeleteButton } from '../../common/DeleteButton'
import { Tooltip } from '../../common/Tooltip'
import { useSnippetsFilters } from './WithSnippetsTableFilters'
import { useFilteredSnippets } from './WithFilteredSnippetsContext'
import type { ReactNode } from 'react'
import type { Snippet } from '../../../types/Snippet'
import type { ListTableColumn } from '../../common/ListTable'

interface ColumnProps {
	snippet: Snippet
}

const ActivateColumn: React.FC<ColumnProps> = ({ snippet }) => {
	const { activate, deactivate } = useSnippetsAPI()
	const { activeByCondition } = useFilteredSnippets()
	const { refreshSnippetsList } = useSnippetsList()

	if (snippet.trashed) {
		return ''
	}

	switch (snippet.scope) {
		case 'single-use':
			return (
				<a
					className="snippet-execution-button"
					title={__('Run Once', 'code-snippets')}
					href={buildUrl(window.location.href, { action: 'run-once', snippet: snippet.id })}
				>
					&nbsp;
				</a>
			)

		case 'condition':
			return (
				<a className="snippet-condition-count" href={getSnippetEditUrl(snippet)}>
					{activeByCondition.get(snippet.id)?.length ?? 0}
				</a>
			)

		default: {
			const actionText = snippet.network && !snippet.shared_network
				? snippet.active ? __('Network Deactivate', 'code-snippets') : __('Network Activate', 'code-snippets')
				: snippet.active ? __('Deactivate', 'code-snippets') : __('Activate', 'code-snippets')

			return (
				<>
					<label className="screen-reader-text" htmlFor={`snippet-${snippet.id}-switch`}>
						{actionText}
					</label>

					<input
						id={`snippet-${snippet.id}-switch`}
						type="checkbox"
						checked={snippet.active}
						className="switch"
						title={actionText}
						onChange={() => {
							(snippet.active ? deactivate(snippet) : activate(snippet))
								.then(refreshSnippetsList)
								.catch(handleUnknownError)
						}}
					/>
				</>
			)
		}
	}
}

const ActionLinks = ({ snippet }: { snippet: Snippet }) => {
	const api = useSnippetsAPI()
	const { refreshSnippetsList } = useSnippetsList()

	const Edit = !snippet.trashed && (() =>
		<a href={getSnippetEditUrl(snippet)}>
			{snippet.locked ? __('View', 'code-snippets') : __('Edit', 'code-snippets')}
		</a>)

	const Clone = !snippet.trashed && (() =>
		<Button link onClick={() => {
			api.create(createSnippetObject({
				...snippet,
				id: 0,
				active: false,
				// translators: %s: snippet title.
				name: sprintf(__('%s [CLONE]', 'code-snippets'), snippet.name)
			}))
				.then(refreshSnippetsList)
				.catch(handleUnknownError)
		}}>
			{__('Clone', 'code-snippets')}
		</Button>)

	const Export = () =>
		<Button link onClick={() => {
			api.export(snippet)
				.then(response => downloadSnippetExportFile(response, snippet))
				.catch(handleUnknownError)
		}}>
			{__('Export', 'code-snippets')}
		</Button>

	const Restore = snippet.trashed && (() =>
		<Button link onClick={() => {
			api.restore(snippet)
				.then(refreshSnippetsList)
				.catch(handleUnknownError)
		}}>
			{__('Restore', 'code-snippets')}
		</Button>)

	const Delete = (!snippet.locked || snippet.trashed) && (() =>
		<DeleteButton link className="delete" snippet={snippet} onSuccess={refreshSnippetsList} />)

	return (
		<>
			{[Edit, Clone, Restore, Export, Delete]
				.filter(Action => false !== Action)
				.reduce<ReactNode>(
					(Actions, Action) =>
						null === Actions ? <Action /> : <>{Actions} | <Action /></>,
					null)}
		</>
	)
}

const RowActions: React.FC<ColumnProps> = ({ snippet }) => {
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
		<div className={classnames('row-actions', { visible: !snippet.trashed })}>
			<ActionLinks snippet={snippet} />
		</div>
	)
}

const NameColumn: React.FC<ColumnProps> = ({ snippet }) =>
	<>
		{snippet.locked && (
			<Tooltip inline end icon={<span className="dashicons dashicons-lock"></span>}>
				{__('This snippet is locked and cannot be modified.', 'code-snippets')}
			</Tooltip>)}

		{!snippet.trashed && (isNetworkAdmin() || !snippet.network || window.CODE_SNIPPETS_MANAGE?.hasNetworkCap)
			? <a href={getSnippetEditUrl(snippet)} className="snippet-name">{getSnippetDisplayName(snippet)}</a>
			: getSnippetDisplayName(snippet)}

		{snippet.shared_network && <span className="badge">{__('Shared on Network', 'code-snippets')}</span>}

		<RowActions snippet={snippet} />
	</>

const TypeColumn: React.FC<ColumnProps> = ({ snippet }) => {
	const { setCurrentType } = useSnippetsFilters()
	const type = getSnippetType(snippet)

	return (
		<a
			href={buildUrl(window.location.href, { type })}
			onClick={event => {
				event.preventDefault()
				setCurrentType(type)
			}}
		>
			<Badge name={type} />
		</a>
	)
}

const TagsColumn: React.FC<ColumnProps> = ({ snippet }) =>
	snippet.tags.map((tag, index) =>
		<Fragment key={tag}>
			<a key={tag} href={buildUrl(window.location.href, { tag })}>
				{tag}
			</a>
			{index < snippet.tags.length - 1 ? ', ' : ''}
		</Fragment>)

const DateColumn: React.FC<ColumnProps> = ({ snippet }) =>
	snippet.modified
		? <span className="modified-column-content" title={snippet.modified}>
			<time dateTime={snippet.modified}>
				{humanTimeDiff(snippet.modified, undefined)}
			</time>
		</span>
		: <>&#8212;</>

const PriorityColumn: React.FC<ColumnProps> = ({ snippet }) => {
	const [value, setValue] = useState(snippet.priority)
	const snippetsAPI = useSnippetsAPI()
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
				disabled={snippet.locked || snippet.trashed}
			/>
		</form>
	)
}

const withHiddenState = (
	column: ListTableColumn<Snippet>,
	hiddenColumns: readonly string[]
): ListTableColumn<Snippet> => ({
	...column,
	isHidden: hiddenColumns.includes(column.id.toString())
})

const baseTableColumns: ListTableColumn<Snippet>[] = [
	{
		id: 'activate',
		render: snippet => <ActivateColumn snippet={snippet} />
	},
	{
		id: 'name',
		title: __('Name', 'code-snippets'),
		isPrimary: true,
		sortedValue: snippet => getSnippetDisplayName(snippet).toLowerCase(),
		render: snippet => <NameColumn snippet={snippet} />
	},
	{
		id: 'type',
		title: __('Type', 'code-snippets'),
		sortedValue: snippet => getSnippetType(snippet),
		render: snippet => <TypeColumn snippet={snippet} />
	},
	{
		id: 'desc',
		title: __('Description', 'code-snippets'),
		render: snippet => <div className="snippet-description-content"><RawHTML>{snippet.desc}</RawHTML></div>
	},
	{
		id: 'tags',
		title: __('Tags', 'code-snippets'),
		render: snippet => <TagsColumn snippet={snippet} />
	},
	{
		id: 'date',
		title: __('Modified', 'code-snippets'),
		sortedValue: snippet => snippet.modified ? new Date(snippet.modified).toISOString() : '',
		render: snippet => <DateColumn snippet={snippet} />
	},
	{
		id: 'priority',
		title: __('Priority', 'code-snippets'),
		sortedValue: snippet => snippet.priority,
		render: snippet => <PriorityColumn snippet={snippet} />
	}
]

export const getTableColumns = (hiddenColumns: readonly string[] = []): ListTableColumn<Snippet>[] =>
	baseTableColumns.map(column => withHiddenState(column, hiddenColumns))
