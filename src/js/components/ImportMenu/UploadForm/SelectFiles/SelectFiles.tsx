import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'
import { ImportCard } from '../../common/ImportCard'
import { DragDropUploadArea } from './DragDropUploadArea'
import { SelectedFilesList } from './SelectedFilesList'
import { UploadButton } from './UploadButton'
import type { UploadedFile } from './UploadButton'
import type { ImportResult } from '../SelectSnippets/ImportResultDisplay'
import type { ImportableSnippetSchema } from '../../../../types/schema/ImportableSnippetSchema'
import type { Dispatch, RefObject, SetStateAction } from 'react'

const processFileList = (fileList: FileList): UploadedFile[] => {
	const uuids = new Uint32Array(fileList.length)
	window.crypto.getRandomValues(uuids)

	return Array.from(uuids).map((uuid, index) => ({
		id: uuid.toString(),
		name: fileList[index].name,
		file: fileList[index]
	}))
}

const buildFileList = (files: UploadedFile[]): FileList => {
	const dataTransfer = new DataTransfer()
	files.forEach(newFile => dataTransfer.items.add(newFile.file))
	return dataTransfer.files
}

export interface SelectFilesProps {
	onSuccess: (snippets: ImportableSnippetSchema[]) => void
	fileInputRef: RefObject<HTMLInputElement>
	selectedFiles: UploadedFile[] | undefined
	setImportResult: (result: ImportResult | undefined) => void
	setSelectedFiles: Dispatch<SetStateAction<UploadedFile[] | undefined>>
}

export const SelectFiles: React.FC<SelectFilesProps> = ({
	onSuccess,
	fileInputRef,
	selectedFiles,
	setImportResult,
	setSelectedFiles
}) => {
	const [isUploading, setIsUploading] = useState(false)

	const handleRemoveFile = (file: UploadedFile) => {
		setSelectedFiles(previous => {
			const newFiles = previous?.filter(fileItem => fileItem.id !== file.id)

			if (newFiles && fileInputRef.current) {
				fileInputRef.current.files = buildFileList(newFiles)
			}

			return newFiles
		})
	}

	return (
		<ImportCard className="import-upload-card">
			<h2>{__('Choose files', 'code-snippets')}</h2>
			<p className="description">
				{__('Choose one or more Code Snippets (.xml or .json) files to parse and preview.', 'code-snippets')}
			</p>

			<DragDropUploadArea
				fileInputRef={fileInputRef}
				disabled={isUploading}
				onFileSelect={fileList => {
					setSelectedFiles(fileList && processFileList(fileList))
					setImportResult(undefined)
				}}
			/>

			{selectedFiles && 0 < selectedFiles.length && (
				<SelectedFilesList
					files={selectedFiles}
					onRemoveFile={handleRemoveFile}
				/>)}

			<footer>
				<UploadButton {...{ isUploading, setIsUploading, onSuccess, selectedFiles, setImportResult }} />
			</footer>
		</ImportCard>
	)
}
