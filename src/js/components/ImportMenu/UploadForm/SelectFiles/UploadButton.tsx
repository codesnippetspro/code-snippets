import { __ } from '@wordpress/i18n'
import React from 'react'
import { useRestAPI } from '../../../../hooks/useRestAPI'
import { unpackErrorResponse } from '../../../../utils/errors'
import { REST_BASES } from '../../../../utils/restAPI'
import { Button } from '../../../common/Button'
import type { Dispatch, SetStateAction } from 'react'
import type { ImportableSnippetSchema } from '../../../../types/schema/ImportableSnippetSchema'
import type { ImportResult } from '../SelectSnippets/ImportResultDisplay'

interface FileParseResponse {
	snippets: ImportableSnippetSchema[]
	total_count: number
	message: string
	warnings?: string[]
}

export interface UploadedFile {
	id: string
	name: string
	file: File
}

export interface UploadButtonProps {
	onSuccess: (snippets: ImportableSnippetSchema[]) => void
	selectedFiles: UploadedFile[] | undefined
	setImportResult: (result: ImportResult | undefined) => void
	isUploading: boolean
	setIsUploading: Dispatch<SetStateAction<boolean>>
}

export const UploadButton: React.FC<UploadButtonProps> = ({ isUploading, setIsUploading, selectedFiles, onSuccess, setImportResult }) => {
	const { api } = useRestAPI()

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
			`${REST_BASES.import.files}/parse`,
			formData,
			{ headers: { 'Content-Type': 'multipart/form-data' } })
			.then(({ snippets, message, warnings }) => {
				onSuccess(snippets)

				if (warnings && 0 < warnings.length) {
					setImportResult({ step: 'upload', success: true, message, warnings })
				}
			})
			.catch((error: unknown) => {
				console.error('Parse error:', error)
				setImportResult({ step: 'upload', success: false, message: unpackErrorResponse(error) })
			})
			.finally(() => setIsUploading(false))
	}

	return (
		<Button
			primary
			onClick={handleUpload}
			disabled={!selectedFiles || 0 === selectedFiles.length || isUploading}
		>
			{isUploading
				? __('Uploading files…', 'code-snippets')
				: __('Upload files', 'code-snippets')}
		</Button>
	)
}
