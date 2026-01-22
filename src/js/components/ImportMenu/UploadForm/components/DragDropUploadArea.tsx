import React from 'react'
import classnames from 'classnames'
import { __ } from '@wordpress/i18n'
import { useDragAndDrop } from '../hooks/useDragAndDrop'
import type { RefObject } from 'react'

export interface DragDropUploadAreaProps {
	fileInputRef: RefObject<HTMLInputElement>
	onFileSelect: (files: FileList | null) => void
	disabled?: boolean
}

export const DragDropUploadArea: React.FC<DragDropUploadAreaProps> = ({ fileInputRef, onFileSelect, disabled }) => {
	const { dragOver, handleDragOver, handleDragLeave, handleDrop } = useDragAndDrop({ onFilesDrop: onFileSelect })

	const handleClick = () => {
		if (!disabled) {
			fileInputRef.current?.click()
		}
	}

	return (
		<>
			<div
				className={classnames('upload-drop-zone', { 'drag-over': dragOver, 'disabled': disabled })}
				onDragOver={handleDragOver}
				onDragLeave={handleDragLeave}
				onDrop={handleDrop}
				onClick={handleClick}
			>
				<div className="drop-zone-icon">📁</div>
				<p>{__('Drag and drop files here, or click to browse', 'code-snippets')}</p>
				<p>{__('Supports JSON and XML files', 'code-snippets')}</p>
			</div>

			<input
				ref={fileInputRef}
				type="file"
				accept="application/json,.json,text/xml"
				multiple
				onChange={event => onFileSelect(event.target.files)}
				disabled={disabled}
			/>
		</>
	)
}
