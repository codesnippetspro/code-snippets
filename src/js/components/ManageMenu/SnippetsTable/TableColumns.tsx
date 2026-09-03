import { humanTimeDiff } from '@wordpress/date'
import { RawHTML } from '@wordpress/element'
import { __ } from '@wordpress/i18n'
import React, { Fragment } from 'react'
import { useSnippetsAPI } from '../../../hooks/useSnippetsAPI'
import { useSnippetsList } from '../../../hooks/useSnippetsList'
import { handleUnknownError } from '../../../utils/errors'
import { isNetworkAdmin } from '../../../utils/screen'
import { getSnippetDisplayName, getSnippetEditUrl, getSnippetType } from '../../../utils/snippets/snippets'
import { getRunOnceNonce } from '../../../utils/restAPI'
import { buildUrl } from '../../../utils/urls'
import { Badge } from '../../common/Badge'
import { SnippetPriorityInput } from '../../common/snippets/SnippetPriorityInput'
import { Tooltip } from '../../common/Tooltip'
import { RowActions } from './RowActions'
import { useFilteredSnippets } from './WithFilteredSnippetsContext'
import { useSnippetsFilters } from './WithSnippetsTableFilters'
import type { Key } from 'react'
import type { Snippet } from '../../../types/Snippet'
import type { ListTableColumn } from '../../common/ListTable'

interface ColumnProps {
	snippet: Snippet
}

const runOnceUrl = (snippet: Snippet, nonce: string): string =>
	buildUrl(window.location.href, {
		action: 'run-once',
		snippet: snippet.id,
		network: snippet.network ? 'true' : 'false',
		_wpnonce: nonce
	})

// The rendered link carries the nonce from page load; the click reads the one
// the Heartbeat has refreshed since, so a page left open still works.
const RunOnceButton: React.FC<ColumnProps> = ({ snippet }) =>
	<a
		className="snippet-execution-button"
		title={__('Run Once', 'code-snippets')}
		href={runOnceUrl(snippet, window.CODE_SNIPPETS_MANAGE?.runOnceNonce ?? '')}
		onClick={event => {
			event.preventDefault()
			window.location.assign(runOnceUrl(snippet, getRunOnceNonce()))
		}}
	>
		<span className="screen-reader-text">{__('Run Once', 'code-snippets')}</span>
		<span aria-hidden="true">&nbsp;</span>
	</a>

const ActivationSwitch: React.FC<ColumnProps> = ({ snippet }) => {
	const { activate, deactivate } = useSnippetsAPI()
	const { refreshSnippetsList } = useSnippetsList()

	const actionText = snippet.network && !snippet.shared_network
		? snippet.active ? __('Network Deactivate', 'code-snippets') : __('Network Activate', 'code-snippets')
		: snippet.active ? __('Deactivate', 'code-snippets') : __('Activate', 'code-snippets')

	return (
		<input
			id={`snippet-${snippet.id}-switch`}
			type="checkbox"
			role="switch"
			checked={snippet.active}
			aria-checked={snippet.active}
			className="switch"
			title={actionText}
			aria-label={actionText}
			onChange={() => {
				(snippet.active ? deactivate(snippet) : activate(snippet))
					.then(refreshSnippetsList)
					.catch(handleUnknownError)
			}}
		/>
	)
}

export const ActivateColumn: React.FC<ColumnProps> = ({ snippet }) => {
	const { activeByCondition } = useFilteredSnippets()

	if (snippet.trashed) {
		return ''
	}

	switch (snippet.scope) {
		case 'single-use':
			return <RunOnceButton snippet={snippet} />

		case 'condition':
			return (
				<a className="snippet-condition-count" href={getSnippetEditUrl(snippet)}>
					{activeByCondition.get(snippet.id)?.length ?? 0}
				</a>
			)

		default:
			return <ActivationSwitch snippet={snippet} />
	}
}

export const SnippetExtraIcons: React.FC<ColumnProps> = ({ snippet }) =>
	<div className="extra-icons">
		{snippet.locked && (
			<Tooltip
				inline
				end
				label={__('About snippet lock', 'code-snippets')}
				icon={<span className="dashicons dashicons-lock" aria-hidden="true"></span>}
			>
				{__('This snippet is locked and cannot be modified.', 'code-snippets')}
			</Tooltip>)}
	</div>

export const SnippetName: React.FC<ColumnProps> = ({ snippet }) =>
	<>
		{!snippet.trashed && (isNetworkAdmin() || !snippet.network || window.CODE_SNIPPETS_MANAGE?.hasNetworkCap)
			? <a
				href={getSnippetEditUrl(snippet)}
				className="snippet-name"
				title={getSnippetDisplayName(snippet)}
			>
				{getSnippetDisplayName(snippet)}
			</a>
			: <span className="snippet-name" title={getSnippetDisplayName(snippet)}>
				{getSnippetDisplayName(snippet)}
			</span>}

		{snippet.shared_network && <span className="badge">{__('Shared on Network', 'code-snippets')}</span>}
	</>

const NameColumn: React.FC<ColumnProps> = ({ snippet }) =>
	<>
		<SnippetExtraIcons snippet={snippet} />
		<SnippetName snippet={snippet} />
		<RowActions snippet={snippet} />
	</>

export const TypeColumn: React.FC<ColumnProps> = ({ snippet }) => {
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

export const TagsColumn: React.FC<ColumnProps> = ({ snippet }) =>
	snippet.tags.map((tag, index) =>
		<Fragment key={tag}>
			<a key={tag} href={buildUrl(window.location.href, { tag })}>
				{tag}
			</a>
			{index < snippet.tags.length - 1 ? ', ' : ''}
		</Fragment>)

export const DateColumn: React.FC<ColumnProps> = ({ snippet }) =>
	snippet.modified
		? <span className="modified-column-content" title={snippet.modified}>
			<time dateTime={snippet.modified}>
				{humanTimeDiff(snippet.modified, undefined)}
			</time>
		</span>
		: <>&#8212;</>

const baseTableColumns: ListTableColumn<Snippet>[] = [
	{
		id: 'activate',
		title: <span className="screen-reader-text">{__('Activate', 'code-snippets')}</span>,
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
		render: snippet => <SnippetPriorityInput snippet={snippet} />
	}
]

export const getTableColumns = (hiddenColumns: Set<Key>): ListTableColumn<Snippet>[] =>
	baseTableColumns.map(column => ({ ...column, isHidden: hiddenColumns.has(column.id) }))
