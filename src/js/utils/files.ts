import { getSnippetType } from './snippets/snippets'
import type { SnippetsExport } from '../types/schema/SnippetsExport'
import type { Snippet } from '../types/Snippet'

const SECOND_IN_MS = 1000
const TIMEOUT_SECONDS = 40
const JSON_INDENT_SPACES = 2
const EXPORT_FILENAME = 'snippets'
const EXPORT_GENERATOR = 'Code Snippets'
const DEFAULT_PRIORITY = 10
const EXPORT_DATE_LENGTH = 16

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

	// Some browsers (notably headless Chromium) can ignore programmatic clicks on detached anchors.
	// Appending the link to the DOM before clicking improves reliability.
	link.style.display = 'none'
	document.body.appendChild(link)

	setTimeout(() => {
		link.click()
		link.remove()
	}, 0)
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

const isDefaultExportValue = (field: string, value: unknown): boolean => {
	switch (field) {
		case 'desc':
		case 'name':
		case 'code':
			return '' === value

		case 'tags':
			return Array.isArray(value) && 0 === value.length

		case 'scope':
			return 'global' === value

		case 'condition_id':
			return 0 === value

		case 'active':
		case 'locked':
		case 'trashed':
			return false === value

		case 'priority':
			return DEFAULT_PRIORITY === value

		case 'network':
		case 'shared_network':
		case 'code_error':
		case 'code_error_trace':
			return null === value || false === value

		default:
			return undefined === value || null === value
	}
}

const buildExportSnippet = ({
	name,
	desc,
	code,
	tags,
	scope,
	active,
	locked,
	trashed,
	network,
	shared_network,
	priority,
	conditionId,
	code_error,
	code_error_trace
}: Snippet): SnippetsExport['snippets'][number] => {
	const exportSnippet: SnippetsExport['snippets'][number] = Object.fromEntries(
		Object.entries({
			name,
			desc,
			code,
			tags,
			scope,
			active,
			locked,
			trashed,
			network,
			shared_network,
			priority,
			condition_id: conditionId,
			code_error,
			code_error_trace
		}).filter(([field, value]) => !isDefaultExportValue(field, value))
	)

	return exportSnippet
}

export const downloadBulkSnippetExportFile = (snippets: readonly Snippet[]) => {
	if (0 === snippets.length) {
		return
	}

	const content: SnippetsExport = {
		generator: EXPORT_GENERATOR,
		date_created: new Date().toISOString().slice(0, EXPORT_DATE_LENGTH).replace('T', ' '),
		snippets: snippets.map(buildExportSnippet)
	}

	downloadAsFile(
		JSON.stringify(content, undefined, JSON_INDENT_SPACES),
		`${EXPORT_FILENAME}.code-snippets.json`,
		'application/json'
	)
}
