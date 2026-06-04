import { __, _n, _x, sprintf } from '@wordpress/i18n'
import React, { Fragment, useEffect, useMemo, useState } from 'react'
import classnames from 'classnames'
import { createInterpolateElement } from '@wordpress/element'
import { useRestAPI } from '../../../hooks/useRestAPI'
import { useSnippetsAPI } from '../../../hooks/useSnippetsAPI'
import { useSnippetsList } from '../../../hooks/useSnippetsList'
import { handleUnknownError } from '../../../utils/errors'
import { downloadBulkSnippetExportFile } from '../../../utils/files'
import { REST_BASES } from '../../../utils/restAPI'
import { cloneSnippetObject, getSnippetType } from '../../../utils/snippets/snippets'
import { buildUrl } from '../../../utils/urls'
import { ListTable } from '../../common/ListTable'
import { SubmitButton } from '../../common/SubmitButton'
import { INDEX_STATUS, useSnippetsFilters } from './WithSnippetsTableFilters'
import { useFilteredSnippets } from './WithFilteredSnippetsContext'
import { getTableColumns } from './TableColumns'
import type { ListTableAction } from '../../common/ListTable'
import type { SnippetStatus } from './WithSnippetsTableFilters'
import type { Snippet } from '../../../types/Snippet'

const STATUS_LABELS: [SnippetStatus, string][] = [
	['all', __('All', 'code-snippets')],
	['active', __('Active', 'code-snippets')],
	['inactive', __('Inactive', 'code-snippets')],
	['recently_active', __('Recently Active', 'code-snippets')],
	['locked', __('Locked', 'code-snippets')],
	['unlocked', __('Unlocked', 'code-snippets')],
	['trashed', __('Trashed', 'code-snippets')]
]

type SnippetsTableAction =
	'activate' | 'deactivate' |
	'clone' | 'export' | 'download' |
	'trash' | 'restore' | 'delete'

const BULK_ACTIONS: ListTableAction<SnippetsTableAction>[] = [
	{ key: 'activate', label: __('Activate', 'code-snippets') },
	{ key: 'deactivate', label: __('Deactivate', 'code-snippets') },
	{ key: 'clone', label: __('Clone', 'code-snippets') },
	{ key: 'export', label: __('Export', 'code-snippets') },
	{ key: 'download', label: __('Download', 'code-snippets') },
	{ key: 'trash', label: __('Trash', 'code-snippets') }
]

const TRASHED_BULK_ACTIONS: ListTableAction<SnippetsTableAction>[] = [
	{ key: 'restore', label: __('Restore', 'code-snippets') },
	{ key: 'delete', label: __('Delete Permanently', 'code-snippets') }
]

const BULK_DOWNLOAD_ACTION = 'bulk-download'
const INDIVIDUAL_DOWNLOAD_DELAY_MS = 200

const submitBulkSnippetDownload = (snippets: readonly Snippet[]): Promise<void> => {
	if (0 === snippets.length) {
		return Promise.resolve()
	}

	const form = document.createElement('form')

	const appendHiddenField = (name: string, value: string) => {
		const input = document.createElement('input')
		input.type = 'hidden'
		input.name = name
		input.value = value
		form.appendChild(input)
	}

	form.method = 'post'
	form.action = window.location.href
	form.hidden = true

	appendHiddenField('code_snippets_action', BULK_DOWNLOAD_ACTION)
	appendHiddenField('code_snippets_bulk_download_nonce', window.CODE_SNIPPETS_MANAGE?.bulkDownloadNonce ?? '')
	appendHiddenField(
		'snippets',
		JSON.stringify(snippets.map(({ id, network }) => ({ id, network })))
	)

	document.body.appendChild(form)
	form.submit()

	window.setTimeout(() => {
		form.remove()
	}, 0)

	return Promise.resolve()
}

const submitBulkSnippetDownloadsIndividually = (snippets: readonly Snippet[]): Promise<void> =>
	snippets.reduce(
		(promise, snippet) =>
			promise.then(
				() =>
					new Promise<void>(resolve => {
						void submitBulkSnippetDownload([snippet])
						window.setTimeout(resolve, INDIVIDUAL_DOWNLOAD_DELAY_MS)
					})
			),
		Promise.resolve()
	)

const SnippetStatusCounts = () => {
	const { currentStatus, setCurrentStatus } = useSnippetsFilters()
	const { snippetsByStatus } = useFilteredSnippets()

	const visibleStatuses = STATUS_LABELS.filter(([status]) =>
		snippetsByStatus.has(status) && ('unlocked' !== status || snippetsByStatus.has('locked')))

	return (
		<ul className="subsubsub">
			{visibleStatuses.map(([status, label], index) =>
				<Fragment key={status}>
					<li className={status}>
						<a
							href={buildUrl(window.location.href, { status: INDEX_STATUS === status ? undefined : status })}
							className={currentStatus === status ? 'current' : undefined}
							aria-current={currentStatus === status ? 'page' : undefined}
							onClick={event => {
								event.preventDefault()
								setCurrentStatus(status)
							}}
						>
							{`${label} `}
							<span className="count">{
								// translators: %d: number of snippets in the current view.
								sprintf(_x('(%d)', 'table view count', 'code-snippets'), snippetsByStatus.get(status)?.length ?? 0)
							}</span>
						</a>
						{index < visibleStatuses.length - 1 && ' | '}
					</li>
				</Fragment>)}
		</ul>
	)
}

const ClearRecentlyActiveButton: React.FC = () => {
	const { api } = useRestAPI()
	const { refreshSnippetsList } = useSnippetsList()
	const { currentStatus } = useSnippetsFilters()

	return 'recently_active' === currentStatus
		? <div className="alignleft actions">
			<SubmitButton
				secondary
				name="clear-recent-list"
				text={__('Clear List', 'code-snippets')}
				onClick={event => {
					event.preventDefault()
					api.del(REST_BASES.recentlyActive)
						.then(refreshSnippetsList)
						.catch(handleUnknownError)
				}}
			/>
		</div>
		: null
}

interface ExtraTableNavProps {
	visibleSnippets: Snippet[]
}

interface ManageTableSettings {
	hiddenColumns: Set<string>
	truncateRowValues: boolean
}

const useManageTableSettings = (): ManageTableSettings => {
	const [hiddenColumns, setHiddenColumns] = useState(() => new Set(window.CODE_SNIPPETS_MANAGE?.hiddenColumns ?? []))
	const [truncateRowValues, setTruncateRowValues] = useState(
		() => 0 !== Number(window.CODE_SNIPPETS_MANAGE?.truncateRowValues ?? 1)
	)

	useEffect(() => {
		const screenOptions = document.getElementById('adv-settings')

		if (!screenOptions) {
			return
		}

		const updateHiddenColumns = () => {
			setHiddenColumns(
				new Set(Array.from(screenOptions.querySelectorAll<HTMLInputElement>('.hide-column-tog:not(:checked)'))
					.map(toggle => toggle.value))
			)

			setTruncateRowValues(
				screenOptions.querySelector<HTMLInputElement>('#snippets-table-truncate-row-values')?.checked ?? true
			)
		}

		updateHiddenColumns()
		screenOptions.addEventListener('change', updateHiddenColumns)

		return () => {
			screenOptions.removeEventListener('change', updateHiddenColumns)
		}
	}, [])

	return { hiddenColumns, truncateRowValues }
}

const FilterByTagControl: React.FC<ExtraTableNavProps> = ({ visibleSnippets }) => {
	const { currentTag, setCurrentTag } = useSnippetsFilters()

	const tagsList: Set<string> = useMemo(
		() => visibleSnippets.reduce((tags, snippet) => {
			snippet.tags.forEach(tag => tags.add(tag))
			return tags
		}, new Set<string>()),
		[visibleSnippets])

	return 0 < tagsList.size
		? <div className="alignleft actions">
			<label htmlFor="snippets-tag-filter" className="screen-reader-text">
				{__('Filter snippets by tag', 'code-snippets')}
			</label>
			<select
				id="snippets-tag-filter"
				name="tag"
				value={currentTag}
				aria-label={__('Filter snippets by tag', 'code-snippets')}
				onChange={event => setCurrentTag(event.target.value)}
			>
				<option value="">{__('Show all tags', 'code-snippets')}</option>
				{[...tagsList].map(tag =>
					<option key={tag} value={tag}>{tag}</option>)}
			</select>
		</div>
		: null
}

const SearchBox = () => {
	const { searchQuery, setSearchQuery } = useSnippetsFilters()

	return (
		<search aria-label={__('Search Snippets', 'code-snippets')}>
			<p className="search-box">
				<input
					type="search"
					id="snippets_search"
					name="s"
					value={searchQuery ?? ''}
					aria-label={__('Search Snippets:', 'code-snippets')}
					onChange={event => setSearchQuery(event.target.value)}
					placeholder={__('Search snippets', 'code-snippets')}
				/>
			</p>
		</search>
	)
}

const NoItemsMessage = () => {
	const { currentType, currentTag, searchQuery } = useSnippetsFilters()

	return searchQuery || currentTag
		? <>
			{__('No snippets were found matching the current search query.', 'code-snippets')}
			{__(' Please enter a new query or use the "Clear Filters" button above.', 'code-snippets')}
		</>
		: <>{createInterpolateElement(
			currentType
				? __("It looks like you don't have any snippets of this type. <a>Perhaps you would like to add a new one?</a>", 'code-snippets')
				: __("It looks like you don't have any snippets. <a>Perhaps you would like to add a new one?</a>", 'code-snippets'),
			{
				// eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
				a: <a href={buildUrl(window.CODE_SNIPPETS?.urls.addNew, { type: currentType })} />
			}
		)}
		</>
}

const applyAndRefresh = async (
	targets: Snippet[],
	action: (snippet: Snippet) => Promise<Snippet> | Promise<void>,
	refresh: () => Promise<void>
): Promise<void> => {
	if (0 < targets.length) {
		for (const snippet of targets) {
			await action(snippet).catch(handleUnknownError)
		}

		await refresh()
	}
}

const useApplyBulkAction = (
	allSnippets: Snippet[]
): (action: SnippetsTableAction, selected: Set<Snippet['id']>) => Promise<void> => {
	const api = useSnippetsAPI()
	const { refreshSnippetsList } = useSnippetsList()

	return async (action, selected) => {
		switch (action) {
			case 'activate':
				await applyAndRefresh(
					allSnippets.filter(snippet => selected.has(snippet.id) && !snippet.active),
					snippet => api.activate({ id: snippet.id, network: snippet.network }),
					refreshSnippetsList)
				break

			case 'deactivate':
				await applyAndRefresh(
					allSnippets.filter(snippet => selected.has(snippet.id) && snippet.active),
					snippet => api.deactivate({ id: snippet.id, network: snippet.network }),
					refreshSnippetsList)
				break

			case 'clone':
				await applyAndRefresh(
					allSnippets.filter(snippet => selected.has(snippet.id) && !snippet.trashed),
					snippet => api.create(cloneSnippetObject(snippet)),
					refreshSnippetsList)
				break

			case 'export':
				downloadBulkSnippetExportFile(allSnippets.filter(snippet => selected.has(snippet.id)))
				break

			case 'download': {
				const selectedSnippets = allSnippets.filter(snippet => selected.has(snippet.id))

				return 1 < selectedSnippets.length && !window.CODE_SNIPPETS_MANAGE?.supportsZipDownloads
					? submitBulkSnippetDownloadsIndividually(selectedSnippets)
					: submitBulkSnippetDownload(selectedSnippets)
			}

			case 'trash':
			case 'delete':
				await applyAndRefresh(
					allSnippets.filter(snippet => selected.has(snippet.id) && snippet.trashed),
					snippet => api.delete({ id: snippet.id, network: snippet.network }),
					refreshSnippetsList)
				break
		}
	}
}

export const SnippetsListTable: React.FC = () => {
	const { snippetsByStatus } = useFilteredSnippets()
	const { currentStatus, setCurrentStatus } = useSnippetsFilters()
	const { hiddenColumns, truncateRowValues } = useManageTableSettings()

	const currentSnippets = useMemo(
		() => snippetsByStatus.get(currentStatus) ?? [],
		[snippetsByStatus, currentStatus]
	)
	const totalItems = currentSnippets.length
	const itemsPerPage = window.CODE_SNIPPETS_MANAGE?.snippetsPerPage

	const columns = useMemo(() => getTableColumns(hiddenColumns), [hiddenColumns])
	const applyBulkAction = useApplyBulkAction(currentSnippets)

	useEffect(() => {
		if (INDEX_STATUS !== currentStatus && !snippetsByStatus.has(currentStatus)) {
			setCurrentStatus(INDEX_STATUS)
		}
	}, [currentStatus, setCurrentStatus, snippetsByStatus])

	return (
		<>
			<SnippetStatusCounts />
			<SearchBox />

			<p className="screen-reader-text" role="status" aria-live="polite">
				{sprintf(
					// translators: %d: number of snippets matching current filters.
					_n('%d snippet found.', '%d snippets found.', totalItems),
					totalItems
				)}
			</p>

			<ListTable
				items={currentSnippets}
				getKey={snippet => snippet.id}
				className={classnames({ 'truncate-row-values': truncateRowValues })}
				columns={columns}
				actions={'trashed' === currentStatus ? TRASHED_BULK_ACTIONS : BULK_ACTIONS}
				doAction={applyBulkAction}
				totalPages={itemsPerPage && Math.ceil(totalItems / itemsPerPage)}
				extraTableNav={which =>
					<>
						{'top' === which && <FilterByTagControl visibleSnippets={snippetsByStatus.get('all') ?? []} />}
						<ClearRecentlyActiveButton />
					</>}
				rowClassName={snippet =>
					`snippet ${snippet.active ? 'active' : 'inactive'}-snippet ${getSnippetType(snippet)}-snippet ${snippet.scope}-snippet`}
				noItems={<NoItemsMessage />}
			/>
		</>
	)
}
