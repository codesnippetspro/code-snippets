import React, { useCallback, useState } from 'react'
import { createContextHook } from '../../../utils/bootstrap'
import type { PropsWithChildren } from 'react'
import type { CloudSnippetSchema } from '../../../types/schema/CloudSnippetSchema'

export interface CloudSnippetDownloadRecord {
	isDownloading: boolean
	localId?: number
}

interface CloudSnippetDownloadsContext {
	downloadRecords: Partial<Record<CloudSnippetSchema['id'], CloudSnippetDownloadRecord>>
	updateDownloadRecord: (
		snippetId: CloudSnippetSchema['id'],
		update: Partial<CloudSnippetDownloadRecord>
	) => void
}

const [Context, useCloudSnippetDownloads] =
	createContextHook<CloudSnippetDownloadsContext>('useCloudSnippetDownloads')

export const WithCloudSnippetDownloadsContext: React.FC<PropsWithChildren> = ({ children }) => {
	const [downloadRecords, setDownloadRecords] =
		useState<CloudSnippetDownloadsContext['downloadRecords']>({})
	const updateDownloadRecord = useCallback<CloudSnippetDownloadsContext['updateDownloadRecord']>(
		(snippetId, update) => setDownloadRecords(previous => ({
			...previous,
			[snippetId]: {
				isDownloading: false,
				...previous[snippetId],
				...update
			}
		})),
		[]
	)

	return (
		<Context.Provider value={{ downloadRecords, updateDownloadRecord }}>
			{children}
		</Context.Provider>
	)
}

export { useCloudSnippetDownloads }
