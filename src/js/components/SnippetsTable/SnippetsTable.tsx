import { __, sprintf } from '@wordpress/i18n'
import React from 'react'
import classnames from 'classnames'
import { addQueryArgs } from '@wordpress/url'
import { WithRestAPIContext } from '../../hooks/useRestAPI'
import { WithSnippetsListContext } from '../../hooks/useSnippetsList'
import { WithSnippetsTableContext, useSnippetsTable } from '../../hooks/useSnippetsTable'
import { SNIPPET_TYPES } from '../../types/Snippet'
import { SNIPPET_TYPE_LABELS } from '../../utils/snippets/snippets'
import { Badge } from '../common/Badge'
import { Button } from '../common/Button'
import { SnippetsListTable } from './SnippetsListTable'
import type { SnippetType } from '../../types/Snippet'
import type { MouseEventHandler } from 'react'

interface SnippetTypeTabProps {
	type?: SnippetType
}

const SnippetTypeTab: React.FC<SnippetTypeTabProps> = ({ type }) => {
	const { currentType, setCurrentType } = useSnippetsTable()
	const tabName = type ?? 'all'

	const handleClick: MouseEventHandler<HTMLAnchorElement> = event => {
		event.preventDefault()
		setCurrentType(type)
	}

	return (
		<a
			href={addQueryArgs(window.location.href, { type: tabName })}
			className={classnames('nav-tab', `${tabName}-tab`, { 'nav-tab-active': type === currentType })}
			onClick={handleClick}
		>
			<span className={`${tabName}-label`}>
				{type ? SNIPPET_TYPE_LABELS[type] : __('All Snippets', 'code-snippets')}
			</span>
			{type && <Badge name={type} />}
		</a>
	)
}

const PageHeading = () => {
	const { searchQueryText, searchLineNumber, currentTag, setSearchQuery, setCurrentTag } = useSnippetsTable()
	return (
		<h1>
			{__('Manage Code Snippets', 'code-snippets')}

			{searchQueryText || currentTag
				? <span className="subtitle">
					{__('Search results', 'code-snippets')}

					{/* translators: %s: search query. */}
					{searchQueryText && sprintf( __( ' for “%s”', 'code-snippets' ), searchQueryText )}

					{/* translators: %s: search query. */}
					{searchLineNumber && sprintf( __( ' on line “%d”', 'code-snippets' ), searchLineNumber )}

					{/* translators: %s: tag name. */}
					{currentTag && sprintf( __( ' in tag “%s”', 'code-snippets' ), currentTag )}

					{' '}
					<Button className="clear-filters" onClick={() => {
						setSearchQuery()
						setCurrentTag()
					}}>
						{__('Clear Filters', 'code-snippets')}
					</Button>
				</span>
				: null}
		</h1>
	)
}

const SnippetsTableInner = () =>
	<div className="wrap">
		<PageHeading />

		<h2 className="nav-tab-wrapper snippet-type-tabs">
			<SnippetTypeTab />
			{SNIPPET_TYPES.map(type => <SnippetTypeTab key={type} type={type} />)}
		</h2>

		<SnippetsListTable />
	</div>

export const SnippetsTable: React.FC = () =>
	<WithRestAPIContext>
		<WithSnippetsListContext>
			<WithSnippetsTableContext>
				<SnippetsTableInner />
			</WithSnippetsTableContext>
		</WithSnippetsListContext>
	</WithRestAPIContext>
