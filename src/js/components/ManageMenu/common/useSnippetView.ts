import { useCallback, useState } from 'react'
import { handleUnknownError } from '../../../utils/errors'
import { REST_BASES } from '../../../utils/restAPI'
import { useRestAPI } from '../../../hooks/useRestAPI'

export type SnippetView = 'card' | 'table'

interface SnippetViewUpdateSchema {
	view: SnippetView
}

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

	const [snippetView, setViewState] = useState<SnippetView>(() => {
		const preference = window.CODE_SNIPPETS?.snippetView
		return 'card' === preference ? 'card' : 'table'
	})

	const setSnippetView = useCallback((view: SnippetView) => {
		setViewState(view)
		api.post<SnippetViewUpdateSchema, SnippetViewUpdateSchema>(REST_BASES.preferences.snippetView, { view })
			.catch(handleUnknownError)
	}, [api])

	return { snippetView, setSnippetView }
}
