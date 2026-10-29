import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'
import { Spinner } from '@wordpress/components'
import { useRestAPI } from '../../../../hooks/useRestAPI'
import { REST_BASES } from '../../../../utils/restAPI'
import { getSnippetEditUrl, isProSnippet } from '../../../../utils/snippets/snippets'
import { isLicensed } from '../../../../utils/screen'
import { Button } from '../../../common/Button'
import { ErrorTooltip } from '../../../common/Tooltip'
import { UpsellDialog } from '../../../common/UpsellDialog'
import { useCloudSnippetDownloads } from './WithCloudSnippetDownloadsContext'
import type { CloudSnippetSchema } from '../../../../types/schema/CloudSnippetSchema'

export interface DownloadSnippetResponse {
	success: boolean
	snippet_id: number
	link_id: number
}

export interface CloudSnippetDownloadButtonProps {
	onDownloaded?: VoidFunction
	snippet: CloudSnippetSchema
}

export interface CloudSnippetDownload {
	isDownloading: boolean
	/** The local copy of the snippet, once it has one. */
	localSnippetId: number | null | undefined
	errorMessage: string | undefined
	download: VoidFunction
}

export const useCloudSnippetDownload = ({ snippet, onDownloaded }: CloudSnippetDownloadButtonProps): CloudSnippetDownload => {
	const { api } = useRestAPI()
	const { downloadRecords, updateDownloadRecord } = useCloudSnippetDownloads()
	const [errorMessage, setErrorMessage] = useState<string>()
	const { isDownloading = false, localId } = downloadRecords[snippet.id] ?? {}

	const download = () => {
		updateDownloadRecord(snippet.id, { isDownloading: true })
		setErrorMessage(undefined)

		api.post<DownloadSnippetResponse>(`${REST_BASES.cloud.snippets}/${snippet.id}/download`)
			.then(response => {
				updateDownloadRecord(snippet.id, { localId: response.snippet_id })
				onDownloaded?.()
			})
			.catch((error: unknown) => {
				setErrorMessage('string' === typeof error
					? error
					: __('An error occurred while trying to download the snippet.', 'code-snippets'))
			})
			.finally(() => updateDownloadRecord(snippet.id, { isDownloading: false }))
	}

	// A cloud snippet with no local copy reports an ID of zero rather than leaving it out.
	const localSnippetId = 0 === snippet.local_id ? localId : snippet.local_id ?? localId

	return { isDownloading, localSnippetId, errorMessage, download }
}

export const CloudSnippetDownloadButton: React.FC<CloudSnippetDownloadButtonProps> = ({ snippet, onDownloaded }) => {
	const { isDownloading, localSnippetId, errorMessage, download } = useCloudSnippetDownload({ snippet, onDownloaded })
	const [isUpsellOpen, setIsUpsellOpen] = useState(false)

	if (localSnippetId) {
		return (
			<a className="button button-primary" href={getSnippetEditUrl({ id: localSnippetId })} target="_blank" rel="noopener noreferrer">
				{__('Edit', 'code-snippets')}
			</a>
		)
	}

	if (isProSnippet(snippet) && !isLicensed()) {
		return (
			<>
				<Button className="cloud-pro-button" onClick={() => setIsUpsellOpen(true)}>
					{__('Pro Only', 'code-snippets')}
				</Button>
				<UpsellDialog isOpen={isUpsellOpen} setIsOpen={setIsUpsellOpen} />
			</>
		)
	}

	return (
		<>
			{isDownloading && <Spinner />}
			{errorMessage && <ErrorTooltip message={errorMessage} />}

			<Button primary onClick={download} disabled={isDownloading}>
				{__('Download', 'code-snippets')}
			</Button>
		</>
	)
}
