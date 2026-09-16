import React, { useCallback, useEffect, useState } from 'react'
import { __ } from '@wordpress/i18n'
import { createContextHook } from '../utils/bootstrap'
import { isNetworkAdmin } from '../utils/screen'
import { parseSnippetObject } from '../utils/snippets/objects'
import { useSnippetsAPI } from './useSnippetsAPI'
import type { PropsWithChildren } from 'react'
import type { Snippet } from '../types/Snippet'

export interface SnippetsListContext {
	snippetsList: readonly Snippet[] | undefined
	/**
	 * Whether the complete list has arrived from the API. Until it has, the list
	 * is whatever was embedded in the page, which is capped and may be partial,
	 * so nothing should present it as the whole library.
	 */
	isListLoaded: boolean
	/** Set when the list could not be fetched, so the screen can say so rather than show a partial list as if it were complete. */
	listError: string | undefined
	refreshSnippetsList: () => Promise<void>
	/** Ask for snippet code, which the list omits. Only searching code contents needs it. */
	ensureSnippetCode: () => void
}

const [Context, useSnippetsList] = createContextHook<SnippetsListContext>('useSnippetsList')

export const WithSnippetsListContext: React.FC<PropsWithChildren> = ({ children }) => {
	const { fetchAll } = useSnippetsAPI()
	const [snippetsList, setSnippetsList] = useState<Snippet[] | undefined>(
		() => window.CODE_SNIPPETS_MANAGE?.snippetsList?.map(parseSnippetObject)
	)
	const [isListLoaded, setIsListLoaded] = useState(false)
	const [listError, setListError] = useState<string>()
	const [withCode, setWithCode] = useState(false)

	// Flipping this re-runs the fetch below, so code is loaded through the same
	// path as everything else rather than racing a second request against it.
	const ensureSnippetCode = useCallback(() => setWithCode(true), [])

	const refreshSnippetsList = useCallback(async (): Promise<void> => {
		try {
			const response = await fetchAll(isNetworkAdmin(), { withCode })
			setSnippetsList(response)
			setIsListLoaded(true)
			setListError(undefined)
		} catch (error: unknown) {
			console.error('Error fetching snippets list', error)
			setListError(__('Could not load the complete list of snippets, so some may be missing below.', 'code-snippets'))
		}
	}, [fetchAll, withCode])

	useEffect(() => {
		refreshSnippetsList()
			.catch(() => undefined)
	}, [refreshSnippetsList])

	const value: SnippetsListContext = {
		snippetsList,
		isListLoaded,
		listError,
		refreshSnippetsList,
		ensureSnippetCode
	}

	return <Context.Provider value={value}>{children}</Context.Provider>
}

export { useSnippetsList }
