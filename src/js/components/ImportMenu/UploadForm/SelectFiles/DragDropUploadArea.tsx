import React, { useState } from 'react'
import classnames from 'classnames'
import { __ } from '@wordpress/i18n'
import type { DragEventHandler, RefObject } from 'react'

export interface DragDropUploadAreaProps {
	fileInputRef: RefObject<HTMLInputElement>
	onFileSelect: (files: FileList | undefined) => void
	disabled?: boolean
}

export const DragDropUploadArea: React.FC<DragDropUploadAreaProps> = ({ fileInputRef, onFileSelect, disabled }) => {
	const [dragOver, setDragOver] = useState(false)

	const handleDragOver: DragEventHandler<HTMLElement> = event => {
		event.preventDefault()
		setDragOver(true)
	}

	const handleDragLeave: DragEventHandler<HTMLElement> = event => {
		event.preventDefault()
		setDragOver(false)
	}

	const handleFiles = (files: FileList) => {
		if (0 < files.length) {
			onFileSelect(files)
		}
	}

	const handleClick = () => {
		if (!disabled) {
			fileInputRef.current?.click()
		}
	}

	return (
		<>
			<div
				className={classnames('upload-drop-zone', { 'drag-over': dragOver, 'disabled': disabled })}
				onClick={handleClick}
				onDragOver={handleDragOver}
				onDragLeave={handleDragLeave}
				onDrop={event => {
					handleDragLeave(event)
					handleFiles(event.dataTransfer.files)
				}}
			>
				<div className="drop-zone-icon" aria-hidden="true">📁</div>
				<p>{__('Drag and drop files here, or click to browse', 'code-snippets')}</p>
				<p>{__('Supports JSON and XML files', 'code-snippets')}</p>
			</div>

			<input
				ref={fileInputRef}
				type="file"
				accept="application/json,.json,text/xml"
				multiple
				onChange={event => onFileSelect(event.target.files ?? undefined)}
				disabled={disabled}
			/>
		</>
	)
}
