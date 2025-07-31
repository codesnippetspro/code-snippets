import { getSnippetType } from './snippets/snippets'
import type { SnippetsExport } from '../types/schema/SnippetsExport'
import type { Snippet } from '../types/Snippet'

const SECOND_IN_MS = 1000
const TIMEOUT_SECONDS = 40
const JSON_INDENT_SPACES = 2

const MIME_INFO = <const> {
	php: ['php', 'text/php'],
	html: ['php', 'text/php'],
	css: ['css', 'text/css'],
	js: ['js', 'text/javascript'],
	cond: ['json', 'application/json'],
	json: ['json', 'application/json']
}

export const downloadAsFile = (content: BlobPart, filename: string, type: string) => {
	const link = document.createElement('a')
	link.download = filename
	link.href = URL.createObjectURL(new Blob([content], { type }))

	setTimeout(() => URL.revokeObjectURL(link.href), TIMEOUT_SECONDS * SECOND_IN_MS)
	setTimeout(() => link.click(), 0)
}

export const downloadSnippetExportFile = (
	content: SnippetsExport | string,
	{ id, name, scope }: Snippet,
	type?: keyof typeof MIME_INFO
) => {
	const sanitizedName = name.toLowerCase().replace(/[^\w-]+/g, '-').trim()
	const title = '' === sanitizedName ? `snippet-${id}` : sanitizedName

	if ('string' === typeof content) {
		const [ext, mimeType] = MIME_INFO[type ?? getSnippetType({ scope })]
		const filename = `${title}.code-snippets.${ext}`
		downloadAsFile(content, filename, mimeType)
	} else {
		const filename = `${title}.code-snippets.json`
		downloadAsFile(JSON.stringify(content, undefined, JSON_INDENT_SPACES), filename, 'application/json')
	}
}
