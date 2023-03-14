const SECOND_IN_MS = 1000
const TIMEOUT_SECONDS = 40

export const isNetworkAdmin = () =>
	window.pagenow.endsWith('-network')

export const downloadAsFile = (content: BlobPart, filename: string, type: string) => {
	const link = document.createElement('a')
	link.download = filename
	link.href = URL.createObjectURL(new Blob([content], { type }))

	setTimeout(() => URL.revokeObjectURL(link.href), TIMEOUT_SECONDS * SECOND_IN_MS)
	setTimeout(() => link.click(), 0)
}
