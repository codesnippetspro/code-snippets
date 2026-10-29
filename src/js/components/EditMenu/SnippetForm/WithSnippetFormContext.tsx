import { isAxiosError } from 'axios'
import React, { useCallback, useMemo, useState } from 'react'
import { createContextHook } from '../../../utils/bootstrap'
import { isLicensed } from '../../../utils/screen'
import { isProSnippet } from '../../../utils/snippets/snippets'
import type { Dispatch, PropsWithChildren, SetStateAction } from 'react'
import type { ScreenNotice } from '../../../types/ScreenNotice'
import type { Snippet } from '../../../types/Snippet'
import type { CodeEditorInstance } from '../../../types/vendor/WordPressCodeEditor'

export interface SnippetFormContext {
	snippet: Snippet
	isWorking: boolean
	isReadOnly: boolean
	isDirty: boolean
	setSnippet: Dispatch<SetStateAction<Snippet>>
	acceptSnippet: (snippet: Snippet) => void
	updateSnippet: Dispatch<SetStateAction<Snippet>>
	setIsWorking: Dispatch<SetStateAction<boolean>>
	currentNotice: ScreenNotice | undefined
	setCurrentNotice: Dispatch<SetStateAction<ScreenNotice | undefined>>
	codeEditorInstance: CodeEditorInstance | undefined
	handleRequestError: (error: unknown, message?: string) => void
	setCodeEditorInstance: Dispatch<SetStateAction<CodeEditorInstance | undefined>>
}

const [Context, useSnippetForm] = createContextHook<SnippetFormContext>('useSnippetForm')

export interface WithSnippetFormContextProps extends PropsWithChildren {
	initialSnippet: () => Snippet
}

const getSnippetDraftState = (snippet: Snippet) => ({
	name: snippet.name,
	desc: snippet.desc,
	code: snippet.code,
	tags: snippet.tags,
	scope: snippet.scope,
	priority: snippet.priority,
	active: snippet.active,
	locked: snippet.locked,
	network: snippet.network,
	sharedNetwork: snippet.shared_network,
	conditionId: snippet.conditionId
})

const isSnippetDraftDirty = (snippet: Snippet, savedSnippet: Snippet): boolean => {
	const draftState = JSON.stringify(getSnippetDraftState(snippet))
	const savedDraftState = JSON.stringify(getSnippetDraftState(savedSnippet))
	return draftState !== savedDraftState
}

export const WithSnippetFormContext: React.FC<WithSnippetFormContextProps> = ({ children, initialSnippet }) => {
	const [initialValue] = useState<Snippet>(initialSnippet)
	const [snippet, setSnippet] = useState<Snippet>(initialValue)
	const [savedSnippet, setSavedSnippet] = useState<Snippet>(initialValue)
	const [isWorking, setIsWorking] = useState(false)
	const [currentNotice, setCurrentNotice] = useState<ScreenNotice>()
	const [codeEditorInstance, setCodeEditorInstance] = useState<CodeEditorInstance>()

	const isReadOnly = useMemo(
		() => snippet.locked || !isLicensed() && isProSnippet({ scope: snippet.scope }),
		[snippet.locked, snippet.scope]
	)
	const isDirty = useMemo(
		() => isSnippetDraftDirty(snippet, savedSnippet),
		[snippet, savedSnippet]
	)
	const acceptSnippet = useCallback((value: Snippet) => {
		setSnippet(value)
		setSavedSnippet(value)
	}, [])

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
		isDirty,
		setSnippet,
		acceptSnippet,
		setIsWorking,
		updateSnippet,
		currentNotice,
		setCurrentNotice,
		codeEditorInstance,
		handleRequestError,
		setCodeEditorInstance
	}

	return <Context.Provider value={value}>{children}</Context.Provider>
}

export { useSnippetForm }
