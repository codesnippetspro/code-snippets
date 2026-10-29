import { __, _n, sprintf } from '@wordpress/i18n'
import classnames from 'classnames'
import React, { useEffect, useId, useRef, useState } from 'react'
import { WithRestAPIContext } from '../../../../hooks/useRestAPI'
import { isLocalSearchSource, useUniversalSearch } from '../../../../hooks/useUniversalSearch'
import { REST_BASES } from '../../../../utils/restAPI'
import { fetchQueryParam } from '../../../../utils/urls'
import { Badge } from '../../../common/Badge'
import { AI_SEARCH_TAB, CloudSearchNotice, Highlight, SEARCH_LOADING_LABELS, SEARCH_SOURCE_LABELS, SearchCodePreview, SearchItemBadge, formatResultCount, getSearchItemUrl, getSearchPageUrl, opensInNewTab } from './SearchResultParts'
import type { SearchGroup, SearchItem, SearchSource } from '../../../../types/Search'

type SearchScope = 'all' | SearchSource

const SNIPPETS_LIMIT = 3
const GROUP_LIMIT = 2

// AI searches only the community, and waits for typing to settle for longer, as each search asks more of the cloud.
const AI_SOURCES: readonly SearchSource[] = ['community']
const AI_LIMIT = 3
const AI_DELAY_MS = 600

// How many results are fetched for each source, which is as many as a group can list.
const RESULTS_PER_SOURCE = 50

interface VisibleGroup {
	group: SearchGroup
	items: SearchItem[]
}

/**
 * Groups are cut down to their top results, with a link to the rest. Choosing a
 * scope shows everything that was fetched for that source.
 */
const getVisibleGroups = (groups: SearchGroup[], scope: SearchScope): VisibleGroup[] =>
	groups
		.filter(group => 'all' === scope || group.source === scope)
		.map(group => {
			const limit = 'snippets' === group.source ? SNIPPETS_LIMIT : GROUP_LIMIT
			return { group, items: 'all' === scope ? group.items.slice(0, limit) : group.items }
		})

const getScopeSource = (scope: SearchScope): SearchSource | undefined =>
	'all' === scope ? undefined : scope

const isTypingTarget = (target: EventTarget | null): boolean =>
	target instanceof HTMLElement &&
	(target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName))

const isShortcut = (event: KeyboardEvent): boolean =>
	'/' === event.key &&
	!event.ctrlKey && !event.metaKey && !event.altKey &&
	!event.defaultPrevented && !event.isComposing &&
	!isTypingTarget(event.target) &&
	// A modal dialog keeps the keyboard to itself, so the shortcut must not reach behind it.
	null === document.querySelector('[aria-modal="true"]')

const openSearchItem = (item: SearchItem, query: string) => {
	const url = getSearchItemUrl(item, query)

	if (opensInNewTab(item)) {
		window.open(url, '_blank', 'noopener,noreferrer')
	} else {
		window.location.assign(url)
	}
}

interface ScopeChipsProps {
	groups: SearchGroup[]
	total: number
	scope: SearchScope
	setScope: (scope: SearchScope) => void
}

const ScopeChips: React.FC<ScopeChipsProps> = ({ groups, total, scope, setScope }) =>
	<div className="universal-search-scopes" role="group" aria-label={__('Filter results by source', 'code-snippets')}>
		{[{ source: 'all' as const, total }, ...groups].map(group =>
			<button
				key={group.source}
				type="button"
				className={classnames('universal-search-scope', { 'is-active': scope === group.source })}
				aria-pressed={scope === group.source}
				onClick={() => setScope(group.source)}
			>
				{'all' === group.source ? __('All', 'code-snippets') : SEARCH_SOURCE_LABELS[group.source]}
				<span className="universal-search-count">{group.total.toLocaleString()}</span>
			</button>)}
	</div>

interface SearchOptionProps {
	id: string
	item: SearchItem
	term: string
	isActive: boolean
	onActivate: VoidFunction
}

const SearchOption: React.FC<SearchOptionProps> = ({ id, item, term, isActive, onActivate }) => {
	const [element, setElement] = useState<HTMLAnchorElement | null>(null)

	return (
		<a
			ref={setElement}
			id={id}
			role="option"
			aria-selected={isActive}
			tabIndex={-1}
			className={classnames('universal-search-option', { 'is-active': isActive })}
			href={getSearchItemUrl(item, term)}
			onMouseMove={onActivate}
			{...opensInNewTab(item) && { target: '_blank', rel: 'noopener noreferrer' }}
		>
			<SearchItemBadge type={item.type} />

			<span className="universal-search-option-text">
				<span className="universal-search-option-title">
					<Highlight text={item.title} term={term} />
				</span>

				{item.description && (
					<span className="universal-search-option-description">
						<Highlight text={item.description} term={term} />
					</span>)}

				{item.code && (
					<span className="screen-reader-text">
						{// translators: 1: line number, 2: line of code.
							sprintf(__('Found on line %1$s: %2$s', 'code-snippets'), item.code.line.toLocaleString(), item.code.text)}
					</span>)}
			</span>

			{isActive && item.code && element && <SearchCodePreview code={item.code} term={term} anchor={element} />}
		</a>
	)
}

interface SearchGroupListProps {
	group: SearchGroup
	items: SearchItem[]
	term: string
	/** Heading shown in place of the source's name. */
	label?: React.ReactNode
	/** Where the group's full list of results is, when not the source's own tab. */
	viewAllUrl?: string
	activeId: string | undefined
	getOptionId: (item: SearchItem) => string
	setActiveId: (id: string) => void
}

const SearchGroupList: React.FC<SearchGroupListProps> = ({
	group,
	items,
	term,
	label,
	viewAllUrl,
	activeId,
	getOptionId,
	setActiveId
}) => {
	const headingId = useId()

	return (
		<div role="group" aria-labelledby={headingId}>
			<div className="universal-search-group-header" role="presentation">
				<span id={headingId}>
					{label ?? SEARCH_SOURCE_LABELS[group.source]}
					<span className="universal-search-count">{group.total.toLocaleString()}</span>
				</span>

				{group.total > items.length && (
					<a href={viewAllUrl ?? getSearchPageUrl({ query: term, source: group.source })}>
						{// translators: %s: number of search results.
							sprintf(__('View all %s', 'code-snippets'), group.total.toLocaleString())}
					</a>)}
			</div>

			{items.map(item =>
				<SearchOption
					key={item.id}
					id={getOptionId(item)}
					item={item}
					term={term}
					isActive={item.id === activeId}
					onActivate={() => setActiveId(item.id)}
				/>)}
		</div>
	)
}

interface SearchFooterProps {
	term: string
	total: number
	scope: SearchScope
}

const SearchFooter: React.FC<SearchFooterProps> = ({ term, total, scope }) =>
	<div className="universal-search-footer">
		<span>{__('↑↓ navigate · ↵ open · Esc close', 'code-snippets')}</span>

		{0 < total && (
			<a href={getSearchPageUrl({ query: term, source: getScopeSource(scope) })}>
				{// translators: %s: number of search results.
					sprintf(_n('See all %s result ↵', 'See all %s results ↵', total, 'code-snippets'), total.toLocaleString())}
			</a>)}
	</div>

interface SearchDropdownProps {
	listboxId: string
	hidden: boolean
	term: string
	groups: SearchGroup[]
	visibleGroups: VisibleGroup[]
	total: number
	scope: SearchScope
	setScope: (scope: SearchScope) => void
	pendingLabels: string[]
	isLoading: boolean
	hasFailed: boolean
	activeId: string | undefined
	getOptionId: (item: SearchItem) => string
	setActiveId: (id: string | undefined) => void
	aiGroup: VisibleGroup | undefined
	isAiLoading: boolean
}

type SearchDropdownResultsProps = Pick<SearchDropdownProps,
	'listboxId' | 'term' | 'visibleGroups' | 'pendingLabels' | 'activeId' | 'getOptionId' | 'setActiveId' | 'aiGroup' | 'isAiLoading'>

const SearchDropdownResults: React.FC<SearchDropdownResultsProps> = ({
	listboxId, term, visibleGroups, pendingLabels, activeId, getOptionId, setActiveId, aiGroup, isAiLoading
}) =>
	<div
		id={listboxId}
		role="listbox"
		className="universal-search-results"
		aria-label={__('Search results', 'code-snippets')}
	>
		{visibleGroups.map(({ group, items }) =>
			<SearchGroupList
				key={group.source}
				group={group}
				items={items}
				term={term}
				activeId={activeId}
				getOptionId={getOptionId}
				setActiveId={setActiveId}
			/>)}

		{pendingLabels.map(label =>
			<div key={label} className="universal-search-pending" role="presentation">
				<span className="universal-search-pending-badge"></span>
				<span className="universal-search-pending-bar"></span>
				<span>{label}</span>
			</div>)}

		{aiGroup && (
			<SearchGroupList
				group={aiGroup.group}
				items={aiGroup.items}
				term={term}
				label={<><Badge name="ai" small>{__('AI', 'code-snippets')}</Badge>{__('AI results', 'code-snippets')}</>}
				viewAllUrl={getSearchPageUrl({ query: term, source: AI_SEARCH_TAB })}
				activeId={activeId}
				getOptionId={getOptionId}
				setActiveId={setActiveId}
			/>)}

		{isAiLoading && (
			<div className="universal-search-pending" role="presentation">
				<span className="universal-search-pending-badge"></span>
				<span className="universal-search-pending-bar"></span>
				<span>{__('Searching with AI…', 'code-snippets')}</span>
			</div>)}
	</div>

const SearchDropdown: React.FC<SearchDropdownProps> = ({
	listboxId,
	hidden,
	term,
	groups,
	visibleGroups,
	total,
	scope,
	setScope,
	pendingLabels,
	isLoading,
	hasFailed,
	activeId,
	getOptionId,
	setActiveId,
	aiGroup,
	isAiLoading
}) =>
	<div className="universal-search-dropdown" hidden={hidden} onMouseLeave={() => setActiveId(undefined)}>
		{0 < total && <ScopeChips groups={groups} total={total} scope={scope} setScope={setScope} />}

		{hasFailed && (
			<p className="universal-search-empty">
				{__('Snippets and settings could not be searched. Please try again.', 'code-snippets')}
			</p>)}

		{!hasFailed && 0 === total && !aiGroup && !isLoading && !isAiLoading && (
			<p className="universal-search-empty">
				{// translators: %s: search query.
					sprintf(__('No results for “%s”.', 'code-snippets'), term)}
			</p>)}

		<SearchDropdownResults {...{ listboxId,
			term,
			visibleGroups,
			pendingLabels,
			activeId,
			getOptionId,
			setActiveId,
			aiGroup,
			isAiLoading }} />

		<CloudSearchNotice />

		<SearchFooter term={term} total={total} scope={scope} />
	</div>

interface SearchFieldProps {
	inputRef: React.RefObject<HTMLInputElement>
	query: string
	isExpanded: boolean
	listboxId: string
	activeOptionId: string | undefined
	onChange: (query: string) => void
	onFocus: VoidFunction
	onKeyDown: React.KeyboardEventHandler<HTMLInputElement>
	onCollapse: VoidFunction
}

const SearchField: React.FC<SearchFieldProps> = ({
	inputRef,
	query,
	isExpanded,
	listboxId,
	activeOptionId,
	onChange,
	onFocus,
	onKeyDown,
	onCollapse
}) =>
	<div className="universal-search-field" role="search">
		<span className="dashicons dashicons-search" aria-hidden="true"></span>

		<input
			ref={inputRef}
			type="search"
			role="combobox"
			className="universal-search-input"
			autoComplete="off"
			spellCheck={false}
			aria-label={__('Search snippets, settings, docs and cloud', 'code-snippets')}
			aria-autocomplete="list"
			aria-expanded={isExpanded}
			aria-controls={listboxId}
			aria-activedescendant={activeOptionId}
			placeholder={__('Search snippets, settings, docs, cloud…', 'code-snippets')}
			value={query}
			onChange={event => onChange(event.target.value)}
			onFocus={onFocus}
			onKeyDown={onKeyDown}
		/>

		{'' === query && <kbd aria-hidden="true">/</kbd>}

		<button
			type="button"
			className="universal-search-collapse"
			aria-label={__('Close search', 'code-snippets')}
			onClick={onCollapse}
		>
			<span className="dashicons dashicons-no-alt" aria-hidden="true"></span>
		</button>
	</div>

interface DocumentListenersProps {
	wrapperRef: React.RefObject<HTMLElement>
	onShortcut: VoidFunction
	onPointerDownOutside: VoidFunction
}

/**
 * Listen for the two things that happen outside the search bar: the `/`
 * shortcut that focuses it, and a press elsewhere on the page that dismisses it.
 */
const useDocumentListeners = ({ wrapperRef, onShortcut, onPointerDownOutside }: DocumentListenersProps) => {
	const handlers = useRef({ onShortcut, onPointerDownOutside })
	handlers.current = { onShortcut, onPointerDownOutside }

	useEffect(() => {
		const handleKeyDown = (event: KeyboardEvent) => {
			if (isShortcut(event)) {
				event.preventDefault()
				handlers.current.onShortcut()
			}
		}

		const handlePointerDown = (event: MouseEvent) => {
			if (event.target instanceof Node && !wrapperRef.current?.contains(event.target)) {
				handlers.current.onPointerDownOutside()
			}
		}

		document.addEventListener('keydown', handleKeyDown)
		document.addEventListener('mousedown', handlePointerDown)

		return () => {
			document.removeEventListener('keydown', handleKeyDown)
			document.removeEventListener('mousedown', handlePointerDown)
		}
	}, [wrapperRef])
}

const isSearchPage = 'search' === fetchQueryParam('subpage')

interface SearchBarDataProps {
	query: string
	isOpen: boolean
	selectedScope: SearchScope
}

const useSearchBarData = ({ query, isOpen, selectedScope }: SearchBarDataProps) => {
	// The search page lists the results itself, so the bar only searches once the user opens it.
	const { term, isSearchable, groups, loadingSources, total, hasFailed } =
		useUniversalSearch({ query, perSource: RESULTS_PER_SOURCE, enabled: isOpen })

	const scope = groups.some(group => group.source === selectedScope) ? selectedScope : 'all'
	const isAiScope = 'all' === scope || 'community' === scope

	// The AI search runs alongside the keyword search, so its results arrive without being asked for.
	const aiSearch = useUniversalSearch({
		query,
		perSource: RESULTS_PER_SOURCE,
		sources: AI_SOURCES,
		method: 'ai',
		delay: AI_DELAY_MS,
		enabled: isOpen && isAiScope
	})

	// Its results are listed after the keyword results, once those are in, so they do not
	// jump down the list. They may repeat a keyword result, so their ids are kept apart, and
	// those not on this site lead to the AI tab, as a keyword search may not find them.
	const isLocalLoading = loadingSources.some(isLocalSearchSource)
	const aiGroup: VisibleGroup | undefined = isAiScope && !isLocalLoading && aiSearch.groups[0]
		? {
			group: aiSearch.groups[0],
			items: aiSearch.groups[0].items.slice(0, AI_LIMIT).map(item => ({
				...item,
				id: `ai-${item.id}`,
				url: item.url || getSearchPageUrl({ query: term, source: AI_SEARCH_TAB })
			}))
		}
		: undefined
	const isAiLoading = isAiScope && 0 < aiSearch.loadingSources.length

	const visibleGroups = getVisibleGroups(groups, scope)
	const options = [...visibleGroups, ...aiGroup ? [aiGroup] : []].flatMap(({ items }) => items)

	const pendingLabels = loadingSources
		.filter(source => 'all' === scope || source === scope)
		.flatMap(source => SEARCH_LOADING_LABELS[source] ?? [])

	return {
		term, isSearchable, groups, loadingSources, total, hasFailed, scope, aiGroup, isAiLoading, visibleGroups, options, pendingLabels
	}
}

interface SearchKeyboardProps {
	options: SearchItem[]
	activeItem: SearchItem | undefined
	getOptionId: (item: SearchItem) => string
	setSelectedId: (id: string) => void
	setIsOpen: (open: boolean) => void
	setIsExpanded: (expanded: boolean) => void
	isDropdownOpen: boolean
	isSearchable: boolean
	term: string
	scope: SearchScope
	close: VoidFunction
}

const getSearchKeyDownHandler = ({
	options, activeItem, getOptionId, setSelectedId, setIsOpen, setIsExpanded,
	isDropdownOpen, isSearchable, term, scope, close
}: SearchKeyboardProps): React.KeyboardEventHandler<HTMLInputElement> => {
	const moveSelection = (step: number) => {
		if (0 < options.length) {
			const activeIndex = activeItem ? options.indexOf(activeItem) : -1
			const start = -1 === activeIndex && 0 > step ? 0 : activeIndex
			const nextItem = options[(start + step + options.length) % options.length]

			setSelectedId(nextItem.id)
			document.getElementById(getOptionId(nextItem))?.scrollIntoView({ block: 'nearest' })
		}
	}

	return (event: React.KeyboardEvent<HTMLInputElement>) => {
		switch (event.key) {
			case 'ArrowDown':
			case 'ArrowUp':
				event.preventDefault()
				setIsOpen(true)
				moveSelection('ArrowDown' === event.key ? 1 : -1)
				break

			case 'Enter':
				event.preventDefault()

				if (isDropdownOpen && activeItem) {
					openSearchItem(activeItem, term)
				} else if (isSearchable) {
					window.location.assign(getSearchPageUrl({ query: term, source: getScopeSource(scope) }))
				}
				break

			case 'Escape':
				// Keeps the browser from also clearing the search field.
				event.preventDefault()

				if (isDropdownOpen) {
					close()
				} else {
					setIsExpanded(false)
				}
				break
		}
	}
}

interface SearchFocusProps {
	wrapperRef: React.RefObject<HTMLDivElement>
	inputRef: React.RefObject<HTMLInputElement>
	isExpanded: boolean
	setIsExpanded: (expanded: boolean) => void
	close: VoidFunction
}

const useSearchFocus = ({ wrapperRef, inputRef, isExpanded, setIsExpanded, close }: SearchFocusProps) => {
	useDocumentListeners({
		wrapperRef,
		onShortcut: () => {
			setIsExpanded(true)
			inputRef.current?.focus()
		},
		onPointerDownOutside: () => {
			close()
			setIsExpanded(false)
		}
	})

	// The field is hidden while collapsed, so it can only take focus once expanding has rendered it.
	useEffect(() => {
		if (isExpanded) {
			inputRef.current?.focus()
		}
	}, [isExpanded, inputRef])
}

const useSearchBar = () => {
	const [query, setQuery] = useState(() => isSearchPage ? fetchQueryParam('s') ?? '' : '')
	const [isOpen, setIsOpen] = useState(false)
	const [isExpanded, setIsExpanded] = useState(false)
	const [selectedScope, setSelectedScope] = useState<SearchScope>('all')
	const [selectedId, setSelectedId] = useState<string>()
	const wrapperRef = useRef<HTMLDivElement>(null)
	const inputRef = useRef<HTMLInputElement>(null)
	const baseId = useId()

	const data = useSearchBarData({ query, isOpen, selectedScope })
	const { isSearchable, options } = data
	const activeItem = options.find(item => item.id === selectedId)
	const isDropdownOpen = isOpen && isSearchable
	const getOptionId = (item: SearchItem) => `${baseId}-${item.id}`
	const activeOptionId = activeItem && getOptionId(activeItem)

	const close = () => {
		setIsOpen(false)
		setSelectedId(undefined)
	}

	useSearchFocus({ wrapperRef, inputRef, isExpanded, setIsExpanded, close })

	const handleKeyDown = getSearchKeyDownHandler({
		...data,
		activeItem,
		getOptionId,
		setSelectedId,
		setIsOpen,
		setIsExpanded,
		isDropdownOpen,
		close
	})

	return {
		data,
		wrapperRef,
		inputRef,
		baseId,
		isExpanded,
		setIsExpanded,
		isDropdownOpen,
		activeOptionId,
		query,
		setQuery,
		setSelectedId,
		setIsOpen,
		handleKeyDown,
		close,
		setSelectedScope,
		activeId: activeItem?.id,
		getOptionId
	}
}

const SearchBarField: React.FC<Pick<ReturnType<typeof useSearchBar>,
	'inputRef' | 'query' | 'isDropdownOpen' | 'baseId' | 'activeOptionId' | 'setQuery' | 'setSelectedId' |
	'setIsOpen' | 'handleKeyDown' | 'close' | 'setIsExpanded'>> = ({
	inputRef, query, isDropdownOpen, baseId, activeOptionId, setQuery, setSelectedId,
	setIsOpen, handleKeyDown, close, setIsExpanded
}) =>
	<SearchField
		inputRef={inputRef}
		query={query}
		isExpanded={isDropdownOpen}
		listboxId={`${baseId}-results`}
		activeOptionId={isDropdownOpen ? activeOptionId : undefined}
		onChange={value => {
			setQuery(value)
			setSelectedId(undefined)
			setIsOpen(true)
		}}
		onFocus={() => setIsOpen(true)}
		onKeyDown={handleKeyDown}
		onCollapse={() => {
			close()
			setIsExpanded(false)
		}}
	/>

const SearchBarView: React.FC<ReturnType<typeof useSearchBar>> = props => {
	const { data, wrapperRef, baseId, isExpanded, setIsExpanded, isDropdownOpen,
		close, setSelectedScope, activeId, getOptionId, setSelectedId } = props
	const { term, groups, loadingSources, total, hasFailed, scope, aiGroup, isAiLoading, visibleGroups, pendingLabels } = data

	return (
		<div
			ref={wrapperRef}
			className={classnames('universal-search', { 'is-expanded': isExpanded })}
			onBlur={event => {
				if (event.relatedTarget instanceof Node && !event.currentTarget.contains(event.relatedTarget)) {
					close()
				}
			}}
		>
			<button
				type="button"
				className="universal-search-toggle"
				aria-label={__('Search', 'code-snippets')}
				onClick={() => setIsExpanded(true)}
			>
				<span className="dashicons dashicons-search" aria-hidden="true"></span>
			</button>

			<SearchBarField {...props} />

			{/* The bar is on every screen, so its announcements carry no status role that would compete with the screen's own. */}
			<div className="screen-reader-text" aria-live="polite" aria-atomic="true">
				{isDropdownOpen && !hasFailed && (0 < loadingSources.length
					? pendingLabels.join(' ') || __('Searching…', 'code-snippets')
					: formatResultCount(total + (aiGroup?.group.total ?? 0)))}
			</div>

			<SearchDropdown
				listboxId={`${baseId}-results`}
				hidden={!isDropdownOpen}
				term={term}
				groups={groups}
				visibleGroups={visibleGroups}
				total={total}
				scope={scope}
				setScope={setSelectedScope}
				pendingLabels={pendingLabels}
				isLoading={0 < loadingSources.length}
				hasFailed={hasFailed}
				activeId={activeId}
				getOptionId={getOptionId}
				setActiveId={setSelectedId}
				aiGroup={aiGroup}
				isAiLoading={isAiLoading}
			/>
		</div>
	)
}

const SearchBar = () => <SearchBarView {...useSearchBar()} />

export const UniversalSearch = () =>
	REST_BASES.search
		? <WithRestAPIContext><SearchBar /></WithRestAPIContext>
		: null
