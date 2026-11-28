import { __, sprintf } from '@wordpress/i18n'
import React from 'react'
import { Button } from '../../common/Button'
import { useSnippetsFilters } from './WithSnippetsTableFilters'

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
