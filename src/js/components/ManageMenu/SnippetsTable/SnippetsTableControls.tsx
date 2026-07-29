import { __, _x, sprintf } from '@wordpress/i18n'
import React, { Fragment, useMemo } from 'react'
import { useRestAPI } from '../../../hooks/useRestAPI'
import { useSnippetsList } from '../../../hooks/useSnippetsList'
import { handleUnknownError } from '../../../utils/errors'
import { REST_BASES } from '../../../utils/restAPI'
import { buildUrl } from '../../../utils/urls'
import { SubmitButton } from '../../common/SubmitButton'
import { SearchArea } from './SnippetsTableSearch'
import { useFilteredSnippets } from './WithFilteredSnippetsContext'
import { INDEX_STATUS, useSnippetsFilters } from './WithSnippetsTableFilters'
import type { Snippet } from '../../../types/Snippet'
import type { SnippetStatus } from './WithSnippetsTableFilters'

const STATUS_LABELS: [SnippetStatus, string][] = [
	['all', __('All', 'code-snippets')],
	['active', __('Active', 'code-snippets')],
	['inactive', __('Inactive', 'code-snippets')],
	['recently_active', __('Recently Active', 'code-snippets')],
	['locked', __('Locked', 'code-snippets')],
	['unlocked', __('Unlocked', 'code-snippets')],
	['trashed', __('Trashed', 'code-snippets')]
]

const SnippetStatusCounts = () => {
	const { currentStatus, setCurrentStatus } = useSnippetsFilters()
	const { snippetsByStatus } = useFilteredSnippets()

	const visibleStatuses = STATUS_LABELS.filter(([status]) =>
		snippetsByStatus.has(status) && ('unlocked' !== status || snippetsByStatus.has('locked')))

	return (
		<ul className="subsubsub">
			{visibleStatuses.map(([status, label]) =>
				<Fragment key={status}>
					<li className={status}>
						<a
							href={buildUrl(window.location.href, {
								status: INDEX_STATUS === status ? undefined : status
							})}
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
								sprintf(
									_x('(%d)', 'table view count', 'code-snippets'),
									snippetsByStatus.get(status)?.length ?? 0
								)
							}</span>
						</a>
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

interface FilterByTagControlProps {
	visibleSnippets: Snippet[]
}

const FilterByTagControl: React.FC<FilterByTagControlProps> = ({ visibleSnippets }) => {
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
				onChange={event => setCurrentTag(event.target.value)}
			>
				<option value="">{__('All Tags', 'code-snippets')}</option>
				{[...tagsList].map(tag =>
					<option key={tag} value={tag}>{tag}</option>)}
			</select>
		</div>
		: null
}

export interface SnippetsTableNavigationProps {
	which: 'top' | 'bottom'
	visibleSnippets: Snippet[]
}

export const SnippetsTableNavigation: React.FC<SnippetsTableNavigationProps> = ({
	which,
	visibleSnippets
}) =>
	<>
		{'top' === which && <FilterByTagControl visibleSnippets={visibleSnippets} />}
		<ClearRecentlyActiveButton />
		{'top' === which && <SearchArea />}
	</>

export const SnippetsTableToolbar = () =>
	<div className="snippets-table-toolbar">
		<SnippetStatusCounts />
	</div>
