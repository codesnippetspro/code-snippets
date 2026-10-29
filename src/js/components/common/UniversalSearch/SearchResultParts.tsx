import { Popover } from '@wordpress/components'
import { __, _n, sprintf } from '@wordpress/i18n'
import React from 'react'
import { isCloudConnected, isLicensed, shouldShowUpsell } from '../../../utils/screen'
import { splitHighlights } from '../../../utils/text'
import { buildUrl } from '../../../utils/urls'
import { Badge } from '../Badge'
import type { SearchCodeExcerpt, SearchItem, SearchItemType, SearchSource } from '../../../types/Search'
import type { SnippetType } from '../../../types/Snippet'

export const SEARCH_SOURCE_LABELS: Record<SearchSource, string> = {
	snippets: __('Snippets', 'code-snippets'),
	settings: __('Settings', 'code-snippets'),
	stats: __('Stats', 'code-snippets'),
	welcome: __('Welcome', 'code-snippets'),
	docs: __('Docs', 'code-snippets'),
	cloud: __('Cloud Library', 'code-snippets'),
	community: __('Cloud Community', 'code-snippets')
}

export const SEARCH_LOADING_LABELS: Partial<Record<SearchSource, string>> = {
	docs: __('Searching codesnippets.pro/docs…', 'code-snippets'),
	cloud: __('Searching your Cloud Library…', 'code-snippets'),
	community: __('Searching Cloud Community…', 'code-snippets')
}

const NEUTRAL_TYPE_LABELS: Record<Exclude<SearchItemType, SnippetType>, string> = {
	setting: __('Setting', 'code-snippets'),
	stat: __('Stat', 'code-snippets'),
	guide: __('Guide', 'code-snippets'),
	doc: __('Doc', 'code-snippets')
}

const isNeutralType = (type: SearchItemType): type is keyof typeof NEUTRAL_TYPE_LABELS =>
	type in NEUTRAL_TYPE_LABELS

export const formatResultCount = (count: number): string =>
	// translators: %s: number of search results.
	sprintf(_n('%s result', '%s results', count, 'code-snippets'), count.toLocaleString())

/** The results page tab listing what an AI search of the cloud found. */
export const AI_SEARCH_TAB = 'ai'

export const PRICING_URL = 'https://codesnippets.pro/pricing/'

export interface SearchPageUrlProps {
	query: string
	source?: SearchSource | typeof AI_SEARCH_TAB
}

export const getSearchPageUrl = ({ query, source }: SearchPageUrlProps): string =>
	buildUrl(window.CODE_SNIPPETS?.urls.manage, { subpage: 'search', s: query.trim(), source })

/**
 * Where selecting a result leads. Cloud snippets that are not on this site have
 * no screen of their own, so they lead to the results page, which can preview
 * and download them.
 */
export const getSearchItemUrl = (item: SearchItem, query: string): string =>
	item.url || getSearchPageUrl({ query, source: item.source })

export const opensInNewTab = (item: SearchItem): boolean =>
	'docs' === item.source

export interface HighlightProps {
	text: string
	term: string
}

export const Highlight: React.FC<HighlightProps> = ({ text, term }) =>
	<>
		{splitHighlights(text, term).map((part, index) =>
			part.highlighted
				? <mark key={index}>{part.text}</mark>
				: <React.Fragment key={index}>{part.text}</React.Fragment>)}
	</>

export interface SearchItemBadgeProps {
	type: SearchItemType
}

export const SearchItemBadge: React.FC<SearchItemBadgeProps> = ({ type }) =>
	isNeutralType(type)
		? <Badge name="neutral">{NEUTRAL_TYPE_LABELS[type]}</Badge>
		: <Badge name={type} />

interface CodePreviewLineProps {
	number: number
	isContext?: boolean
	children: React.ReactNode
}

const CodePreviewLine: React.FC<CodePreviewLineProps> = ({ number, isContext, children }) =>
	<div className={isContext ? 'universal-search-code-preview-line is-context' : 'universal-search-code-preview-line'}>
		<span className="universal-search-code-preview-number">{number}</span>
		<span className="universal-search-code-preview-text">{children}</span>
	</div>

export interface SearchCodePreviewProps {
	code: SearchCodeExcerpt
	term: string
	anchor: HTMLElement
	placement?: React.ComponentProps<typeof Popover>['placement']
}

/**
 * The line of code a result matched on, shown beside the result. The lines
 * either side are blurred so only the match stands out.
 */
export const SearchCodePreview: React.FC<SearchCodePreviewProps> = ({ code, term, anchor, placement = 'right-start' }) =>
	<Popover
		anchor={anchor}
		placement={placement}
		offset={8}
		focusOnMount={false}
		animate={false}
		className="universal-search-code-popover"
	>
		<code className="universal-search-code-preview" aria-hidden="true">
			{code.before && <CodePreviewLine number={code.line - 1} isContext>{code.before}</CodePreviewLine>}
			<CodePreviewLine number={code.line}><Highlight text={code.text} term={term} /></CodePreviewLine>
			{code.after && <CodePreviewLine number={code.line + 1} isContext>{code.after}</CodePreviewLine>}
		</code>
	</Popover>

export const CloudSearchNotice: React.FC = () => {
	const connectUrl = window.CODE_SNIPPETS?.urls.connectCloud

	if (!isLicensed()) {
		return shouldShowUpsell()
			? <p className="universal-search-cloud-notice is-upsell">
				<Badge name="pro" small>{__('Pro', 'code-snippets')}</Badge>
				<span>{__('Search your own Cloud Library too, along with your synced and private snippets.', 'code-snippets')}</span>
				<a href={PRICING_URL} target="_blank" rel="noopener noreferrer">
					{__('Upgrade to Pro', 'code-snippets')}
					<span className="screen-reader-text">{__('(opens in a new tab)', 'code-snippets')}</span>
				</a>
			</p>
			: null
	}

	return !isCloudConnected() && connectUrl
		? <p className="universal-search-cloud-notice">
			<span>{__('Connect to Code Snippets Cloud to also search your Cloud Library.', 'code-snippets')}</span>
			<a href={connectUrl}>{__('Connect', 'code-snippets')}</a>
		</p>
		: null
}
