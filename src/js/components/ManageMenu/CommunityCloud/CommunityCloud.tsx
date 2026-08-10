import React, { useState } from 'react'
import { __, sprintf } from '@wordpress/i18n'
import { WithRestAPIContext } from '../../../hooks/useRestAPI'
import { useSnippetView } from '../../../hooks/useSnippetView'
import { updateQueryParams } from '../../../utils/urls'
import { ScreenMetaSlot } from '../../common/ScreenMetaSlot'
import { SubnavTabs, getTabFromQuery } from '../../common/SubnavTabs'
import { WithCloudSnippetDownloadsContext } from '../../common/cloud/WithCloudSnippetDownloadsContext'
import { UpsellPage } from '../../common/UpsellDialog'
import { WithCloudSearchContext, useCloudSearch } from './WithCloudSearchContext'
import { CloudSearch } from './CloudSearch'
import type { SubnavTab} from '../../common/SubnavTabs'

const TAB_QUERY_PARAM = 'tab'

const TABS: SubnavTab[] = [
	{
		name: 'snippets',
		label: __('Snippets', 'code-snippets')
	},
	{
		name: 'bundles',
		label: __('Bundles', 'code-snippets'),
		pro: true
	}
]

const CommunityCloudInner = () => {
	const [currentTab, setCurrentTab] = useState(() => getTabFromQuery(TABS, TAB_QUERY_PARAM))
	const { snippetView, setSnippetView } = useSnippetView()
	const { searchResults } = useCloudSearch()

	const TabContent = () => {
		switch (currentTab.name) {
			case 'snippets':
				return <CloudSearch snippetView={snippetView} setSnippetView={setSnippetView} />

			case 'bundles':
				return <UpsellPage />

			default:
				return null
		}
	}

	return (
		<>
			<SubnavTabs
				className="community-cloud-nav"
				ariaLabel={__('Community Cloud types', 'code-snippets')}
				tabs={TABS}
				getTabCount={tab => 'snippets' === tab.name ? searchResults?.totalItems : undefined}
				currentTab={currentTab}
				setCurrentTab={tab => {
					updateQueryParams({ [TAB_QUERY_PARAM]: tab.name })
					setCurrentTab(tab)
				}}
			/>

			<ScreenMetaSlot hidden={'bundles' === currentTab.name} />

			{'bundles' === currentTab.name
				? null
				: <>
					<div className="snippets-page-header">
						<h1>{
							// translators: %s: label of the currently selected community cloud tab.
							sprintf(__('Community Cloud: %s', 'code-snippets'), currentTab.label)}</h1>
					</div>

					<hr className="wp-header-end" />

					<p className="snippets-page-description">
						{__('Search the community cloud and download user-shared snippets directly to your site.', 'code-snippets')}
					</p>
				</>}

			<TabContent />
		</>
	)
}

export const CommunityCloud = () =>
	<WithRestAPIContext>
		<WithCloudSnippetDownloadsContext>
			<WithCloudSearchContext>
				<CommunityCloudInner />
			</WithCloudSearchContext>
		</WithCloudSnippetDownloadsContext>
	</WithRestAPIContext>
