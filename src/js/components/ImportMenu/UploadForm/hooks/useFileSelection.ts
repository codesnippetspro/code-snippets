import { useRef, useState } from 'react'

const removeFileFromList = (fileList: FileList, indexToRemove: number): FileList => {
	const dataTransfer = new DataTransfer()

	for (let i = 0; i < fileList.length; i++) {
		if (i !== indexToRemove) {
			dataTransfer.items.add(fileList[i])
		}
	}

	return dataTransfer.files
}

export interface FileSelection {
	selectedFiles: FileList | null
	fileInputRef: React.RefObject<HTMLInputElement>
	handleFileSelect: (files: FileList | null) => void
	removeFile: (index: number) => void
	clearFiles: VoidFunction
	triggerFileInput: VoidFunction
}

export const useFileSelection = (): FileSelection => {
	const [selectedFiles, setSelectedFiles] = useState<FileList | null>(null)
	const fileInputRef = useRef<HTMLInputElement>(null)

	const handleFileSelect = (files: FileList | null) => {
		setSelectedFiles(files)
	}

	const removeFile = (index: number) => {
		if (!selectedFiles) {
			return
		}

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
