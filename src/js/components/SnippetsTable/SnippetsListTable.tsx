import { __, _x, sprintf } from '@wordpress/i18n'
import { useSnippetsList } from '../../hooks/useSnippetsList'
import { Snippet, SNIPPET_STATUSES, SnippetStatus, SnippetType } from '../../types/Snippet'
import { getSnippetType } from '../../utils/snippets/snippets'
import { ListTable, ListTableBulkAction } from '../common/ListTable'
import React, { useMemo, useState } from 'react'
import { addQueryArgs } from '@wordpress/url'
import { SubmitButton } from '../common/SubmitButton'
import { TableColumns } from './TableColumns'

const VIEW_LABELS: Record<SnippetStatus | 'all', string> = {
	all: __('All', 'code-snippets'),
	active: __('Active', 'code-snippets'),
	inactive: __('Inactive', 'code-snippets'),
	recently_activated: __('Recently Activate', 'code-snippets')
}

const actions: ListTableBulkAction<Snippet['id']>[] = [
	{
		name: __('Activate', 'code-snippets'),
		apply: () => Promise.resolve()
	},
	{
		name: __('Deactivate', 'code-snippets'),
		apply: () => Promise.resolve()
	},
	{
		name: __('Clone', 'code-snippets'),
		apply: () => Promise.resolve()
	},
	{
		name: __('Export', 'code-snippets'),
		apply: () => Promise.resolve()
	},
	{
		name: __('Export code', 'code-snippets'),
		apply: () => Promise.resolve()
	},
	{
		name: __('Delete', 'code-snippets'),
		apply: () => Promise.resolve()
	}
]

interface TableViewsProps {
	currentStatus?: SnippetStatus
	setCurrentStatus: (status?: SnippetStatus) => void
}

const TableViews: React.FC<TableViewsProps> = ({ currentStatus, setCurrentStatus }) => {

	return (
		<ul className="subsubsub">

			{SNIPPET_STATUSES.map(status =>
				<li key={status} className={status}>
					<a
						href={addQueryArgs(window.location.href, { status })}
						className={currentStatus === status ? 'current' : undefined}
						onClick={event => {
							event.preventDefault()
							setCurrentStatus(status)
						}}
					>
						{`${VIEW_LABELS[status]} `}
						<span className="count">{
							// translators: %d: number of snippets in the current view.
							sprintf(_x('(%d)', 'table view count', 'code-snippets'), 0)
						}</span>
					</a>
				</li>)}
		</ul>
	)
}

interface ExtraTableNavProps {
	which: 'top' | 'bottom'
	currentTag?: string
	currentStatus?: SnippetStatus
	visibleSnippets: Snippet[]
	setCurrentTag: (tag?: string) => void
}

const ExtraTableNav: React.FC<ExtraTableNavProps> = ({ which, currentTag, setCurrentTag, currentStatus, visibleSnippets }) => {

	const tagsList: Set<string> | undefined = useMemo(
		() =>
			'top' === which
				? visibleSnippets.reduce((tags, snippet) => {
					snippet.tags.forEach(tag => tags.add(tag))
					return tags
				}, new Set<string>())
				: undefined,
		[which, visibleSnippets])

	return (
		<>
			{tagsList ?
				<div className="alignleft actions">
					<select name="tag" onChange={event => {
						setCurrentTag(event.target.value ?? undefined)
					}}>
						<option value="">{__('Show all tags', 'code-snippets')}</option>
						{[...tagsList].map(tag =>
							<option key={tag} value={tag} selected={currentTag === tag}>{tag}</option>)}
					</select>
				</div>
				: null}

			{'recently_activated' === currentStatus
				? <div className="alignleft actions">
					<SubmitButton secondary name="clear-recent-list" text={__('Clear List', 'code-snippets')} />
				</div>
				: null}
		</>
	)
}

export interface SnippetsListTableProps {
	currentType?: SnippetType
	currentStatus?: SnippetStatus
	setCurrentStatus: (status?: SnippetStatus) => void
}

export const SnippetsListTable: React.FC<SnippetsListTableProps> = ({ currentType, currentStatus, setCurrentStatus }) => {
	const { snippetsList } = useSnippetsList()
	const [currentTag, setCurrentTag] = useState<string>()

	const visibleSnippets = useMemo(
		() => snippetsList?.filter(snippet =>
			(!currentType || getSnippetType(snippet) === currentType) &&
			(!currentStatus || (currentStatus === 'active' && snippet.active) || (currentStatus === 'inactive' && !snippet.active)) &&
			(!currentTag || snippet.tags.includes(currentTag))
		) ?? [],
		[snippetsList, currentType, currentTag, currentStatus])

	return (
		<>
			<TableViews currentStatus={currentStatus} setCurrentStatus={setCurrentStatus} />

			<ListTable
				items={visibleSnippets}
				getKey={snippet => snippet.id}
				columns={TableColumns}
				actions={actions}
				extraTableNav={which =>
					<ExtraTableNav {...{ which, visibleSnippets, currentStatus, currentType, currentTag, setCurrentTag }} />}
			/>
		</>
	)
}
