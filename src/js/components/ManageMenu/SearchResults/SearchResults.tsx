import { CheckboxControl } from '@wordpress/components'
import { __, sprintf } from '@wordpress/i18n'
import React, { useId, useState } from 'react'
import { WithRestAPIContext } from '../../../hooks/useRestAPI'
import { WithSnippetsAPIContext } from '../../../hooks/useSnippetsAPI'
import { isSearchSourceAvailable, useUniversalSearch } from '../../../hooks/useUniversalSearch'
import { SEARCH_FIELDS, SEARCH_PRO_SOURCES, SEARCH_SOURCES } from '../../../types/Search'
import { isLicensed, shouldShowUpsell } from '../../../utils/screen'
import { buildUrl, fetchQueryParam } from '../../../utils/urls'
import { WithCloudSnippetDownloadsContext } from '../common/cloud/WithCloudSnippetDownloadsContext'
import { ScreenMetaSlot } from '../../common/ScreenMetaSlot'
import { SubnavTabs, getTabFromQuery } from '../../common/SubnavTabs'
import { AI_SEARCH_TAB, CloudSearchNotice, PRICING_URL, SEARCH_LOADING_LABELS, SEARCH_SOURCE_LABELS, formatResultCount } from '../common/UniversalSearch/SearchResultParts'
import { SearchResult } from './SearchResult'
import type { ReactNode } from 'react'
import type { UniversalSearch } from '../../../hooks/useUniversalSearch'
import type { SearchField, SearchGroup, SearchSource, SearchTab } from '../../../types/Search'

const RESULTS_PER_SOURCE = 50

const SOURCE_QUERY_PARAM = 'source'

const FIELD_LABELS: Record<SearchField, string> = {
	title: __('Title', 'code-snippets'),
	description: __('Description', 'code-snippets'),
	code: __('Code', 'code-snippets')
}

const isPending = (loadingSources: SearchSource[], currentTab: SearchTab): boolean =>
	'all' === currentTab.name
		? 1 < loadingSources.length
		: 'ai' !== currentTab.name && loadingSources.includes(currentTab.name)

const countResults = (groups: SearchGroup[]): number =>
	groups.reduce((total, group) => total + group.total, 0)

const TABS: SearchTab[] = [
	{
		name: 'all',
		label: __('All results', 'code-snippets'),
		locked: false
	},
	...SEARCH_SOURCES
		.filter(source =>
			isSearchSourceAvailable(source) || SEARCH_PRO_SOURCES.includes(source) && shouldShowUpsell())
		.map(name => ({
			name,
			label: SEARCH_SOURCE_LABELS[name],
			pro: SEARCH_PRO_SOURCES.includes(name),
			locked: SEARCH_PRO_SOURCES.includes(name) && !isLicensed()
		})),
	{
		name: AI_SEARCH_TAB,
		label: __('Search with AI', 'code-snippets'),
		locked: false
	}
]

interface SearchResultGroupProps {
	group: SearchGroup
	term: string
	fields: readonly SearchField[]
}

const SearchResultGroup: React.FC<SearchResultGroupProps> = ({ group, term, fields }) => {
	const headingId = useId()

	return (
		<section className="search-results-group" aria-labelledby={headingId}>
			<h2 id={headingId} className="search-results-group-heading">
				{SEARCH_SOURCE_LABELS[group.source]}
				<span className="search-results-count">{group.total.toLocaleString()}</span>
			</h2>

			<ul className="search-results-list">
				{group.items.map(item =>
					<SearchResult key={item.id} item={item} term={term} fields={fields} />)}
			</ul>

			{group.total > group.items.length && (
				<p className="search-results-truncated">
					{sprintf(
						// translators: 1: number of results shown, 2: total number of results.
						__('Showing the first %1$s of %2$s results. Search for something more specific to narrow them down.', 'code-snippets'),
						group.items.length.toLocaleString(),
						group.total.toLocaleString()
					)}
				</p>)}
		</section>
	)
}

interface FieldFiltersProps {
	fields: readonly SearchField[]
	setFields: (fields: SearchField[]) => void
}

const FieldFilters: React.FC<FieldFiltersProps> = ({ fields, setFields }) =>
	<fieldset className="search-results-fields">
		<legend>{__('Match in:', 'code-snippets')}</legend>
		{SEARCH_FIELDS.map(field =>
			<CheckboxControl
				key={field}
				__nextHasNoMarginBottom
				label={FIELD_LABELS[field]}
				checked={fields.includes(field)}
				onChange={checked => setFields(SEARCH_FIELDS.filter(other =>
					other === field ? checked : fields.includes(other)))}
			/>)}
	</fieldset>

const getSearchResultMessage = (
	{ isSearchable, hasFailed, loadingSources }: UniversalSearch,
	fields: readonly SearchField[],
	visibleGroups: SearchGroup[],
	currentTab: SearchTab
): ReactNode | undefined => {
	if (!isSearchable) {
		return 0 < fields.length
			? __('Type at least two characters in the search bar above to search snippets, settings, docs and cloud.', 'code-snippets')
			: __('Choose at least one place to match in.', 'code-snippets')
	}

	if (hasFailed) {
		return __('Snippets and settings could not be searched. Reload the page to try again.', 'code-snippets')
	}

	if (0 === visibleGroups.length && !isPending(loadingSources, currentTab)) {
		return __('Nothing matched. Try a different term or another source.', 'code-snippets')
	}

	return undefined
}

interface SearchResultsListProps {
	search: UniversalSearch
	currentTab: SearchTab
	fields: readonly SearchField[]
}

const SearchResultsList: React.FC<SearchResultsListProps> = ({ search, currentTab, fields }) => {
	const { term, isSearchable, groups, loadingSources, hasFailed } = search

	const visibleGroups = 'all' === currentTab.name ? groups : groups.filter(group => group.source === currentTab.name)

	const pendingLabels = loadingSources
		.filter(source => 'all' === currentTab.name || source === currentTab.name)
		.flatMap(source => SEARCH_LOADING_LABELS[source] ?? [])

	const message = getSearchResultMessage(search, fields, visibleGroups, currentTab)

	return (
		<div className="search-results-main">
			<div className="screen-reader-text" role="status" aria-live="polite">
				{isSearchable && !hasFailed && (0 < pendingLabels.length
					? pendingLabels.join(' ')
					: formatResultCount(countResults(visibleGroups)))}
			</div>

			{message && <p className="search-results-empty">{message}</p>}

			{visibleGroups.map(group =>
				<SearchResultGroup key={group.source} group={group} term={term} fields={fields} />)}

			{pendingLabels.map(label =>
				<p key={label} className="search-results-pending">{label}</p>)}
		</div>
	)
}

interface CloudLibraryUpsellProps {
	query: string
}

/**
 * Shown in place of Cloud Library results on a site without Pro.
 */
const CloudLibraryUpsell: React.FC<CloudLibraryUpsellProps> = ({ query }) =>
	<div className="demo-upsell search-results-upsell">
		<h2 className="demo-upsell__title">
			{// translators: %s: search query.
				sprintf(__('Search your own Cloud Library for “%s”', 'code-snippets'), query.trim())}
		</h2>

		<p>
			{__('With Code Snippets Pro, the search bar also looks through every snippet saved to your Cloud Library, including private snippets and those synced from your other sites, so you can find and download them from here.', 'code-snippets')}
		</p>

		<div className="demo-upsell__actions">
			<a className="button button-primary button-large" href={PRICING_URL} target="_blank" rel="noopener noreferrer">
				{__('Upgrade to Pro', 'code-snippets')}
				<span className="screen-reader-text">{__('(opens in a new tab)', 'code-snippets')}</span>
			</a>

			<a
				className="button button-secondary button-large"
				href={buildUrl(window.CODE_SNIPPETS?.urls.manage, { subpage: 'cloud-library' })}
			>
				{__('See the Cloud Library', 'code-snippets')}
			</a>
		</div>
	</div>

interface AiSearchTabProps {
	isLocked: boolean
	search: UniversalSearch
}

const AiSearchTab = ({ isLocked, search }: AiSearchTabProps) =>
	<>
		<p className="search-results-ai-intro">
			{__('AI search finds community snippets by what they do – describe what you need in your own words.', 'code-snippets')}
		</p>

		{!isLocked && <SearchResultsList search={search} currentTab={TABS[0]} fields={SEARCH_FIELDS} />}
	</>

interface SearchTabProps {
	search: UniversalSearch
	fields: readonly SearchField[]
	setFields: (fields: readonly SearchField[]) => void
	currentTab: SearchTab
}

const SearchTab = ({ currentTab, search, fields, setFields }: SearchTabProps) =>
	currentTab.locked
		? null
		: <>
			<FieldFilters fields={fields} setFields={setFields} />

			{'all' === currentTab.name && isLicensed() && <CloudSearchNotice />}

			<SearchResultsList search={search} currentTab={currentTab} fields={fields} />
		</>

interface TabCountProps {
	tab: SearchTab
	search: UniversalSearch
	aiSearch: UniversalSearch
}

const getTabCount = ({ tab, search, aiSearch }: TabCountProps): number | undefined => {
	if (AI_SEARCH_TAB === tab.name) {
		return 0 === aiSearch.loadingSources.length ? aiSearch.total : undefined
	}

	if (tab.locked) {
		return undefined
	}

	if ('all' === tab.name) {
		return 0 === search.loadingSources.length ? search.total : undefined
	}

	return search.loadingSources.includes(tab.name)
		? undefined
		: search.groups.find(group => group.source === tab.name)?.total ?? 0
}

interface SearchResultsHeaderProps {
	search: UniversalSearch
	currentTab: SearchTab
	shownSearch: UniversalSearch
	visibleGroups: SearchGroup[]
}

const SearchResultsHeader: React.FC<SearchResultsHeaderProps> = ({ search, currentTab, shownSearch, visibleGroups }) =>
	<div className="snippets-page-header">
		<h1>
			{search.isSearchable
				// translators: %s: search query.
				? sprintf(__('Search results for “%s”', 'code-snippets'), search.term)
				: __('Search', 'code-snippets')}
		</h1>

		{search.isSearchable && !currentTab.locked &&
			!isPending(shownSearch.loadingSources, currentTab.name === AI_SEARCH_TAB ? TABS[0] : currentTab) && (
			<span className="search-results-total">{formatResultCount(countResults(visibleGroups))}</span>)}
	</div>

const SearchResultsPage = () => {
	const query = fetchQueryParam('s') ?? ''

	const [currentTab, setCurrentTab] = useState(() => getTabFromQuery(TABS, SOURCE_QUERY_PARAM))
	const [fields, setFields] = useState<readonly SearchField[]>(SEARCH_FIELDS)

	const search = useUniversalSearch({ query, fields, perSource: RESULTS_PER_SOURCE, excerpts: false })

	// An AI search takes longer and asks more of the cloud, so it only runs once its tab is opened.
	const aiSearch = useUniversalSearch({
		query,
		perSource: RESULTS_PER_SOURCE,
		excerpts: false,
		sources: ['community'],
		method: 'ai',
		enabled: AI_SEARCH_TAB === currentTab.name
	})

	const shownSearch = AI_SEARCH_TAB === currentTab.name ? aiSearch : search
	const visibleGroups = AI_SEARCH_TAB === currentTab.name
		? aiSearch.groups
		: search.groups.filter(group => 'all' === currentTab.name || group.source === currentTab.name)

	return (
		<div className="search-results-page">
			<SubnavTabs
				className="search-results-nav"
				ariaLabel={__('Search result sources', 'code-snippets')}
				tabs={TABS}
				currentTab={currentTab}
				setCurrentTab={setCurrentTab}
				getTabCount={search.isSearchable ? tab => getTabCount({ tab, search, aiSearch }) : undefined}
				queryParamName={SOURCE_QUERY_PARAM}
			/>

			<ScreenMetaSlot />

			<SearchResultsHeader {...{ search, currentTab, shownSearch, visibleGroups }} />

			<hr className="wp-header-end" />

			{currentTab.locked && <CloudLibraryUpsell query={query} />}

			{AI_SEARCH_TAB === currentTab.name
				? <AiSearchTab search={aiSearch} isLocked={currentTab.locked} />
				: <SearchTab search={search} currentTab={currentTab} fields={fields} setFields={setFields} />}
		</div>
	)
}

export const SearchResults = () =>
	<WithRestAPIContext>
		<WithSnippetsAPIContext>
			<WithCloudSnippetDownloadsContext>
				<SearchResultsPage />
			</WithCloudSnippetDownloadsContext>
		</WithSnippetsAPIContext>
	</WithRestAPIContext>
