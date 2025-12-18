import React, { useState, useEffect } from 'react'
import { __ } from '@wordpress/i18n'
import { FileUploadForm } from './FromFileUpload/FileUploadForm'
import { ImportForm } from './FromOtherPlugins/ImportForm'
import { ImportSection } from './shared'

type TabType = 'upload' | 'plugins'

export const ImportApp: React.FC = () => {
	const [activeTab, setActiveTab] = useState<TabType>('upload')

	useEffect(() => {
		const urlParams = new URLSearchParams(window.location.search)
		const tabParam = urlParams.get('tab') as TabType
		if (tabParam === 'plugins' || tabParam === 'upload') {
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
					className={`nav-tab${activeTab === 'upload' ? ' nav-tab-active' : ''}`}
					href="#"
					onClick={(e) => {
						e.preventDefault()
						handleTabChange('upload')
					}}
				>
					{__('Import Snippets', 'code-snippets')}
				</a>
				<a
					className={`nav-tab${activeTab === 'plugins' ? ' nav-tab-active' : ''}`}
					href="#"
					onClick={(e) => {
						e.preventDefault()
						handleTabChange('plugins')
					}}
				>
					{__('Import from other plugins', 'code-snippets')}
				</a>
			</h2>

			<ImportSection active={activeTab === 'upload'}>
				<FileUploadForm />
			</ImportSection>

			<ImportSection active={activeTab === 'plugins'}>
				<ImportForm />
			</ImportSection>
		</div>
	)
}
