import { __, sprintf } from '@wordpress/i18n'
import { createInterpolateElement } from '@wordpress/element'
import React, { useState } from 'react'
import classnames from 'classnames'
import { WithRestAPIContext } from '../../../hooks/useRestAPI'
import { WithSnippetsAPIContext } from '../../../hooks/useSnippetsAPI'
import { WithSnippetsListContext } from '../../../hooks/useSnippetsList'
import { SNIPPET_TYPES } from '../../../types/Snippet'
import { isLicensed } from '../../../utils/screen'
import { SNIPPET_TYPE_LABELS, getSnippetEditUrl, isProType } from '../../../utils/snippets/snippets'
import { buildUrl } from '../../../utils/urls'
import { Badge } from '../../common/Badge'
import { Button } from '../../common/Button'
import { Notice } from '../../common/Notice'
import { UpsellDialog } from '../../common/UpsellDialog'
import { WithSnippetsTableFilters, useSnippetsFilters } from './WithSnippetsTableFilters'
import { WithFilteredSnippetsContext } from './WithFilteredSnippetsContext'
import { SnippetsListTable } from './SnippetsListTable'
import type { SnippetType } from '../../../types/Snippet'

interface SnippetTypeTabProps {
	type?: SnippetType
	setIsUpgradeDialogOpen: (isOpen: boolean) => void
}

const SnippetTypeTab: React.FC<SnippetTypeTabProps> = ({ type, setIsUpgradeDialogOpen }) => {
	const { currentType, setCurrentType } = useSnippetsFilters()
	const tabName = type ?? 'all'

	return (
		<a
			href={buildUrl(window.location.href, { type: tabName })}
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
		<>
			<a href={getSnippetEditUrl()} className="button button-primary button-large create-snippet-button">
				{__('Create new snippet', 'code-snippets')}
			</a>

			<h1>
				{__('Manage Code Snippets', 'code-snippets')}

				{searchQueryText || currentTag
					? <span className="subtitle">
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
					</span>
					: null}
			</h1>

			<SafeModeNotice />
		</>
	)
}

const SafeModeNotice = () =>
	window.CODE_SNIPPETS_MANAGE?.isSafeModeActive
		? <Notice type="error">
			<p>
				<strong>{__('Warning:', 'code-snippets')}</strong>{'\n'}
				{__('Safe mode is active and snippets will not execute!', 'code-snippets')}{'\n'}

				{createInterpolateElement(
					__('Remove the <code>CODE_SNIPPETS_SAFE_MODE</code> constant from <code>wp-config.php</code> file to turn off safe mode.', 'code-snippets'),
					{
						code: <code />
					}
				)}{'\n'}

				<a href="https://codesnippets.pro/doc/safe-mode" target="_blank" rel="noreferrer">
					{__('Read more', 'code-snippets')}
				</a>
			</p>
		</Notice>
		: null

const SnippetsTableInner = () => {
	const [isUpgradeDialogOpen, setIsUpgradeDialogOpen] = useState(false)

	return (
		<div className="wrap">
			<PageHeading />

			<nav
				className="nav-tab-wrapper snippet-type-tabs"
				aria-label={__('Snippet types', 'code-snippets')}
			>
				<SnippetTypeTab setIsUpgradeDialogOpen={setIsUpgradeDialogOpen} />
				{SNIPPET_TYPES.map(type =>
					<SnippetTypeTab key={type} type={type} setIsUpgradeDialogOpen={setIsUpgradeDialogOpen} />)}
			</nav>

			<WithFilteredSnippetsContext>
				<SnippetsListTable />
			</WithFilteredSnippetsContext>

			<UpsellDialog isOpen={isUpgradeDialogOpen} setIsOpen={setIsUpgradeDialogOpen} />
		</div>
	)
}

export const SnippetsTable: React.FC = () =>
	<WithRestAPIContext>
		<WithSnippetsAPIContext>
			<WithSnippetsListContext>
				<WithSnippetsTableFilters>
					<SnippetsTableInner />
				</WithSnippetsTableFilters>
			</WithSnippetsListContext>
		</WithSnippetsAPIContext>
	</WithRestAPIContext>
