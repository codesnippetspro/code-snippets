import { useState, useRef } from 'react'
import { removeFileFromList } from '../utils/fileUtils'

export const useFileSelection = () => {
	const [selectedFiles, setSelectedFiles] = useState<FileList | null>(null)
	const fileInputRef = useRef<HTMLInputElement>(null)

	const handleFileSelect = (files: FileList | null) => {
		setSelectedFiles(files)
	}

	const removeFile = (index: number) => {
		if (!selectedFiles) return
		
		const newFiles = removeFileFromList(selectedFiles, index)
		setSelectedFiles(newFiles)
		
		if (fileInputRef.current) {
			fileInputRef.current.files = newFiles
		}
	}

	const clearFiles = () => {
		setSelectedFiles(null)
		if (fileInputRef.current) {
			fileInputRef.current.value = ''
		}
	}

	const triggerFileInput = () => {
		fileInputRef.current?.click()
	}

	return {
		selectedFiles,
		fileInputRef,
		handleFileSelect,
		removeFile,
		clearFiles,
		triggerFileInput
	}
}
