import { CheckboxControl } from '@wordpress/components'
import { __, sprintf } from '@wordpress/i18n'
import React, { useId, useState } from 'react'
import { WithRestAPIContext } from '../../../hooks/useRestAPI'
import { WithSnippetsAPIContext } from '../../../hooks/useSnippetsAPI'
import { isSearchSourceAvailable, useUniversalSearch } from '../../../hooks/useUniversalSearch'
import { SEARCH_FIELDS, SEARCH_PRO_SOURCES, SEARCH_SOURCES } from '../../../types/Search'
import { isLicensed, shouldShowUpsell } from '../../../utils/screen'
import { buildUrl, fetchQueryParam } from '../../../utils/urls'
import { WithCloudSnippetDownloadsContext } from '../../common/cloud/WithCloudSnippetDownloadsContext'
import { ScreenMetaSlot } from '../../common/ScreenMetaSlot'
import { SubnavTabs, getTabFromQuery } from '../../common/SubnavTabs'
import {
	AI_SEARCH_TAB,
	CloudSearchNotice,
	Highlight,
	PRICING_URL,
	SEARCH_LOADING_LABELS,
	SEARCH_SOURCE_LABELS,
	SearchCodePreview,
	SearchItemBadge,
	formatResultCount,
	getSearchItemUrl,
	opensInNewTab
} from '../../common/UniversalSearch/SearchResultParts'
import { SearchResultActions, SnippetActivationSwitch } from './SearchResultActions'
import type { UniversalSearch } from '../../../hooks/useUniversalSearch'
import type { SearchCodeExcerpt, SearchField, SearchGroup, SearchItem, SearchSource } from '../../../types/Search'
import type { SubnavTab } from '../../common/SubnavTabs'

type SourceFilter = 'all' | SearchSource | typeof AI_SEARCH_TAB

const SOURCE_QUERY_PARAM = 'source'

const RESULTS_PER_SOURCE = 50

const AI_SOURCES: readonly SearchSource[] = ['community']

const FIELD_LABELS: Record<SearchField, string> = {
	title: __('Title', 'code-snippets'),
	description: __('Description', 'code-snippets'),
	code: __('Code', 'code-snippets')
}

/**
 * A Pro source is listed without a licence only to show what it offers, which
 * is left out when upgrade prompts are turned off.
 */
const isSourceListed = (source: SearchSource): boolean =>
	isSearchSourceAvailable(source) || SEARCH_PRO_SOURCES.includes(source) && shouldShowUpsell()

const getSourceTabs = (): SubnavTab<SourceFilter>[] => [
	{ name: 'all', label: __('All results', 'code-snippets') },
	...SEARCH_SOURCES
		.filter(isSourceListed)
		.map(source => ({ name: source, label: SEARCH_SOURCE_LABELS[source], pro: SEARCH_PRO_SOURCES.includes(source) })),
	{ name: AI_SEARCH_TAB, label: __('Search with AI', 'code-snippets') }
]

const isLockedTab = (filter: SourceFilter): boolean =>
	'all' !== filter && AI_SEARCH_TAB !== filter && SEARCH_PRO_SOURCES.includes(filter) && !isLicensed()

const isPending = (loadingSources: SearchSource[], filter: SourceFilter): boolean =>
	loadingSources.some(source => 'all' === filter || source === filter)

const getPendingLabels = (loadingSources: SearchSource[], filter: SourceFilter): string[] =>
	loadingSources
		.filter(source => 'all' === filter || source === filter)
		.flatMap(source => SEARCH_LOADING_LABELS[source] ?? [])

const countResults = (groups: SearchGroup[]): number =>
	groups.reduce((total, group) => total + group.total, 0)

interface CodeMatchButtonProps {
	code: SearchCodeExcerpt
	term: string
}

/**
 * Shows the line of code a result matched on, in the same preview as the search bar.
 */
const CodeMatchButton: React.FC<CodeMatchButtonProps> = ({ code, term }) => {
	const [anchor, setAnchor] = useState<HTMLButtonElement | null>(null)
	const [isOpen, setIsOpen] = useState(false)

	return (
		<>
			<button
				ref={setAnchor}
				type="button"
				className="button button-small search-result-code-button"
				aria-expanded={isOpen}
				onClick={() => setIsOpen(!isOpen)}
				onBlur={() => setIsOpen(false)}
				onKeyDown={event => {
					if ('Escape' === event.key) {
						setIsOpen(false)
					}
				}}
			>
				<span className="dashicons dashicons-editor-code" aria-hidden="true"></span>
				{// translators: %s: line number.
					sprintf(__('Line %s', 'code-snippets'), code.line.toLocaleString())}
				<span className="screen-reader-text">{code.text}</span>
			</button>

			{isOpen && anchor && <SearchCodePreview code={code} term={term} anchor={anchor} placement="bottom-end" />}
		</>
	)
}

interface SearchResultProps {
	item: SearchItem
	term: string
	fields: readonly SearchField[]
}

const SearchResult: React.FC<SearchResultProps> = ({ item, term, fields }) => {
	const [isActive, setIsActive] = useState(item.snippet?.active ?? false)
	const url = getSearchItemUrl(item, term)

	// Only the fields being matched in are highlighted, so a highlight always marks a match.
	const highlightIn = (field: SearchField) => fields.includes(field) ? term : ''

	return (
		<li className="search-result">
			<SearchItemBadge type={item.type} />

			<div className="search-result-body">
				<h3 className="search-result-title">
					{item.url
						? <a href={url} {...opensInNewTab(item) && { target: '_blank', rel: 'noopener noreferrer' }}>
							<Highlight text={item.title} term={highlightIn('title')} />
						</a>
						: <Highlight text={item.title} term={highlightIn('title')} />}
				</h3>

				{item.description && (
					<p className="search-result-description">
						<Highlight text={item.description} term={highlightIn('description')} />
					</p>)}

				<div className="row-actions visible">
					<SearchResultActions item={item} />
				</div>
			</div>

			<div className="search-result-aside">
				{item.path && <span className="search-result-path">{item.path}</span>}

				{item.code && <CodeMatchButton code={item.code} term={highlightIn('code')} />}

				{item.snippet && 'cond' !== item.type && (
					<SnippetActivationSwitch item={item} summary={item.snippet} isActive={isActive} setIsActive={setIsActive} />)}
			</div>
		</li>
	)
}

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
				{group.items.map(item => <SearchResult key={item.id} item={item} term={term} fields={fields} />)}
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

interface SearchResultsListProps {
	search: UniversalSearch
	filter: SourceFilter
	fields: readonly SearchField[]
}

const SearchResultsList: React.FC<SearchResultsListProps> = ({ search, filter, fields }) => {
	const { term, isSearchable, groups, loadingSources, hasFailed } = search
	const visibleGroups = groups.filter(group => 'all' === filter || group.source === filter)
	const pendingLabels = getPendingLabels(loadingSources, filter)

	return (
		<div className="search-results-main">
			<div className="screen-reader-text" role="status" aria-live="polite">
				{isSearchable && !hasFailed && (0 < pendingLabels.length
					? pendingLabels.join(' ')
					: formatResultCount(countResults(visibleGroups)))}
			</div>

			{!isSearchable && (
				<p className="search-results-empty">
					{0 < fields.length
						? __('Type at least two characters in the search bar above to search snippets, settings, docs and cloud.', 'code-snippets')
						: __('Choose at least one place to match in.', 'code-snippets')}
				</p>)}

			{hasFailed && (
				<p className="search-results-empty">
					{__('Snippets and settings could not be searched. Reload the page to try again.', 'code-snippets')}
				</p>)}

			{isSearchable && !hasFailed && 0 === visibleGroups.length && !isPending(loadingSources, filter) && (
				<p className="search-results-empty">
					{__('Nothing matched. Try a different term or another source.', 'code-snippets')}
				</p>)}

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

const AiSearchIntro = () =>
	<p className="search-results-ai-intro">
		{__('AI search finds community snippets by what they do, so describe what you need in your own words.', 'code-snippets')}
	</p>

const SearchResultsPage = () => {
	const [query] = useState(() => fetchQueryParam('s') ?? '')
	const [tabs] = useState(getSourceTabs)
	const [currentTab, setCurrentTab] = useState(() => getTabFromQuery(tabs, SOURCE_QUERY_PARAM))
	const [fields, setFields] = useState<readonly SearchField[]>(SEARCH_FIELDS)
	const filter = currentTab.name
	const isAiTab = AI_SEARCH_TAB === filter
	const search = useUniversalSearch({ query, fields, perSource: RESULTS_PER_SOURCE, excerpts: false })

	// An AI search takes longer and asks more of the cloud, so it only runs once its tab is opened.
	const aiSearch = useUniversalSearch({
		query,
		perSource: RESULTS_PER_SOURCE,
		excerpts: false,
		sources: AI_SOURCES,
		method: 'ai',
		enabled: isAiTab
	})

	const shownSearch = isAiTab ? aiSearch : search
	const visibleGroups = isAiTab
		? aiSearch.groups
		: search.groups.filter(group => 'all' === filter || group.source === filter)

	const getTabCount = (tab: SubnavTab<SourceFilter>): number | undefined => {
		if (AI_SEARCH_TAB === tab.name) {
			return isAiTab && 0 === aiSearch.loadingSources.length ? aiSearch.total : undefined
		}

		if (isLockedTab(tab.name)) {
			return undefined
		}

		if ('all' === tab.name) {
			return 0 === search.loadingSources.length ? search.total : undefined
		}

		return search.loadingSources.includes(tab.name)
			? undefined
			: search.groups.find(group => group.source === tab.name)?.total ?? 0
	}

	return (
		<div className="search-results-page">
			<SubnavTabs
				className="search-results-nav"
				ariaLabel={__('Search result sources', 'code-snippets')}
				tabs={tabs}
				currentTab={currentTab}
				setCurrentTab={setCurrentTab}
				getTabCount={search.isSearchable ? getTabCount : undefined}
				queryParamName={SOURCE_QUERY_PARAM}
			/>

			<ScreenMetaSlot />

			<div className="snippets-page-header">
				<h1>
					{search.isSearchable
						// translators: %s: search query.
						? sprintf(__('Search results for “%s”', 'code-snippets'), search.term)
						: __('Search', 'code-snippets')}
				</h1>

				{search.isSearchable && !isLockedTab(filter) && !isPending(shownSearch.loadingSources, isAiTab ? 'all' : filter) && (
					<span className="search-results-total">{formatResultCount(countResults(visibleGroups))}</span>)}
			</div>

			<hr className="wp-header-end" />

			{isLockedTab(filter) && <CloudLibraryUpsell query={query} />}

			{isAiTab && <AiSearchIntro />}

			{!isAiTab && !isLockedTab(filter) && <>
				<FieldFilters fields={fields} setFields={setFields} />
				{'all' === filter && isLicensed() && <CloudSearchNotice />}
			</>}

			{!isLockedTab(filter) && (
				<SearchResultsList
					search={shownSearch}
					filter={isAiTab ? 'all' : filter}
					fields={isAiTab ? SEARCH_FIELDS : fields}
				/>)}
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
