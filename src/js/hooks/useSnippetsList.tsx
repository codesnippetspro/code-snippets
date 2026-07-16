import React, { useCallback, useEffect, useState } from 'react'
import { createContextHook } from '../utils/bootstrap'
import { isNetworkAdmin } from '../utils/screen'
import { parseSnippetObject } from '../utils/snippets/objects'
import { useSnippetsAPI } from './useSnippetsAPI'
import type { PropsWithChildren } from 'react'
import type { Snippet } from '../types/Snippet'

export interface SnippetsListContext {
	snippetsList: readonly Snippet[] | undefined
	refreshSnippetsList: () => Promise<void>
}

const [Context, useSnippetsList] = createContextHook<SnippetsListContext>('useSnippetsList')

export const WithSnippetsListContext: React.FC<PropsWithChildren> = ({ children }) => {
	const { fetchAll } = useSnippetsAPI()
	// Seed from the list localized with the page so counts and rows render
	// immediately; the mount-time refresh below keeps the data fresh. Raw
	// localized fields are parsed into full snippet objects, matching the
	// shape the REST API responses are parsed into.
	const [snippetsList, setSnippetsList] = useState<Snippet[] | undefined>(
		() => window.CODE_SNIPPETS_MANAGE?.snippetsList?.map(parseSnippetObject)
	)

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

	return <Context.Provider value={value}>{children}</Context.Provider>
}

export { useSnippetsList }
