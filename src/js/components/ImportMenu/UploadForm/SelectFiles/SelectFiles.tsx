import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'
import { useRestAPI } from '../../../../hooks/useRestAPI'
import { REST_NAMESPACED } from '../../../../utils/restAPI'
import { Button } from '../../../common/Button'
import { ImportCard } from '../../common/ImportCard'
import { DragDropUploadArea } from './DragDropUploadArea'
import { SelectedFilesList } from './SelectedFilesList'
import type { ImportResult } from '../SelectSnippets/ImportResultDisplay'
import type { ImportableSnippetSchema } from '../../../../types/schema/ImportableSnippetSchema'
import type { Dispatch, RefObject, SetStateAction} from 'react'

interface FileParseResponse {
	snippets: ImportableSnippetSchema[]
	total_count: number
	message: string
	warnings?: string[]
}

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

export interface UploadedFile {
	id: string
	name: string
	file: File
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
	const { api } = useRestAPI()
	const [isUploading, setIsUploading] = useState(false)

	const handleUpload = () => {
		if (!selectedFiles || 0 === selectedFiles.length) {
			alert(__('Please select files to upload.', 'code-snippets'))
			return
		}

		setIsUploading(true)
		setImportResult(undefined)

		const formData = new FormData()

		for (const selectedFile of selectedFiles) {
			formData.append('files[]', selectedFile.file)
		}

		api.post<FileParseResponse, FormData>(
			`${REST_NAMESPACED}1/file-upload/parse`,
			formData,
			{ headers: { 'Content-Type': 'multipart/form-data' } })
			.then(({ snippets, message, warnings }) => {
				onSuccess(snippets)

				if (warnings && 0 < warnings.length) {
					setImportResult({ success: true, message, warnings })
				}
			})
			.catch((error: unknown) => {
				console.error('Parse error:', error)
				setImportResult({
					success: false,
					message: error instanceof Error ? error.message : __('An unknown error occurred.', 'code-snippets')
				})
			})
			.finally(() => setIsUploading(false))
	}

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

			{selectedFiles && 0 < selectedFiles.length &&
				<SelectedFilesList files={selectedFiles} onRemoveFile={handleRemoveFile} />}

			<footer>
				<Button
					primary
					onClick={handleUpload}
					disabled={!selectedFiles || 0 === selectedFiles.length || isUploading}
				>
					{isUploading
						? __('Uploading files…', 'code-snippets')
						: __('Upload files', 'code-snippets')}
				</Button>
			</footer>
		</ImportCard>
	)
}
