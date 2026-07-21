import { __ } from '@wordpress/i18n'
import { createInterpolateElement } from '@wordpress/element'
import React, { useState } from 'react'
import classnames from 'classnames'
import { WithRestAPIContext } from '../../../hooks/useRestAPI'
import { useSnippetView } from '../../../hooks/useSnippetView'
import { WithSnippetsAPIContext } from '../../../hooks/useSnippetsAPI'
import { WithSnippetsListContext, useSnippetsList } from '../../../hooks/useSnippetsList'
import { SNIPPET_TYPES } from '../../../types/Snippet'
import { isLicensed } from '../../../utils/screen'
import { SNIPPET_TYPE_LABELS, getSnippetEditUrl, isProType } from '../../../utils/snippets/snippets'
import { buildUrl } from '../../../utils/urls'
import { Badge } from '../../common/Badge'
import { SnippetsIcon } from '../../common/icons/ToolbarIcons'
import { Notice } from '../../common/Notice'
import { ScreenMetaSlot } from '../../common/ScreenMetaSlot'
import { SnippetViewToggle } from '../../common/SnippetViewToggle'
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
				{type ? <Badge small name={type} /> : <SnippetsIcon aria-hidden="true" />}
				<span className={`${tabName}-label`}>
					{type ? SNIPPET_TYPE_LABELS[type] : __('All', 'code-snippets')}
				</span>
				{undefined !== count && <span className="subnav-count">{count}</span>}
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
	const [isUpgradeDialogOpen, setIsUpgradeDialogOpen] = useState(false)
	const { snippetView, setSnippetView } = useSnippetView()
	const { snippetsList } = useSnippetsList()

	const allCount = snippetsList?.filter(snippet => !snippet.trashed).length

	return (
		<>
			<nav
				className="snippet-type-nav"
				aria-label={__('Snippet types', 'code-snippets')}
			>
				<ul>
					<SnippetTypeTab count={allCount} setIsUpgradeDialogOpen={setIsUpgradeDialogOpen} />
					{SNIPPET_TYPES.map(type =>
						<SnippetTypeTab key={type} type={type} setIsUpgradeDialogOpen={setIsUpgradeDialogOpen} />)}

					<li className="snippet-view-toggle-nav-item">
						<SnippetViewToggle snippetView={snippetView} setSnippetView={setSnippetView} />
					</li>

					<li className="create-snippet-nav-item">
						<a href={getSnippetEditUrl()} className="button button-primary">
							{__('Create new snippet', 'code-snippets')}
						</a>
					</li>
				</ul>
			</nav>

			<ScreenMetaSlot />

			<h2>{__('Snippets', 'code-snippets')}</h2>

			<hr className="wp-header-end" />

			<SafeModeNotice />

			<WithFilteredSnippetsContext>
				<SnippetsListTable snippetView={snippetView} />
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
