import { isAxiosError } from 'axios'
import React, { createContext, useCallback, useContext, useMemo, useState } from 'react'
import { isLicensed } from '../utils/general'
import { isProSnippet } from '../utils/snippets'
import { useSnippetSubmit } from './useSnippetSubmit'
import type { Dispatch, PropsWithChildren, SetStateAction} from 'react'
import type { ScreenNotice } from '../types/ScreenNotice'
import type { Snippet } from '../types/Snippet'
import type { CodeEditorInstance } from '../types/WordPressCodeEditor'

export interface SnippetFormContext {
	snippet: Snippet
	setSnippet: Dispatch<SetStateAction<Snippet>>
	updateSnippet: Dispatch<SetStateAction<Snippet>>
	isReadOnly: boolean
	isWorking: boolean
	setIsWorking: Dispatch<SetStateAction<boolean>>
	currentNotice: ScreenNotice | undefined
	setCurrentNotice: Dispatch<SetStateAction<ScreenNotice | undefined>>
	codeEditorInstance: CodeEditorInstance | undefined
	setCodeEditorInstance: Dispatch<SetStateAction<CodeEditorInstance | undefined>>
	handleRequestError: (error: unknown, message?: string) => void
	submitSnippet: () => Promise<Snippet | undefined>
	submitAndActivateSnippet: () => Promise<Snippet | undefined>
	submitAndDeactivateSnippet: () => Promise<Snippet | undefined>
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
	const [snippet, setSnippet] = useState<Snippet>(initialSnippet)
	const [isWorking, setIsWorking] = useState(false)
	const [currentNotice, setCurrentNotice] = useState<ScreenNotice>()
	const [codeEditorInstance, setCodeEditorInstance] = useState<CodeEditorInstance>()
	const submitSnippet = useSnippetSubmit(setSnippet, setIsWorking, setCurrentNotice)
	const isReadOnly = useMemo(() => !isLicensed() && isProSnippet(snippet.scope), [snippet.scope])

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
		setSnippet,
		updateSnippet,
		isReadOnly,
		isWorking,
		setIsWorking,
		currentNotice,
		setCurrentNotice,
		codeEditorInstance,
		setCodeEditorInstance,
		handleRequestError,
		submitSnippet: () => submitSnippet(snippet),
		submitAndActivateSnippet: () => submitSnippet(snippet, true),
		submitAndDeactivateSnippet: () => submitSnippet(snippet, false)
	}

	return <SnippetFormContext.Provider value={value}>{children}</SnippetFormContext.Provider>
}
