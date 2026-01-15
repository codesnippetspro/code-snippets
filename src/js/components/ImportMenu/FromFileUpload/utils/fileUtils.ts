const FILE_SIZE_FRACTION_DIGITS = 2

export const formatFileSize = (bytes: number): string => {
	if (0 === bytes) {
		return '0 Bytes'
	}
	const k = 1024
	const sizes = ['Bytes', 'KB', 'MB', 'GB']
	const i = Math.floor(Math.log(bytes) / Math.log(k))
	return `${parseFloat((bytes / Math.pow(k, i)).toFixed(FILE_SIZE_FRACTION_DIGITS))} ${sizes[i]}`
}

export const removeFileFromList = (fileList: FileList, indexToRemove: number): FileList => {
	const dt = new DataTransfer()
	for (let i = 0; i < fileList.length; i++) {
		if (i !== indexToRemove) {
			dt.items.add(fileList[i])
		}
	}
	return dt.files
}
