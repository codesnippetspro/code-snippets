import React, { useState } from 'react'
import { __, sprintf } from '@wordpress/i18n'
import { Highlight, SearchCodePreview, SearchItemBadge, getSearchItemUrl, opensInNewTab } from '../../common/UniversalSearch/SearchResultParts'
import { SearchResultActions, SnippetActivationSwitch } from './SearchResultActions'
import type { SearchCodeExcerpt, SearchField, SearchItem } from '../../../types/Search'

interface CodeMatchBarProps {
	code: SearchCodeExcerpt
	term: string
}

/**
 * Shows the line of code a result matched on, in the same preview as the search bar.
 */
const CodeMatchBar: React.FC<CodeMatchBarProps> = ({ code, term }) => {
	const [anchor, setAnchor] = useState<HTMLButtonElement | null>(null)
	const [isOpen, setIsOpen] = useState(false)

	return (
		<>
			<button
				ref={setAnchor}
				type="button"
				className="search-result-code-bar"
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
				<span>
					{// translators: %s: line number.
						sprintf(__('Found in – Line %s', 'code-snippets'), code.line.toLocaleString())}
				</span>
				<span className="dashicons dashicons-visibility search-result-code-bar-open" aria-hidden="true"></span>
				<span className="screen-reader-text">{code.text}</span>
			</button>

			{isOpen && anchor && <SearchCodePreview code={code} term={term} anchor={anchor} placement="bottom-start" />}
		</>
	)
}

export interface SearchResultProps {
	item: SearchItem
	term: string
	fields: readonly SearchField[]
}

export const SearchResult: React.FC<SearchResultProps> = ({ item, term, fields }) => {
	const [isActive, setIsActive] = useState(item.snippet?.active ?? false)
	const url = getSearchItemUrl(item, term)

	// Only the fields being matched in are highlighted, so a highlight always marks a match.
	const highlightIn = (field: SearchField) => fields.includes(field) ? term : ''

	// Every result shares the snippets table's row layout: switch, details, type, actions. Only
	// local snippets can be switched on, so the other rows leave that column empty to stay aligned.
	return (
		<li className="search-result">
			<div className="search-result-switch">
				{item.snippet && 'cond' !== item.type && (
					<SnippetActivationSwitch item={item} summary={item.snippet} isActive={isActive} setIsActive={setIsActive} />)}
			</div>

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

				{/* A local snippet's path is only its type, which the badge already shows. */}
				{!item.snippet && item.path && <p className="search-result-meta">{item.path}</p>}

				{item.code && <CodeMatchBar code={item.code} term={highlightIn('code')} />}
			</div>

			<SearchItemBadge type={item.type} />

			<div className="row-actions visible search-result-actions">
				<SearchResultActions item={item} />
			</div>
		</li>
	)
}
