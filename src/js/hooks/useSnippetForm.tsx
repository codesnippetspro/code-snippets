import { isAxiosError } from 'axios'
import React, { useCallback, useMemo, useState } from 'react'
import { createContextHook } from '../utils/hooks'
import { isLicensed } from '../utils/screen'
import { isProSnippet } from '../utils/snippets/snippets'
import type { Dispatch, PropsWithChildren, SetStateAction } from 'react'
import type { ScreenNotice } from '../types/ScreenNotice'
import type { Snippet } from '../types/Snippet'
import type { CodeEditorInstance } from '../types/WordPressCodeEditor'

export interface SnippetFormContext {
	snippet: Snippet
	isWorking: boolean
	isReadOnly: boolean
	setSnippet: Dispatch<SetStateAction<Snippet>>
	updateSnippet: Dispatch<SetStateAction<Snippet>>
	setIsWorking: Dispatch<SetStateAction<boolean>>
	currentNotice: ScreenNotice | undefined
	setCurrentNotice: Dispatch<SetStateAction<ScreenNotice | undefined>>
	codeEditorInstance: CodeEditorInstance | undefined
	handleRequestError: (error: unknown, message?: string) => void
	setCodeEditorInstance: Dispatch<SetStateAction<CodeEditorInstance | undefined>>
}

export const [SnippetFormContext, useSnippetForm] = createContextHook<SnippetFormContext>('SnippetForm')

export interface WithSnippetFormContextProps extends PropsWithChildren {
	initialSnippet: () => Snippet
}

export const WithSnippetFormContext: React.FC<WithSnippetFormContextProps> = ({ children, initialSnippet }) => {
	const [snippet, setSnippet] = useState<Snippet>(initialSnippet)
	const [isWorking, setIsWorking] = useState(false)
	const [currentNotice, setCurrentNotice] = useState<ScreenNotice>()
	const [codeEditorInstance, setCodeEditorInstance] = useState<CodeEditorInstance>()

	const isReadOnly = useMemo(() => !isLicensed() && isProSnippet({ scope: snippet.scope }), [snippet.scope])

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

	const value: SnippetFormContext = {
		snippet,
		isWorking,
		isReadOnly,
		setSnippet,
		setIsWorking,
		updateSnippet,
		currentNotice,
		setCurrentNotice,
		codeEditorInstance,
		handleRequestError,
		setCodeEditorInstance
	}

	return <SnippetFormContext.Provider value={value}>{children}</SnippetFormContext.Provider>
}
