import React, { useState } from 'react'
import { __, sprintf } from '@wordpress/i18n'
import { WithRestAPIContext } from '../../../hooks/useRestAPI'
import { useSnippetView } from '../../../hooks/useSnippetView'
import { fetchConstQueryParam, updateQueryParams } from '../../../utils/urls'
import { ScreenMetaSlot } from '../../common/ScreenMetaSlot'
import { SubnavTabs } from '../../common/SubnavTabs'
import { WithCloudSearchContext } from './WithCloudSearchContext'
import { CloudSearch } from './CloudSearch'

const TAB_QUERY_PARAM = 'tab'
const TABS = ['snippets', 'bundles'] as const

type CommunityTab = typeof TABS[number]

const TAB_LABELS: Record<CommunityTab, string> = {
	snippets: __('Snippets', 'code-snippets'),
	bundles: __('Bundles', 'code-snippets')
}

const CommunityCloudInner = () => {
	const [currentTab, setCurrentTab] =
		useState<CommunityTab>(() => fetchConstQueryParam(TAB_QUERY_PARAM, TABS) ?? TABS[0])
	const { snippetView, setSnippetView } = useSnippetView()

	return (
		<>
			<SubnavTabs
				className="community-cloud-nav"
				ariaLabel={__('Community Cloud types', 'code-snippets')}
				tabs={[
					{
						name: 'snippets',
						label: TAB_LABELS.snippets
					},
					{
						name: 'bundles',
						label: TAB_LABELS.bundles,
						pro: true
					}
				]}
				currentTab={currentTab}
				setCurrentTab={tab => {
					updateQueryParams({ [TAB_QUERY_PARAM]: tab })
					setCurrentTab(tab)
				}}
			/>

			<ScreenMetaSlot />

			<div className="snippets-page-header">
				<h1>{sprintf(
					// translators: %s: label of the currently selected community cloud tab.
					__('Community Cloud: %s', 'code-snippets'),
					TAB_LABELS[currentTab]
				)}</h1>
			</div>

			<hr className="wp-header-end" />

			<p className="snippets-page-description">
				{__(
					'Search the community cloud and download user-shared snippets directly to your site.',
					'code-snippets'
				)}
			</p>

			{'snippets' === currentTab && (
				<CloudSearch snippetView={snippetView} setSnippetView={setSnippetView} />)}
		</>
	)
}

export const CommunityCloud = () =>
	<WithRestAPIContext>
		<WithCloudSearchContext>
			<CommunityCloudInner />
		</WithCloudSearchContext>
	</WithRestAPIContext>
