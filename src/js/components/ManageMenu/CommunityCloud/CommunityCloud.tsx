import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'
import { WithRestAPIContext } from '../../../hooks/useRestAPI'
import { fetchConstQueryParam } from '../../../utils/urls'
import { AdminTabs } from '../../common/AdminTabs'
import { WithCloudSearchContext } from './WithCloudSearchContext'
import { CloudSearch } from './CloudSearch'

const TAB_QUERY_PARAM = 'tab'
const TABS = ['snippets', 'bundles'] as const

export const CommunityCloud = () => {
	const [currentTab, setCurrentTab] = useState(() => fetchConstQueryParam(TAB_QUERY_PARAM, TABS) ?? TABS[0])

	return (
		<>
			<h2>{__('Community Cloud', 'code-snippets')}</h2>
			<hr className="wp-header-end" />

			<AdminTabs
				tabs={TABS}
				ariaLabel={__('Community Cloud types', 'code-snippets')}
				proTabs={['bundles']}
				tabLabels={{
					snippets: __('Code Snippets', 'code-snippets'),
					bundles: __('Bundles', 'code-snippets')
				}}
				currentTab={currentTab}
				setCurrentTab={setCurrentTab}
				queryArgName={TAB_QUERY_PARAM}
			/>

			<WithRestAPIContext>
				{'snippets' === currentTab && (
					<WithCloudSearchContext>
						<CloudSearch />
					</WithCloudSearchContext>)}
			</WithRestAPIContext>
		</>
	)
}
