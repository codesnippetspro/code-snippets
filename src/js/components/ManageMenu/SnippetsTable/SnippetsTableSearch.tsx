import { __, sprintf } from '@wordpress/i18n'
import React, { useEffect, useState } from 'react'
import { Button } from '../../common/Button'
import { useSnippetsFilters } from './WithSnippetsTableFilters'

export const SearchArea = () => {
	const { searchQuery, setSearchQuery } = useSnippetsFilters()
	const [query, setQuery] = useState(searchQuery ?? '')

	useEffect(() => setQuery(searchQuery ?? ''), [searchQuery])

	return (
		<div className="snippets-search-area">
			<form
				className="search-box"
				aria-label={__('Search Snippets', 'code-snippets')}
				onSubmit={event => {
					event.preventDefault()
					setSearchQuery(query)
				}}
			>
				<input
					type="search"
					id="snippets_search"
					name="s"
					value={query}
					aria-label={__('Search Snippets:', 'code-snippets')}
					onChange={event => setQuery(event.target.value)}
					placeholder={__('Search snippets', 'code-snippets')}
				/>

				<Button secondary type="submit">
					{__('Search', 'code-snippets')}
				</Button>
			</form>
		</div>
	)
}

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
