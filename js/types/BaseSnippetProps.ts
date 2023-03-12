import { Snippet } from './Snippet'

export interface BaseSnippetProps {
	snippet: Snippet
	setSnippetField: <T extends keyof Snippet>(field: T, value: Snippet[T]) => void
}
