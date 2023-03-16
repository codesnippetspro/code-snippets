import { Dispatch, SetStateAction } from 'react'
import { SnippetActionsProps } from '../Edit/actions'
import { Snippet } from './Snippet'

export interface SnippetInputProps {
	snippet: Snippet
	setSnippet: Dispatch<SetStateAction<Snippet>>
	isReadOnly: boolean
}

export interface SnippetActionsInputProps extends SnippetActionsProps, SnippetInputProps {
	isWorking: boolean
}
