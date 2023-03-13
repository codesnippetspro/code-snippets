import { Dispatch, SetStateAction } from 'react'
import { Snippet } from './Snippet'

export interface BaseSnippetProps {
	snippet: Snippet
	setSnippet: Dispatch<SetStateAction<Snippet>>
}
