import React, { useCallback, useEffect, useState } from 'react'
import { createContextHook } from '../utils/hooks'
import { isNetworkAdmin } from '../utils/screen'
import { useRestAPI } from './useRestAPI'
import type { PropsWithChildren } from 'react'
import type { Snippet } from '../types/Snippet'

export interface SnippetsListContext {
	snippetsList: readonly Snippet[] | undefined
	refreshSnippetsList: () => Promise<void>
}

const [SnippetsListContext, useSnippetsList] = createContextHook<SnippetsListContext>('SnippetsList')

export const WithSnippetsListContext: React.FC<PropsWithChildren> = ({ children }) => {
	const { snippetsAPI: { fetchAll } } = useRestAPI()
	const [snippetsList, setSnippetsList] = useState<Snippet[]>()

	const refreshSnippetsList = useCallback(async (): Promise<void> => {
		try {
			console.info('Fetching snippets list')
			const response = await fetchAll(isNetworkAdmin())
			setSnippetsList(response)
		} catch (error: unknown) {
			console.error('Error fetching snippets list', error)
		}
	}, [fetchAll])

	useEffect(() => {
		refreshSnippetsList()
			.catch(() => undefined)
	}, [refreshSnippetsList])

	const value: SnippetsListContext = {
		snippetsList,
		refreshSnippetsList
	}

	return <SnippetsListContext.Provider value={value}>{children}</SnippetsListContext.Provider>
}

export { useSnippetsList }
