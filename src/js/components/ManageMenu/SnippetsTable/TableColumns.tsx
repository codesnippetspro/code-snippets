import { humanTimeDiff } from '@wordpress/date'
import { RawHTML } from '@wordpress/element'
import { __ } from '@wordpress/i18n'
import React, { Fragment } from 'react'
import { useSnippetsAPI } from '../../../hooks/useSnippetsAPI'
import { useSnippetsList } from '../../../hooks/useSnippetsList'
import { handleUnknownError } from '../../../utils/errors'
import { isNetworkAdmin } from '../../../utils/screen'
import { getSnippetDisplayName, getSnippetEditUrl, getSnippetType } from '../../../utils/snippets/snippets'
import { buildUrl } from '../../../utils/urls'
import { Badge } from '../../common/Badge'
import { SnippetPriorityInput } from '../../common/SnippetPriorityInput'
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

const RunOnceButton: React.FC<ColumnProps> = ({ snippet }) =>
	<a
		className="snippet-execution-button"
		title={__('Run Once', 'code-snippets')}
		href={buildUrl(window.location.href, { action: 'run-once', snippet: snippet.id })}
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

export const PriorityColumn: React.FC<ColumnProps> = ({ snippet }) =>
	<SnippetPriorityInput snippet={snippet} />

const TYPE_COLUMN_LABEL = __('Type', 'code-snippets')
const DESCRIPTION_COLUMN_LABEL = __('Description', 'code-snippets')
const TAGS_COLUMN_LABEL = __('Tags', 'code-snippets')
const MODIFIED_COLUMN_LABEL = __('Modified', 'code-snippets')
const PRIORITY_COLUMN_LABEL = __('Priority', 'code-snippets')

const baseTableColumns: ListTableColumn<Snippet>[] = [
	{
		id: 'activate',
		title: <span className="screen-reader-text">{__('Activate', 'code-snippets')}</span>,
		render: snippet => <ActivateColumn snippet={snippet} />
	},
	{
		id: 'name',
		title: <>
			<span className="desktop-column-title">{__('Name', 'code-snippets')}</span>
			<span className="mobile-column-title">{__('Snippet Name', 'code-snippets')}</span>
		</>,
		isPrimary: true,
		sortedValue: snippet => getSnippetDisplayName(snippet).toLowerCase(),
		render: snippet => <NameColumn snippet={snippet} />
	},
	{
		id: 'type',
		title: TYPE_COLUMN_LABEL,
		mobileLabel: TYPE_COLUMN_LABEL,
		sortedValue: snippet => getSnippetType(snippet),
		render: snippet => <TypeColumn snippet={snippet} />
	},
	{
		id: 'desc',
		title: DESCRIPTION_COLUMN_LABEL,
		mobileLabel: DESCRIPTION_COLUMN_LABEL,
		render: snippet => <div className="snippet-description-content"><RawHTML>{snippet.desc}</RawHTML></div>
	},
	{
		id: 'tags',
		title: TAGS_COLUMN_LABEL,
		mobileLabel: TAGS_COLUMN_LABEL,
		render: snippet => <TagsColumn snippet={snippet} />
	},
	{
		id: 'date',
		title: MODIFIED_COLUMN_LABEL,
		mobileLabel: MODIFIED_COLUMN_LABEL,
		sortedValue: snippet => snippet.modified ? new Date(snippet.modified).toISOString() : '',
		render: snippet => <DateColumn snippet={snippet} />
	},
	{
		id: 'priority',
		title: PRIORITY_COLUMN_LABEL,
		mobileLabel: PRIORITY_COLUMN_LABEL,
		sortedValue: snippet => snippet.priority,
		render: snippet => <PriorityColumn snippet={snippet} />
	}
]

export const getTableColumns = (hiddenColumns: Set<Key>): ListTableColumn<Snippet>[] =>
	baseTableColumns.map(column => ({ ...column, isHidden: hiddenColumns.has(column.id) }))
