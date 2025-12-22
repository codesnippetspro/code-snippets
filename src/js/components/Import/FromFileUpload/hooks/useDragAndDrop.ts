import { useState } from 'react'

interface UseDragAndDropProps {
	onFilesDrop: (files: FileList) => void
}

export const useDragAndDrop = ({ onFilesDrop }: UseDragAndDropProps) => {
	const [dragOver, setDragOver] = useState(false)

	const handleDragOver = (e: React.DragEvent) => {
		e.preventDefault()
		setDragOver(true)
	}

	const handleDragLeave = (e: React.DragEvent) => {
		e.preventDefault()
		setDragOver(false)
	}

	const handleDrop = (e: React.DragEvent) => {
		e.preventDefault()
		setDragOver(false)
		
		const files = e.dataTransfer.files
		if (files.length > 0) {
			onFilesDrop(files)
		}
	}

	return {
		dragOver,
		handleDragOver,
		handleDragLeave,
		handleDrop
	}
}
