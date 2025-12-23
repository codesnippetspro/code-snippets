import React from 'react'
import { __ } from '@wordpress/i18n'
import { useDragAndDrop } from '../hooks/useDragAndDrop'

interface DragDropUploadAreaProps {
	fileInputRef: React.RefObject<HTMLInputElement>
	onFileSelect: (files: FileList | null) => void
	disabled?: boolean
}

export const DragDropUploadArea: React.FC<DragDropUploadAreaProps> = ({
	fileInputRef,
	onFileSelect,
	disabled = false
}) => {
	const { dragOver, handleDragOver, handleDragLeave, handleDrop } = useDragAndDrop({
		onFilesDrop: onFileSelect
	})

	const handleClick = () => {
		if (!disabled) {
			fileInputRef.current?.click()
		}
	}

	return (
		<>
			<div
				className={`upload-drop-zone ${dragOver ? 'drag-over' : ''}`}
				onDragOver={handleDragOver}
				onDragLeave={handleDragLeave}
				onDrop={handleDrop}
				onClick={handleClick}
				style={{
					border: `2px dashed ${dragOver ? '#0073aa' : '#ccd0d4'}`,
					borderRadius: '4px',
					padding: '40px 20px',
					textAlign: 'center',
					cursor: disabled ? 'not-allowed' : 'pointer',
					backgroundColor: dragOver ? '#f0f6fc' : disabled ? '#f6f7f7' : '#fafafa',
					marginBottom: '20px',
					transition: 'all 0.3s ease',
					opacity: disabled ? 0.6 : 1
				}}
			>
				<div style={{ fontSize: '48px', marginBottom: '10px', color: '#666' }}>📁</div>
				<p style={{ margin: '0 0 8px 0', fontSize: '16px', fontWeight: '500' }}>
					{__('Drag and drop files here, or click to browse', 'code-snippets')}
				</p>
				<p style={{ margin: '0', color: '#666', fontSize: '14px' }}>
					{__('Supports JSON and XML files', 'code-snippets')}
				</p>
			</div>

			<input
				ref={fileInputRef}
				type="file"
				accept="application/json,.json,text/xml"
				multiple
				onChange={(e) => onFileSelect(e.target.files)}
				style={{ display: 'none' }}
				disabled={disabled}
			/>
		</>
	)
}
