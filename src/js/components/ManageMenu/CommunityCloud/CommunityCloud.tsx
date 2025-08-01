import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'
import classnames from 'classnames'
import { addQueryArgs } from '@wordpress/url'
import { WithCloudSearchContext } from '../../../hooks/useCloudSearch'
import { WithRestAPIContext } from '../../../hooks/useRestAPI'
import { fetchQueryParam, updateQueryParam } from '../../../utils/urls'
import { CloudSearch } from './CloudSearch'

const TABS = ['snippets', 'bundles'] as const
type TabName = typeof TABS[number]

const TAB_LABELS: Record<TabName, string> = {
	snippets: __('Code Snippets', 'code-snippets'),
	bundles: __('Bundles', 'code-snippets')
}

interface NavTabsProps {
	currentTab: TabName
	setCurrentTab: (tab: TabName) => void
}

const NavTabs: React.FC<NavTabsProps> = ({ currentTab, setCurrentTab }) =>
	<h2 className="nav-tab-wrapper">
		{TABS.map(tab =>
			<a
				key={tab}
				href={addQueryArgs(window.location.href, { type: tab })}
				className={classnames('nav-tab', `${tab}-tab`, { 'nav-tab-active': tab === currentTab })}
				onClick={event => {
					event.preventDefault()
					updateQueryParam('tab', tab)
					setCurrentTab(tab)
				}}
			>
				{TAB_LABELS[tab]}
			</a>)}
	</h2>

export const CommunityCloud = () => {
	const [currentTab, setCurrentTab] = useState(() => fetchQueryParam('tab') as TabName | null ?? TABS[0])

	return (
		<div className="wrap">
			<div className="snippets-page-heading">
				<h1>{__('Community Cloud', 'code-snippets')}</h1>
			</div>

			<NavTabs currentTab={currentTab} setCurrentTab={setCurrentTab} />

			{'snippets' === currentTab
				? <WithRestAPIContext>
					<WithCloudSearchContext>
						<CloudSearch />
					</WithCloudSearchContext>
				</WithRestAPIContext>
				: null}
		</div>
	)
}
