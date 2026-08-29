import { __, sprintf } from '@wordpress/i18n'
import { useSnippetsAPI } from '../../../hooks/useSnippetsAPI'
import { useActionFeedback } from '../../../hooks/useActionFeedback'
import { useSnippetsList } from '../../../hooks/useSnippetsList'
import { downloadBulkSnippetExportFile } from '../../../utils/files'
import { cloneSnippetObject } from '../../../utils/snippets/snippets'
import type { ListTableAction } from '../../common/ListTable'
import type { Snippet } from '../../../types/Snippet'

export type SnippetsTableAction =
	'activate' | 'deactivate' |
	'clone' | 'export' | 'download' |
	'trash' | 'restore' | 'delete'

export const BULK_ACTIONS: ListTableAction<SnippetsTableAction>[] = [
	{ key: 'activate', label: __('Activate', 'code-snippets') },
	{ key: 'deactivate', label: __('Deactivate', 'code-snippets') },
	{ key: 'clone', label: __('Clone', 'code-snippets') },
	{ key: 'export', label: __('Export', 'code-snippets') },
	{ key: 'download', label: __('Download', 'code-snippets') },
	{ key: 'trash', label: __('Trash', 'code-snippets') }
]

export const TRASHED_BULK_ACTIONS: ListTableAction<SnippetsTableAction>[] = [
	{ key: 'restore', label: __('Restore', 'code-snippets') },
	{ key: 'delete', label: __('Delete Permanently', 'code-snippets') }
]

const BULK_DOWNLOAD_ACTION = 'bulk-download'
const INDIVIDUAL_DOWNLOAD_DELAY_MS = 200

const submitBulkSnippetDownload = (snippets: readonly Snippet[]): Promise<void> => {
	if (0 === snippets.length) {
		return Promise.resolve()
	}

	const form = document.createElement('form')

	const appendHiddenField = (name: string, value: string) => {
		const input = document.createElement('input')
		input.type = 'hidden'
		input.name = name
		input.value = value
		form.appendChild(input)
	}

	form.method = 'post'
	form.action = window.location.href
	form.hidden = true

	appendHiddenField('code_snippets_action', BULK_DOWNLOAD_ACTION)
	appendHiddenField('code_snippets_bulk_download_nonce', window.CODE_SNIPPETS_MANAGE?.bulkDownloadNonce ?? '')
	appendHiddenField(
		'snippets',
		JSON.stringify(snippets.map(({ id, network }) => ({ id, network })))
	)

	document.body.appendChild(form)
	form.submit()

	window.setTimeout(() => {
		form.remove()
	}, 0)

	return Promise.resolve()
}

const submitBulkSnippetDownloadsIndividually = (snippets: readonly Snippet[]): Promise<void> =>
	snippets.reduce(
		(promise, snippet) =>
			promise.then(
				() =>
					new Promise<void>(resolve => {
						void submitBulkSnippetDownload([snippet])
						window.setTimeout(resolve, INDIVIDUAL_DOWNLOAD_DELAY_MS)
					})
			),
		Promise.resolve()
	)

const applyAndRefresh = async (
	targets: Snippet[],
	action: (snippet: Snippet) => Promise<Snippet> | Promise<void>,
	refresh: () => Promise<void>,
	onFailure: (failed: number, total: number, error: unknown) => void
): Promise<void> => {
	if (0 < targets.length) {
		let failed = 0
		let firstError: unknown

		// Every snippet is attempted even when one fails, so a single bad
		// snippet does not silently halt the rest of the batch. Failures used
		// to be discarded here, which is why a bulk action that did nothing
		// looked exactly like one that had worked.
		for (const snippet of targets) {
			try {
				await action(snippet)
			} catch (error: unknown) {
				failed += 1
				firstError ??= error
			}
		}

		await refresh()

		if (0 < failed) {
			onFailure(failed, targets.length, firstError)
		}
	}
}

/**
 * Build the failure reporter for one bulk action.
 *
 * The count is included because a batch can partly succeed, and "three of ten
 * failed" is a very different situation to "nothing happened".
 */
const bulkFailureReporter = (
	reportFailure: (action: string, error: unknown) => void,
	label: string
) => (failed: number, total: number, error: unknown) =>
	reportFailure(
		sprintf(
			/* translators: 1: what was being done, 2: number that failed, 3: number attempted. */
			__('%1$s (%2$d of %3$d failed)', 'code-snippets'),
			label,
			failed,
			total
		),
		error
	)

/**
 * Send the selected snippets to the browser as downloads.
 *
 * Falls back to one download per snippet where the server cannot build a zip.
 */
const submitBulkDownload = (selectedSnippets: Snippet[]): Promise<void> =>
	1 < selectedSnippets.length && !window.CODE_SNIPPETS_MANAGE?.supportsZipDownloads
		? submitBulkSnippetDownloadsIndividually(selectedSnippets)
		: submitBulkSnippetDownload(selectedSnippets)

export const useApplyBulkAction = (
	allSnippets: Snippet[]
): (action: SnippetsTableAction | undefined, selected: Set<Snippet['id']>) => Promise<void> => {
	const api = useSnippetsAPI()
	const { refreshSnippetsList } = useSnippetsList()
	const { reportFailure } = useActionFeedback()

	return async (action, selected) => {
		switch (action) {
			case 'activate':
				await applyAndRefresh(
					allSnippets.filter(snippet => selected.has(snippet.id) && !snippet.active),
					snippet => api.activate({ id: snippet.id, network: snippet.network }),
					refreshSnippetsList,
					bulkFailureReporter(reportFailure, __('activate the selected snippets', 'code-snippets')))
				break

			case 'deactivate':
				await applyAndRefresh(
					allSnippets.filter(snippet => selected.has(snippet.id) && snippet.active),
					snippet => api.deactivate({ id: snippet.id, network: snippet.network }),
					refreshSnippetsList,
					bulkFailureReporter(reportFailure, __('deactivate the selected snippets', 'code-snippets')))
				break

			case 'clone':
				await applyAndRefresh(
					allSnippets.filter(snippet => selected.has(snippet.id) && !snippet.trashed),
					snippet => api.create(cloneSnippetObject(snippet)),
					refreshSnippetsList,
					bulkFailureReporter(reportFailure, __('clone the selected snippets', 'code-snippets')))
				break

			case 'export':
				downloadBulkSnippetExportFile(allSnippets.filter(snippet => selected.has(snippet.id)))
				break

			case 'download':
				return submitBulkDownload(allSnippets.filter(snippet => selected.has(snippet.id)))

			case 'trash':
			case 'delete':
				await applyAndRefresh(
					allSnippets.filter(snippet => selected.has(snippet.id)),
					snippet => api.delete({ id: snippet.id, network: snippet.network }),
					refreshSnippetsList,
					bulkFailureReporter(reportFailure, __('remove the selected snippets', 'code-snippets')))
				break

			case undefined:
				break
		}
	}
}
