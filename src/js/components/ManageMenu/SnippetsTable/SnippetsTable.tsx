import { __, sprintf } from '@wordpress/i18n'
import { createInterpolateElement } from '@wordpress/element'
import React from 'react'
import classnames from 'classnames'
import { useHorizontalScrollOverflow } from '../../../hooks/useHorizontalScrollOverflow'
import { WithRestAPIContext } from '../../../hooks/useRestAPI'
import { useSnippetView } from '../../../hooks/useSnippetView'
import { WithSnippetsAPIContext } from '../../../hooks/useSnippetsAPI'
import { WithSnippetsListContext } from '../../../hooks/useSnippetsList'
import { SNIPPET_TYPES } from '../../../types/Snippet'
import { isLicensed } from '../../../utils/screen'
import { SNIPPET_TYPE_LABELS, getSnippetAddNewUrl, isProType } from '../../../utils/snippets/snippets'
import { buildUrl } from '../../../utils/urls'
import { Badge } from '../../common/Badge'
import { Notice } from '../../common/Notice'
import { ScreenMetaSlot } from '../../common/ScreenMetaSlot'
import { UpsellPage } from '../../common/UpsellDialog'
import { WithSnippetsTableFilters, useSnippetsFilters } from './WithSnippetsTableFilters'
import { WithFilteredSnippetsContext } from './WithFilteredSnippetsContext'
import { SnippetsListTable } from './SnippetsListTable'
import type { SnippetType } from '../../../types/Snippet'

interface SnippetTypeTabProps {
	type?: SnippetType
}

const SnippetTypeTab: React.FC<SnippetTypeTabProps> = ({ type }) => {
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
					setCurrentType(type)
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

const SnippetsTableInner = () => {
	const { snippetView, setSnippetView } = useSnippetView()
	const { currentType } = useSnippetsFilters()
	const { atStart, atEnd, scrollRef } = useHorizontalScrollOverflow()

	return (
		<>
			<div
				className={classnames('snippet-type-nav-wrapper', {
					'has-scroll-start': !atStart,
					'has-scroll-end': !atEnd
				})}
			>
				<nav
					ref={scrollRef}
					className="snippet-type-nav"
					aria-label={__('Snippet types', 'code-snippets')}
				>
					<ul>
						<SnippetTypeTab />
						{SNIPPET_TYPES.map(type => <SnippetTypeTab key={type} type={type} />)}
					</ul>
				</nav>
			</div>

			<ScreenMetaSlot />

			<div className="snippets-page-header">
				<h1>{sprintf(
					// translators: %s: label of the currently selected snippet type.
					__('Local Snippets: %s', 'code-snippets'),
					currentType ? SNIPPET_TYPE_LABELS[currentType] : __('All Snippets', 'code-snippets')
				)}</h1>
				<a href={getSnippetAddNewUrl(currentType)} className="button button-primary">
					{__('Create New', 'code-snippets')}
				</a>
			</div>

			<hr className="wp-header-end" />

			<SafeModeNotice />

			{currentType && !isLicensed() && isProType(currentType)
				? <UpsellPage />
				: <WithFilteredSnippetsContext>
					<SnippetsListTable snippetView={snippetView} setSnippetView={setSnippetView} />
				</WithFilteredSnippetsContext>}
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
