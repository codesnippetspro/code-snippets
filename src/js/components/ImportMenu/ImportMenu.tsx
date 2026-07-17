import React, { useState } from 'react'
import classnames from 'classnames'
import { __, sprintf } from '@wordpress/i18n'
import { WithRestAPIContext } from '../../hooks/useRestAPI'
import { fetchQueryParam, updateQueryParams } from '../../utils/urls'
import { ScreenMetaSlot } from '../common/ScreenMetaSlot'
import { SubnavTabs } from '../common/SubnavTabs'
import { Toolbar } from '../common/Toolbar'
import { UploadForm } from './UploadForm/UploadForm'
import { MigrateForm } from './MigrateForm/MigrateForm'
import type { ReactNode } from 'react'

const TABS = ['upload', 'migrate'] as const

type TabType = typeof TABS[number]

const TAB_CONTENT: Record<TabType, ReactNode> = {
	upload: <UploadForm />,
	migrate: <MigrateForm />
}

const TAB_LABELS: Record<TabType, string> = {
	upload: __('Upload snippets', 'code-snippets'),
	migrate: __('Migrate from other plugins', 'code-snippets')
}

const isValidTab = (value: string): value is TabType =>
	TABS.includes(value as TabType)

const getDefaultTab = (): TabType => {
	const tabParam = fetchQueryParam('tab')
	return tabParam && isValidTab(tabParam) ? tabParam : TABS[0]
}

export const ImportMenu: React.FC = () => {
	const [activeTab, setActiveTab] = useState<TabType>(getDefaultTab)

	return (
		<>
			<Toolbar />

			<SubnavTabs
				className="import-type-nav"
				ariaLabel={__('Import sources', 'code-snippets')}
				tabs={TABS.map(tab => ({ name: tab, label: TAB_LABELS[tab] }))}
				currentTab={activeTab}
				setCurrentTab={tab => {
					setActiveTab(tab)
					updateQueryParams({ tab })
				}}
			/>

			<ScreenMetaSlot />

			<div className="snippets-page-header">
				<h1>{sprintf(
					// translators: %s: label of the currently selected import tab.
					__('Import: %s', 'code-snippets'),
					TAB_LABELS[activeTab]
				)}</h1>
			</div>

			<hr className="wp-header-end" />

			<WithRestAPIContext>
				{TABS.map(tab =>
					<div
						key={tab}
						className={classnames('import-snippets-section', { 'active-section': tab === activeTab })}
					>
						{TAB_CONTENT[tab]}
					</div>)}
			</WithRestAPIContext>
		</>
	)
}
