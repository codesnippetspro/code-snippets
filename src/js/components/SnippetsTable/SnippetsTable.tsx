import { __, sprintf } from '@wordpress/i18n'
import React, { useState } from 'react'
import classnames from 'classnames'
import { addQueryArgs } from '@wordpress/url'
import { WithFilteredSnippetsContext } from '../../hooks/useFilteredSnippets'
import { WithRestAPIContext } from '../../hooks/useRestAPI'
import { WithSnippetsListContext } from '../../hooks/useSnippetsList'
import { WithSnippetsTableFiltersContext, useSnippetsFilters } from '../../hooks/useSnippetsFilters'
import { SNIPPET_TYPES } from '../../types/Snippet'
import { isLicensed } from '../../utils/screen'
import { SNIPPET_TYPE_LABELS, isProType } from '../../utils/snippets/snippets'
import { Badge } from '../common/Badge'
import { Button } from '../common/Button'
import { UpsellDialog } from '../common/UpsellDialog'
import { SnippetsListTable } from './SnippetsListTable'
import type { SnippetType } from '../../types/Snippet'

interface SnippetTypeTabProps {
	type?: SnippetType
	setIsUpgradeDialogOpen: (isOpen: boolean) => void
}

const SnippetTypeTab: React.FC<SnippetTypeTabProps> = ({ type, setIsUpgradeDialogOpen }) => {
	const { currentType, setCurrentType } = useSnippetsFilters()
	const tabName = type ?? 'all'

	return (
		<a
			href={addQueryArgs(window.location.href, { type: tabName })}
			className={classnames('nav-tab', `${tabName}-tab`, {
				'nav-tab-active': type === currentType,
				'nav-tab-inactive': type && type !== currentType && !isLicensed() && isProType(type)
			})}
			onClick={event => {
				event.preventDefault()

				if (type && !isLicensed() && isProType(type)) {
					setIsUpgradeDialogOpen(true)
				} else {
					setCurrentType(type)
				}
			}}
		>
			<span className={`${tabName}-label`}>
				{type ? SNIPPET_TYPE_LABELS[type] : __('All Snippets', 'code-snippets')}
			</span>
			{type && <Badge name={type} />
			}
		</a>
	)
}

const PageHeading = () => {
	const { searchQueryText, searchLineNumber, currentTag, setSearchQuery, setCurrentTag } = useSnippetsFilters()
	return (
		<h1>
			{__('Manage Code Snippets', 'code-snippets')}

			{searchQueryText || currentTag
				? <span className="subtitle">
					{__('Search results', 'code-snippets')}

					{/* translators: %s: search query. */}
					{searchQueryText && sprintf(__(' for “%s”', 'code-snippets'), searchQueryText)}

					{/* translators: %s: search query. */}
					{searchLineNumber && sprintf(__(' on line “%d”', 'code-snippets'), searchLineNumber)}

					{/* translators: %s: tag name. */}
					{currentTag && sprintf(__(' in tag “%s”', 'code-snippets'), currentTag)}

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

const SnippetsTableInner = () => {
	const [isUpgradeDialogOpen, setIsUpgradeDialogOpen] = useState(false)

	return (
		<div className="wrap">
			<PageHeading />

			<h2 className="nav-tab-wrapper snippet-type-tabs">
				<SnippetTypeTab setIsUpgradeDialogOpen={setIsUpgradeDialogOpen} />
				{SNIPPET_TYPES.map(type =>
					<SnippetTypeTab key={type} type={type} setIsUpgradeDialogOpen={setIsUpgradeDialogOpen} />)}
			</h2>

			<WithFilteredSnippetsContext>
				<SnippetsListTable />
			</WithFilteredSnippetsContext>

			<UpsellDialog isOpen={isUpgradeDialogOpen} setIsOpen={setIsUpgradeDialogOpen} />
		</div>
	)
}

export const SnippetsTable: React.FC = () =>
	<WithRestAPIContext>
		<WithSnippetsListContext>
			<WithSnippetsTableFiltersContext>
				<SnippetsTableInner />
			</WithSnippetsTableFiltersContext>
		</WithSnippetsListContext>
	</WithRestAPIContext>
