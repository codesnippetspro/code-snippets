import React, { useState } from 'react'
import classnames from 'classnames'
import { __ } from '@wordpress/i18n'
import { WithRestAPIContext } from '../../hooks/useRestAPI'
import { fetchQueryParam, updateQueryParam } from '../../utils/urls'
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
			<div className="import-snippets-menu wrap">
				<h2>{__('Import Snippets', 'code-snippets')}</h2>

				<div className="narrow">
					<nav
						className="nav-tab-wrapper"
						aria-label={__('Import sources', 'code-snippets')}
					>
						{TABS.map(tab =>
							<button
								key={tab}
								type="button"
								className={classnames('nav-tab', { 'nav-tab-active': tab === activeTab })}
								onClick={() => {
									setActiveTab(tab)
									updateQueryParam('tab', tab)
								}}
							>
								{TAB_LABELS[tab]}
							</button>)}
					</nav>

					<WithRestAPIContext>
						{TABS.map(tab =>
							<div key={tab} className={classnames('import-snippets-section', { 'active-section': tab === activeTab })}>
								{TAB_CONTENT[tab]}
							</div>)}
					</WithRestAPIContext>
				</div>
			</div>
		</>
	)
}
