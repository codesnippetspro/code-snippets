import React from 'react'
import { __ } from '@wordpress/i18n'
import { formatFileSize } from '../utils/fileUtils'

interface SelectedFilesListProps {
	files: FileList
	onRemoveFile: (index: number) => void
}

export const SelectedFilesList: React.FC<SelectedFilesListProps> = ({
	files,
	onRemoveFile
}) => {
	return (
		<div className="selected-files" style={{ marginBottom: '20px' }}>
			<h3 style={{ margin: '0 0 12px 0', fontSize: '14px', fontWeight: '600' }}>
				{__('Selected Files:', 'code-snippets')} ({files.length})
			</h3>
			<div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
				{Array.from(files).map((file, index) => (
					<div 
						key={index}
						style={{
							display: 'flex',
							alignItems: 'center',
							justifyContent: 'space-between',
							padding: '8px 12px',
							backgroundColor: '#f9f9f9',
							borderRadius: '4px',
							border: '1px solid #ddd'
						}}
					>
						<div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
							<span style={{ fontSize: '16px' }}>📄</span>
							<div>
								<div style={{ fontWeight: '500' }}>{file.name}</div>
								<div style={{ fontSize: '12px', color: '#666' }}>
									{formatFileSize(file.size)}
								</div>
							</div>
						</div>
						<button
							type="button"
							onClick={(e) => {
								e.stopPropagation()
								onRemoveFile(index)
							}}
							style={{
								background: 'none',
								border: 'none',
								color: '#d63638',
								cursor: 'pointer',
								fontSize: '16px',
								padding: '4px'
							}}
							title={__('Remove file', 'code-snippets')}
						>
							✕
						</button>
					</div>
				))}
			</div>
		</div>
	)
}
