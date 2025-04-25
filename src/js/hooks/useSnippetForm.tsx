import { isAxiosError } from 'axios'
import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react'
import { handleUnknownError } from '../utils/errors'
import { isLicensed, isNetworkAdmin } from '../utils/screen'
import { isProSnippet } from '../utils/snippets/snippets'
import { submitSnippet } from '../utils/snippets/submit'
import { useSnippetsAPI } from './useSnippetsAPI'
import type { SnippetsAPI } from './useSnippetsAPI'
import type { Dispatch, PropsWithChildren, SetStateAction } from 'react'
import type { ScreenNotice } from '../types/ScreenNotice'
import type { Snippet } from '../types/Snippet'
import type { CodeEditorInstance } from '../types/WordPressCodeEditor'

export interface SnippetFormContext {
	api: SnippetsAPI
	snippet: Snippet
	isWorking: boolean
	isReadOnly: boolean
	setSnippet: Dispatch<SetStateAction<Snippet>>
	saveSnippet: (delta?: Partial<Snippet>) => Promise<Snippet | undefined>
	snippetsList: readonly Snippet[] | undefined
	updateSnippet: Dispatch<SetStateAction<Snippet>>
	setIsWorking: Dispatch<SetStateAction<boolean>>
	currentNotice: ScreenNotice | undefined
	setCurrentNotice: Dispatch<SetStateAction<ScreenNotice | undefined>>
	codeEditorInstance: CodeEditorInstance | undefined
	handleRequestError: (error: unknown, message?: string) => void
	refreshSnippetsList: () => void
	setCodeEditorInstance: Dispatch<SetStateAction<CodeEditorInstance | undefined>>
}

const SnippetFormContext = createContext<SnippetFormContext | undefined>(undefined)

export const useSnippetForm = () => {
	const value = useContext(SnippetFormContext)

	if (value === undefined) {
		throw Error('useSnippetForm can only be used within a SnippetForm context provider')
	}

	return value
}

export interface WithSnippetFormContextProps extends PropsWithChildren {
	initialSnippet: () => Snippet
}

export const WithSnippetFormContext: React.FC<WithSnippetFormContextProps> = ({ children, initialSnippet }) => {
	const api = useSnippetsAPI()
	const [snippet, setSnippet] = useState<Snippet>(initialSnippet)
	const [isWorking, setIsWorking] = useState(false)
	const [snippetsList, setSnippetsList] = useState<Snippet[]>()
	const [currentNotice, setCurrentNotice] = useState<ScreenNotice>()
	const [codeEditorInstance, setCodeEditorInstance] = useState<CodeEditorInstance>()

	const isReadOnly = useMemo(() => !isLicensed() && isProSnippet({ scope: snippet.scope }), [snippet.scope])

	const saveSnippet = useCallback((delta?: Partial<Snippet>) =>
		submitSnippet({ ...snippet, ...delta }, { api, setSnippet, setIsWorking, setCurrentNotice }), [api, snippet])

	useEffect(() => {
		if (!snippetsList) {
			api.fetchAll(isNetworkAdmin())
				.then(response => setSnippetsList(response))
				.catch(handleUnknownError)
		}
	}, [api, snippetsList])

	const handleRequestError = useCallback((error: unknown, message?: string) => {
		console.error('Request failed', error)
		setIsWorking(false)
		setCurrentNotice(['error', [message, isAxiosError(error) ? error.message : ''].filter(Boolean).join(' ')])
	}, [setIsWorking, setCurrentNotice])

	const updateSnippet: Dispatch<SetStateAction<Snippet>> = useCallback((value: SetStateAction<Snippet>) => {
		setSnippet(previous => {
			const updated = 'object' === typeof value ? value : value(previous)
			codeEditorInstance?.codemirror.setValue(updated.code)
			window.tinymce?.activeEditor.setContent(updated.desc)
			return updated
		})
	}, [codeEditorInstance?.codemirror])

	const refreshSnippetsList = useCallback(() => setSnippetsList(undefined), [])

	const value: SnippetFormContext = {
		api,
		snippet,
		isWorking,
		isReadOnly,
		setSnippet,
		saveSnippet,
		snippetsList,
		setIsWorking,
		updateSnippet,
		currentNotice,
		setCurrentNotice,
		codeEditorInstance,
		handleRequestError,
		refreshSnippetsList,
		setCodeEditorInstance,
	}

	return <SnippetFormContext.Provider value={value}>{children}</SnippetFormContext.Provider>
}
