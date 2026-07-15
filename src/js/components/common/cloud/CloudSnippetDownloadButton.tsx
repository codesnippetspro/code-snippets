import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'
import { Spinner } from '@wordpress/components'
import { useRestAPI } from '../../../hooks/useRestAPI'
import { REST_BASES } from '../../../utils/restAPI'
import { getSnippetEditUrl, isProSnippet } from '../../../utils/snippets/snippets'
import { isLicensed } from '../../../utils/screen'
import { Button } from '../Button'
import { ErrorTooltip } from '../Tooltip'
import { UpsellDialog } from '../UpsellDialog'
import type { CloudSnippetSchema } from '../../../types/schema/CloudSnippetSchema'

export interface DownloadSnippetResponse {
	success: boolean
	snippet_id: number
	link_id: number
}

interface CloudSnippetDownloadButtonProps {
	snippet: CloudSnippetSchema
}

export const CloudSnippetDownloadButton: React.FC<CloudSnippetDownloadButtonProps> = ({ snippet }) => {
	const { api } = useRestAPI()
	const [isWorking, setIsWorking] = useState(false)
	const [errorMessage, setErrorMessage] = useState<string>()
	const [localSnippetId, setLocalSnippetId] = useState<number>(snippet.local_id ?? 0)
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

	const handleDownload = () => {
		setIsWorking(true)
		setErrorMessage(undefined)

		api.post<DownloadSnippetResponse>(`${REST_BASES.cloud.snippets}/${snippet.id}/download`)
			.then(response => {
				setLocalSnippetId(response.snippet_id)
			})
			.catch((error: unknown) => {
				setErrorMessage('string' === typeof error
					? error
					: __('An error occurred while trying to download the snippet.', 'code-snippets'))
			})
			.finally(() => setIsWorking(false))
	}

	return (
		<>
			{isWorking && <Spinner />}
			{errorMessage && <ErrorTooltip message={errorMessage} />}

			<Button primary onClick={handleDownload} disabled={isWorking}>
				{__('Download', 'code-snippets')}
			</Button>
		</>
	)
}
