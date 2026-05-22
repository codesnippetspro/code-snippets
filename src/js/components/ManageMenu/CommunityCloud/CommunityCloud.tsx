import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'
import classnames from 'classnames'
import { WithRestAPIContext } from '../../../hooks/useRestAPI'
import { isLicensed } from '../../../utils/screen'
import { buildUrl, fetchQueryParam, updateQueryParam } from '../../../utils/urls'
import { UpsellDialog } from '../../common/UpsellDialog'
import { WithCloudSearchContext } from './WithCloudSearchContext'
import { CloudSearch } from './CloudSearch'

const TABS = ['snippets', 'bundles'] as const
type TabName = typeof TABS[number]

const TAB_LABELS: Record<TabName, string> = {
	snippets: __('Code Snippets', 'code-snippets'),
	bundles: __('Bundles', 'code-snippets')
}

const PRO_TABS: TabName[] = ['bundles']

export const CommunityCloud = () => {
	const [currentTab, setCurrentTab] = useState(() => fetchQueryParam('tab') as TabName | null ?? TABS[0])
	const [isUpsellDialogOpen, setIsUpsellDialogOpen] = useState(false)

	return (
		<>
			<h2>{__('Community Cloud', 'code-snippets')}</h2>

			<hr className="wp-header-end" />

			<nav
				className="nav-tab-wrapper"
				aria-label={__('Community Cloud types', 'code-snippets')}
			>
				{TABS.map(tab =>
					<a
						key={tab}
						href={buildUrl(window.location.href, { type: tab })}
						className={classnames('nav-tab', `${tab}-tab`, { 'nav-tab-active': tab === currentTab })}
						aria-current={tab === currentTab ? 'page' : undefined}
						onClick={event => {
							event.preventDefault()

							if (PRO_TABS.includes(tab) && !isLicensed()) {
								setIsUpsellDialogOpen(true)
							} else {
								updateQueryParam('tab', tab)
								setCurrentTab(tab)
							}
						}}
					>
						{TAB_LABELS[tab]}
						{PRO_TABS.includes(tab) && !isLicensed() && <span className="pro-chip">{__('Pro', 'code-snippets')}</span>}
					</a>)}
			</nav>

			{'snippets' === currentTab
				? <WithRestAPIContext>
					<WithCloudSearchContext>
						<CloudSearch />
					</WithCloudSearchContext>
				</WithRestAPIContext>
				: null}

			<UpsellDialog isOpen={isUpsellDialogOpen} setIsOpen={setIsUpsellDialogOpen} />
		</>
	)
}
