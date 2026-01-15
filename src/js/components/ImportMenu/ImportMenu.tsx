import React, { useEffect, useState } from 'react'
import { __ } from '@wordpress/i18n'
import { FileUploadForm } from './FromFileUpload/FileUploadForm'
import { ImportForm } from './FromOtherPlugins/ImportForm'
import { ImportSection } from './common/components/ImportSection'

type TabType = 'upload' | 'plugins'

const isTabType = (value: string): value is TabType =>
	'upload' === value || 'plugins' === value

export const ImportMenu: React.FC = () => {
	const [activeTab, setActiveTab] = useState<TabType>('upload')

	useEffect(() => {
		const urlParams = new URLSearchParams(window.location.search)
		const tabParam = urlParams.get('tab')
		if (tabParam && isTabType(tabParam)) {
			setActiveTab(tabParam)
		}
	}, [])

	const handleTabChange = (tab: TabType) => {
		setActiveTab(tab)

		const url = new URL(window.location.href)
		url.searchParams.set('tab', tab)
		window.history.replaceState({}, '', url)
	}

	return (
		<div className="narrow" style={{ maxWidth: '800px' }}>
			<h2 className="nav-tab-wrapper" style={{ marginBottom: '20px' }}>
				<a
					className={`nav-tab${'upload' === activeTab ? ' nav-tab-active' : ''}`}
					href="#"
					onClick={e => {
						e.preventDefault()
						handleTabChange('upload')
					}}
				>
					{__('Import Snippets', 'code-snippets')}
				</a>
				<a
					className={`nav-tab${'plugins' === activeTab ? ' nav-tab-active' : ''}`}
					href="#"
					onClick={e => {
						e.preventDefault()
						handleTabChange('plugins')
					}}
				>
					{__('Import from other plugins', 'code-snippets')}
				</a>
			</h2>

			<ImportSection active={'upload' === activeTab}>
				<FileUploadForm />
			</ImportSection>

			<ImportSection active={'plugins' === activeTab}>
				<ImportForm />
			</ImportSection>
		</div>
	)
}
