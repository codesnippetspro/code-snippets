import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'
import { WithRestAPIContext } from '../../../hooks/useRestAPI'
import { useSnippetView } from '../../../hooks/useSnippetView'
import { fetchConstQueryParam, updateQueryParams } from '../../../utils/urls'
import { SnippetsIcon } from '../../common/icons/ToolbarIcons'
import { ScreenMetaSlot } from '../../common/ScreenMetaSlot'
import { SnippetViewToggle } from '../../common/SnippetViewToggle'
import { SubnavTabs } from '../../common/SubnavTabs'
import { WithCloudSearchContext, useCloudSearch } from './WithCloudSearchContext'
import { CloudSearch } from './CloudSearch'

const TAB_QUERY_PARAM = 'tab'
const TABS = ['snippets', 'bundles'] as const

type CommunityTab = typeof TABS[number]

const CommunityCloudInner = () => {
	const [currentTab, setCurrentTab] =
		useState<CommunityTab>(() => fetchConstQueryParam(TAB_QUERY_PARAM, TABS) ?? TABS[0])
	const { snippetView, setSnippetView } = useSnippetView()
	const { searchResults } = useCloudSearch()

	return (
		<>
			<SubnavTabs
				className="community-cloud-nav"
				ariaLabel={__('Community Cloud types', 'code-snippets')}
				tabs={[
					{
						name: 'snippets',
						label: __('Code Snippets', 'code-snippets'),
						icon: <SnippetsIcon aria-hidden="true" />,
						count: searchResults?.totalItems
					},
					{
						name: 'bundles',
						label: __('Bundles', 'code-snippets'),
						icon:
							<span
								className="dashicons dashicons-screenoptions snippet-type-icon"
								aria-hidden="true"
							></span>,
						pro: true
					}
				]}
				currentTab={currentTab}
				setCurrentTab={tab => {
					updateQueryParams({ [TAB_QUERY_PARAM]: tab })
					setCurrentTab(tab)
				}}
				endContent={
					<li className="snippet-view-toggle-nav-item">
						<SnippetViewToggle snippetView={snippetView} setSnippetView={setSnippetView} />
					</li>
				}
			/>

			<ScreenMetaSlot />

			<h2>{__('Community Cloud', 'code-snippets')}</h2>

			<hr className="wp-header-end" />

			{'snippets' === currentTab && <CloudSearch snippetView={snippetView} />}
		</>
	)
}

export const CommunityCloud = () =>
	<WithRestAPIContext>
		<WithCloudSearchContext>
			<CommunityCloudInner />
		</WithCloudSearchContext>
	</WithRestAPIContext>
