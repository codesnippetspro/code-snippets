import { Snippet } from '../types/Snippet'
import { getSnippetType } from './snippets'

const SECOND_IN_MS = 1000
const TIMEOUT_SECONDS = 40


const MIME_INFO = <const> {
	php: ['php', 'text/php'],
	html: ['php', 'text/php'],
	css: ['css', 'text/css'],
	js: ['js', 'text/javascript'],
	cond: ['json', 'application/json'],
	json: ['json', 'application/json']
}

export const isNetworkAdmin = () =>
	window.pagenow.endsWith('-network')

export const downloadAsFile = (content: BlobPart, filename: string, type: string) => {
	const link = document.createElement('a')
	link.download = filename
	link.href = URL.createObjectURL(new Blob([content], { type }))

	setTimeout(() => URL.revokeObjectURL(link.href), TIMEOUT_SECONDS * SECOND_IN_MS)
	setTimeout(() => link.click(), 0)
}

export const downloadSnippetExportFile = (content: BlobPart, { id, name, scope }: Snippet, type?: keyof typeof MIME_INFO) => {
	const [ext, mimeType] = MIME_INFO[type ?? getSnippetType(scope)]

	const title = name.toLowerCase().replace(/[^\w-]+/g, '-') ?? `snippet-${id}`
	const filename = `${title}.code-snippets.${ext}`

	downloadAsFile(content, filename, mimeType)
}
