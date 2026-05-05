import { __, _n, _x, sprintf } from '@wordpress/i18n'
import React, { Fragment, useEffect, useMemo, useState } from 'react'
import classnames from 'classnames'
import { createInterpolateElement } from '@wordpress/element'
import { useRestAPI } from '../../../hooks/useRestAPI'
import { useSnippetsList } from '../../../hooks/useSnippetsList'
import { handleUnknownError } from '../../../utils/errors'
import { downloadBulkSnippetExportFile } from '../../../utils/files'
import { REST_BASES } from '../../../utils/restAPI'
import { getSnippetDisplayName, getSnippetType } from '../../../utils/snippets/snippets'
import { buildUrl } from '../../../utils/urls'
import { ListTable } from '../../common/ListTable'
import { SubmitButton } from '../../common/SubmitButton'
import { INDEX_STATUS, useSnippetsFilters } from './WithSnippetsTableFilters'
import { useFilteredSnippets } from './WithFilteredSnippetsContext'
import { getTableColumns } from './TableColumns'
import type { SnippetStatus} from './WithSnippetsTableFilters'
import type { ListTableBulkAction } from '../../common/ListTable'
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

const BULK_DOWNLOAD_ACTION = 'bulk-download'
const INDIVIDUAL_DOWNLOAD_DELAY_MS = 200

const appendHiddenField = (form: HTMLFormElement, name: string, value: string) => {
	const input = document.createElement('input')
	input.type = 'hidden'
	input.name = name
	input.value = value
	form.appendChild(input)
}

const submitBulkSnippetDownload = (snippets: readonly Snippet[]): Promise<void> => {
	if (0 === snippets.length) {
		return Promise.resolve()
	}

	const form = document.createElement('form')

	form.method = 'post'
	form.action = window.location.href
	form.hidden = true

	appendHiddenField(form, 'code_snippets_action', BULK_DOWNLOAD_ACTION)
	appendHiddenField(form, 'code_snippets_bulk_download_nonce', window.CODE_SNIPPETS_MANAGE?.bulkDownloadNonce ?? '')
	appendHiddenField(
		form,
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
				<label className="screen-reader-text" htmlFor="snippets_search">{__('Search Snippets:', 'code-snippets')}</label>
				<input
					type="search"
					id="snippets_search"
					name="s"
					value={searchQuery ?? ''}
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
			{ a: <a href={buildUrl(window.CODE_SNIPPETS?.urls.addNew, { type: currentType })} /> }
		)}
		</>
}

const useBulkActions = (allSnippets: Snippet[]): ListTableBulkAction<Snippet['id']>[] =>
{
	return useMemo(
		() => [
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
				apply: (selected: Set<Snippet['id']>) => {
					downloadBulkSnippetExportFile(
						allSnippets.filter(snippet => selected.has(snippet.id))
					)
					return Promise.resolve()
				}
			},
			{
				name: __('Download', 'code-snippets'),
				apply: (selected: Set<Snippet['id']>) => {
					const selectedSnippets = allSnippets.filter(snippet => selected.has(snippet.id))

					if (1 < selectedSnippets.length && !window.CODE_SNIPPETS_MANAGE?.supportsZipDownloads) {
						return submitBulkSnippetDownloadsIndividually(selectedSnippets)
					}

					return submitBulkSnippetDownload(selectedSnippets)
				}
			},
			{
				name: __('Trash', 'code-snippets'),
				apply: () => Promise.resolve()
			}
		],
		[allSnippets]
	)
}

export const SnippetsListTable: React.FC = () => {
	const { currentStatus, setCurrentStatus } = useSnippetsFilters()
	const { snippetsByStatus } = useFilteredSnippets()
	const { hiddenColumns, truncateRowValues } = useManageTableSettings()

	const allSnippets = useMemo(() => snippetsByStatus.get('all') ?? [], [snippetsByStatus])
	const totalItems = snippetsByStatus.get(currentStatus)?.length ?? 0
	const itemsPerPage = window.CODE_SNIPPETS_MANAGE?.snippetsPerPage

	const columns = useMemo(() => getTableColumns(hiddenColumns), [hiddenColumns])
	const actions = useBulkActions(allSnippets)

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
				items={snippetsByStatus.get(currentStatus) ?? []}
				getKey={snippet => snippet.id}
				ariaLabel={__('Snippets list', 'code-snippets')}
				getCheckboxAriaLabel={(snippet: Snippet) => sprintf(
					// translators: %s: Snippet name.
					__('Select %s', 'code-snippets'),
					getSnippetDisplayName(snippet)
				)}
				className={classnames({ 'truncate-row-values': truncateRowValues })}
				columns={columns}
				actions={actions}
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
