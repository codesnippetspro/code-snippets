import { __, sprintf } from '@wordpress/i18n'
import { createInterpolateElement } from '@wordpress/element'
import React, { useCallback, useMemo, useState } from 'react'
import classnames from 'classnames'
import { WithRestAPIContext } from '../../../hooks/useRestAPI'
import { useSnippetView } from '../../../hooks/useSnippetView'
import { WithSnippetsAPIContext } from '../../../hooks/useSnippetsAPI'
import { WithSnippetsListContext, useSnippetsList } from '../../../hooks/useSnippetsList'
import { SNIPPET_TYPES } from '../../../types/Snippet'
import { isLicensed } from '../../../utils/screen'
import { SNIPPET_TYPE_LABELS, getSnippetAddNewUrl, getSnippetType, isProType } from '../../../utils/snippets/snippets'
import { buildUrl } from '../../../utils/urls'
import { Badge } from '../../common/Badge'
import { Notice } from '../../common/Notice'
import { ScreenMetaSlot } from '../../common/ScreenMetaSlot'
import { UpsellDialog } from '../../common/UpsellDialog'
import { WithSnippetsTableFilters, useSnippetsFilters } from './WithSnippetsTableFilters'
import { WithFilteredSnippetsContext } from './WithFilteredSnippetsContext'
import { SnippetsListTable } from './SnippetsListTable'
import type { SnippetType } from '../../../types/Snippet'

interface SnippetTypeTabProps {
	type?: SnippetType
	count?: number
	setIsUpgradeDialogOpen: (isOpen: boolean) => void
}

const SnippetTypeTab: React.FC<SnippetTypeTabProps> = ({ type, count, setIsUpgradeDialogOpen }) => {
	const { currentType, setCurrentType } = useSnippetsFilters()
	const tabName = type ?? 'all'

	return (
		<li>
			<a
				href={buildUrl(window.location.href, { type: tabName })}
				className={classnames('snippet-type-link', `${tabName}-type-link`, {
					'active-type': type === currentType,
					'pro-locked-type': type && type !== currentType && !isLicensed() && isProType(type)
				})}
				aria-current={type === currentType ? 'page' : undefined}
				onClick={event => {
					event.preventDefault()

					if (type && !isLicensed() && isProType(type)) {
						setIsUpgradeDialogOpen(true)
					} else {
						setCurrentType(type)
					}
				}}
			>
				{type && <Badge name={type} />}
				<span className={classnames(`${tabName}-label`, 'snippet-type-name')}>
					{type
						? SNIPPET_TYPE_LABELS[type]
						: <>
							<span className="snippet-type-name-full">{__('All Snippets', 'code-snippets')}</span>
							<span className="snippet-type-name-short">{__('All', 'code-snippets')}</span>
						</>}
				</span>
				{count ? <span className="subnav-count">{count}</span> : null}
				{type && isProType(type) && !isLicensed() && <span className="pro-chip">{__('Pro', 'code-snippets')}</span>}
			</a>
		</li>
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

// Counts render immediately from the values localized with the page, then
// switch to live values derived from the snippets list once it has loaded.
const useSnippetTypeCounts = () => {
	const { snippetsList } = useSnippetsList()

	const countedSnippets = useMemo(
		() => snippetsList?.filter(snippet => !snippet.trashed),
		[snippetsList]
	)

	const typeCounts = useMemo(
		() => countedSnippets?.reduce((counts, snippet) => {
			const type = getSnippetType(snippet)
			return counts.set(type, (counts.get(type) ?? 0) + 1)
		}, new Map<SnippetType, number>()),
		[countedSnippets]
	)

	const localized = window.CODE_SNIPPETS_MANAGE?.typeCounts

	const getCount = useCallback(
		(type?: SnippetType) => countedSnippets
			? type ? typeCounts?.get(type) ?? 0 : countedSnippets.length
			: localized?.[type ?? 'all'],
		[countedSnippets, typeCounts, localized]
	)

	return { getCount }
}

const SnippetsTableInner = () => {
	const [isUpgradeDialogOpen, setIsUpgradeDialogOpen] = useState(false)
	const { snippetView, setSnippetView } = useSnippetView()
	const { currentType } = useSnippetsFilters()
	const { getCount } = useSnippetTypeCounts()

	return (
		<>
			<div className="snippet-type-nav-container">
				<nav
					className="snippet-type-nav"
					aria-label={__('Snippet types', 'code-snippets')}
				>
					<ul>
						<SnippetTypeTab
							count={getCount()}
							setIsUpgradeDialogOpen={setIsUpgradeDialogOpen}
						/>
						{SNIPPET_TYPES.map(type =>
							<SnippetTypeTab
								key={type}
								type={type}
								count={getCount(type)}
								setIsUpgradeDialogOpen={setIsUpgradeDialogOpen}
							/>)}
					</ul>
				</nav>
				<span className="snippet-type-nav-fade" aria-hidden="true" />
			</div>

			<ScreenMetaSlot />

			<div className="snippets-page-header">
				<h1>{sprintf(
					// translators: %s: label of the currently selected snippet type.
					__('Local Snippets: %s', 'code-snippets'),
					currentType ? SNIPPET_TYPE_LABELS[currentType] : __('All Snippets', 'code-snippets')
				)}</h1>
				<a href={getSnippetAddNewUrl(currentType)} className="button button-primary">
					{__('Create new Snippet', 'code-snippets')}
				</a>
			</div>

			<hr className="wp-header-end" />

			<SafeModeNotice />

			<WithFilteredSnippetsContext>
				<SnippetsListTable snippetView={snippetView} setSnippetView={setSnippetView} />
			</WithFilteredSnippetsContext>

			<UpsellDialog isOpen={isUpgradeDialogOpen} setIsOpen={setIsUpgradeDialogOpen} />
		</>
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
