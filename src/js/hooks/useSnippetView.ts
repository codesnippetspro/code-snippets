import { useCallback, useState } from 'react'
import { DEFAULT_SNIPPET_VIEW } from '../types/SnippetView'
import { handleUnknownError } from '../utils/errors'
import { REST_BASES } from '../utils/restAPI'
import { useRestAPI } from './useRestAPI'
import type { SnippetView } from '../types/SnippetView'

export interface UseSnippetView {
	snippetView: SnippetView
	setSnippetView: (view: SnippetView) => void
}

/**
 * Plugin-wide snippet view preference (cards or table).
 *
 * The initial value is localized into the page, and updates are persisted
 * through the preferences REST endpoint so the choice survives page loads
 * everywhere snippet lists are displayed. The UI updates optimistically
 * rather than waiting for the save to complete.
 */
export const useSnippetView = (): UseSnippetView => {
	const { api } = useRestAPI()
	const [snippetView, setViewState] = useState<SnippetView>(
		() => window.CODE_SNIPPETS?.snippetView ?? DEFAULT_SNIPPET_VIEW
	)

	const setSnippetView = useCallback((view: SnippetView) => {
		setViewState(view)
		api.post<{ view: SnippetView }, { view: SnippetView }>(`${REST_BASES.preferences}/snippet-view`, { view })
			.catch(handleUnknownError)
	}, [api])

	return { snippetView, setSnippetView }
}
