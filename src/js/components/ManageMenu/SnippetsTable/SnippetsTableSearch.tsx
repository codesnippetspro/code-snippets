import { __, _n, sprintf } from '@wordpress/i18n'
import React from 'react'
import { Button } from '../../common/Button'
import { useSnippetsFilters } from './WithSnippetsTableFilters'

const SearchBox = () => {
	const { searchQuery, setSearchQuery } = useSnippetsFilters()

	return (
		<search aria-label={__('Search Snippets', 'code-snippets')}>
			<form
				className="search-box"
				onSubmit={event => event.preventDefault()}
			>
				<input
					type="search"
					id="snippets_search"
					name="s"
					value={searchQuery ?? ''}
					aria-label={__('Search Snippets:', 'code-snippets')}
					onChange={event => setSearchQuery(event.target.value)}
					placeholder={__('Search snippets', 'code-snippets')}
				/>
				<Button type="submit">
					{__('Search', 'code-snippets')}
				</Button>
			</form>
		</search>
	)
}


export const SearchArea = () =>
	<div className="snippets-search-area">
		<SearchBox />
	</div>

export const SearchResultsIndicator = () => {
	const { searchQueryText, searchLineNumber, currentTag, setSearchQuery, setCurrentTag } = useSnippetsFilters()

	return searchQueryText || currentTag
		? <p className="snippets-search-subtitle">
			{__('Search results', 'code-snippets')}

			{/* translators: %s: search query. */}
			{searchQueryText && sprintf(__(' for “%s”', 'code-snippets'), searchQueryText)}

			{/* translators: %d: code line number. */}
			{searchLineNumber && sprintf(__(' on line “%d”', 'code-snippets'), searchLineNumber)}

			{/* translators: %s: tag name. */}
			{currentTag && sprintf(__(' in tag “%s”', 'code-snippets'), currentTag)}

			{' '}
			<Button small className="clear-filters" onClick={() => {
				setSearchQuery()
				setCurrentTag()
			}}>
				{__('Clear Filters', 'code-snippets')}
			</Button>
		</p>
		: null
}
