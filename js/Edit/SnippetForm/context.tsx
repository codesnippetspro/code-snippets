import { AxiosError, isAxiosError } from 'axios'
import React, { createContext, Dispatch, PropsWithChildren, SetStateAction, useCallback, useContext, useMemo, useState } from 'react'
import { Notice } from '../../types/Notice'
import { Snippet } from '../../types/Snippet'
import { CodeEditorInstance } from '../../types/WordPressCodeEditor'
import { isLicensed, isProSnippet } from '../../utils/snippets'
import { useSnippetSubmit } from '../utils/submit'

export interface SnippetFormContext {
	snippet: Snippet
	setSnippet: Dispatch<SetStateAction<Snippet>>
	updateSnippet: Dispatch<SetStateAction<Snippet>>
	isReadOnly: boolean
	isWorking: boolean
	setIsWorking: Dispatch<SetStateAction<boolean>>
	currentNotice: Notice | undefined
	setCurrentNotice: Dispatch<SetStateAction<Notice | undefined>>
	codeEditorInstance: CodeEditorInstance | undefined
	setCodeEditorInstance: Dispatch<SetStateAction<CodeEditorInstance | undefined>>
	handleRequestError: (error: AxiosError | unknown, message?: string) => void
	submitSnippet: VoidFunction
	submitAndActivateSnippet: VoidFunction
	submitAndDeactivateSnippet: VoidFunction
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
	const [currentNotice, setCurrentNotice] = useState<Notice>()
	const [codeEditorInstance, setCodeEditorInstance] = useState<CodeEditorInstance>()
	const submitSnippet = useSnippetSubmit(setSnippet, setIsWorking, setCurrentNotice)
	const isReadOnly = useMemo(() => !isLicensed() && isProSnippet(snippet.scope), [snippet.scope])

	const handleRequestError = useCallback((error: AxiosError | unknown, message?: string) => {
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
