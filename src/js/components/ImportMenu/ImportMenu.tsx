import React, { useState } from 'react'
import classnames from 'classnames'
import { __, sprintf } from '@wordpress/i18n'
import { WithRestAPIContext } from '../../hooks/useRestAPI'
import { ScreenMetaSlot } from '../common/ScreenMetaSlot'
import { SubnavTabs, getTabFromQuery } from '../common/SubnavTabs'
import { Toolbar } from '../common/Toolbar'
import { UploadForm } from './UploadForm/UploadForm'
import { MigrateForm } from './MigrateForm/MigrateForm'
import type { SubnavTab} from '../common/SubnavTabs'
import type { ReactNode } from 'react'

const TAB_QUERY_PARAM = 'tab'

interface ImportMenuTab extends SubnavTab<'upload' | 'migrate'> {
	content: ReactNode
}

const TABS: ImportMenuTab[] = [
	{
		name: 'upload',
		label: __('Upload snippets', 'code-snippets'),
		content: <UploadForm />
	},
	{
		name: 'migrate',
		label: __('Migrate from other plugins', 'code-snippets'),
		content: <MigrateForm />
	}
]

export const ImportMenu: React.FC = () => {
	const [currentTab, setCurrentTab] = useState<ImportMenuTab>(() => getTabFromQuery(TABS, TAB_QUERY_PARAM))

	return (
		<>
			<Toolbar />

			<SubnavTabs
				className="import-type-nav"
				ariaLabel={__('Import sources', 'code-snippets')}
				tabs={TABS}
				currentTab={currentTab}
				queryParamName={TAB_QUERY_PARAM}
				setCurrentTab={setCurrentTab}
			/>

			<ScreenMetaSlot />

			<div className="snippets-page-header">
				<h1>{// translators: %s: label of the currently selected import tab.
					sprintf(__('Import: %s', 'code-snippets'), currentTab.label)}</h1>
			</div>

			<hr className="wp-header-end" />

			<WithRestAPIContext>
				{TABS.map(tab =>
					<div
						key={tab.name}
						className={classnames('import-snippets-section', { 'active-section': tab.name === currentTab.name })}
					>
						{tab.content}
					</div>)}
			</WithRestAPIContext>
		</>
	)
}
