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

type DefaultExportValueCheck = (value: unknown) => boolean

const isNullish = (value: unknown): value is undefined | null => undefined === value || null === value

const DEFAULT_EXPORT_VALUE_CHECKS: Record<string, DefaultExportValueCheck> = {
	id: value => 0 === value,
	desc: value => '' === value,
	name: value => '' === value,
	code: value => '' === value,
	tags: value => Array.isArray(value) && 0 === value.length,
	scope: value => 'global' === value,
	condition_id: value => 0 === value,
	active: value => false === value,
	locked: value => false === value,
	trashed: value => false === value,
	priority: value => DEFAULT_PRIORITY === value,
	modified: value => '' === value || isNullish(value),
	last_active: value => 0 === value || isNullish(value),
	network: value => null === value || false === value,
	shared_network: value => null === value || false === value,
	code_error: value => null === value || false === value,
	code_error_trace: value => null === value || false === value
}

const isDefaultExportValue = (field: string, value: unknown): boolean =>
	(DEFAULT_EXPORT_VALUE_CHECKS[field] ?? isNullish)(value)

const buildExportSnippet = ({
	id,
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
	modified,
	priority,
	conditionId,
	lastActive,
	code_error,
	code_error_trace
}: Snippet): SnippetsExport['snippets'][number] =>
	Object.fromEntries(
		Object.entries({
			id,
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
			modified,
			priority,
			condition_id: conditionId,
			last_active: lastActive,
			code_error,
			code_error_trace
		}).filter(([field, value]) => !isDefaultExportValue(field, value))
	)

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
