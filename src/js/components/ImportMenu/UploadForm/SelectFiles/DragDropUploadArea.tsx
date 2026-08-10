import React, { useId, useState } from 'react'
import classnames from 'classnames'
import { __ } from '@wordpress/i18n'
import type { DragEventHandler, RefObject } from 'react'

export interface DragDropUploadAreaProps {
	fileInputRef: RefObject<HTMLInputElement>
	onFileSelect: (files: FileList | undefined) => void
	disabled?: boolean
}

const useDragDropZone = (
	disabled: boolean | undefined,
	onFileSelect: (files: FileList | undefined) => void
) => {
	const [dragOver, setDragOver] = useState(false)

	const handleDragOver: DragEventHandler<HTMLElement> = event => {
		if (disabled) {
			return
		}

		event.preventDefault()
		setDragOver(true)
	}

	const handleDragLeave: DragEventHandler<HTMLElement> = event => {
		if (disabled) {
			return
		}

		event.preventDefault()
		setDragOver(false)
	}

	const handleDrop: DragEventHandler<HTMLElement> = event => {
		if (disabled) {
			return
		}

		handleDragLeave(event)
		if (0 < event.dataTransfer.files.length) {
			onFileSelect(event.dataTransfer.files)
		}
	}

	return { dragOver, handleDragOver, handleDragLeave, handleDrop }
}

export const DragDropUploadArea: React.FC<DragDropUploadAreaProps> = ({ fileInputRef, onFileSelect, disabled }) => {
	const fileInputId = useId()
	const { dragOver, handleDragOver, handleDragLeave, handleDrop } = useDragDropZone(disabled, onFileSelect)

	return (
		<div
			className="upload-drop-zone-wrapper"
			onDragOver={handleDragOver}
			onDragLeave={handleDragLeave}
			onDrop={handleDrop}
		>
			<label
				htmlFor={fileInputId}
				className={classnames('upload-drop-zone', { 'drag-over': dragOver, 'disabled': disabled })}
			>
				<div className="drop-zone-icon" aria-hidden="true">📁</div>
				<p>{__('Drag and drop files here, or click to browse', 'code-snippets')}</p>
				<p>{__('Supports JSON and XML files', 'code-snippets')}</p>
			</label>
			<input
				ref={fileInputRef}
				id={fileInputId}
				className="upload-drop-zone-file-input"
				type="file"
				accept="application/json,.json,text/xml"
				multiple
				onChange={event => onFileSelect(event.target.files ?? undefined)}
				disabled={disabled}
				aria-label={__('Select files to import', 'code-snippets')}
			/>
		</div>
	)
}
