import React, { useCallback, useEffect, useState } from 'react'
import { handleUnknownError } from '../utils/errors'
import { createContextHook } from '../utils/hooks'
import { isNetworkAdmin } from '../utils/screen'
import { useRestAPI } from './useRestAPI'
import type { PropsWithChildren } from 'react'
import type { Snippet } from '../types/Snippet'

export interface SnippetsListContext {
	snippetsList: readonly Snippet[] | undefined
	refreshSnippetsList: () => void
}

const [SnippetsListContext, useSnippetsList] = createContextHook<SnippetsListContext>('SnippetsList')

export const WithSnippetsListContext: React.FC<PropsWithChildren> = ({ children }) => {
	const { snippetsAPI: { fetchAll } } = useRestAPI()
	const [snippetsList, setSnippetsList] = useState<Snippet[]>()

	useEffect(() => {
		if (!snippetsList) {
			fetchAll(isNetworkAdmin())
				.then(response => setSnippetsList(response))
				.catch(handleUnknownError)
		}
	}, [fetchAll, snippetsList])

	const refreshSnippetsList = useCallback(() => setSnippetsList(undefined), [])

	const value: SnippetsListContext = {
		snippetsList,
		refreshSnippetsList
	}

	return <SnippetsListContext.Provider value={value}>{children}</SnippetsListContext.Provider>
}

export { useSnippetsList }
