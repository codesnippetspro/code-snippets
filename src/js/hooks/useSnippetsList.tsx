import React, { useCallback, useEffect, useState } from 'react'
import { createContextHook } from '../utils/bootstrap'
import { isNetworkAdmin } from '../utils/screen'
import { parseSnippetObject } from '../utils/snippets/objects'
import { useSnippetsAPI } from './useSnippetsAPI'
import type { PropsWithChildren } from 'react'
import type { Snippet } from '../types/Snippet'

export interface SnippetsListContext {
	snippetsList: readonly Snippet[] | undefined
	/** Whether the last attempt to load the full list failed, leaving only the snippets sent with the page. */
	hasLoadError: boolean
	refreshSnippetsList: () => Promise<void>
}

const [Context, useSnippetsList] = createContextHook<SnippetsListContext>('useSnippetsList')

export const WithSnippetsListContext: React.FC<PropsWithChildren> = ({ children }) => {
	const { fetchAll } = useSnippetsAPI()
	const [snippetsList, setSnippetsList] = useState<Snippet[] | undefined>(
		() => window.CODE_SNIPPETS_MANAGE?.snippetsList?.map(parseSnippetObject)
	)
	const [hasLoadError, setHasLoadError] = useState(false)

	const refreshSnippetsList = useCallback(async (): Promise<void> => {
		try {
			console.info('Fetching snippets list')
			const response = await fetchAll(isNetworkAdmin())
			setSnippetsList(response)
			setHasLoadError(false)
		} catch (error: unknown) {
			console.error('Error fetching snippets list', error)
			setHasLoadError(true)
		}
	}, [fetchAll])

	useEffect(() => {
		refreshSnippetsList()
			.catch(() => undefined)
	}, [refreshSnippetsList])

	const value: SnippetsListContext = {
		snippetsList,
		hasLoadError,
		refreshSnippetsList
	}

	return <Context.Provider value={value}>{children}</Context.Provider>
}

export { useSnippetsList }
