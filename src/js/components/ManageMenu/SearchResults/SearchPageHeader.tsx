import { useState } from 'react'
import { UniversalSearch } from '../../../hooks/useUniversalSearch'
import { SEARCH_FIELDS, type SearchField, type SearchSource, SearchSourceFilter, SearchTab } from '../../../types/Search'
import { isLicensed } from '../../../utils/screen'
import { ScreenMetaSlot } from '../../common/ScreenMetaSlot'
import { getTabFromQuery, SubnavTab, SubnavTabs } from '../../common/SubnavTabs'
import { AI_SEARCH_TAB, CloudSearchNotice, formatResultCount } from '../../common/UniversalSearch/SearchResultParts'
import { __, sprintf } from '@wordpress/i18n'
import React from 'react'

export interface SearchPageHeaderProps {
	tabs: SearchTab[]
	search: UniversalSearch
	aiSearch: UniversalSearch
	currentTab: SearchTab
	setCurrentTab: (tab: SearchTab) => void
	sourceQueryParam: string
}

export const SearchPageHeader = ({
	tabs,
	search,
	aiSearch,
	currentTab,
	setCurrentTab,
	sourceQueryParam
}: SearchPageHeaderProps) => {

	const visibleGroups = AI_SEARCH_TAB === currentTab.name
		? aiSearch.groups
		: search.groups.filter(group => 'all' === currentTab.name || group.source === currentTab.name)


	const getTabCount = (tab: SearchTab): number | undefined => {
		if (AI_SEARCH_TAB === tab.name) {
			return AI_SEARCH_TAB === currentTab.name && 0 === aiSearch.loadingSources.length ? aiSearch.total : undefined
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

	return (
		<>
			<SubnavTabs
				className="search-results-nav"
				ariaLabel={__('Search result sources', 'code-snippets')}
				tabs={tabs}
				currentTab={currentTab}
				setCurrentTab={setCurrentTab}
				getTabCount={search.isSearchable ? getTabCount : undefined}
				queryParamName={sourceQueryParam}
			/>

			<ScreenMetaSlot />

			<div className="snippets-page-header">
				<h1>
					{search.isSearchable
						// translators: %s: search query.
						? sprintf(__('Search results for “%s”', 'code-snippets'), search.term)
						: __('Search', 'code-snippets')}
				</h1>

				{search.isSearchable && !currentTab.locked && !isPending(shownSearch.loadingSources, isAiTab ? 'all' : filter) && (
					<span className="search-results-total">{formatResultCount(countResults(visibleGroups))}</span>)}
			</div>

			<hr className="wp-header-end" />
		</>
	)
}w
