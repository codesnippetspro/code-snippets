import { Dispatch, SetStateAction } from 'react'
import { Snippet } from './Snippet'

export interface SnippetInputProps {
	snippet: Snippet
	setSnippet: Dispatch<SetStateAction<Snippet>>
}
